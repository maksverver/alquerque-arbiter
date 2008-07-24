#!/usr/bin/env python

from os     import popen3
from sys    import argv, stdout, stderr, exit
from thread import start_new_thread
from time   import sleep


class PlayerException(Exception):
    def __init__(self, value, player):
        self.value  = value
        self.player = player

    def __str__(self):
        return '%s caused exception: %s.' % (self.player, self.description())


class NotResponding(PlayerException):
    "Pipe to player process died or timed out"

    def description(self):
        return 'process not responding; %s' % self.value


class InvalidSyntax(PlayerException):
    "Syntax error in player communication"

    def description(self):
        return 'invalid syntax; %s' % self.value


class InvalidMove(PlayerException):
    "Player made an invalid move"

    def description(self):
        return 'invalid move; %s' % self.value



def run_error_thread(name, error):
    while True:
        line = error.readline()
        if line:
            print >>stdout, '%s> %s' % (name, line.rstrip())
            stdout.flush()
        else:
            break


class Player:
    """Represents a player in the game and provides methods for communicating
       with the player."""

    def __init__(self, name, input, output, error):
        self.name   = name
        self.input  = input
        self.output = output
        start_new_thread(run_error_thread, (name, error))

    def __str__(self):
        return self.name

    def read_line(self):
        line = self.input.readline()
        if line.endswith('\n'):
            return line.rstrip('\n')
        else:
            raise NotResponding('unexpected end of input', self)

    def write(self, data):
        try:
            self.output.write(data)
            self.output.flush()
        except IOError, e:
            raise NotResponding(e, self)

    def write_line(self, data):
        #print 'Sending "%s" to %s' % (data, self.name)
        self.write(str(data) + "\n")

    def close(self):
        try:
            self.output.close()
            self.input.close()
            self.error.close()
        except NotResponding, e:
            pass


def parse_int(self, str, min, max):
    try:
        i = int(str)
    except ValueError, e:
        raise InvalidSyntax('integer expected', self)
    if i < min or i > max:
        raise InvalidMove('integer between %d and %d (inclusive) expected' % (min, max), self)
    return i


def pos_to_str(r, c):
    return "%c%d"%(ord('a') + c, 1 + r)

def str_to_pos(str):
    return (int(str[1]) - 1, ord(str[0]) - ord('a'))

def dirs(r, c):
    if ((r) + (c))%2:
        return [ (-1, 0), ( 0,-1), ( 0,+1), (+1, 0) ]
    else:
        return [ (-1,-1), (-1, 0), (-1,+1), ( 0,-1),
                 ( 0,+1), (+1,-1), (+1, 0), (+1,+1) ]

class Game:

    def __init__(self, player1, player2):
        self.board  = [ 7*[ player1 ], 7*[ player1 ], 7*[ player1 ],
                        3*[ player2 ] + [ None ] + 3*[ player1 ],
                        7*[ player2 ], 7*[ player2 ], 7*[ player2 ] ]
        self.players = [ player1, player2 ]
        self.history = []
        self.fields  = [ (r,c) for r in range(7) for c in range(7) ]
        self.execute()

    def gen_capture_moves(self, r1, c1, move, first = True):
        caps = False
        for dr, dc in dirs(r1, c1):
            r2, c2 = r1 + dr, c1 + dc
            r3, c3 = r2 + dr, c2 + dc
            if (r3,c3) in self.fields and self.board[r2][c2] not in (None, self.player) \
                and self.board[r3][c3] == None:
                old = self.board[r2][c2]
                self.board[r2][c2] = None
                self.gen_capture_moves(r3, c3, move + '*' + pos_to_str(r3,c3), False)
                self.board[r2][c2] = old
                caps = True
        if not first and not caps:
            self.moves.append(move)

    def execute(self, move = None):
        if move:
            if move[2] == '-':
                # Execute movement
                r1, c1 = str_to_pos(move[0:2])
                r2, c2 = str_to_pos(move[3:5])
                self.board[r1][c1] = None
                self.board[r2][c2] = self.player
            else:
                # Execute capture
                while len(move) >= 5:
                    r1, c1 = str_to_pos(move[0:2])
                    r2, c2 = str_to_pos(move[3:5])
                    self.board[r1][c1] = None
                    self.board[(r1 + r2)/2][(c1 + c2)/2] = None
                    self.board[r2][c2] = self.player
                    move = move[3:]
            self.history.append(move)

        # Set current player
        self.player = self.players[len(self.history)%len(self.players)]

        # Regenerate list of valid moves
        self.moves = []

        # First, generate capture moves
        for r, c in self.fields:
            if self.board[r][c] == self.player:
                self.board[r][c] = None
                self.gen_capture_moves(r, c, pos_to_str(r, c))
                self.board[r][c] = self.player

        if self.moves:
            return

        # If no capture moves exist, generate normal moves
        if len(self.history) > 2:
            last = self.history[-2]
            forbidden = last[3:5] + '-' + last[0:2]
        else:
            forbidden = None
        for r1, c1 in self.fields:
            if self.board[r1][c1] == self.player:
                for dr, dc in dirs(r1, c1):
                    r2, c2 = r1 + dr, c1 + dc
                    if (r2,c2) in self.fields and self.board[r2][c2] == None:
                        move = pos_to_str(r1, c1) + '-' + pos_to_str(r2, c2)
                        if move <> forbidden:
                            self.moves.append(move)

    def run(self):
        result = None
        quit = False
        move = "Start"
        try:
            for _ in range(200):
                p = self.players.index(self.player)
                if not self.moves:
                    self.player.write_line("Quit")
                    resultDesc = 'Player %d cannot move!' % (p + 1)
                    result = '%d-%d' % (12*p, 12*(1 - p))
                    break

                self.player.write_line(move)
                move = self.player.read_line()
                if move not in self.moves:
                    resultDesc = 'Player %d made an invalid move: "%s"!' % (p + 1, move)
                    result = '%d-%d' % (12*p, 12*(1 - p))
                    quit = True
                    break
                print >>stdout, "MOVE:", move
                stdout.flush()
                self.execute(move)
        except PlayerException, e:
            print >>stderr, e
            p = self.players.index(e.player)
            resultDesc = 'Player %d is disqualified!' % (p + 1)
            result = '%d-%d' % (12*p, 12*(1 - p))
            quit = True

        if not result:
            resultDesc = 'Game is drawn'
            score = [ 0 for _ in self.players ]
            for r,c in self.fields:
                if self.board[r][c]:
                    score[self.players.index(self.board[r][c])] += 1
            result = '%d-%d' % (max(0, 7 - score[1]), max(0, 7 - score[0]))

        if quit:
            for player in self.players:
                try:    player.quit()
                except: pass

        sleep(1)    # allow players to send final output

        for player in self.players:
            try:    player.close()
            except: pass

        stderr.flush()
        print >>stdout, "RESULT:", result, resultDesc 
        stdout.flush()


if __name__ == '__main__':
    if len(argv) <> 3:
        print 'Usage: %s <command1> <command2>' % argv[0]
        exit()

    input, output, error = popen3(argv[1])
    player1 = Player('player1', output, input, error)
    input, output, error = popen3(argv[2])
    player2 = Player('player2', output, input, error)
    game = Game(player1, player2)
    game.run()
