<head>
	<title>Eye in the Sky: Aspect Shuffler</title>
	<style>
		body {font-family: Arial;}
		
		table, th, td {
			border: 1px solid black;
			border-collapse: collapse;
			vertical-align: top;
		}
		
		.title { width: 12em; }
		.aspect { width: 64em; }
		
		h1 { color: red; }
	</style>
</head>

<?php
	require_once 'fateinclude.php';
	function randAspect($name,$conn) {
		//Given a name and active connection, returns one random aspect from that name.
		$query = $conn->query("SELECT aspect FROM aspects WHERE name='$name'");
		$numaspects = $query->num_rows;
		
		$rand = rand(0,$numaspects-1);
		
		//Seek to the randomly-chosen row number.
		$query->data_seek($rand);
		$aspect = $query->fetch_array(MYSQLI_ASSOC)['aspect'];
		
		return $aspect;
	}
	echo "<h1>Random Draw<h1>\n";
	foreach($types as $array) {
		$desc = typeSwitch($array);
		//Add an 's' to match with the array name; eg '$pcs'
		$array .= 's';
		
		$shuffled = $$array;
		shuffle($shuffled);
		
		echo "<h2>$desc"."s</h2>\n";
		echo "<table><tr><th class='title'>$desc</th><th class='aspect'>Aspect</th></tr>\n";
		
		foreach ($shuffled as $iname) {
			echo "<tr><td>$iname</td>\n<td>";
			echo randAspect($iname,$conn);
			echo "</td></tr>\n";
		}
		echo "</table>";
	}
	
	//Special handling of 'Game Aspects', as they aren't connected to a proper character.
	echo "<h2>Game Aspects</h2>\n";
	$query = $conn->query("SELECT aspect FROM aspects WHERE name='Game'");
	$numaspects = $query->num_rows;
	$rand = rand(0,$numaspects-1);
	$query->data_seek($rand);
	$aspect = $query->fetch_array(MYSQLI_ASSOC)['aspect'];
	
	echo "<table><tr><th class='title'>Issue</th><th class='aspect'>Aspect</th></tr>\n";
	echo "<tr><td>Eye in the Sky</td>\n<td>$aspect</td></tr></table>";
	
	echo "<h1>All Aspects</h1>";
	
	//Find out the largest number of aspects, so that each table can be that wide.
	$query = $conn->query("SELECT DISTINCT numaspects FROM characters ORDER BY numaspects DESC");
	$maxaspects= $query->fetch_array(MYSQLI_ASSOC);
	$maxaspects= $maxaspects['numaspects'];
	$query->close();
	
	
	foreach($types as $array) {
		$desc = typeSwitch($array);
		//Add 's' so you can hook into, for example, the '$pcs' array.
		$array .= 's';
		
		echo "<h2>$desc"."s</h2>\n";
		echo "<table><tr><th class='title'>$desc</th>\n";
		//One column for each aspect.
		for ($i=0; $i < $maxaspects; $i++) {
			echo "<th class='title'>Aspect</th>";
		}
		echo "</tr>\n";
		
		foreach ($$array as $name) {
			echo "<tr><td>$name</td>";
			//Get a query of all the aspects.
			$query = $conn->query("SELECT aspect FROM aspects WHERE name='$name' ORDER BY num");
			$numaspects = $query->num_rows;
			
			for ($i=0; $i<$maxaspects; $i++) {
				echo "<td>";
				//If therre's an aspect there, display it; otherwise, the cell will be empty space.
				if ($i < $numaspects) {
					$query->data_seek($i);
					$aspect = $query->fetch_array(MYSQLI_ASSOC)['aspect'];
					echo $aspect;
				}
				echo "</td>\n";
			}

			echo "</tr>\n";
		}
		echo "</table>";
	}
	
	//Again, special handling for Game Aspects, because they aren't tied to an individual character that would go in the Characters list.
	echo "<h2>Game Aspects</h2>\n";
	$query = $conn->query("SELECT aspect FROM aspects WHERE name='Game'");
	$numaspects = $query->num_rows;
	
	//Only the one 'character' in this one, so can make rows based solely on how many aspects it has.
	echo "<table><tr><th class='title'>Issue</th>\n";
	for ($i = 0; $i < $numaspects; $i++) {
		echo "<th>Aspect</th>";
	}
	echo "</tr>\n";
	echo "<tr><td>Eye in the Sky</td>\n";
	for ($i = 0; $i < $numaspects; $i++) {
		$query->data_seek($i);
		echo "<td>". $query->fetch_array(MYSQLI_NUM)[0] ."</td>";
	}
	echo "</tr></table>";

?>