<?php
	require_once 'fateinclude.php';
	
	//Selector of NPCs.
	echo "<form method='post' action='#' class='npc_selector'>\n";
	echo "\t<select name='getnpcs[]' multiple='multiple' size='5'>\n";
	$npci = 0;
	echo "\t<optgroup>\n";
	foreach ($npcs as $npc) {
		echo "\t\t<option value='$npc'>$npc</option>\n";
		$npci++;
		if ( ($npci % 10) == 0) { echo "</optgroup>\n\t<optgroup>\n"; }
	}
	echo "\t</optgroup>\n";
	echo "\t<input type='submit' value='Load'>\n";
	echo "\n\t</select>\n</form>";

?>