<?php require_once('common.inc.php') ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Alquerque - Online Arbiter</title>
<link rel="StyleSheet" href="arbiter.css" title="Arbiter" type="text/css" />
</head>
<body>
<h1><span class="title">Alquerque - Online Arbiter</span></h1>

<p class="quote">Un bon mot ne prouve rien. (Witty saying to be inserted here.)</p>

<div class="box">
<h2>Play match</h2>
<?php
$players = array();
$dh = opendir(LOCALBASE.'/players');
while (($file = readdir($dh)) !== FALSE)
{
    if(is_dir(LOCALBASE.'/players/'.$file) && $file{0} != '.')
        $players[] = $file;
}
closedir($dh);
sort($players);
?>
<form method="post" action="play.php">
<p><b>Player 1:</b><br />
<select name="Player1"><option value=""></option><?php
foreach($players as $player)
    echo '<option value="', $player, '">', htmlentities($player), '</option>';
?></select>
<input type="text" name="Player1Args" /> <i>(arguments)</i></p>
<p><b>Player 2:</b><br />
<select name="Player2"><option value=""></option><?php
foreach($players as $player)
    echo '<option value="', $player, '">', htmlentities($player), '</option>';
?></select>
<input type="text" name="Player2Args" /> <i>(arguments)</i></p>
<p><input type="submit" value="Play..." /></p>
</form>
</div>

<div class="box">
<h2>Replay log file</h2>
<form method="post" action="play.php" enctype="multipart/form-data">
<p><b>Log file:</b><br />
<input type="file" name="log" /></p>
<p><input type="submit" value="Replay..." /></p>
</form>

<p><a href="logs/">Game logs available here</a>.</p>
</div>

<div class="box">
<h2>Add player program</h2>
<form method="post" enctype="multipart/form-data" action="add_player.php">
<p><strong>Program name:</strong><br />
<input type="text" name="Name" /></p>
<p><strong>Source file:</strong><br />
<input type="file" name="Source" /></p>
<p><strong>Source language:</strong><br />
<select name="Language">
<option value=""></option>
<option value="c">C (using GCC 3.4.6)</option>
<option value="c-ndebug">C with NDEBUG defined (using GCC 3.4.6)</option>
<option value="c++">C++ (using GCC 3.4.6)</option>
<option value="c++-ndebug">C++ with NDEBUG defined (using GCC 3.4.6)</option>
<option value="java">Java (using Sun JDK 1.5)</option>
</select></p>
<p><input type="checkbox" name="DeleteSource" value="yes" checked="checked" />
Erase source file after compilation.</p>
<p><input type="submit" value="Add..." /></p>
</form>
</div>

<div class="footer">Copyright &copy; 2004-2007 by Maks Verver
(<a href="mailto:maks@hell.student.utwente.nl">maks@hell.student.utwente.nl</a>)</div>

</body>
</html>
