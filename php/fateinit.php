<?php
	require_once 'fateinclude.php';
?>
<head>
	<title>FateGen: Initiative</title>
	<style>
		.name_pc { font-weight: bold; }
		
		.npc { background: lightcoral; }
		
		table {width: 50%; }
		
		
	</style>
</head>
<?php

foreach ( array('physical','social') as $init) { //Initiative for each conflict type
	
	//Populate the skill hierarchy. This determines turn order, with tiebreakers farther down the hierarchy.
	switch ($init) {
		case 'social':
			$hierarchy = array('empathy','deceive','rapport','will');
			break;
		case 'physical':
		default:
			$hierarchy = array('notice','athletics','combat','will');
			break;
	}
	
	//Build the query.
	$query = "SELECT name, type, refresh, physique, ";
	
	$limit = count($hierarchy); 
	
	for ($i=0; $i<$limit; $i++) {
		//Loop through the skill hierarchy to add to the column names.
		$skill = $hierarchy[$i];
		
		//If it's the first skill, make sure to factor in the initiative bonus as well.
		if ($i == 0) {
			$hierarchy[0]= $skill . '_total';
			//Add the init_*_bonus from the characters table.
			$query .= "$skill+init_${init}_bonus as ${hierarchy[0]}, ";
		}
		
		elseif ($i < $limit-1) {
			//Need a comma.
			$query .= "$skill, ";
		}
		
		elseif ($i == $limit-1) {
			//The last one, no comma needed
			$query .= "$skill ";
		}
	}
	// Add the table and requirements.
	$query .= "FROM characters NATURAL JOIN skills WHERE type='pc'";
	
	// If getnpcs is set, add all those NPCs to the query as well.
	if (isset($_POST['getnpcs'])) {
		foreach ($_POST['getnpcs'] as $npc) {
			$query .= " OR name = '$npc'";
		}
	}
	
	// Add the ordering requirements.
	$query .= " ORDER BY ";
	//Loop through the hierarchy again for nested ordering.
	for ($i=0; $i<$limit; $i++) {
		$skill = $hierarchy[$i];
		$query .= "$skill DESC";
		
		if ($i < $limit-1) {
			//Not done yet; add a comma.
			$query .= ", ";
		}
	}
	echo "<h1>" . ucfirst($init) . "</h1>\n";
	
	$init_order = $conn->query($query);
	$rows = $init_order->num_rows;
	
	echo "\t<table>\n";
	echo "\t\t<th>Character</th>";
	foreach ($hierarchy as $i => $skill) {
		echo "<th>";
		if ($i == 0) {echo ucfirst(substr($skill,0,strpos($skill,'_'))) . " total";}
		else {echo ucfirst($skill);}
		echo "</th>\n";
	}
	
	//Loop through each combatant.
	for ($i=0; $i<$rows; $i++) {
		$init_order->data_seek($i);
		$row = $init_order->fetch_array(MYSQLI_ASSOC);
		echo "\t\t<tr class='${row['type']}'><td class='name_${row['type']}'>${row['name']}</td>\n";
		
		//Grab just the skill totals.
		$row = array_slice($row,4,NULL,TRUE);
		foreach($row as $skill => $rating) {
			echo "\t\t\t<td>" . adjective($rating) . "</td>\n";
		}
		echo "\t\t</tr>\n";
	}
	
	echo "\t</table><br>\n";
	
	//Table good for copy-pasting into the Conflict Sheet.
	echo "\t<table>\n";
	
	echo "\t<tr><th colspan='3'>" . strtoupper($init) . "</td></tr>";
	
	echo "\t\t<th>Character</th>\n\t\t<th>FP</th>\n\t\t<th>Stress</th>\n";
	
	for ($i=0; $i<$rows; $i++) {
		$init_order->data_seek($i);
		$row = $init_order->fetch_array(MYSQLI_ASSOC);
		$type = $row['type'];
		
		echo "\t\t<tr class='$type'>\n\t\t\t<td class='name_$type'>${row['name']}</td>\n";
		if ($type == 'pc') { $fp = $row['refresh']; }
		else { $fp = ""; }
		
		echo "\t\t\t<td>$fp</td>\n";
		
		switch ($init) {
			case 'social': {
				$stressskill = 'will';
				break;
			}
			default: {
				$stressskill = 'physique';
				break;
			}
		}
		
		$stressrating = $row[$stressskill];
		if ($stressrating < 1) { $stressboxes = 2; }
		elseif ($stressrating < 3) {$stressboxes = 3; }
		else {$stressboxes = 4;}
		
		echo "\t\t\t<td>";
		echo str_repeat("O",$stressboxes);		
		echo "</td>\n";
		
		echo "\t\t</tr>\n";
	}
	
	echo "\t</table>\n";
}

//Include the NPC selector, to add NPCs to the initiative list.
include 'fate_npcselector.php';

?>