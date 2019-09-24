<?php
	//Connect to the SQL server.
	require_once 'login.php';
	$conn = new mysqli($hn, $un, $pw, "Fate");
	
	//Create an array of all the skill names.
	$skills = array();
	$skilldesc = $conn->query("DESCRIBE skills");
	//Start at index 1 to skip the character name column.
	for ($i = 1; $i < $skilldesc->num_rows; $i++) {
		$skilldesc->data_seek($i);
		$row = $skilldesc->fetch_array(MYSQLI_NUM);
		$skills[] = $row[0];
	}
	$skilldesc->close();
	
	//Do the same for JavaScript.
?>

<script>
	let skillNames= <?php echo json_encode($skills); ?>;
</script>
	
	
<?php
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
			case -3:
				return "Catastrophic (-3)";
				break;
			case -4:
				return "Horrifying (-4)";
				break;
		}
		if ($rating > 8) {
			return "Beyond Legendary (+$rating)";
		}
		
		else if ($rating < -4) {
			return "Beyond Horrifying ($rating)";
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
	
	//Navigation Header
	echo "\n<div id='nav'>\n";
	foreach(array("fate","fateaspects","fategen","fatesheets","fateinit") as $link) {
		echo "<a href='$link.php'>$link</a>\n";
	}
	echo "\n</div>\n";
	
	// Import the external style sheet.
	echo "<style>@import 'fate.css';</style>\n\n";
	
	// Import the external javascript.
	echo "<script type='text/javascript' src='fate.js'></script>\n\n";
?>