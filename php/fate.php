<head>
	<title>Eye in the Sky</title>
	<style>
		.pc {
			border: 1px solid black;
			margin-bottom: 1em;
		}
		
		.pc h1, .pc h2 { padding-left: 2%; }
		
		.hidden h2, .hidden p { /* Only hide children elements, not the header; otherwise, you won't be able to click and un-hide it.*/
			display: none;
		}
	</style>
</head>

<?php
	require_once 'fateinclude.php';
	
	echo "<p><span onclick='toggleAll()'>Show/Hide all</span></p>";
	
	foreach ($pcs as $pc) {
		//Hidden by default.
		echo "<div class='pc hidden' id='$pc'>\n";
			echo "<h1><span onclick='showHide(\"$pc\");'>$pc</span></h1>\n";
			
			echo "<h2>Aspects</h2>\n"; {
				$result = $conn->query("SELECT aspect,num FROM aspects WHERE name='$pc' ORDER BY num");
				if (!$result) {die("No aspects found");}
				for ($j = 0; $j < $result->num_rows; $j++) {
					echo "<p>";
					$row = $result->fetch_array(MYSQLI_ASSOC);
					
					switch ($row['num']) { //If it's aspect 1 or 2, give it the special title.
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
				$result->close();
				echo "\n";
			}
			
			echo "<h2>Skills</h2>\n"; {
				$query = $conn->query("SELECT cap FROM characters WHERE name='$pc'");
				$cap = $query->fetch_array()[0]; // Sets $cap to the character's skill cap.
				$query->close();
				
				$query = $conn->query("SELECT * FROM skills WHERE name='$pc'");
				$pcskills = $query->fetch_array(MYSQLI_ASSOC);
				$query->close();
				
				for ($i = $cap; $i >= 1; $i--) {
					//Loop from the cap down to 1.
					echo "<p>";
					//Start with the rating's adjective.
					echo adjective($i);
					
					//Get the number of slots at that rating.
					$query= $conn->query("SELECT slots$i FROM characters WHERE name='$pc'");
					$numslots = $query->fetch_array()[0];
					$query->close();
					
					//Filter the array to find just those skills that are at this rating.
					$skillsatrating = array_filter($pcskills,function($v) use($i) {
						return $v == $i;
					}, 0);
					
					//Count the number of those skills.
					$numskills = count($skillsatrating);
					
					echo " ($numskills/$numslots): ";
					
					$skillsfetch = array();
					foreach ($skillsatrating as $skill => $rating) { // Sets the skills at that rating into a numerical array, capitalised.
						$skillsfetch[] = ucfirst($skill);
					}
					
					if ($numskills < $numslots) { // Pad the numerical array with blank spaces as needed.
						for ($j = $numskills; $j < $numslots; $j++) {
							$skillsfetch[] = "_";
						}
					}
					
					echo itemise($skillsfetch);
					echo "</p>\n";
					
				}
				
				//And one more <p> for Mediocre (+0), the skills which you don't have rated.
				echo "<p>" . adjective(0);
				$skillsatrating = array_filter($pcskills,function($v) {
					//No need to use use() on this one, as it will only need to be 0.
					return $v == 0;
				}, 0);
				
				$skillsfetch = array();
				foreach ($skillsatrating as $skill => $rating) {
					//The player's name is still an array member, and counts as '0', so this one needs to be skipped.
					if ($skill != "name") {
						$skillsfetch[] = ucfirst($skill);
					}
				}
				
				echo " (" . count($skillsfetch) . "): ";
				
				echo itemise($skillsfetch);
				
				echo "</p>\n";
			}
			
			echo "<h2>Stunts</h2>\n"; {
				$result = $conn->query("SELECT stunt,description FROM stunts WHERE name='$pc'");
				
				for ($j = 0; $j<$result->num_rows; $j++) {
					echo "<p><strong>";
					$row = $result->fetch_array(MYSQLI_ASSOC);
					echo $row['stunt'];
					echo ":</strong> ";
					
					echo $row['description'];
					echo "</p>\n";
				}
				$result->close();
				echo "\n";
			}
		echo "</div></div>\n\n";
	}
	
	echo "<h1>All Skills</h1>";
	foreach ($skills as $skill) { //One line for each skill.
		echo "<p>" . ucfirst($skill) . ": ";
		//Get just the player characters who have at least 1 in the skill, and sort them by descending order.
		$skillranks= $conn->query("SELECT name,$skill FROM skills NATURAL JOIN characters WHERE $skill > 0 AND type='pc' ORDER BY $skill DESC");
		$count = $skillranks->num_rows;
		
		for ($i = 0; $i < $count; $i++) {
			$row= $skillranks->fetch_array(MYSQLI_ASSOC);
			extract($row); //Extract the fetched array for ease of use inside a string.
			$rating = $$skill;
			echo "$name ($rating)";
			// If there are more loops after this one, give a comma and space; otherwise, end with a full stop.
			// This one doesn't use itemise() becaues I don't want the 'and'; just a list of comma-separated names.
			print $i+1 < $count? ", " : ".";
		}
		echo "</p>\n";
	}
	
	foreach (array('physical', 'social') as $init) { //Initiative for each conflict type
		echo "<h1>" . ucfirst($init) . " Initiative</h1>\n";
		
		//Populate hierarchy. This determines turn order, with ties broken by skills farther down the hierarchy.
		switch ($init) {
			case 'social':
				$hierarchy= array('empathy','deceive','rapport','provoke','will');
				break;
			case 'physical':
			default:
				$hierarchy= array('notice','athletics','combat','physique','will');
				break;
		}
		$query = "SELECT name,"; { // Build query
			for($i = 0; $i < count($hierarchy); $i++) { //Loop through the skill hierarchy to add to the query.
				$skill = $hierarchy[$i];
				if ($i == 0) {
					//The first skill is special, because it may take modifiers from stunts. Rename it to "<skill>_total" to reflect this.
					$hierarchy[0]= $skill . "_total";
					//Add the init_physical_bonus or init_social_bonus from the characters table.
					$query .= "${skill}+init_${init}_bonus AS ${hierarchy[0]}, ";
				}
				else if ($i == count($hierarchy)-1) { //The last one, no comma
					$query .= "$skill ";
				}
				else { //More to come, add a comma
					$query .= "$skill, ";
				}
			}
			$query .= "FROM characters NATURAL JOIN skills WHERE type='pc' ORDER BY ";
			//Loop through the hierarchy again for nested sorting.
			for($i = 0; $i < count($hierarchy); $i++) {
				$skill = $hierarchy[$i];
				$query .= "$skill DESC";
				
				if ($i < count($hierarchy)-1) { // Not done yet, so add a comma.
					$query .= ", ";
				}
			}
		}
		$init_order = $conn->query($query);
		$rows = $init_order->num_rows;
		
		{ echo "<table>"; // Table version
			echo "<th>PC</th>";
			foreach ($hierarchy as $i => $skill) {
				if ($i == 0) {
					echo "<th>";
					//Capitalise the skill. Also, for the first one only, change underscore to space.
					echo ucfirst(substr($skill,0,strpos($skill,"_"))) . " total</th>";
				}
				else {
					echo "<th>" . ucfirst($skill). "</th>";
				}
			}
			
			for ($i = 0; $i < $rows; $i++) {
				$row = $init_order->fetch_array(MYSQLI_ASSOC);
				echo "<tr>";
				echo "<td><strong>${row['name']}</strong></td>";
				
				$row= array_slice($row,1,NULL,TRUE);
				foreach($row as $skill => $rating) {
					echo "<td>" . adjective($rating) . "</td>";
				}
				
				echo "</tr>";
			}
			echo "</table>";
		}
		$init_order->data_seek(0);
		for ($i = 0; $i < $rows; $i++) { //Row by row version
			$row= $init_order->fetch_array(MYSQLI_ASSOC);
			echo "<p><strong>${row['name']}:</strong> ";
			
			$array = array();
			$j = 0;
			foreach($row as $skill => $rating) {
				//Skip the first one, because it's just the PC name again.
				if ($j==0) {
					$j++;
					continue;
				}
				if ($j==1) {
					//Capitalise the skill. Also, for the first one only, change underscore to space.
					$array[] = ucfirst(substr($skill,0,strpos($skill,"_"))) . " total ($rating)";
				}
				else {
					$array[] = ucfirst($skill)." ($rating)";
				}
				$j++;
			}
			echo itemise($array);
			echo "</p>";
		}
		echo "\n";
	}
	echo "\n";
	$conn->close(); // END OF SQL
	
	function itemise($array) {
		//Takes an array and returns a string itemising the elements. e.g. array(1,2,3) will be returned as "1, 2, and 3."
		
		$i = 0; // Initialise to 0.
		$limit = count($array);
		$string = "";
		
		foreach ($array as $item) {
			$string .= $item;
			
			if ($i == $limit - 2) { //Second-to-last
				//If there are at least 3 items, add an Oxford comma.
				if ($limit > 2) $string .= ",";
				$string .= " and ";
			}
			
			else if ($i < $limit - 2) $string .= ", ";
			
			//We're at the end; just give it a full stop.
			else $string .= ".";
			
			$i++;
		}
		
		return $string;
	}
?>
<script>
	function showHide(id) {
		document.getElementById(id).classList.toggle('hidden');
	}
	function toggleAll() {
		if (typeof shown == 'undefined') {
			this.shown= false;
		}
		
		if (shown) { //If they're visible already...
			let allPCs= document.getElementsByClassName('pc');
			for (let i=0; i<allPCs.length; i++) {
				if (!allPCs[i].classList.contains('hidden')) {
					allPCs[i].classList.add('hidden'); //Hide them all
				}
			}
			this.shown= false;
		}
		
		else { //If they're NOT all visible
			let allHidden= document.getElementsByClassName('pc');
			for (let i=0; i<allHidden.length; i++) {
				if (allHidden[i].classList.contains('hidden')) {
					allHidden[i].classList.remove('hidden'); //Reveal them all
				}
			}
			this.shown= true;
		}
	}
</script>