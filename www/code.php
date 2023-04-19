<?php
if (!$Init) { return; }

$dateYearOrder = " ORDER BY IFNULL(YEAR(date), year) ASC, date ASC";

function allPersons($db) {
	$qr = $db->query("SELECT * FROM personen ORDER BY lastName DESC, firstName, middleName");
	$array = array();
	while ( ($row = $qr->fetch_assoc()) != false ) {
		$person = new Person($row, $db);
		$array[] = $person;
	}
	return $array;
}

function personLink($person, $staat=false) {
	return "<a href='?person=" . $person->id . ($staat ? "&staat" : "") . "'>" . $person->fullName . "</a>";
}

function sourceLink($url) {
	return " <sup><a href='$url' target='_blank'>[bron]</a></sup>";
}

function findMarriage($person1, $person2) {
	if (!$person1) { return false; }
	if (!$person2) { return false; }
	if (!$person1->MARRIAGE) { $person1->getMarriage(); }
	if (!$person2->MARRIAGE) { $person2->getMarriage(); }
	foreach ($person1->marriage as $marriage) {
		if ($marriage['partner']->id == $person2->id) {
			return $marriage;
		}
	}
	return false;
}

$maanden = array("januari","februari","maart","april","mei","juni","juli","augustus","september","oktober","november","december");
function dateString($date) {
	global $maanden;
	if (is_numeric($date)) {
		return (string)$date;
	} else {
		$date = new DateTime($date);
		return 
			$date->format("j ") . 
			$maanden[intval($date->format("n"))-1] . 
			$date->format(" Y");
	}
}

function dateDiff($date1, $date2 = null) {
	if (is_numeric($date1) and is_numeric($date2)) {
		return "~" . (intval($date2)-intval($date1)) . " jaar";
	}
	if (is_numeric($date1)) {
		$n = intval(date_format(date_create($date2),"Y")) - $date1;
		return ($n == 0 ? 0 : ($n-1) . " of " . $n) . " jaar";
	}
	if (is_numeric($date2)) {
		$n = $date2 - intval(date_format(date_create($date1),"Y"));
		return ($n == 0 ? 0 : ($n-1) . " of " . $n) . " jaar";
	}
	$date1 = new DateTime($date1);
	$date2 = new DateTime($date2);
	$interval = $date2->diff($date1);
	return $interval->format("%y jaar");
}

function dateAndPlace($date, $place, $sep=" te ") {
	$result = "";
	if ( ($date && $date != "??") || $place ) {
		$result .= $date == "??" ? "" : dateString($date);
		$result .= $date != "??" && $place ? $sep : "";
		$result .= $place ? $place : "";
	}
	return $result;
}

function dateAndPlace_($date, $place, $sep=" te ") {
	$result = "";
	if ( ($date && $date != "??") || $place ) {
		$result .= " (";
		$result .= dateAndPlace($date, $place, $sep);
		$result .= ")";
	}
	return $result;
}

function personStaat($person) {
	if (!$person) { return ""; }
	if (!$person->BIRTH) { $person->getBirth(); }
	if (!$person->DEATH) { $person->getDeath(); }
	if (!$person->CHILDREN) { $person->getChildren(); }
	if (!$person->MARRIAGE) { $person->getMarriage(); }
	$staat = personLink($person);
	if ($person->birthDate && $person->birthDate != "??") {
		$staat .= "<br />* " .  dateAndPlace($person->birthDate, $person->birthPlace, ", ");
	}
	foreach ($person->marriage as $marriage) {
		$staat .=  "<br />x ";
		$staat .=  dateAndPlace($marriage['date'], isset($marriage['place']) ? $marriage['place'] : false, ", ");
		$staat .=  " (" . ($marriage['partner']->nickName ? $marriage['partner']->nickName : $marriage['partner']->firstName) . ")";
	}
	if ($person->deathDate && $person->deathDate != "??") {
		$staat .= "<br />† " . dateAndPlace($person->deathDate, $person->deathPlace, ", ");
	}
	return $staat;
}


class Person {

	private $db;

	public $id;
	public $firstName;
	public $middleName;
	public $lastName;
	public $nickName;
	public $fullName;
	public $name;

	public $father;
	public $mother;
	public $birthDate;
	public $birthSource;
	public $birthPlace;

	public $doopDate;
	public $doopSource;
	public $doopPlace;

	public $deathDate;
	public $deathSource;
	public $deathPlace;

	public $children = array();

	public $marriage = array();

	public $siblings = array();

	public $BIRTH = false;
	public $DEATH = false;
	public $CHILDREN = false;
	public $MARRIAGE = false;

	function __construct($input, $db) {
		$this->db = $db;
		if (is_array(($input))) {
			$this->setBasics($input);
		} else {
			$id = $input;
			$qr = $this->db->query("SELECT * FROM personen WHERE id=$id");
			if ($qr->num_rows > 0) {
				$this->setBasics($qr->fetch_assoc());
			} else {
				throw new Exception("Could not find id $id.");
			}
		}
	}

