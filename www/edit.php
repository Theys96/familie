<?php
error_reporting(E_ALL);
session_start();
require 'config.php';
$Db = new Mysqli($mysql_host, $mysql_username, $mysql_password, $mysql_host);
$Init = true;
require 'code.php';
if ($_POST['password'] == "Havinga321") {
	$_SESSION['editLoggedin'] = "true";
}
$loggedIn = boolval($_SESSION['editLoggedin']);

function personFullname($row) {
	return 
		$row['firstName'] . 
		($row['middleName'] == "" ? "" : " " . $row['middleName']) . 
		($row['nickName'] == "" ? "" : " \"" . $row['nickName'] . "\" ") . 
		($row['lastName'] == "" ? "" : " " . $row['lastName']);
}
function sqlDate($date) {
	return "'".date_format(date_create($date), "Y-m-d")."'";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
</style>
</head>
<body>

<?php if (!$loggedIn): ?>
<form method='post'>
	Wachtwoord: <input type='password' name='password' /><br />
	<input type='submit' value='OK' />
</form>
<?php endif; ?>
<?php if ($loggedIn && !isset($_GET['record'])) : ?>

<?php
if (isset($_POST['newPerson']) && $_POST['firstName'] != "") {
	$Db->query("INSERT INTO personen (firstName, middleName, lastName, nickName) 
		VALUES ('".$_POST['firstName']."', '".$_POST['middleName']."', '".$_POST['lastName']."', '".$_POST['nickName']."')");
	$id = $Db->insert_id;
	$Db->query("INSERT INTO geboorte (child) VALUES ($id)");
	if ($_POST['overlijden']) {
		$Db->query("INSERT INTO overlijden (person) VALUES ($id)");
	}
	if ($_POST['doop']) {
		$Db->query("INSERT INTO doop (person) VALUES ($id)");
	}
}
if (isset($_POST['newHuwelijk']) && isset($_POST['groom']) && isset($_POST['bride'])) {
	$Db->query("INSERT INTO huwelijk (groom, bride) VALUES (".$_POST['groom'].", ".$_POST['bride'].")");
}
?>

<table>
	<tr><td valign='top'>
		<h3>Nieuw persoon</h3>
		<form method='post'>
		<input type='hidden' value='true' name='newPerson' />
		<table>
			<tr><td>Voornaam:</td><td><input type='text' name='firstName' /></td></tr>
			<tr><td>Tweede naam:</td><td><input type='text' name='middleName' /></td></tr>
			<tr><td>Achternaam:</td><td><input type='text' name='lastName' /></td></tr>
			<tr><td>Roepnaam:</td><td><input type='text' name='nickName' /></td></tr>
			<tr><td>Overleden</td><td><input type='checkbox' name='overlijden' value='true' /></td></tr>
			<tr><td>Gedoopt</td><td><input type='checkbox' name='doop' value='true' /></td></tr>
		</table>
		<input type='submit' value='Toevoegen' />
		</form>
	</td>
	<td valign='top'>
		<h3>Nieuw huwelijk</h3>
		<form method='post'>
		<input type='hidden' value='true' name='newHuwelijk' />
		<table>
			<tr><td>Bruidegom:</td><td><input type='number' name='groom' /></td></tr>
			<tr><td>Bruid:</td><td><input type='number' name='bride' /></td></tr>
		</table>
		<input type='submit' value='Toevoegen' />
		</form>
	</td></tr>
</table>

<h2>Personen</h2>
<table>
	<tr>
		<td><b>ID</b></td>
		<td><b>Naam</b></td>
	</tr>
<?php
$qr = $Db->query("SELECT * FROM personen");
while ( ($person = $qr->fetch_assoc()) != false) {
	echo "<tr>";
	echo "<td><a href='?record=P-".$person['id']."'>Edit</a></td>";
	echo "<td>" . $person['id'] . "</td>";
	echo "<td>" . personFullname($person) . "</td>";
	echo "</tr>\n";
}
?>
</table>

<?php endif; ?>
<?php if ($loggedIn && isset($_GET['record'])) : ?>

<?php

$record = explode("-", $_GET['record']);
$type = strtoupper($record[0]);
$id = intval($record[1]);

echo "<a href='?'>Lijst</a><br /><br />";
switch($type) {
	case 'P':
		if (isset($_POST['delete'])) {
			if ($_POST['event'] == 'doop') {
				$Db->query("DELETE FROM doop WHERE person=" . $id);
			}
			elseif ($_POST['event'] == 'overlijden') {
				$Db->query("DELETE FROM overlijden WHERE person=" . $id);
			}
		}
		if (isset($_POST['update'])) {
			$Db->query("UPDATE personen SET 
				firstName='".$_POST['firstName']."',
				middleName='".$_POST['middleName']."',
				lastName='".$_POST['lastName']."',
				nickName='".$_POST['nickName']."',
				notes='".$Db->real_escape_string($_POST['notes'])."'
				WHERE id=".$id);
		}
		if (isset($_GET['add'])) {
			if ($_GET['add'] == 'doop') {
				$qr = $Db->query("SELECT * FROM doop WHERE person=$id");
				if ($qr->num_rows == 0) {
					$Db->query("INSERT INTO doop (person) VALUES ($id)");
				}
			}
			if ($_GET['add'] == 'overlijden') {
				$qr = $Db->query("SELECT * FROM overlijden WHERE person=$id");
				if ($qr->num_rows == 0) {
					$Db->query("INSERT INTO overlijden (person) VALUES ($id)");
				}
			}
		}

		$qr = $Db->query("SELECT * FROM personen WHERE id=$id");
		if ($qr->num_rows) {
			$person = $qr->fetch_assoc();

			$prev = $Db->query("SELECT * FROM personen WHERE id<$id ORDER BY id DESC LIMIT 1");
			$next = $Db->query("SELECT * FROM personen WHERE id>$id ORDER BY id ASC LIMIT 1");
			if ($prev->num_rows) {
				$_prev = $prev->fetch_assoc();
				echo "<a href='?record=P-".$_prev['id']."'>Vorige</a>";
			}
			if ($prev->num_rows && $next->num_rows) {
				echo "  |  ";
			}
			if ($next->num_rows) {
				$_next = $next->fetch_assoc();
				echo "<a href='?record=P-".$_next['id']."'>Volgende</a>";
			}

			echo "<h2>" . $type . "-" . $id . ": " . personFullname($person) . "</h2>";
			echo "<form method='post'><input type='hidden' name='update' value='true' />";
			echo "<table>";
			echo "<tr><td>Voornaam: </td><td><input type='text' name='firstName' value='".$person['firstName']."' /></td></tr>";
			echo "<tr><td>Tweede naam: </td><td><input type='text' name='middleName' value='".$person['middleName']."' /></td></tr>";
			echo "<tr><td>Achternaam: </td><td><input type='text' name='lastName' value='".$person['lastName']."' /></td></tr>";
			echo "<tr><td>Roepnaam: </td><td><input type='text' name='nickName' value='".$person['nickName']."' /></td></tr>";
			echo "<tr><td>Notities: </td><td><textarea style='width: 400px; height: 100px;' name='notes'>".$person['notes']."</textarea></td></tr>";
			echo "</table>";
			echo "<input type='submit' value='Update' />";
			echo "</form><br />";
			
			$hasEvents = array('doop' => false, 'overlijden' => false);

			$qr = $Db->query("SELECT * FROM geboorte WHERE child=$id");
			if ($qr->num_rows) {
				$g = $qr->fetch_assoc();
				echo "<a href='?record=G-" . $g['child'] . "'>Geboorte</a><br />";
			}

			$qr = $Db->query("SELECT * FROM doop WHERE person=$id");
			if ($qr->num_rows) {
				$d = $qr->fetch_assoc();
				echo "<a href='?record=D-" . $d['person'] . "'>Doop</a><br />";
				$hasEvents['doop'] = true;
			} else {
				echo "<i><a href='?record=P-" . $id . "&add=doop'>Doop toevoegen</a></i><br />";
			}

			$qr = $Db->query("SELECT * FROM overlijden WHERE person=$id");
			if ($qr->num_rows) {
				$o = $qr->fetch_assoc();
				echo "<a href='?record=O-" . $o['person'] . "'>Overlijden</a><br />";
				$hasEvents['overlijden'] = true;
			} else {
				echo "<i><a href='?record=P-" . $id . "&add=overlijden'>Overlijden toevoegen</a></i><br />";
			}

			$qr = $Db->query("SELECT * FROM huwelijk WHERE groom=$id OR bride=$id");
			if ($qr->num_rows) {
				echo "<h3>Huwelijk</h3>";
				while ( ($h = $qr->fetch_assoc()) != false ) {
					$groom = $Db->query("SELECT * FROM personen WHERE id=" . $h['groom'])->fetch_assoc();
					$bride = $Db->query("SELECT * FROM personen WHERE id=" . $h['bride'])->fetch_assoc();
					echo "<a href='?record=H-" . $h['id'] . "'>" . personFullname($groom) . " en " . personFullname($bride) . "</a><br />";
				}
			}

			$qr = $Db->query("SELECT * FROM geboorte WHERE father=$id OR mother=$id");
			if ($qr->num_rows) {
				echo "<h3>Kinderen</h3>";
				while ( ($g = $qr->fetch_assoc()) != false ) {
					$child = $Db->query("SELECT * FROM personen WHERE id=" . $g['child'])->fetch_assoc();
					echo "<a href='?record=P-" . $child['id'] . "'>" . personFullname($child) . "</a><br />";
				}
			}

			$qr = $Db->query("SELECT * FROM geboorte WHERE child=$id");
			if ( ($p = $qr->fetch_assoc()) && ($p['father'] || $p['mother']) ) {
				echo "<h3>Ouders</h3>";
				if ($p['father']) {
					$father = $Db->query("SELECT * FROM personen WHERE id=" . $p['father'])->fetch_assoc();
					echo "Vader: <a href='?record=P-" . $father['id'] . "'>" . personFullname($father) . "</a><br />";
				}
				if ($p['mother']) {
					$mother = $Db->query("SELECT * FROM personen WHERE id=" . $p['mother'])->fetch_assoc();
					echo "Moeder: <a href='?record=P-" . $mother['id'] . "'>" . personFullname($mother) . "</a><br />";
				}
			}

			echo "<h3>Verwijderen</h3>";
			echo "<form method='post'><input type='hidden' name='delete' value='true'>";
			echo "<select name='event' id='events'>";
			$any = false;
			foreach ($hasEvents as $event => $exists) {
				if ($exists) {
					echo "<option value='" . $event . "'>" . ucfirst($event) . "</option>";
					$any = true;
				}
			}
			if (!$any) {
				echo "<option value='na' disabled selected>Niets om te verwijderen</option>"; 
			}
			echo "<select/>";
			echo "&nbsp<input type='submit' value='Verwijder' />";
			echo "</form><br />";
		}
	break;

	case 'H':
		if (isset($_POST['update'])) {
			$Db->query("UPDATE huwelijk SET 
				groom=".$_POST['groom'].",
				bride=".$_POST['bride'].",
				date=".($_POST['dateNull'] == 'true' ? "null" : sqlDate($_POST['date'])).",
				year=".($_POST['yearNull'] == 'true' ? "null" : intval($_POST['year'])).",
				url=".($_POST['urlNull'] == 'true' ? "null" : "'".$_POST['url']."'").",
				place=".($_POST['placeNull'] == 'true' ? "null" : "'".$_POST['place']."'")."
				WHERE id=".$id);
		}

		$qr = $Db->query("SELECT * FROM huwelijk WHERE id=$id");
		if ($qr->num_rows) {
			$prev = $Db->query("SELECT * FROM huwelijk WHERE id<$id ORDER BY id DESC LIMIT 1");
			$next = $Db->query("SELECT * FROM huwelijk WHERE id>$id ORDER BY id ASC LIMIT 1");
			if ($prev->num_rows) {
				$_prev = $prev->fetch_assoc();
				echo "<a href='?record=H-".$_prev['id']."'>Vorige</a>";
			}
			if ($prev->num_rows && $next->num_rows) {
				echo "  |  ";
			}
			if ($next->num_rows) {
				$_next = $next->fetch_assoc();
				echo "<a href='?record=H-".$_next['id']."'>Volgende</a>";
			}

			$huwelijk = $qr->fetch_assoc();
			$groom = $Db->query("SELECT * FROM personen WHERE id=" . $huwelijk['groom'])->fetch_assoc();
			$bride = $Db->query("SELECT * FROM personen WHERE id=" . $huwelijk['bride'])->fetch_assoc();

			echo "<h2>" . $type . "-" . $id . ": " . personFullname($groom) . " en " . personFullname($bride) . "</h2>";
			echo "<form method='post'><input type='hidden' name='update' value='true' />";
			echo "<table>";
			echo "<tr><td>Bruidegom: </td><td><input type='number' name='groom' value='".$huwelijk['groom']."' /></td>
				<td><a href='?record=P-" . $groom['id'] . "'>" . personFullname($groom) . "</a></td></tr>";
			echo "<tr><td>Bruid: </td><td><input type='number' name='bride' value='".$huwelijk['bride']."' /></td>
				<td><a href='?record=P-" . $bride['id'] . "'>" . personFullname($bride) . "</a></td></tr>";
			echo "<tr><td>Datum: </td><td><input type='date' name='date' value='" . $huwelijk['date'] . "' /></td>
				<td><label><input type='checkbox' name='dateNull' value='true' " . ($huwelijk['date'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Jaar: </td><td><input type='number' name='year' value='" . $huwelijk['year'] . "' /></td>
				<td><label><input type='checkbox' name='yearNull' value='true' " . ($huwelijk['year'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Plaats: </td><td><input type='text' name='place' value='" . $huwelijk['place'] . "' /></td>
				<td><label><input type='checkbox' name='placeNull' value='true' " . ($huwelijk['place'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Bron: </td><td><input type='text' name='url' value='" . $huwelijk['url'] . "' /></td>
				<td><label><input type='checkbox' name='urlNull' value='true' " . ($huwelijk['url'] ? '' : 'checked') . "> N/A</label></td>
				</td>".($huwelijk['url'] ? "<td><a target='_blank' href='".$huwelijk['url']."'>Open</a></td>" : "")."</tr>";	
			echo "</table>";
			echo "<input type='submit' value='Update' />";
			echo "</form>";
		}
	break;

	case 'G':
		if (isset($_POST['update'])) {
			$Db->query("UPDATE geboorte SET 
				father=".($_POST['fatherNull'] == 'true' ? "null" : $_POST['father']).",
				mother=".($_POST['motherNull'] == 'true' ? "null" : $_POST['mother']).",
				date=".($_POST['dateNull'] == 'true' ? "null" : sqlDate($_POST['date'])).",
				year=".($_POST['yearNull'] == 'true' ? "null" : intval($_POST['year'])).",
				url=".($_POST['urlNull'] == 'true' ? "null" : "'".$_POST['url']."'").",
				place=".($_POST['placeNull'] == 'true' ? "null" : "'".$_POST['place']."'")."
				WHERE child=".$id);
		}

		$qr = $Db->query("SELECT * FROM geboorte WHERE child=$id");
		if ($qr->num_rows) {
			$prev = $Db->query("SELECT * FROM geboorte WHERE child<$id ORDER BY child DESC LIMIT 1");
			$next = $Db->query("SELECT * FROM geboorte WHERE child>$id ORDER BY child ASC LIMIT 1");
			if ($prev->num_rows) {
				$_prev = $prev->fetch_assoc();
				echo "<a href='?record=G-".$_prev['child']."'>Vorige</a>";
			}
			if ($prev->num_rows && $next->num_rows) {
				echo "  |  ";
			}
			if ($next->num_rows) {
				$_next = $next->fetch_assoc();
				echo "<a href='?record=G-".$_next['child']."'>Volgende</a>";
			}

			$geboorte = $qr->fetch_assoc();
			$child = $Db->query("SELECT * FROM personen WHERE id=" . $geboorte['child'])->fetch_assoc();

			echo "<h2>" . $type . "-" . $id . "</h2>";
			echo "<form method='post'><input type='hidden' name='update' value='true' />";
			echo "<table>";
			echo "<tr><td>Kind: </td><td><a href='?record=P-" . $geboorte['child'] . "'>" . personFullname($child) . "</a></td></tr>";
			if ($geboorte['father']) {
				$father = $Db->query("SELECT * FROM personen WHERE id=" . $geboorte['father'])->fetch_assoc();
				echo "<tr><td>Vader: </td><td><input type='number' name='father' value='".$geboorte['father']."' /></td>
					<td><input type='checkbox' name='fatherNull' value='true'> N/A</td>
					<td><a href='?record=P-" . $father['id'] . "'>" . personFullname($father) . "</a></td></tr>";
			} else {
				echo "<tr><td>Vader: </td><td><input type='number' name='father' /></td>
					<td><input type='checkbox' name='fatherNull' value='true' checked> N/A</td></tr>";
			}
			if ($geboorte['mother']) {
				$mother = $Db->query("SELECT * FROM personen WHERE id=" . $geboorte['mother'])->fetch_assoc();
				echo "<tr><td>Moeder: </td><td><input type='number' name='mother' value='".$geboorte['mother']."' /></td>
					<td><input type='checkbox' name='motherNull' value='true'> N/A</td>
					<td><a href='?record=P-" . $mother['id'] . "'>" . personFullname($mother) . "</a></td></tr>";
			} else {
				echo "<tr><td>Moeder: </td><td><input type='number' name='mother' /></td>
					<td><input type='checkbox' name='motherNull' value='true' checked> N/A</td></tr>";
			}
			echo "<tr><td>Datum: </td><td><input type='date' name='date' value='" . $geboorte['date'] . "' /></td>
				<td><label><input type='checkbox' name='dateNull' value='true' " . ($geboorte['date'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Jaar: </td><td><input type='number' name='year' value='" . $geboorte['year'] . "' /></td>
				<td><label><input type='checkbox' name='yearNull' value='true' " . ($geboorte['year'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Plaats: </td><td><input type='text' name='place' value='" . $geboorte['place'] . "' /></td>
				<td><label><input type='checkbox' name='placeNull' value='true' " . ($geboorte['place'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Bron: </td><td><input type='text' name='url' value='" . $geboorte['url'] . "' /></td>
				<td><label><input type='checkbox' name='urlNull' value='true' " . ($geboorte['url'] ? '' : 'checked') . "> N/A</label></td>
				</td>".($geboorte['url'] ? "<td><a target='_blank' href='".$geboorte['url']."'>Open</a></td>" : "")."</tr>";
			echo "</table>";
			echo "<input type='submit' value='Update' />";
			echo "</form>";
		}
	break;

	case 'O':
		if (isset($_POST['update'])) {
			$Db->query("UPDATE overlijden SET 
				date=".($_POST['dateNull'] == 'true' ? "null" : sqlDate($_POST['date'])).",
				year=".($_POST['yearNull'] == 'true' ? "null" : intval($_POST['year'])).",
				url=".($_POST['urlNull'] == 'true' ? "null" : "'".$_POST['url']."'").",
				place=".($_POST['placeNull'] == 'true' ? "null" : "'".$_POST['place']."'")."
				WHERE person=".$id);
		}

		$qr = $Db->query("SELECT * FROM overlijden WHERE person=$id");
		if ($qr->num_rows) {
			$prev = $Db->query("SELECT * FROM overlijden WHERE person<$id ORDER BY person DESC LIMIT 1");
			$next = $Db->query("SELECT * FROM overlijden WHERE person>$id ORDER BY person ASC LIMIT 1");
			if ($prev->num_rows) {
				$_prev = $prev->fetch_assoc();
				echo "<a href='?record=O-".$_prev['person']."'>Vorige</a>";
			}
			if ($prev->num_rows && $next->num_rows) {
				echo "  |  ";
			}
			if ($next->num_rows) {
				$_next = $next->fetch_assoc();
				echo "<a href='?record=O-".$_next['person']."'>Volgende</a>";
			}

			$overlijden = $qr->fetch_assoc();
			$person = $Db->query("SELECT * FROM personen WHERE id=" . $overlijden['person'])->fetch_assoc();

			echo "<h2>" . $type . "-" . $id . "</h2>";
			echo "<form method='post'><input type='hidden' name='update' value='true' />";
			echo "<table>";
			echo "<tr><td>Persoon: </td><td><a href='?record=P-" . $overlijden['person'] . "'>" . personFullname($person) . "</a></td></tr>";
			echo "<tr><td>Datum: </td><td><input type='date' name='date' value='" . $overlijden['date'] . "' /></td>
				<td><label><input type='checkbox' name='dateNull' value='true' " . ($overlijden['date'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Jaar: </td><td><input type='number' name='year' value='" . $overlijden['year'] . "' /></td>
				<td><label><input type='checkbox' name='yearNull' value='true' " . ($overlijden['year'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Plaats: </td><td><input type='text' name='place' value='" . $overlijden['place'] . "' /></td>
				<td><label><input type='checkbox' name='placeNull' value='true' " . ($overlijden['place'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Bron: </td><td><input type='text' name='url' value='" . $overlijden['url'] . "' /></td>
				<td><label><input type='checkbox' name='urlNull' value='true' " . ($overlijden['url'] ? '' : 'checked') . "> N/A</label></td>
				</td>".($overlijden['url'] ? "<td><a target='_blank' href='".$overlijden['url']."'>Open</a></td>" : "")."</tr>";
			echo "</table>";
			echo "<input type='submit' value='Update' />";
			echo "</form>";
		}
	break;

	case 'D':
		if (isset($_POST['update'])) {
			$Db->query("UPDATE doop SET 
				date=".($_POST['dateNull'] == 'true' ? "null" : sqlDate($_POST['date'])).",
				year=".($_POST['yearNull'] == 'true' ? "null" : intval($_POST['year'])).",
				url=".($_POST['urlNull'] == 'true' ? "null" : "'".$_POST['url']."'").",
				place=".($_POST['placeNull'] == 'true' ? "null" : "'".$_POST['place']."'")."
				WHERE person=".$id);
		}

		$qr = $Db->query("SELECT * FROM doop WHERE person=$id");
		if ($qr->num_rows) {
			$prev = $Db->query("SELECT * FROM doop WHERE person<$id ORDER BY person DESC LIMIT 1");
			$next = $Db->query("SELECT * FROM doop WHERE person>$id ORDER BY person ASC LIMIT 1");
			if ($prev->num_rows) {
				$_prev = $prev->fetch_assoc();
				echo "<a href='?record=D-".$_prev['person']."'>Vorige</a>";
			}
			if ($prev->num_rows && $next->num_rows) {
				echo "  |  ";
			}
			if ($next->num_rows) {
				$_next = $next->fetch_assoc();
				echo "<a href='?record=D-".$_next['person']."'>Volgende</a>";
			}

			$doop = $qr->fetch_assoc();
			$person = $Db->query("SELECT * FROM personen WHERE id=" . $doop['person'])->fetch_assoc();

			echo "<h2>" . $type . "-" . $id . "</h2>";
			echo "<form method='post'><input type='hidden' name='update' value='true' />";
			echo "<table>";
			echo "<tr><td>Persoon: </td><td><a href='?record=P-" . $doop['person'] . "'>" . personFullname($person) . "</a></td></tr>";
			echo "<tr><td>Datum: </td><td><input type='date' name='date' value='" . $doop['date'] . "' /></td>
				<td><label><input type='checkbox' name='dateNull' value='true' " . ($doop['date'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Jaar: </td><td><input type='number' name='year' value='" . $doop['year'] . "' /></td>
				<td><label><input type='checkbox' name='yearNull' value='true' " . ($doop['year'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Plaats: </td><td><input type='text' name='place' value='" . $doop['place'] . "' /></td>
				<td><label><input type='checkbox' name='placeNull' value='true' " . ($doop['place'] ? '' : 'checked') . "> N/A</label></td>
				</td></tr>";
			echo "<tr><td>Bron: </td><td><input type='text' name='url' value='" . $doop['url'] . "' /></td>
				<td><label><input type='checkbox' name='urlNull' value='true' " . ($doop['url'] ? '' : 'checked') . "> N/A</label></td>
				</td>".($doop['url'] ? "<td><a target='_blank' href='".$doop['url']."'>Open</a></td>" : "")."</tr>";
			echo "</table>";
			echo "<input type='submit' value='Update' />";
			echo "</form>";
		}
	break;
}
?>

<?php endif; ?>
</body>
</html>