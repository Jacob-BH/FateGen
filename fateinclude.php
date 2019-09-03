<?php
	//Connect to the SQL server.
	require_once 'login.php';
	$conn = new mysqli($hn, $un, $pw, "Fate");
	
	//Create an array of all the skill names.
	$skills = array('athletics','burglary','contacts','crafts','deceive','drive','empathy','fight','investigate','lore','notice','physique','provoke','rapport','resources','shoot','stealth','will');
	
	//Given a number, returns the correct Fate Core ladder adjective.
	function adjective ( $rating ) {
		switch ($rating) {
			case 8:
				return "Legendary (+8)";
				break;
			case 7:
				return "Epic (+7)";
				break;
			case 6:
				return "Fantastic (+6)";
				break;
			case 5:
				return "Superb (+5)";
				break;
			case 4:
				return "Great (+4)";
				break;
			case 3:
				return "Good (+3)";
				break;
			case 2:
				return "Fair (+2)";
				break;
			case 1:
				return "Average (+1)";
				break;
			case 0:
				return "Mediocre (+0)";
				break;
			case -1:
				return "Poor (-1)";
				break;
			case -2:
				return "Terrible (-2)";
				break;
		}
		if ($rating > 8) {
			return "Legendary+" . ($rating-8) . " (+" . $rating . ")";
		}
		
		else if ($rating < -2) {
			return "Terrible" . ($rating+2) . " (" . $rating . ")";
		}
		else {
			return "UNKNOWN (" . $rating . ")";
		}
		
	}
	
	if ($conn->connect_error) die("Fatal Error");
	
	$types = array('pc','npc','loc','corp');
	
	//Automaticaly fetch all arrays from the unique characters in the database, broken up into the different types (pc, npc, etc)
	foreach($types as $type) {
		$result = $conn->query("SELECT DISTINCT name FROM characters WHERE TYPE='$type' ORDER BY name ASC");
		if (!$result) {die("Could not find $type"."s.");}
		for ($j = 0; $j < $result->num_rows; $j++) {
			$row = $result->fetch_array(MYSQLI_ASSOC);
			${$type."s"}[] = $row['name'];
		}
		$result->close();
	}
	
	function typeSwitch ($type) {
		//Given a type code (pc, npc, loc) returns that type's name.
		switch ($type) {
			case 'pc': {
				return "Player Character";
				break;
			}
			case 'npc': {
				return "NPC";
				break;
			}
			case 'loc': {
				return "Place";
				break;
			}
			case 'corp': {
				return "Megacorp";
				break;
			}
			default: {
				return $type;
				break;
			}
		}
	}
	
	function typeSwitchArt ($type) {
		//Given a type code, returns that type's name, with the correct a/an article.
		switch ($type) {
			case 'npc': { //Currently, NPC is the only type which needs 'an'.
				$string = "an ";
				break;
			}
			default: { //'A' is the default.
				$string = "a ";
				break;
			}
		}
		$string .= typeSwitch($type);
		return $string;
	}
?>