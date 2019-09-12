// Clicking the 'showhideall' span will cause any still-hidden sheets to appear, or hide all chars.
O('showhideall').onclick = function() {
	//If any are hidden...
	if (C('hidden').length > 0) {
		//...reveal them all!
		let hid= C('hidden');
		//Must run in reverse to avoid skipping elements as the class is taken away.
		for (let i=hid.length-1; i>=0; i--) {
			hid[i].classList.remove('hidden');
		}
	}
	
	//If none are hidden...
	else {
		//...hide them all!
		let chars= C('char');
		for (let i=0; i<chars.length; i++) {
			chars[i].classList.add('hidden');
		}
	}
}

// Clicking a character's name will cause them to toggle visibility.
for (let i=0; i<C('charname').length; i++) {
	C('charname')[i].onclick = function() {
		this.parentNode.parentNode.classList.toggle('hidden');
	}
}