//String for the innerHTML of new skill dropdowns.
const options=`<option value="_">_</option>\n<option value="athletics">athletics</option>\n<option value="burglary">burglary</option>\n<option value="contacts">contacts</option>\n<option value="crafts">crafts</option>\n<option value="deceive">deceive</option>\n<option value="drive">drive</option>\n<option value="empathy">empathy</option>\n<option value="fight">fight</option>\n<option value="investigate">investigate</option>\n<option value="lore">lore</option>\n<option value="notice">notice</option>\n<option value="physique">physique</option>\n<option value="provoke">provoke</option>\n<option value="rapport">rapport</option>\n<option value="resources">resources</option>\n<option value="shoot">shoot</option>\n<option value="stealth">stealth</option>\n<option value="will">will</option>`;

//
// Responses for changing the visibility of inputs in the combined function form.
//

// First, save the current function as 'func'.
let func= O('functionselect').value;

function functionChange(e) {
	//Save the 'before' and 'after' functions.
	before = func;
	after = this.value;
	
	//Only proceed if there is a difference.
	if (before != after) {
		//Initialise a 'mustHide' and 'mustShow' array, which will be populated depending on the particular value.
		let mustShow=[];
		let mustHide=[];
		
		//Switch based on the value, and which inputs that value needs.
		let func= this.value;
		
		switch(func) {
			//Load, Backup, and Delete will all need just the name dropdown.
			case 'load':
			case 'backup':
			case 'delete': {
				mustShow.push('input_name');
				mustHide.push('input_newname');
				mustHide.push('input_type');
				break;
			}
			//Create will need the newname and type, but not the name dropdown.
			case 'create': {
				mustShow.push('input_newname');
				mustShow.push('input_type');
				mustHide.push('input_name');
				break;
			}
			//Rename will need the name dropdown and newname input, but not the type.
			case 'rename': {
				mustShow.push('input_name');
				mustShow.push('input_newname');
				mustHide.push('input_type');
			}
		}
		
		//Now that the arrays have been populated, use them to add or remove the 'hidden' class as needed.
		
		mustShow.forEach(function(item) {
			//Remove the hidden class, if it has it.
			let obj= O(item);
			if (obj.classList.contains("hidden")) {obj.classList.remove("hidden");}
		});
		
		mustHide.forEach(function(item) {
			//Add the hidden class, if it doesn't have it.
			let obj= O(item);
			if (!obj.classList.contains("hidden")) {obj.classList.add("hidden");}
		});
		
		func = after;
	}
}
O('functionselect').onchange= functionChange;

// Declare character sheet variables into global scope so they can still be used in the console.
let numAspects; let skillPoints; let skillLeft; let cap;
let numStunts; let freeStunts; let refresh;
let skillRatings;
let oAspects; let aAspects;
let oSlots; let aSlots; let oSkills; let aSkills;
let oStunts; let aStuntNames; let aStuntDescs;

