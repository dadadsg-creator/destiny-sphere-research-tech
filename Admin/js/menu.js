var TimeOut = 1000;
var DefaultMenu = "";
var nTimer;
var vsblTN = null;
var active_background	= "";
var passive_background	= "";
var is_loading = false;

function showTNav(num) {
	if (is_loading) {
		resetnTimer();
		if(vsblTN) hideVsblTn();
		lay=eval("document.all.navMenu"+num);
		bch=eval("document.all.d"+num);
		if (lay) { lay.style.display="block"; }
		bch.background = active_background;
		vsblTN = num;
	}	
}

function hideVsblTn() {
	if (is_loading) {
		lay=eval("document.all.navMenu"+vsblTN);
		bch=eval("document.all.d"+vsblTN);
		bch.background = passive_background;
		if (lay) { lay.style.display="none"; }
		vsblTN = null;
	}	
}

function hideVsblAndShowDefault() {
	hideVsblTn();
	if (DefaultMenu) showTNav(DefaultMenu);
}

function hideTNav() {
	resetnTimer(); nTimer = setTimeout("hideVsblAndShowDefault()", TimeOut);
}

function resetnTimer() {
	clearTimeout(nTimer); nTimer = null;
}

function showDefaultMenu(num) {
	if (num) DefaultMenu = num;
	if (DefaultMenu) showTNav(DefaultMenu);
}

function InitMenu(active_bg, passive_bg) {
	active_background	= active_bg;
	passive_background	= passive_bg;
	is_loading = true;
}
