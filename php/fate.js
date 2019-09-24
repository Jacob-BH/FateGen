//Credit for these two functions goes to O'Reilly, from the book "Learning PHP, MySQL & JavaScript, 5th Edition"
//O(), given ID or object, returns the object.
//C(), given class, returns the HTML collection of all objects with that class.
function O(i) { return typeof i == 'object' ? i : document.getElementById(i) }
function C(i) { return document.getElementsByClassName(i)                    }

//adjective() takes a number and returns the proper Fate adjective for that number.
function adjective (rating) {
	//Make sure it's a number.
	rating = parseInt(rating);
	
	switch(rating) {
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
	
	if (rating > 8) {
		return `Beyond Legendary (+${rating})`;
	}
	else if (rating < -2) {
		return `Beyond Horrifying (${rating})`;
	}
	else {
		return `UNKNOWN ({$rating})`;
	}
}