//Load this if the character sheet is not null.
if (O('save') != null) {
	//Get the number of aspects.
	numAspects = O('sheet.numaspects').value;

	//Get the skill points.
	skillPoints = O('sheet.skillpoints').value;

	//Get the skill points left.
	skillLeft = O('skillleft').innerHTML;

	//Get the skill cap.
	cap = O('sheet.cap').value;

	//Get the number of stunts.
	numStunts = O('sheet.numstunts').value;

	//Get the number of FREE stunts.
	freeStunts = O('sheet.freestunts').value;

	//Get the refresh.
	refresh= O('sheet.refresh').value;

	//Initialise the 'skillRatings' object; for each skill, it will save an array of the dropdown ids which are set to that skill.
	skillRatings={};
	skillNames.forEach(function(item) {
		skillRatings[item]= [];
	});

	//Get the aspect textareas, in both their HTML object form and an array of their text.
	oAspects = C('sheet.aspect');
	aAspects = [];
	for (let i=0; i < oAspects.length; i++) {
		aAspects[i] = oAspects[i].innerHTML;
	}

	//Get the skills.
	//First, the slots.
	oSlots = [];
	aSlots = [];

	oSkills = [];
	//let aSkills = [];
	aSkills = [];
	for (let i=cap; i>0; i--) {
		//Populate oSlots and aSlots manually via cap, because positioning within the array is meaningful.
		oSlots[i] = O('slots.'+i);
		aSlots[i] = oSlots[i].value;
		
		//Populate this part of the oSkills array by class name.
		oSkills[i]= C(`sheet.skillselect.${i}`);
		//Initialise the first dimension of the skills array.
		aSkills[i]= [];
		//Push each skill into the second dimension of the skills array.
		for (let j=0; j<oSkills[i].length; j++) {
			let sel = oSkills[i][j];
			let skill = sel.value;
			aSkills[i].push(skill);
		}
		
		//Grab the initial unused skills from the chargen table.
		//Initialise the unused-skills array.
		aSkills[0]= [];
		let unused= C('unused_skill');
		for (let i=0; i<unused.length; i++) {
			aSkills[0].push(unused[i].innerHTML);
		}
	}

	//Get the stunts, in both their object form and arrays of their names and descs.
	oStunts = C('sheet.stunt');
	aStuntNames = [];
	aStuntDescs = [];
	for (let i=0; i<oStunts.length; i++) {
		aStuntNames[i] = oStunts[i].children[0].value;
		aStuntDescs[i] = oStunts[i].children[2].innerHTML;
	}

	// Whenever an aspect is changed, update the array.
	function aspectChange(e) {
		//Get the index from the id, which is of format 'aspect.1'
		let ind= this.id.substring( this.id.indexOf('.')+1 );
		aAspects[ind] = this.value;
	}

	//Add the onchange to all aspects.
	for(let i=0;i < oAspects.length; i++) {
		oAspects[i].onchange = aspectChange;
	}

	// Whenever a skill slot number is changed, update the array.
	function slotsChange(e) {
		let i= this.id.substring(this.id.indexOf('.')+1);
		
		//Otherwise, they are still treated as strings and will run into trouble in double digits.
		let oldSlots= parseInt(aSlots[i]);
		let newSlots= parseInt(this.value);
		
		if (newSlots < oldSlots) {
			//Need to remove some slots! Start from the top.
			for (let j = oldSlots-1; j >= newSlots; j--) {
				
				let obj= oSkills[i][j];
				let span= obj.parentNode;
				let par= span.parentNode;
				par.removeChild(span);
				
				//If this was a multiple of 3 -- and thus, the first on a new line -- remove the BRs as well.
				if ((j%3) == 0) {
					//Save the now-last child.
					let lastChild= par.children[par.children.length-1];
					// If the now-last child element is a br...
					if (lastChild.toString() == "[object HTMLBRElement]") {
						//Remove it.
						par.removeChild(lastChild);
					}
					//Remove the second one, too.
					lastChild= par.children[par.children.length-1];
					// If the now-last child element is a br...
					if (lastChild.toString() == "[object HTMLBRElement]") {
						//Remove it.
						par.removeChild(lastChild);
					}
				}
			}
		}
		
		if (newSlots > oldSlots) {
			//Need to add some slots!
			for (let j = oldSlots; j < newSlots; j++) {
				//Get the parent object.
				let par= O(`skillboxes.${i}`);
				
				makeNewSkill(par,i,j);

			}
		}
		aSlots[i]= this.value;
		
		//Check for repeat skills and run the update functions.
		checkForRepeatSkills();
		updateUnused();
		updateSP();
	}

	//Add the onchange for all slots.
	for(let i=1; i < oSlots.length; i++) {
		//Only for those that actually exist.
		if (oSlots[i] != "undefined") {
			oSlots[i].onchange = slotsChange;
		}
	}

	//Function for adding new skills. Appends the entire <span> element with the skill dropdown inside. Takes "parent", "rating" and "index at rating" (thus, the second Good skill will take 3,1). Or, while looping through slots, [i][j].
	function makeNewSkill (par,i, j) {
		
		//Add space-breaking elements.
		if ((j%3) == 0) {
			//If it's a multiple of three, add two BRs.
			par.appendChild( document.createElement('br') );
			par.appendChild( document.createElement('br') );
		}
		else {
			//Otherwise, just need a space.
			par.append(" ");
		}
		
		// Create the span.
		let newSpan = document.createElement('span');
		newSpan.classList.add(`skillspan`);
		newSpan.id= `skillspan.${i}.${j}`;
		newSpan.draggable = "true";
		newSpan.ondragstart = pickUpSkill;
		
		// Create the dropdown.
		let newSkill = document.createElement('select');
		newSkill.id = `skills.${i}.${j}`;
		newSkill.name = `skills${i}[]`;
		newSkill.classList.add(`sheet.skillselect`);
		newSkill.classList.add(`sheet.skillselect.${i}`);
		newSkill.size = 1;
		newSkill.innerHTML = options;
		newSkill.onchange = skillsChange;
		
		//Has this skill existed on the page (and in the array) before?
		if (aSkills[i][j] != undefined) {
			//If yes, remember the old selection.
			newSkill.value = aSkills[i][j];
		}
		else {
			//If no, fill it in with the placeholder.
			newSkill.value = "_";
			aSkills[i][j] = "_";
		}
		
		//Add this skill to the span.
		newSpan.append(newSkill);
		
		//Add the whole thing to the parent.
		par.append(newSpan);
	}

	// Whenever a skill is changed, update the array.
	function skillsChange(e) {
		//Find the array indices from the id, which is of the format 'skills.RATING.NUM', as in first skill at +2 is 'skills.2.0'.
		let id= this.id;
		//Index of first dot.
		let dot1 = id.indexOf('.');
		//Index of second dot.
		let dot2 = id.indexOf('.',dot1+1);
		//First dimension index is between first and second dots.
		let ind1 = id.substring(dot1+1,dot2);
		//Second dimension index is after the first dot and all the way to the end.
		let ind2 = id.substring(dot2+1);
		
		aSkills[ind1][ind2] = this.value;
		
		//Check whether the change has caused or removed repeats, or unused skills.
		checkForRepeatSkills();
		updateUnused();
	}
	//Add the onchange for all skills.
	for (let i=1; i< oSkills.length; i++) {
		if (oSlots[i] != "undefined") {
			for (let j=0; j<oSkills[i].length; j++) {
				oSkills[i][j].onchange = skillsChange;
			}
		}
	}

	//Whenever a stunt is changed, update the array.
	function stuntChange(e) {
		let id= this.id;
		let ind= id.substring(id.indexOf('.')+1);
		aStuntNames[ind] = this.children[0].value;
		aStuntDescs[ind] = this.children[2].value;
	}
	//Add the onchange for all stunts.
	for (let i=0; i<oStunts.length; i++) {
		oStunts[i].onchange = stuntChange;
	}

	//Whenever numAspects is changed, alter that column.
	function numAspectsChange (e) {
		//Get oldNum, saved in JavaScript. Parse into int.
		let oldNum = parseInt(numAspects);
		
		//Get newNum, the number's new value. Parse into int.
		let newNum = parseInt(this.value);
		
		if (newNum < oldNum) {
			//Have to remove some aspect slots! We'll need the wrapping div, not just the object itself.
			//Remove in reverse order.
			for (let i= oldNum-1; i >= newNum; i--) {
				let obj= oAspects[i];
				let div= obj.parentNode;
				let par= div.parentNode;
				par.removeChild(div);
			}
		}
		
		if (newNum > oldNum) {
			//Have to ADD some aspect slots!
			for (let i= oldNum; i< newNum; i++) {
				//Save the parent.
				let par=O(`aspects`);
				
				//Create the div.
				let newDiv= document.createElement('div');
				newDiv.classList.add(`aspectDiv`);
				newDiv.id= `aspectdiv.${i}`;
				
				//Create the slot.
				let newAspect = document.createElement('textarea');
				newAspect.name = `aspects[]`;
				newAspect.id = `aspect.${i}`;
				newAspect.classList.add(`sheet.aspect`);
				newAspect.cols = 48;
				newAspect.onchange= aspectChange;
				
				//Has this slot existed on the page (and in the array) before?
				if (i < aAspects.length) {
					//If yes, remember the old selection.
					newAspect.innerHTML = aAspects[i];
				}
				else {
					//If on, add it to the array.
					aAspects[i]= "";
				}
				
				//Append the div.
				par.appendChild(newDiv);
				//Inside the div, append the aspect.
				newDiv.appendChild(newAspect);
				
				//Also add two <br>s.
				newDiv.appendChild( document.createElement('br') );
				newDiv.appendChild( document.createElement('br') );
				
				//Objects array is automatically updated by the use of the 'sheet.aspect' class.
			}
		}
		numAspects= newNum;
	}
	//Add the onChange for numAspects.
	O('sheet.numaspects').onchange = numAspectsChange;


	//Update when skill points are changed.
	function skillPointsChange(e) {
		//Get the old number and new number, parsed into ints.
		let oldNum = parseInt(skillPoints);
		let newNum = parseInt(O('sheet.skillpoints').value);
		
		skillPoints= newNum;
		updateSP();
	}
	O('sheet.skillpoints').onchange = skillPointsChange;

	//Update when skill cap is changed.
	function capChange(e) {
		//Get oldNum and newNum, parsed into ints.
		let oldNum = parseInt(cap);
		let newNum = parseInt(O('sheet.cap').value);
		
		//Save the 'all skill paragraphs' div as skillDiv.
		let skillDiv = O('skillps');
		
		if (newNum < oldNum) {
			//Need to remove some paragraphs. Will go top to bottom.
			let skillsSaved= 0;
			for (let i=oldNum; i>newNum; i--) {
				// Find how many slots used to be here.
				let hadSkills = aSlots[i];
				
				//Find the target object. ID is format skills.RATING, e.g. skills.4
				let target = O(`skills.${i}`);
				skillDiv.removeChild(target);
			}
			
			updateSP();
		}
		
		if (newNum > oldNum) {
			//Need to ADD some paragraphs.
			
			//Initialise skillCost, in case of ratings which had slots before.
			let skillCost=0;
			
			for (let i=oldNum+1; i<=newNum; i++) {
				//Create the new paragraph.
				let newP = document.createElement('p');
				newP.id= `skills.${i}`;
				newP.classList.add(`sheet.skillsp`);
				//The adjective
				newP.append(adjective(i)+ " (");
				
				//Apply the events for changing and dragging.
				newP.ondragover= dragSkillOver;
				newP.ondrop= dropSkill;
				
				//The number-of-slots input
				let newSlots= document.createElement('input');
				newSlots.type= 'number';
				newSlots.cols= '1';
				newSlots.name= `slots${i}`;
				newSlots.id= `slots.${i}`;
				newSlots.onchange= slotsChange;
				
				//Set the number-of-slots value.
				//If this slot has existed before...
				if (aSlots[i] != undefined) {
					//...use the old value
					numSlots = parseInt(aSlots[i]);
					
					//And add cost to skillCost.
					skillCost += numSlots * i;
				}
				//If not...
				else {
					//...initialise to 0 and add to arrays.
					numSlots = 0;
					aSlots[i]= 0;
					aSkills[i]= [];
					oSkills[i]= [];
				}
				//Either way, add to the objects array.
				oSlots[i]= newSlots;
				
				//Set the input's value to the num of slots.
				newSlots.value = numSlots;
				
				//Add the input to the newP.
				newP.append(newSlots);
				
				//Close off the parentheses and add the colon.
				newP.append("):\n\n");
				
				//Add the 'skillboxes' span.
				let newSpan = document.createElement('span');
				newSpan.classList.add('skillboxes');
				newSpan.id= `skillboxes.${i}`;
				
				newP.append(newSpan);
				
				//If there have already been slots saved...
				if (numSlots>0) {
					//...we need to fill in those, too.
					for (let j=0; j<numSlots; j++) {
						
						makeNewSkill(newSpan,i,j);
					}
				}
				//Add the new paragraph to the top.
				skillDiv.prepend(newP);
			}
		}
		
		//Update the cap.
		cap = newNum;
		
		//Check for repeats and run the update functions.
		checkForRepeatSkills();
		updateUnused();
		updateSP();
	}
	O('sheet.cap').onchange = capChange;

	function numStuntsChange(e) {
		// Get the old number from the variable.
		let oldNum = parseInt(numStunts);
		
		//Get the new number from the object.
		let newNum = parseInt(this.value);
		
		if (newNum < oldNum) {
			//Need to remove some stunts. Going top to bottom.
			for (let i=oldNum-1; i>=newNum; i--) {
				let obj= oStunts[i];
				let par= obj.parentNode;
				
				par.removeChild(obj);
			}
		}
		
		if (newNum > oldNum) {
			//Need to ADD some slots.
			
			//Get the parent object first.
			let par= O('stunts');
			
			for (let i=oldNum; i<newNum; i++) {
				//Create the paragraph.
				let newP= document.createElement('p');
				newP.id = `stunt.${i}`;
				newP.classList.add(`sheet.stunt`);
				
				//The stunt name input.
				let newName = document.createElement('input')
				newName.classList.add(`stuntname`);
				newName.type= `text`;
				newName.size= `48`;
				newName.maxLength= `48`;
				newName.name= `stunts[]`;
				
				//Append it.
				newP.append(newName);
				
				//Also append a <br>.
				newP.append( document.createElement('br') );
				
				//The stunt desc input.
				let newDesc= document.createElement('textarea');
				newDesc.name= `stuntdescs[]`;
				newDesc.cols= 50;
				newDesc.rows= 7;
				
				//Append it.
				newP.append(newDesc);
				
				//Save the whole thing in the objects array.
				oStunts[i]= newP;
				
				//Has this stunt existed before?
				if (aStuntNames[i] != undefined) {
					//If yes, pull the old information from it.
					newName.value= aStuntNames[i];
					newDesc.innerHTML= aStuntDescs[i];
				}
				else {
					//If no, push blank into the array.
					aStuntNames[i]= "";
					aStuntDescs[i]= "";
				}
				
				//Append the paragraph.
				par.append(newP);
			}
		}
		
		//Update the variable.
		numStunts= newNum;
		
		//Also update remaining freeStunts and refresh.
		let freeStuntsLeft = Math.max(0,freeStunts-numStunts);
		O('freestuntsleft').innerHTML= freeStuntsLeft;
		
		let refreshLeft = refresh;
		if (numStunts > freeStunts) {
			//If you have more stunts than your free allowance...
			refreshLeft -= (numStunts-freeStunts);
			//...subtract the rest from refresh.
		}
		
		O('refreshleft').innerHTML= refreshLeft;
		
		//refreshLeft and freeStuntsLeft are derived attributes, and don't need their own variables updted.
	}
	O('sheet.numstunts').onchange = numStuntsChange;

	function freeStuntsChange(e) {
		// Get new value, parsed.
		let newNum = parseInt(this.value);
		
		//Update remaining freestunts.
		let freeStuntsLeft = Math.max(0,newNum-numStunts);
		O('freestuntsleft').innerHTML= freeStuntsLeft;
		
		//Update remaining refresh.
		let refreshLeft= refresh;
		if (numStunts > newNum) {
			refreshLeft -= (numStunts-newNum);
		}
		O('refreshleft').innerHTML= refreshLeft;
		
		//Update variable.
		freeStunts= newNum;
	}
	O('sheet.freestunts').onchange = freeStuntsChange;

	function refreshChange(e) {
		//Get new value, parsed.
		let newNum= parseInt(this.value);
		refresh = newNum;
		
		//Update remaining refresh.
		let refreshLeft= refresh;
		if (numStunts > freeStunts) {
			refreshLeft -= (numStunts - freeStunts);
		}
		O('refreshleft').innerHTML=refreshLeft;
	}
	O('sheet.refresh').onchange= refreshChange;

	function checkForRepeatSkills() {
		//This function checks whether any skills are repeated by the skillselect, and colours offending pairs with a red background.
		
		//First, clear all those that already have the class.
		//Set the class list in repeatskill.
		let repeatskill= C('repeatskill');
		//Iterate through it from the top. (The HTMLcollection automatic update would result in skipping members if you do a numerical loop from the bottom which includes removing class.)
		for (let i=repeatskill.length-1; i>=0; i--) {
			repeatskill[i].classList.remove('repeatskill');
		}
		
		//Also empty out the skillRatings object to start fresh.
		//Iterate through all skill names to clear the object properties.
		skillNames.forEach(function(skill) {
			skillRatings[skill]= [];
		});
		
		//Now iterate through all sheet.skillselect dropdowns to repopulate skillRatings.
		let allselect= C('sheet.skillselect');
		for (i=0; i<allselect.length; i++) {
			//Save the target object to target.
			let target = allselect[i];
			
			//Grab the skill from the element's value.
			let skill = target.value;
			
			//If it's not the placeholder '_', push the id to the skillRatings object.
			if (skill != '_') {
				skillRatings[skill].push(target.id);
			}
		}
		
		//Iterate through all skills again to find those properties which have more than one selection.
		skillNames.forEach(function(skill) {
			//Is the given property longer than one element?
			if (skillRatings[skill].length > 1) {
				//Iterate through it and apply the repeatskill class.
				for (let i=0; i<skillRatings[skill].length; i++) {
					//Grab the element by id.
					let e = O(skillRatings[skill][i]);
					e.classList.add('repeatskill');
				}
			}
		});
	}

	//Check for repeat skills before allowing a submission.
	function onSave() {
		//If any dropdowns have the repeatskill class, return false and provide an error.
		if (C('repeatskill').length>0) {
			alert("You cannot save this sheet while you have the same skill selected multiple times.");
			return false;
		}
		else {
			return true;
		}
	}
	O('save').onsubmit = onSave;

	//
	//Code for dragging skills from one rating to another.
	//

	//Apply the events for all skill paragraphs.
	for (let i=0; i<C('sheet.skillsp').length; i++) {
		let p= C('sheet.skillsp')[i];
		p.ondragover= dragSkillOver;
		p.ondrop= dropSkill;
		
	}
	//Apply the events for all skill spans.
	for (let i=0; i<C('skillspan').length; i++) {
		let span= C('skillspan')[i];
		span.ondragstart = pickUpSkill;
	}

	// The event for dragstart, or 'picking up' a skill.
	function pickUpSkill(e) {
		//Get the span's ID.
		let spanID= e.target.id;
		
		//Get the ID of the skillselect inside the span.
		let skillID= e.target.children[0].id;
		
		e.dataTransfer.setData("span",spanID);
		e.dataTransfer.setData("skill",skillID);
	}
	// The event for dragging over a skill paragraph.
	function dragSkillOver(e) {
		e.preventDefault(); //Allow the drag-over.
	}
	// The event for dropping into a skill paragraph.
	function dropSkill(e) {
		let span= O(e.dataTransfer.getData("span"));
		let skill= O(e.dataTransfer.getData("skill"));
		
		let id = skill.id;
		let dot1 = id.indexOf('.');
		let dot2 = id.indexOf('.',dot1+1);
		let fromRating = parseInt(id.substring(dot1+1,dot2));
		//Also get the fromIndex.
		let fromIndex = parseInt(id.substring(dot2+1));
		
		let dest=e.target;
		
		id = dest.id;
		dot1 = id.indexOf('.');
		let toRating = parseInt(id.substring(dot1+1));

		
		// If they're just moving it to the same rating, fail out.
		if (fromRating == toRating) {
			console.log(`Shifting a skill from ${adjective(fromRating)} to ${adjective(toRating)} does nothing.`);
		}
		
		else {
			e.preventDefault(); //Allow the drop.
			
			//Find the skillbox span where we'll be dropping this.
			let skillbox= O(`skillboxes.${toRating}`);
			
			//This will also be the sub-index of the moved slot.
			let toIndex = parseInt(aSlots[toRating]);
					
			//Actually move the HTML element.
			//Append space-breaking elements.
			if ((toIndex%3) == 0) {
				//Index is a multiple of three, so we need two BRs.
				skillbox.append( document.createElement('br') );
				skillbox.append( document.createElement('br') );
			}
			else {
				//Otherwise, just need a space.
				skillbox.append(" ");
			}
			
			//Remove from aSkills[fromRating], and add to aSkills[toRating].
			aSkills[toRating][toIndex] = aSkills[fromRating][fromIndex];
			aSkills[fromRating].splice(fromIndex,1);
			
			//Decrement the old skill slots and increment the new skill slots.
			aSlots[fromRating]--;
			oSlots[fromRating].value = aSlots[fromRating];
			aSlots[toRating]++;
			oSlots[toRating].value = aSlots[toRating];
			
			//Change the span ID .
			span.id = `skillspan.${toRating}.${toIndex}`;
			
			//Change the select ID, class, and name.
			skill.id = `skills.${toRating}.${toIndex}`;
			skill.classList.remove(`sheet.skillselect.${fromRating}`);
			skill.classList.add(`sheet.skillselect.${toRating}`);
			skill.name = `skills${toRating}[]`;
			
			//Append the skill into the skillboxes span.
			O(`skillboxes.${toRating}`).append(span);
			
			//Erase and rewrite the origin skillboxes span.
			let origin = O(`skillboxes.${fromRating}`);
			origin.innerHTML= "";
			
			for (let i=0; i<aSkills[fromRating].length; i++) {
				makeNewSkill(origin,fromRating,i);
			}
			
			//Update the skill points.
			updateSP();
		}
	}

	//Function to update the 'unused skills' paragraph as needed.
	function updateUnused() {
		//Checks the current layout of aSkills to update aSkills[0] and thence the unused skills paragraph.
		
		//Make an array of all skills that HAVE been used, up to the current cap and slot numbers.
		let used=[];
		for (let i=1; i<=cap; i++) {
			for (let j=0; j<aSlots[i]; j++) {
				//Grab the skill.
				let skill= aSkills[i][j];
				//Make sure it's an actual skill, not a placeholder.
				if (skill != '_') {
					used.push(skill);
				}
			}
		}
		
		//Filter the list of all skill names, to find those that have NOT been used, and place them into aSkills[0].
		aSkills[0]= skillNames.filter(function(skill) {
			return used.indexOf(skill) == -1;
		});
		
		O('unused_skills').innerHTML= aSkills[0];
		
		// Update the NUMBER of unused, as well.
		O('unused_num').innerHTML = aSkills[0].length;
	}
	
	// Function to update the 'skillleft' and 'skillspent' spans, to be called after skillpoints and aSlots have been updated.
	function updateSP() {
		let skillSpent= 0;
		
		//Loop from 1 to the skill cap, to find the current number of slots. (Cannot simply .reduce() the aSlots array because it remembers ratings which have been nixed by lowering the skill cap.)
		for (let i=1; i<= cap; i++) {
			skillSpent += aSlots[i] * i;
		}
		
		//Update the skillspent span.
		O('skillspent').innerHTML = skillSpent;
		
		//Update the skillleft span.
		O('skillleft').innerHTML = skillPoints - skillSpent;
	}
}

