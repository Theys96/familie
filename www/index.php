<?php
#error_reporting(E_ALL);
#ini_set('display_errors', 1); 
#ini_set('display_startup_errors', 1);
header('Content-Type: text/html; charset=utf-8');
session_start();
require 'config.php';
$Db = new Mysqli($mysql_host, $mysql_username, $mysql_password, $mysql_database);
#$Db->set_charset("utf8");
$Init = true;
require 'code.php';
if (isset($_POST['password']) && $_POST['password'] == "Havinga123") {
	$_SESSION['loggedin'] = "true";
}
$loggedIn = boolval($_SESSION['loggedin']);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
.staat td {
	border: 1px solid black;
	text-align: center;
}
</style>
</head>
<body>

<?php if (!$loggedIn): ?>
<form method='post'>
	Wachtwoord: <input type='password' name='password' /><br />
	<input type='submit' value='OK' />
</form>
<?php endif; ?>

<?php if ($loggedIn && !isset($_GET['person'])) : ?>

<table width="100%">
	<tr>
		<td></td><td></td>
		<td><b>Achternaam</b></td>
		<td><b>Naam</b></td>
		<td></td>
		<td></td>
	</tr>
<?php
foreach (allPersons($Db) as $person) {
	echo "<tr>";
	echo "<td><a href='?person=". $person->id ."'>Open</a></td>";
	echo "<td>" . $person->id . "</td>";
	echo "<td>" . $person->lastName . "</td>";
	echo "<td>" . $person->name . "</td>";
	echo "<td>" . $person->nickName . "</td>";
	echo "</tr>\n";
	echo "</a>";
}
?>
</table>

<?php endif; if ($loggedIn && isset($_GET['person'])) : ?>

<?php
$person = new Person(intval($_GET['person']), $Db);
$person->getBirth();
$person->getDeath();
$person->getChildren();
$person->getMarriage();
$person->getSiblings();
?>

<?php if (!isset($_GET['staat'])) : ?>

<?php
echo "<a href='index.php'>Lijst</a><br />";
echo "<h2>" . $person->fullName . "</h2>";
$haveBirth = $person->birthDate && $person->birthDate != "??";
$haveDeath = $person->deathDate && $person->deathDate != "??";
if ($haveBirth) {
	echo "<b>Geboren:</b> " . dateAndPlace($person->birthDate, $person->birthPlace);
	if (!($person->deathDate)) {
		echo " (" . dateDiff($person->birthDate) . ")";
	}
	if ($person->birthSource) {
		echo sourceLink($person->birthSource);
	}
	echo "<br />";
}
if ($person->doopDate && $person->doopDate != "??") {
	echo "<b>Gedoopt:</b> " . dateAndPlace($person->doopDate, $person->doopPlace);
	if ($person->doopSource) {
		echo sourceLink($person->doopSource);
	}
	echo "<br />";
}
if ($haveDeath) {
	echo "<b>Overleden:</b> " . dateAndPlace($person->deathDate, $person->deathPlace);
	if ($haveBirth) {
		echo " (" . dateDiff($person->birthDate, $person->deathDate) . ")";
	}
	if ($person->deathSource) {
		echo sourceLink($person->deathSource);
	}
	echo "<br />";
} elseif ($person->deathDate) {
	echo "<b>Overleden.</b></br />";
}

echo "<br />";

if ($person->father) {
	$person->father->getBirth();
	echo "<b>Vader:</b> " . personLink($person->father) . "<br />";
	if ($person->father->father) {
		echo "&emsp;<b>Grootvader:</b> " . personLink($person->father->father) . "<br />";
	}
	if ($person->father->mother) {
		echo "&emsp;<b>Grootmoeder:</b> " . personLink($person->father->mother) . "<br />";
	}
}
if ($person->mother) {
	$person->mother->getBirth();
	echo "<b>Moeder:</b> " . personLink($person->mother) . "<br />";
	if ($person->mother->father) {
		echo "&emsp;<b>Grootvader:</b> " . personLink($person->mother->father) . "<br />";
	}
	if ($person->mother->mother) {
		echo "&emsp;<b>Grootmoeder:</b> " . personLink($person->mother->mother) . "<br />";
	}
}

echo "<br />";