	function getSummary() {
		$string = "";
		if (!$this->BIRTH) { $this->getBirth(); }
		if (!$this->DEATH) { $this->getDeath(); }
		$string .= $this->fullName;
		if ($this->birthDate && $this->birthDate != "??") {
			$string .= dateString($this->birthDate) . "<br />";
		}
		if ($this->deathDate && $this->deathDate != "??") {
			$string .= dateString($this->deathDate) . "<br />";
		}
		return $string;
	}

	function setBasics($record) {
		$this->id = $record['id'];
		$this->firstName = $record['firstName'];
		$this->middleName = (isset($record['middleName']) ? $record['middleName'] : "??");
		$this->name = $this->firstName . ($this->middleName == "" ? "" : " " . $this->middleName);
		$this->lastName = $record['lastName'];
		$this->nickName = $record['nickName'];
		$this->fullName = $this->name . ($this->nickName != "" ? " \"".$this->nickName . '"' : "") . ($this->lastName != "" ? " ".$this->lastName : "");
		$this->notes = $record['notes'];
	}

	function getBirth() {
		$qr = $this->db->query("SELECT * FROM geboorte WHERE child=" . $this->id);
		if ($qr->num_rows > 0) {
			$birth = $qr->fetch_assoc();
			if ($birth['father']) {
				$this->father = new Person($birth['father'], $this->db);
			}
			if ($birth['mother']) {
				$this->mother = new Person($birth['mother'], $this->db);
			}
			$this->birthDate = ($birth['date'] ? $birth['date'] : ($birth['year'] ? intval($birth['year']) : "??"));
			if ($birth['url']) {
				$this->birthSource = $birth['url'];
			}
			if ($birth['place']) {
				$this->birthPlace = $birth['place'];
			}
		}

		$qr = $this->db->query("SELECT * FROM doop WHERE person=" . $this->id);
		if  ($qr->num_rows > 0) {
			$doop = $qr->fetch_assoc();
			$this->doopDate = ($doop['date'] ? $doop['date'] : ($doop['year'] ? intval($doop['year']) : "??"));
			if ($doop['url']) {
				$this->doopSource = $doop['url'];
			}
			if ($doop['place']) {
				$this->doopPlace = $doop['place'];
			}
		}

		$this->BIRTH = true;
	}

	function getDeath() {
		$qr = $this->db->query("SELECT * FROM overlijden WHERE person=" . $this->id);
		if ($qr->num_rows == 1) {
			$death = $qr->fetch_assoc();
			$this->deathDate = ($death['date'] ? $death['date'] : ($death['year'] ? intval($death['year']) : "??"));
			if ($death['url']) {
				$this->deathSource = $death['url'];
			}
			if ($death['place']) {
				$this->deathPlace = $death['place'];
			}
		}

		$this->DEATH = true;
	}

	function getChildren() {
		global $dateYearOrder;

		$qr = $this->db->query("SELECT * FROM geboorte WHERE father=" . $this->id . $dateYearOrder);
		while ( ($row = $qr->fetch_assoc()) != false ) {
			$item = array('child' => new Person($row['child'], $this->db));
			if ($row['mother']) { $item['partner'] = new Person($row['mother'], $this->db); }
			if ($row['url']) { $item['source'] = $row['url']; }
			$this->children[] = $item;
		}

		$qr = $this->db->query("SELECT * FROM geboorte WHERE mother=" . $this->id . $dateYearOrder);
		while ( ($row = $qr->fetch_assoc()) != false ) {
			$item = array('child' => new Person($row['child'], $this->db));
			if ($row['father']) { $item['partner'] = new Person($row['father'], $this->db); }
			if ($row['url']) { $item['source'] = $row['url']; }
			$this->children[] = $item;
		}

		$this->CHILDREN = true;
	}

	function getMarriage() {
		global $dateYearOrder;
		
		$qr = $this->db->query("SELECT * FROM huwelijk WHERE groom=" . $this->id . " OR bride=" . $this->id . $dateYearOrder);
		while ( ($row = $qr->fetch_assoc()) != false) {
			$spouse = $this->id == $row['groom'] ? $row['bride'] : $row['groom'];
			$marriage = array(
				'partner' => new Person($spouse, $this->db),
				'date' => $row['date'] ? $row['date'] : ($row['year'] ? intval($row['year']) : "??"));
			if ($row['url']) { $marriage['source'] = $row['url']; }
			if ($row['place']) { $marriage['place'] = $row['place']; }
			$this->marriage[] = $marriage;
		}

		$this->MARRIAGE = true;
	}

	function getSiblings() {
		global $dateYearOrder;

		if (!$this->BIRTH) { $this->getBirth(); }
		$mother = $this->mother->id ? $this->mother->id : -1;
		$father = $this->father->id ? $this->father->id : -1;
		$qr = $this->db->query("SELECT * FROM geboorte WHERE NOT(child=" . $this->id . ") AND (mother=".$mother." OR father=".$father.")" . $dateYearOrder);
		while ( ($row = $qr->fetch_assoc()) != false) {
			$item = array('sibling' => new Person($row['child'], $this->db));
			$item['full'] = ($mother == -1 || $row['mother'] == $mother) && ($father == -1 || $row['father'] == $father);
			if ($row['url']) { $item['source'] = $row['url']; }
			$this->siblings[] = $item;
		}
	}

}
?>