//Javascript function to look up a skill.
function skillLookup (skill) {
	let rating= 0;
	
	aSkills.forEach(function(array,key) {
		let ind= array.indexOf(skill);
		if (ind != -1) {
			rating = key;
		}
	});
	// If we go through the whole array without finding it, return 0.
	return rating;
}

//Javascript function to generate wikidot markup for the loaded character.
function wikify( doneThisBefore = false) {
	//Make sure a character has been loaded.
	if (O('save') == null) {
		return `No character has been loaded.`;
	}
	else {
		// Get their type.
		let type=O('typeselect').value;
		let npc = type != 'pc';
		
		let string = "";
		
		//The aspects part, if we haven't doneThisBefore.
		if (!doneThisBefore) {
			string += `+++ Aspects\n\n`;
			
			//Iterate through the aspects.
			for (let i=0; i<aAspects.length; i++) {
				let aspect= aAspects[i];
				
				//If this is an NPC, add a collapsible block after the high concept.
				if (npc && (i==1)) {
					string += `[[collapsible show="+ SPOILER Aspects" hide="- Hide Aspects" hideLocation="both"]]\n`;
				}
				
				//Add the header.
				string += "++++ ";
				
				//Provide the special prefix for the first two aspects.
				if(i==0) {string += `High Concept: `;}
				if(i==1) {string += `Trouble: `;}
				
				//Put in the actual aspect.
				if (aspect != "") {string += aspect;}
				else {string += `Aspect_${i+1}`;}
				string += "\n";
				
				//If this is not an NPC, put the placeholder explanation in.
				if (!npc) {
					string += "Explanation\n";
				}
				
				//Add a linebreak.
				string += "\n";
			}
			//If this is an NPC, and they have more than one aspect, close out the collapsible.
			if (npc && (aAspects.length>1)) {
				string += `[[/collapsible]]\n`;
			}
			
			//Add another line break between aspects and skills.
			string += "\n";
		}
		
		//The skill part.
		string += `+++ Skills\n\n`;
		
		//If this is an NPC, put it under a collapsible block.
		if (npc) {
			string += `[[collapsible show="+ SPOILER Skills" hide="- Hide Skills"]]\n`;
		}
		
		//Iterate from the skill cap downwards.
		for (let i=cap; i>0; i--) {
			string += `++++ ${adjective(i)}\n`;
			string += `[[div class="rankbox"]]\n`;
			
			//Loop through all the skills at that rating.
			for (let j=0; j<aSkills[i].length; j++) {
				string += `[[div]]\n`;
				let skill= aSkills[i][j];
				//If it's the placeholder, provide "<NOT SET>" text.
				if (skill == '_') { string += `<NOT SET>\n`; }
				//Otherwise, just give the skill itself.
				else { string += `${skill}\n`; }
				//Close off the div.
				string += `[[/div]]\n`;
			}
			
			//Close off the div.
			string += `[[/div]]\n\n`;
		}
		//If this is not an NPC, add the skill points available line.
		if (!npc) {
			string += "++++ Skill Points Available: ";
			string += O('skillleft').innerHTML;
		}
		
		//If this *is* an NPC, close out the collapsible.
		string += `[[/collapsible]]\n`;
		
		//Add line breaks.
		string += "\n\n";
		
		//The stunt part.
		string += `+++ Stunts\n\n`;
		
		//If it's an NPC, wrap it in a collapsible.
		if (npc) { string += `[[collapsible show="+ SPOILER Stunts" hide="- Hide Stunts" hideLocation="both"]]\n`; }
		
		//Iterate through the stunt names.
		for (let i=0; i<aStuntNames.length; i++) {
			string += `++++ ${aStuntNames[i]}\n`;
			string += `${aStuntDescs[i]}\n\n`;
		}
		
		//If it's an NPC, close off the collapsible.
		if (npc) { string +=`[[/collapsible]]\n`; }
		
		string += "\n";
		
		//Show the refresh and/or stress part.
		if (npc) { string += `+++ Stress Tracks\n`; }
		else { string += `+++ Refresh & Stress Tracks\n`; }
		
		string += `[[div class="rsbox"]]\n`;
		
		
		//If it's not an NPC, get the refresh values.
		if (!npc) {
			string += "[[div]]\n";
			string += "Refresh ";
			string += O('refreshleft').innerHTML;
			string += "/";
			string += refresh;
			
			//If they have more free stunts left...
			let freestunts= O('freestuntsleft').innerHTML;
			if (freestunts == 1 ) { string += `, 1 free stunt`; }
			else if (freestunts > 1) { string += `, ${freestunts} free stunts`; }
			
			string += "\n[[/div]]\n";
		}
		
		//Get the stress track values.
		[{stress:"Physical",skill:"physique"},{stress:"Mental",skill:"will"}].forEach(function(object) {
			string += `[[div]]\n${object.stress}`;
			let rating = skillLookup(object.skill);
			let boxes = 2;
			if (rating > 2) { boxes = 4; }
			else if (rating > 0) { boxes = 3; }
			
			for (let i=1; i<=boxes; i++) {
				string += ` [${i}]`;
			}
			
			//And if they have ESPECIALLY high ratings...
			if (rating > 4) { string += ` +1 Mild`}
			string += "\n[[/div]]\n";
		});
		
		string +="[[/div]]\n";
		
		
		return string;
	}
}