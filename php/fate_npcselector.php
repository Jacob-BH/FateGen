<?php
	require_once 'fateinclude.php';
	
	//Selector of NPCs.
	echo "<form method='post' action='#' id='npcs'>\n";
	echo "\t<select name='getnpcs[]' multiple='multiple' size='20'>\n";
	foreach ($npcs as $npc) {
		echo "\t\t<option value='$npc'>$npc</option>\n";
	}
	echo "\t<input type='submit' value='Load'>\n";
	echo "\n\t</select>\n</form>";

?>