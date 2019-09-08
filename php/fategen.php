<head>
	<title>Fate Character Creation Tool</title>
	<style>
		input[type='number'] {width: 3em;}
		
		.stuntname, .pc {font-weight: bold;}
		
		.bottom {vertical-align: bottom;}
		
		.npc { font-style: italic; }
		
		.loc { color: green; }
		
		.corp { color: gold; }
		
		.repeatskill { background: red; }
		
		.sheet\.skillsp { 
			margin: 0.5em;
			padding: 0.2em;
			border: 1px solid black;
		}
		
		.skillspan {
			border: 1px solid black;
			margin: 0.1em;
			padding: 0.2em;
			padding-left: 0.5em;
		}
		
		.hidden {
			display:none;
		}
	</style>
</head>

<?php
	{ //General setup
		require_once 'fateinclude.php';
		
		//If we've POSTed a name -- whether by loading a character or saving an already-loaded character -- transfer to $name.
		if (isset($_POST['name'])) { $name = $_POST['name']; }
		
		echo "<h1>Fate Character Creation Tool</h1>\n";
		
		//Skill datalist for <select> input
		$skillsselect = array("_"); //Add the blank placeholder "_"
		$skillsselect = array_merge($skillsselect,$skills);
		
		//Skill datalist for datalist input
		echo "<datalist id='skillslist'>
			<option value='_'>";
			foreach($skills as $skill) {
				echo "<option value='$skill'>\n";
			}
		echo "</datalist>";
	}
	
	if ($_POST['function']=='backup') { // This part runs if you've backed up a character.
		echo "<p>Backup queries for $name:</p>";
		echo "<pre>";
		
		//This section uses numerically-indexed arrays so that the keys can more easily be used in numerical tests.
		
		foreach(array("characters","aspects","skills","stunts") as $table) {
			$query = $conn->query("SELECT * FROM $table WHERE name='$name'");
			$rows = $query->num_rows;
			
			for ($i=0; $i<$rows; $i++) {
				$backup = "INSERT INTO $table VALUES (";
				$query->data_seek($i);
				$row = $query->fetch_array(MYSQLI_NUM);
				foreach ($row as $j => $value) {
					$backup .= "'". htmlentities($value,ENT_QUOTES) ."'";
					//If there is another column after this one, insert a comma and space.
					if ($j+1 < count($row)) { $backup .= ", "; }
				}
				//Close off the query.
				$backup .= ");\n";
				echo $backup;
			}
		}
		
		
		echo "</pre>";
	}
	
	if ($_POST['function']=='milestone') { // This part runs if you've submitted a milestone.
		//Grab the numbers shared by the PCs. All PCs advance together, sharing milestones.
		$query = $conn->query("SELECT DISTINCT skillpoints, refresh, cap FROM characters WHERE type='pc'");
		
		//Set an array with the 'old' numbers.
		$old = $query->fetch_array(MYSQLI_ASSOC);
		$query->close();
		
		foreach(array("skillpoints","refresh","cap") as $trait) {
			//Only if the POSTed number is actually different from the old one...
			if ($old[$trait] != $_POST[$trait]) {
				$num = $_POST[$trait];
				//...run the queries to update all PCs...
				$conn->query("UPDATE characters SET $trait=$num WHERE type='pc'");
				///...and the character table's default.
				$conn->query("ALTER TABLE characters ALTER $trait SET DEFAULT $num");
			}
		}
	}
	
	if ($_POST['function']=='save') { // This part runs if you've submitted to Save a character.
		//Extract the POSTed information for ease of handling.
		extract($_POST);
		
		//Delete the old versions.
		foreach(array("characters","aspects","skills","stunts") as $table) {
			$conn->query("DELETE from $table WHERE name='$name'");
		}
		
		//Add them to the character table, with the posted type, number of aspects, skill cap, skill points, free stunts, and refresh value.
		$stmt = $conn->prepare("INSERT INTO characters (name,type,numaspects,cap,skillpoints,freestunts,refresh,init_physical_bonus,init_social_bonus) VALUES ('$name',?,?,?,?,?,?,?,?)");
		$stmt->bind_param('siiiiiii',$type,$numaspects,$cap,$skillpoints,$freestunts,$refresh,$init_physical_bonus,$init_social_bonus);
		$stmt->execute();
		
		//Add them to the skills table. This fills them out with a default 0 in each skill.
		$conn->query("INSERT INTO skills (name) VALUES ('$name')");
		
		$stmt = $conn->prepare("INSERT INTO aspects (name,num,aspect) VALUES ('$name',?,?)");
		$stmt->bind_param('is',$num,$aspect);
		//Loops through each aspect textarea.
		for ($i=0; $i < count($aspects); $i++) {
			$aspect = htmlentities($aspects[$i],ENT_QUOTES);
			//Aspects start at one while the array starts at 0. This corrects for that so that high concept is still aspect num 1.
			$num = $i+1;
			//Only execute if the aspect string is non-empty. Otherwise, you'll end up with empty-string aspects, which waste database space.
			if ($aspect != '') {$stmt->execute();}
		}
		
		//Create an associative array of all skill ratings, initialised to 0.
		$skillratings = array();
		foreach($skills as $skill) {
			$skillratings[$skill] = 0;
		}
		
		$queryslots = "UPDATE characters SET ";
		//Loop from the skill cap down, setting how many skill slots the character has.
		for ($i=$cap; $i > 0; $i--) {
			$queryslots .= "slots$i=" . ${slots.$i};
			if ($i>1) {$queryslots .= ", ";}
			
			//Check if there are any skills set at that rating, too.
			if (isset(${skills.$i})) {
				foreach(${skills.$i} as $skill) {
					//'_' is just a placeholder for an empty skill slot. Ignore those. Otherwise, update the $skillratings array with the character's actual skill rating.
					if ($skill != '_') {$skillratings[$skill] = $i;}
				}
			}
		}
		//Finish the 'set skill slots' query.
		$queryslots .= " WHERE name='$name'";
		$queryskill = "UPDATE skills SET ";
		//Loop through all skills, writing out the query.
		for ($i = 0; $i < count($skills); $i++) {
			$skill = $skills[$i];
			$queryskill .= $skill . "=" . $skillratings[$skill];
			//More skills to go after this; add a comma.
			if ($i+1 < count($skills)) {$queryskill .= ", ";}
		}
		//Finish off the skill ratings query.
		$queryskill .= " WHERE name = '$name'";
		$conn->query($queryslots);
		$conn->query($queryskill);
		
		$stmt = $conn->prepare("INSERT INTO stunts (name,num,stunt,description) VALUES ('$name',?,?,?)");
		$stmt->bind_param('iss',$num,$stunt,$desc);
		
		//Loop through all the stunts.
		for ($i=0; $i < count($stunts); $i++) {
			$num = $i+1;
			$stunt = $stunts[$i];
			$desc = $stuntdescs[$i];
			if ($stunt != '') {$stmt->execute();}
		}
	}
	
	if ($_POST['function']=='create') { // This part runs if you've Created a character.
		//Set it into $name so that the character-generation table will work with it.
		$name = htmlentities($_POST['newname'],ENT_QUOTES);
		
		$freename= true;
		
		foreach($types as $array) {
			//Add 's' so you can iterate through the arrays.
			$array .= 's';
			
			//Check whether that name already exists in any of the arrays.
			if (isset($$array)) {
				$ind = array_search($name,$$array);
				if (!($ind === false)) { //Precise match: if it returns false, not returns 0.
					$freename = false;
					echo "<p>$name is already the name of ";
					echo typeSwitchArt(substr($array,0,strlen($array)-1));
					echo ".</p>\n";
					//Unset name so it doesn't try to load the wrong character.
					unset($name);
					break;
				}
			}
		}
		
		if ($freename) { //The name is free and it is safe to proceed with creation.
			$type = $_POST['type'];
			echo "<p>Creating $name as ". typeSwitchArt($type) .".</p>";
			$conn->query("INSERT INTO skills (name) VALUES ('$name')");
			$conn->query("INSERT INTO characters (name,type) VALUES ('$name','$type')");
			
			//Add the name to the array for that type, with an alphabetic sort afterwards.
			$array = $type . "s";
			$$array[] = $name;
			sort($$array);
		}
		
	}

	if ($_POST['function']=='delete') { //This part runs if you've deleted a character.
		echo "<p>Deleting $name.</p>\n";
		
		//Grab the character's type.
		$type = $conn->query("SELECT type FROM characters WHERE name='$name'");
		$type = $type->fetch_array(MYSQLI_NUM);
		$type = $type[0];
		
		//Remove $name from their type array.
		$array = $type . "s";
		//Find the index.
		$index = array_search($name,$$array);
		//Splice it out.
		array_splice($$array,$index,1);
		
		foreach(array('aspects','characters','skills','stunts') as $table) {
			$conn->query("DELETE FROM $table WHERE name='$name'");
		}
		
		//Unset $name so it doesn't try to load the now-deleted character.
		unset($name);
	}
	
	if ($_POST['function']=='rename') { //This part runs if you've submitted a rename.
		$newname = htmlentities($_POST['newname']);
		
		//Update the array for the character's type.
		//First, find that type.
		$result = $conn->query("SELECT type FROM characters WHERE name='$name'");
		$type = $result->fetch_array(MYSQLI_ASSOC);
		$type = $type['type'];
		$result->close();
		
		$array = $type . "s";
		//Find the index of the old name.
		$index = array_search($name,$$array);
		//Replace that index with the new name.
		$$array[$index]= $newname;
		//Sort the array.
		sort($$array);
		
		foreach (array("aspects","characters","skills","stunts") as $table) {
			$conn->query("UPDATE $table SET name='$newname' WHERE name='$name'");
		}
		//Set $name to the new name so that the table will work correctly.
		$name = $newname;
	}

	if (isset($name)) { // This part runs if you've loaded a character by any means, and provides the character generation form.
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
		
		echo "<form action='fategen.php' method='post' id='save'>\n"; { //Character creation form
		echo "<h2>$name (";
		
		// Save the character's type.
		$type = $query_characters['type'];
		
		// selector for character type.
		echo "<select name='type' size='1' id='typeselect'>\n";
			foreach($types as $itype) {
				echo "<option value='$itype'";
				if ($itype == $type) { echo " selected='selected'"; }
				echo ">". typeSwitch($itype) ."</option>\n";
			}
		echo "</select>\n";
		
		echo ")</h2>\n";
		
		// save the name as part of this form, too.
		echo "<input type='hidden' name='name' value='$name'>\n";
		echo "<table>\n<tr>\n";
		
		echo "<td id='aspects'>"; {//Aspects cell
			$numaspects= $query_characters['numaspects'];
			
			echo "<p><strong>Aspects</strong></p>\n";
			
			for ($a= 0; $a < $numaspects; $a++) {
				$query_aspects->data_seek($a);
				$aspect = $query_aspects->fetch_array(MYSQLI_ASSOC)['aspect'];
				echo "<div class='aspectdiv' id='aspectdiv.". ($a) ."'>";
				
				switch ($type) { // Provide special titles for some aspects.
					case 'pc':
					case 'npc': { //PCs and NPCs have the High Concept and Trouble headings.
						if ($a == 0) echo "<strong>High Concept:</strong><br>\n"; 
						if ($a == 1) echo "<strong>Trouble:</strong><br>\n";
						if ($a == 2) echo "Other Aspects:<br>\n";
						break;
					}
					case 'corp': { //Megacorporations have the Slogan heading for the first aspect.
						if ($a == 0) echo "<strong>Slogan:</strong><br>\n"; 
						break;
					}
				}
				echo "\t<textarea name='aspects[]' id='aspect.". ($a-1) ."' class='sheet.aspect' cols='48'>$aspect</textarea><br><br></div>\n";
			}		
			echo "</td>\n";
		}
		
		echo "<td id='skills'>"; { //Skills cell
			echo "<p><strong>Skills</strong></p>\n";
			
			// Get the skill cap.
			$cap = $query_characters['cap'];
			
			$skillspent= 0;
			
			//Make a div which contains only the skill paragraphs (and not the <strong> paragraph above), for ease of prepending new ratings.
			echo "<div id='skillps'>";
			
			for ($i = $cap; $i > 0; $i--) { // For each rating from cap to Average...
				// ...get the number of slots.
				$slotsatrating = $query_characters["slots$i"];
				$skillspent += $slotsatrating * $i; // Add the cost to the 'skillsspent' attribute.
				
				// Filter out just the skills at that rating.
				$skillsatrating_assoc = array_filter($query_skills,function($v) use($i) {return $v == $i;},0);
				$skillsatrating = array();
				foreach ($skillsatrating_assoc as $skill => $rating) { // Put them into a numerically-indexed array.
					$skillsatrating[] = ucfirst($skill);
				}
				$numskillsatrating = count($skillsatrating);
				
				echo "\t<p id='skills.$i' class='sheet.skillsp'>". adjective($i) ." (";
				echo "<input type='number' cols='1' name='slots$i' id='slots.$i' min='0' value='$slotsatrating'>):\n"; //How many slots at that rating?
				
				echo "<span class='skillboxes' id='skillboxes.$i'>"; //Wrap just the skillboxes in a span.
				for ($j=0; $j < $slotsatrating; $j++) {
					if ($j <= $numskillsatrating) {$thisskill = $skillsatrating[$j];}
					else {$thisskill = "_";} //Fill out 'unfilled slots' with the placeholder _.
					if (($j) % 3 == 0) { //Add two line breaks before every fourth dropdown.
						echo "<br><br>";
					}
					echo "<span class='skillspan' id='skillspan.$i.$j' draggable='true'>"; //Wrap each dropdown in a span so it can be dragged.
					echo "\t<select name='skills$i"."[]' id='skills.$i.$j' class='sheet.skillselect sheet.skillselect.$i' size='1'>\n";
					foreach ($skillsselect as $skill) { //Dropdown options.
						if (strtolower($thisskill) == $skill) {echo "\t\t<option value='$skill' selected='selected'>$skill</option>\n";} //Automatically select the correct skill from the dropdown.
						else {echo "\t\t<option value='$skill'>$skill</option>\n";}
					}
					echo "\t</select>\n";
					echo "</span>\n";
				}
				echo "</span></p>\n\n";
			}
			echo "</div></td>\n";
		}
		
		echo "<td id='stunts'>\n"; { //Stunts cell
			echo "<p><strong>Stunts</strong></p>";
			$numstunts = $query_stunts->num_rows;
			
			for ($i=0; $i < $numstunts; $i++) {
				$stunt = $query_stunts->fetch_array(MYSQLI_ASSOC);
				
				echo "\t<p id='stunt.$i' class='sheet.stunt'><input class='stuntname' type='text' size='48' maxlength='48' name='stunts[]' value='". htmlentities($stunt['stunt'],ENT_QUOTES) ."'><br>\n";
				echo "\t\t<textarea name='stuntdescs[]' cols='50' rows='7'>". $stunt['description'] ."</textarea>";
				echo "</p>\n";
			}
			echo "</td></tr>\n<tr>";
		}
			
			echo "<td class='bottom'>"; { //Aspect num cell
				echo "<p><em>Num aspects:</em> ";
				
				echo "<input type='number' name='numaspects' min='1' id='sheet.numaspects' value='$numaspects'></p>\n";
				
				echo "</td>\n";
			}
			
			echo "<td class='bottom'>"; { //Skill points cell
				echo "<p><em>Skill points:</em> ";
				$skillpoints = $query_characters['skillpoints'];
				$skillleft = $skillpoints-$skillspent;
				echo "<span id='skillleft'>$skillleft</span>";
				echo "/<input type='number' name='skillpoints' min='0' id='sheet.skillpoints' value='$skillpoints'></p>\n";
				
				echo "<p><em>Skill cap:</em> "; //skill cap.
				echo "<input type='number' name='cap' min='1' max='8' id='sheet.cap' value='$cap'></p>\n";
				
				//Inputs for physical and social initiative boosts.
				foreach(array("physical","social") as $conflict) {
					$column= "init_$conflict"."_bonus";
					
					$bonus = $query_characters[$column];
					
					echo "<p>";
					echo ucfirst($conflict);
					echo " initiative bonus: ";
					echo "<input type='number' name='$column' min='0' value='$bonus'>";
					
					echo "</p>\n";
				}
				
				echo "</td>\n";
			}
			
			echo "<td class='bottom'>"; { // Refresh cell
				$freestunts = $query_characters['freestunts'];
				$refresh = $query_characters['refresh'];
				$refreshleft = $refresh;
			
				echo "<p><em>Stunts:</em> ";
				echo "<input type='number' name='numstunts' min='0' id='sheet.numstunts' value='". $numstunts ."'></p>\n";
				echo "<p><em>Free stunts left:</em> <span id='freestuntsleft'>". max(0,$freestunts-$numstunts) ."</span>/";
				echo "<input type='number' name='freestunts' min='0' value='$freestunts' id='sheet.freestunts'></p>\n";
				
				if ($numstunts > $freestunts) {
					//If you have more stunts than your free allowance...
					$refreshleft -= ($numstunts - $freestunts);
					//...subtract the rest from refresh.
				}
				
				$refreshleft = min($refresh -$numstunts +$freestunts,$refresh);
				
				echo "<p><em>Refresh:</em> <span id='refreshleft'>$refreshleft</span>/";
				echo "<input type='number' name='refresh' min='0' value='$refresh' id='sheet.refresh'></p>\n";
				
				echo "</td>\n";
			}
		}
		
		
		echo "</tr></table>\n";
		
		// A paragraph showing the skills not yet selected.
		echo "<p id='unused_skills_p'>" . adjective(0) . " skills ";
		
		// Filter them out from the skills array.
		$skillsatrating_assoc = array_filter($query_skills,function($v,$k) {
			// Is this just the spot dedicated to character name?
			if ($k == 'name') { return false; }
			// Otherwise, check whether the rating is equal to zero.
			else { return $v == 0; }
		},ARRAY_FILTER_USE_BOTH);
		
		//Show the number
		echo "(<span id='unused_num'>" . count($skillsatrating_assoc) . "</span>)";
		
		echo ": <span id='unused_skills'>\n";
		
		// Initialise the iteration counter.
		$i = 0;
		
		foreach($skillsatrating_assoc as $skill => $rating) {
			if ($i > 0) { echo ","; }
			echo "<span class='unused_skill'>$skill</span>";
			
			$i++;
		}
		
		// We don't need $i anymore. Unset it.
		unset($i);
		
		echo "</span></p>\n";
		
		echo "<input type='hidden' name='function' value='save'>";
		echo "<input type='submit' value='Save'>\n";
		echo "</form><br><br>\n";
	}
	
	//
	//Combined form
	//
	echo "<form action='fategen.php' method='post' id='combinedform'>\n";
	echo "<table>\n";
	
	// Function dropdown.
	echo "\t<select name='function' id='functionselect' size=1>\n";
		echo "\t\t<option value='load' selected='selected'>Load</option>\n";
		echo "\t\t<option value='backup'>Backup</option>\n";
		echo "\t\t<option value='create'>Create</option>\n";
		echo "\t\t<option value='delete'>Delete</option>\n";
		echo "\t\t<option value='rename'>Rename</option>\n";
	echo "\t</select>\n";
	
	//Character names dropdown.
	echo "\t<select name='name' id='input_name' size=1>\n";
		foreach($types as $itype) {
			$array = $itype .'s';
			foreach ($$array as $iname) {
				echo "\t\t<option class='$itype' value='$iname'";
				//If that name has been loaded before, keep it selected.
				if ($name == $iname) {echo " selected='selected'";}
				echo ">$iname</option>\n";
			}
		}
	echo "\t</select>\n";
	
	//New name input. Hidden by default.
	echo "\t<input type='text' name='newname' id='input_newname' maxlength='32' size='32' placeholder='Name' class='hidden'>\n";
	
	//Character type selector. Hidden by default.
	echo "\t<select name='type' id='input_type' size='1' class='hidden'>\n";
		foreach($types as $itype) {
			echo "\t\t<option value='$itype'";
			//If it's player character, select this by default.
			if ($itype == 'pc') {echo " selected='selected'";}
			echo ">". typeSwitch($itype) ."</option>\n";
		}
	echo "\t</select>\n";
	
	//Submit button. Put a BR in front so the other elements are less likely to bully it out of place.
	echo "<br>\n";
	echo "\t<input type='submit' value='Execute'>\n";
	echo "</form>\n";
	
	echo "<br><br>";
	
	echo "<form action='fategen.php' method='post'>\n"; { //Milestone form
		echo "<table><td>";
		$query = $conn->query("SELECT DISTINCT skillpoints, refresh, cap FROM characters WHERE type='pc'");
		$result = $query->fetch_array(MYSQLI_ASSOC);
		$query->close();
		extract($result);
		
		echo "<p>Skill points: <input type='number' name='skillpoints' value='$skillpoints'></p>\n";
		echo "<p>Refresh: <input type='number' name='refresh' value='$refresh'></p>\n";
		echo "<p>Skill cap: <input type='number' name='cap' min='1' max='8' value='$cap'></p>\n";
		echo "<input type='hidden' name='function' value='milestone'>\n";
		if (isset($name)) {echo "<input type='hidden' name='name' value='$name'>\n";}
		echo "<input type='submit' value='Milestone'>\n";
		echo "</td></table></form>\n\n";
	}
	
	
	
	$conn->close();
?>
<script type="text/javascript" src="fategen.js"></script>
