function setTargetCombBlock()
{
	var index = document.forms["ACTION_SELECT"].action.selectedIndex;
	
	if(index == 0)
	{
		document.getElementById('COMB_BLOCK').style.display = "none";
		document.getElementById('TIME_BLOCK').style.display = "none";
		document.getElementById("HINT_1").style.display = "block";
		document.getElementById("HINT_2").style.display = "none";
		document.getElementById("HINT_3").style.display = "none";
	} else if(index == 1) {
		document.getElementById('COMB_BLOCK').style.display = "block";
		document.getElementById('TIME_BLOCK').style.display = "none";
		document.getElementById("HINT_1").style.display = "none";
		document.getElementById("HINT_2").style.display = "block";
		document.getElementById("HINT_3").style.display = "none";
	} else if(index == 2) {
		document.getElementById('COMB_BLOCK').style.display = "block";
		document.getElementById('TIME_BLOCK').style.display = "block";
		document.getElementById("HINT_1").style.display = "none";
		document.getElementById("HINT_2").style.display = "none";
		document.getElementById("HINT_3").style.display = "block";
	}
}