foreach ($person->marriage as $marriage) {
	echo "Getrouwd met " . personLink($marriage['partner']);
	echo dateAndPlace_($marriage['date'], $marriage['place']);
	if ($marriage['source']) { echo sourceLink($marriage['source']); }
	echo "<br />";
}

if ($person->notes) {
	echo "<p>" . $person->notes . "</p>";
}

if (count($person->children) > 0) {
	echo "<h3>Kinderen</h3>";
	foreach ($person->children as $child) {
		$child['child']->getBirth();
		echo personLink($child['child']);
		if ($child['child']->birthDate && $child['child']->birthDate != "??") {
			echo dateAndPlace_($child['child']->birthDate, $child['child']->birthPlace);
		} else if ($child['child']->doopDate && $child['child']->doopDate != "??") {
			echo " (gedoopt ". dateString($child['child']->doopDate) .")";
		}
		if ($child['partner']) {
			echo " met " . personLink($child['partner']);
		}

		if ($child['source']) {
			echo sourceLink($child['source']);
		}
		echo "<br />";
	}
}

if (count($person->siblings) > 0) {
	echo "<h3>Broers en zussen</h3>";
	foreach ($person->siblings as $sibling) {
		if (!$sibling['full']) {echo "<i>";}
		$sibling['sibling']->getBirth();
		echo personLink($sibling['sibling']);
		if ($sibling['sibling']->birthDate && $sibling['sibling']->birthDate != "??") {
			echo dateAndPlace_($sibling['sibling']->birthDate, $sibling['sibling']->birthPlace);
		} else if ($sibling['sibling']->doopDate && $sibling['sibling']->doopDate != "??") {
			echo " (gedoopt ". dateString($sibling['sibling']->doopDate) .")";
		}
		if ($sibling['source']) {
			echo sourceLink($sibling['source']);
		}
		if (!$sibling['full']) {echo "</i>";}
		echo "<br />";
	}
}

echo "<br /><br /><a href='?person=".$person->id."&staat'>Staat</a>";

?>

<?php endif; if (isset($_GET['staat'])) : ?>

<?php
$a = personStaat($person);
if ($person->father) {
	$person->father->getBirth();
	$b1 = personStaat($person->father);
	if ($person->father->father) {
		$c1 = personStaat($person->father->father);
		$person->father->father->getBirth();
		$d1 = personStaat($person->father->father->father);
		$d2 = personStaat($person->father->father->mother);
	}
	if ($person->father->mother) {
		$c2 = personStaat($person->father->mother);
		$person->father->mother->getBirth();
		$d3 = personStaat($person->father->mother->father);
		$d4 = personStaat($person->father->mother->mother);
	}
}
if ($person->mother) {
	$person->mother->getBirth();
	$b2 = personStaat($person->mother);
	if ($person->mother->father) {
		$c3 = personStaat($person->mother->father);
		$person->mother->father->getBirth();
		$d5 = personStaat($person->mother->father->father);
		$d6 = personStaat($person->mother->father->mother);
	}
	if ($person->mother->mother) {
		$c4 = personStaat($person->mother->mother);
		$person->mother->mother->getBirth();
		$d7 = personStaat($person->mother->mother->father);
		$d8 = personStaat($person->mother->mother->mother);
	}
}
?>

<table class='staat'>
	<tr>
		<td><?php echo $d1; ?></td>
		<td><?php echo $d2; ?></td>
		<td><?php echo $d3; ?></td>
		<td><?php echo $d4; ?></td>
		<td><?php echo $d5; ?></td>
		<td><?php echo $d6; ?></td>
		<td><?php echo $d7; ?></td>
		<td><?php echo $d8; ?></td>
	</tr>
	<tr>
		<td colspan='2'><?php echo $c1; ?></td>
		<td colspan='2'><?php echo $c2; ?></td>
		<td colspan='2'><?php echo $c3; ?></td>
		<td colspan='2'><?php echo $c4; ?></td>
	</tr>
	<tr>
		<td colspan='4'><?php echo $b1; ?></td>
		<td colspan='4'><?php echo $b2; ?></td>
	</tr>
	<tr>
		<td colspan='8'><?php echo $a; ?></td>
	</tr>
</table>

<?php endif; ?>
<?php endif; ?>
</body>
</html>