<?php
require_once('common.inc.php');

$player1           = $_REQUEST['Player1'];
$player1_args      = $_REQUEST['Player1Args'];
$player2           = $_REQUEST['Player2'];
$player2_args      = $_REQUEST['Player2Args'];


if( !empty($player1) && $player1 == basename($player1) ||
    !empty($player2) && $player2 == basename($player2) )
{
    $title = $player1.' vs '.$player2;

    $player1_dir = LOCALBASE.'/players/'.$player1;
    $player2_dir = LOCALBASE.'/players/'.$player2;

    $command1 = sprintf('%s/execute_player %s %s', LOCALBASE, escapeshellarg($player1_dir), escapeshellcmd($player1_args) );
    $command2 = sprintf('%s/execute_player %s %s', LOCALBASE, escapeshellarg($player2_dir), escapeshellcmd($player2_args) );
    $command =  sprintf('/usr/local/bin/python "'.LOCALBASE.'/arbiter.py" %s %s 2>&1', escapeshellarg($command1), escapeshellarg($command2) );

    // Add log file
    $logfile = sprintf( '%s/logs/%s - %s vs %s.log', LOCALBASE, date('Y-m-d H:m:s'), $player1, $player2 );
    $command = sprintf( '%s | tee %s', $command, escapeshellarg($logfile) );

    $fp = popen($command, 'r');
}
else
if (!empty($_FILES['log']))
{
    if (preg_match("/\\d+-\\d+-\\d+ \\d+:\\d+:\\d+ - (.+) vs (.+).log/", $_FILES['log']['name'], $matches))
    {
        $player1 = $matches[1];
        $player2 = $matches[2];
        $title = "$player1 vs $player2";
    }
    else
    {
        $player1 = 'Player 1';
        $player2 = 'Player 2';
        $title = basename($_FILES['log']['name'], '.log');
    }
    $fp = fopen($_FILES['log']['tmp_name'], 'r');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Alquerque  - <?php echo htmlentities($title); ?></title>
<base href="http://<?php echo $_SERVER['SERVER_NAME'], $_SERVER['PHP_SELF']; ?>" />
<link rel="StyleSheet" href="arbiter.css" title="Arbiter" type="text/css" />
</head>
<body>
<script type="text/javascript">
function popup(url, width, height)
{
    x = parseInt((screen.width - width)/2);
    y = parseInt((screen.height - height)/2);
    return window.open( url, '_blank',
        'left='+x+',top='+y+',width='+width+',height='+height+
        ',location=0,menubar=0,resizable=1,scrollbars=0,status=0,titlebar=1,toolbar=0' );
}
</script>

<?php
if (!isset($fp))
{
?><h3>Invalid parameters given!</h3><?php
} else {
?><h1><span class="title"><?php echo htmlentities($title); ?><span id="resultScore"></span></h1><?php
}
?>

<table>
<tr><td><h2 style="text-align:center">Game Board</h2></td>
    <td><h2 style="text-align:center">Moves played</h2></td></tr>
<tr><td>    
<p style="text-align:center"><b><span id="resultText"></span></b></p>
<div style="text-align:center; padding-bottom: 0.5em">
<img style="vertical-align:middle" src="stone1s.gif" alt="White stones:" />&nbsp;<span id="stones1">24</span>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<img style="vertical-align:middle" src="stone2s.gif" alt="Black stones:" />&nbsp;<span id="stones2">24</span>
</div>
<div style="background:url(board.gif); width: 310px; height:310px; text-align: right;line-height:0px; padding:5px; margin-right:20px;">
<?php
    for($r = 0; $r < 7; ++$r)
    {
        for($c = 0; $c < 7; ++$c)
            echo '<img alt="" id="fld', (6 - $r), $c, '" src="stone0.gif" width="40" height="40" />';
        echo '<br />';
    }
?>
</div>
</td>
<td><select id="moveList" size="2" style="width: 200px; height: 300px"><option>--- Start ---</option></select>
</td></tr>
<tr><td colspan="2"><h2 style="text-align:center">Comments</h2>
                    <div style="width:600px; height:300px;" class="messagelog" id="messageLog"></div></td></tr>
<tr><td colspan="2">Show comments for player:
<input type="radio" name="whichComments" value="1" id="comments1" onchange="showComments()"/>1: <?php echo htmlentities($player1); ?> 
<input type="radio" name="whichComments" value="2" id="comments2" onchange="showComments()"/>2: <?php echo htmlentities($player2); ?>
</td></tr>
</table>
</form>

<script type="text/javascript">
var moveList = document.getElementById('moveList');

function field(r, c)
{
    return document.getElementById('fld' + r + c);
}

function replaceChild(parent, child)
{
    while (parent.firstChild)
        parent.removeChild(parent.firstChild);
    parent.appendChild(child);
}

function show()
{
    var i = moveList.selectedIndex;
    if (!(i >= 0))
        return;
    var option = moveList.options[i];
    var count = new Array;
    count[1] = count[2] = 0;
    for (var r = 0; 7 > r; ++r)
        for (var c = 0; 7 > c; ++c)
        {
            field(r, c).src = 'stone' + option.board[r][c] + '.gif';
            ++count[option.board[r][c]];
        }
    replaceChild(document.getElementById('stones1'), document.createTextNode(count[1]));
    replaceChild(document.getElementById('stones2'), document.createTextNode(count[2]));
    showComments();
}

function showComments()
{
    var i = moveList.selectedIndex;
    var text = "";
    if (i >= 0) if (document.getElementById('comments1').checked)
        text = moveList.options[i].comments[0];
    if (i >= 0) if (document.getElementById('comments2').checked)
        text = moveList.options[i].comments[1];

    var messageLog = document.getElementById('messageLog');
    while (messageLog.firstChild)
        messageLog.removeChild(messageLog.firstChild);

    var lines = text.split("\n");
    for (var n = 0; n < lines.length; ++n)
    {
        messageLog.appendChild(document.createTextNode(lines[n]));
        messageLog.appendChild(document.createElement('br'));
    }
}

function init()
{
    var option = moveList.firstChild;
    option.board = new Array(
        new Array(1, 1, 1, 1, 1, 1, 1),
        new Array(1, 1, 1, 1, 1, 1, 1),
        new Array(1, 1, 1, 1, 1, 1, 1),
        new Array(2, 2, 2, 0, 1, 1, 1),
        new Array(2, 2, 2, 2, 2, 2, 2),
        new Array(2, 2, 2, 2, 2, 2, 2),
        new Array(2, 2, 2, 2, 2, 2, 2) );
    option.comments = Array('', '');

    moveList.onchange = show;
    moveList.selectedIndex = 0;
    show();
}

function parseFields(str)
{
    var fields = new Array();
    for(var n = 0; str.length >= n + 2; n += 3)
    {
        var pos = new Object;
        pos.r = str.charAt(n + 1) - 1;
        pos.c = "abcdefg".indexOf(str.charAt(n));
        fields[fields.length] = pos;
    }
    return fields;
}

function updateBoard(oldBoard, move)
{
    var board = new Array();
    for (var r = 0; r != 7; ++r)
    {
        board[r] = new Array();
        for (var c = 0; c != 7; ++c)
            board[r][c] = oldBoard[r][c];
    }

    var fields = parseFields(move);

    var fld1 = fields[0], fld2 = fields[fields.length - 1];
    board[fld1.r][fld1.c] = oldBoard[fld2.r][fld2.c];
    board[fld2.r][fld2.c] = oldBoard[fld1.r][fld1.c];
    for (var n = 0; fields.length >= n + 2; ++n)
        if (move.charAt(2 + 3*n) == '*')
            board[(fields[n].r + fields[n + 1].r)/2][(fields[n].c + fields[n + 1].c)/2] = 0;
    return board;
}

function move(str)
{
    var option = document.createElement('option');
    option.text = parseInt((moveList.length + 1)/2) + '. ' + str;
    option.board = updateBoard(moveList.options[moveList.length - 1].board, str);
    option.comments = Array( '', '' );
    try {
        moveList.add(option, null);
    }
    catch (error) {
        moveList.add(option);	// work around for IE
    }
    if (moveList.selectedIndex == moveList.length - 2)
    {
        moveList.selectedIndex = moveList.length - 1;
        show();
    }
}

function result(score1, score2, description)
{
    document.getElementById('resultScore').appendChild(
        document.createTextNode(': ' + score1 + '-' + score2));
    document.getElementById('resultText').appendChild(
        document.createTextNode(description));
    show();
}

function player1(message)
{
    moveList.options[moveList.length - 1].comments[0] += message + "\n";
    showComments();
}

function player2(message)
{
    moveList.options[moveList.length - 1].comments[1] += message + "\n";
    showComments();
}

init();

</script>

<p><a href="index.php">Back to homepage.</a></p>

<div class="footer">Copyright &copy; 2004-2007 by Maks Verver
(<a href="mailto:maks@hell.student.utwente.nl">maks@hell.student.utwente.nl</a>)</div>
</body>
<?php
if(isset($fp))
{
    flush();
    while(($line = fgets($fp)) !== FALSE)
    {
        $line = trim($line);
        if(preg_match('/^player1> (.+)/', $line, $matches))
            $code = sprintf('player1("%s")', addslashes($matches[1]));
        else
        if(preg_match('/^player2> (.+)/', $line, $matches))
            $code = sprintf('player2("%s")', addslashes($matches[1]));
        else
        if(preg_match('/^MOVE: (.+)/', $line, $matches))
            $code = sprintf('move("%s")', addslashes($matches[1]));
        else
        if(preg_match('/^RESULT: (\d+)-(\d+) (.+)/', $line, $matches))
            $code = sprintf('result(%s, %s, "%s")',
                $matches[1], $matches[2], addslashes($matches[3]));
        else
            continue;
        echo '<script type="text/javascript">', $code, ';</script>', "\n";
        flush();
    }
}
?>
</html>
