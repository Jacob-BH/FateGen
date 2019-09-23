<?php
	require_once 'fateinclude.php';
?>
<head>
	<title>FateGen: Character Sheet List</title>
	<style>
		.char {
			border: 1px solid black;
			margin-bottom: 1em;
		}
		
		.char h1 { padding-left: 2%;}
		
		.char table { border: 1px dashed black; }
		
		.hidden table { display: none;}
	</style>
</head>
<?php
	include 'fate_npcselector.php';
	
	echo "<p><span id='showhideall'>Show/Hide all</span></p>\n\n";
	
	foreach ($pcs as $name) {
		charDisplay($name,$conn);
	}
	
	if (isset($_POST['getnpcs'])) {
		$getnpcs = $_POST['getnpcs'];
		
		echo "<h1>NPCs</h1>\n";
		foreach($getnpcs as $name) {
			charDisplay($name,$conn);
		}
	}
	
	function charDisplay($name,$conn) {
		//Takes a character name and database object and displays their character sheet.
		//Characters are hidden by default.
		echo "<div class='char hidden' id='$name'>\n";
		
		echo "\t<h1><span class='charname'>$name</span></h1>\n";
		
		// Load a query for each table. CHARACTERS and SKILLS will each be a single row, but ASPECTS and STUNTS will likely be more.
		foreach(array("characters","skills","aspects","stunts") as $table) {
			$temp = "query_".$table;
			$query = "SELECT * FROM $table WHERE name='$name'";
			// If it's aspects or stunts, make sure it's ordered by the number.
			if ( ($table == "aspects") || ($table == "stunts") ) { $query .= " ORDER BY num"; }
			
			// Run the query.
			$$temp = $conn->query($query);
			
			// If it's characters or skills, turn it straight into an associative array.
			if ( ($table == "characters") || ($table == "skills") ) { $$temp = $$temp->fetch_array(MYSQLI_ASSOC); }
		}
		
		echo "\t<table>\n";
		echo "\t<tr><th>Aspects</th><th>Skills</th><th>Stunts</th></tr>\n\t<tr>\n";
		//Aspects section
		echo "\t\t<td class='aspects'>\n"; {
			for ($i=0; $i < $query_aspects->num_rows; $i++) {
				$query_aspects->data_seek($i);
				$row = $query_aspects->fetch_array(MYSQLI_ASSOC);
				
				echo "\t\t\t<p>";
				switch($row['num']) { //If it's aspect 1 or 2, give it a special title.
					case 1:
						echo "<strong>High Concept:</strong> ";
						break;
					case 2:
						echo "<strong>Trouble:</strong> ";
						break;
				}
				echo $row['aspect'];
				echo "</p>\n";
			}
			$query_aspects->close();
			echo "\t\t</td>\n\n";
		}
		
		//Skills section
		echo "\t\t<td class='skills'>\n"; {
			$cap = $query_characters['cap'];
			
			// Loop from the cap down to 1.
			for ($i = $cap; $i >= 1; $i--) {
				echo "\t\t\t<p>";
				//Start with the rating's adjective.
				echo adjective($i) . " ";
				
				//Get the number of slots at that rating.
				$numslots = $query_characters["slots$i"];
				
				//Filter the array to find just those skills that are at this rating.
				$skillsatrating = array_filter($query_skills, function($v) use ($i) {
					return $v == $i;
				}, 0);
				
				//Count the number of those skills.
				$numskills = count($skillsatrating);
				
				//Show the ratio.
				echo "($numskills/$numslots): ";
				
				//Make a new, numerically-indexed, array of just the capitalised skill names.
				$showatrating= array();
				foreach ($skillsatrating as $skill => $rating) {
					$showatrating[] = ucfirst($skill);
				}
				
				//If needed, pad the numerical array with blank spaces.
				if ($numskills < $numslots) {
					for ($j = $numskills; $j < $numslots; $j++) {
						$showatrating[] = '_';
					}
				}
				
				echo itemise($showatrating);
				
				echo "</p>\n";
			}
			
			//One more for the skills still at Mediocre.
			
			echo "\t\t\t<p>" . adjective(0) . " (";
			
			$skillsatrating = array_filter($query_skills,function($v,$k) {
				//Is this just the character name? Skip it.
				if ($k == 'name') { return false; }
				//Otherwise, check if the rating is zero.
				else { return $v == 0; }
			},ARRAY_FILTER_USE_BOTH);
			
			//Convert into a numerical array.
			$skillsatrating = array_keys($skillsatrating);
			//Capitalise them all.
			for ($i=0; $i < count($skillsatrating); $i++) {
				$skillsatrating[$i] = ucfirst($skillsatrating[$i]);
			}
			echo count($skillsatrating) . "): ";
			echo itemise($skillsatrating);
			echo "</p>\n\t\t</td>\n\n";
		}
		
		//Stunts section
		echo "\t\t<td class='stunts'>\n"; {
			for ($i=0; $i < $query_stunts->num_rows; $i++) {
				$query_stunts->data_seek($i);
				$row = $query_stunts->fetch_array(MYSQLI_ASSOC);
				
				echo "\t\t\t<p><strong>" . $row['stunt'] . ":</strong> ";
				echo $row['description'];
				echo "<p>\n";
			}
			$query_stunts->close();
			echo "\t\t</td>\n";
		}
		echo "\t</tr>\n</table>\n";
		
		echo "</div>\n\n";
	}
	
	function itemise($array) {
		//Takes an array and returns a string itemising the elements. e.g. array(1,2,3) will be returned as "1, 2, and 3."
		
		$string = "";
		
		for ($i = 0; $i < count($array); $i++) {
			//Show the element.
			$string .= $array[$i];
			
			//If it's the last one in the array, end with a full stop.
			if ($i == count($array)-1) { $string .= "."; }
			
			//Otherwise, is it only two elements long? Add an 'and'.
			elseif (count($array) == 2) { $string .= " and "; }
			
			//Otherwise, is it before the penultimate? Add a comma and space.
			elseif ($i < count($array)-2) { $string .= ", "; }
			
			//Otherwise, add a comma, space, and 'and'.
			else { $string .= ", and "; }
		}
		
		return $string;
	}
?>
<script type='text/javascript' src='fatesheets.js'></script>
