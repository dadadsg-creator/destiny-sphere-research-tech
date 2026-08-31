if (typeof fcolor == 'undefined') { var fcolor = "#dddddd";}
if (typeof backcolor == 'undefined') { var backcolor = "#3C4C7B";}
if (typeof textcolor == 'undefined') { var textcolor = "#000000";}
if (typeof capcolor == 'undefined') { var capcolor = "#ffffff";}
if (typeof closecolor == 'undefined') { var closecolor = "#eeeeee";}
if (typeof width == 'undefined') { var width = "350";}
if (typeof border == 'undefined') { var border = "1";}
if (typeof offsetx == 'undefined') { var offsetx = 10;}
if (typeof offsety == 'undefined') { var offsety = 10;}

ns4 = (document.layers)? true:false
ie4 = (document.all)? true:false
ff = (document.getElementById)? true:false

if (!ie4 & ff) {
	ie4 = true;
}

var ie6 = false;

if ( (ie4) ) {
	if (navigator.userAgent.indexOf('MSIE 5')>0) {
		ie5 = true;
	} else {
		ie5 = false; 
	}
	if (navigator.userAgent.indexOf('MSIE 6')>0) {
		ie6 = true;
	} else {
		ie6 = false;
	}
	if (navigator.userAgent.indexOf('MSIE 7')>0) {
		ie7 = true;
	} else {
		ie7 = false;
	}
	if (navigator.userAgent.indexOf('U')>0) {
		ff1 = true;
	} else {
		ff1 = false;
	}
} else {
	ie5 = false;
}

var x = 0;
var y = 0;
var snow = 0;
var sw = 0;
var cnt = 0;
var dir = 1;
var tr = 1;
if ( (ns4) || (ie4) ) {
	if ((ns4)) over = document.overDiv
	if ((ie4) || (ff1)) over = overDiv.style
	document.onmousemove = mouseMove
	if (ns4) document.captureEvents(Event.MOUSEMOVE)
}

function drc(text, title, add) {
	dtc(1,text,title, add);
}

function nd() {
	if ( cnt >= 1 ) { sw = 0 };
	if ( (ns4) || (ie4) ) {
		if ( sw == 0 ) {
			snow = 0;
			hideObject(over);
		} else {
			cnt++;
		}
	}
}

function dtc(d,text, title, add) {
	if (typeof add == 'undefined') { var add = "&nbsp;";}
	txt = "<table width="+width+" border=0 cellPadding="+border+" cellSpacing=0 BGCOLOR=\""+backcolor+"\"><tr><td><table width=100% border=0 cellPadding=2 cellSpacing=0><tr><TD align=left><b><span style=\"color:#f8f8f8;\">"+title+"</span></b></td><TD align=right><b><span style=\"color:#f8f8f8;\">"+add+"</span></b></td></tr></table><table width=100% border=0 cellPadding=2 cellSpacing=0 bgColor=\""+fcolor+"\"><tr><td><span class=\"wnd_gen_txt_large\">"+text+"<SPAN></td></tr></table></td></tr></table>"
	layerWrite(txt);
	dir = d;
	disp();
}

function disp() {
	if ( (ns4) || (ie4) ) {
		if (snow == 0) 	{
			if (dir == 2) { // Center
				moveTo(over,x+offsetx-(width/2),y+offsety);
			}
			if (dir == 1) { // Right
				moveTo(over,x+offsetx,y+offsety);
			}
			if (dir == 0) { // Left
				moveTo(over,x-offsetx-width,y+offsety);
			}
			showObject(over);
			snow = 1;
		}
	}

}

function mouseMove(e) {
	if (ns4||ff1) {x=e.pageX; y=e.pageY;}
	if (ie4) {x=event.x+document.body.scrollLeft; y=event.y+document.body.scrollTop;}//{x=event.x; y=event.y;}
	if (ie5||ie6||ie7) {x=event.x+document.body.scrollLeft; y=event.y+document.body.scrollTop;}
	if (snow) {
		if (dir == 2) { // Center
			moveTo(over,x+offsetx-(width/2),y+offsety);
		}
		if (dir == 1) { // Right
			moveTo(over,x+offsetx,y+offsety);
		}
		if (dir == 0) { // Left
			moveTo(over,x-offsetx-width,y+offsety);
		}
	}
}

function cClick() {
	hideObject(over);
	sw=0;
}

function layerWrite(txt) {
        if (ns4) {
                var lyr = document.overDiv.document
                lyr.write(txt)
                lyr.close()
        }
        else if (ie4) document.all["overDiv"].innerHTML = txt
		if (tr) { trk(); }
}

function showObject(obj) {
        if (ns4) obj.visibility = "show"
        else if (ie4) obj.visibility = "visible"
}

function hideObject(obj) {
        if (ns4) obj.visibility = "hide"
        else if (ie4) obj.visibility = "hidden"
}

function moveTo(obj,xL,yL) {
        obj.left = xL
        obj.top = yL
}

function trk() {
	if ( (ns4) || (ie4) ) {
			
	}
	tr = 0;
}

function checkBrowser(){
	this.ver=navigator.appVersion
	this.dom=document.getElementById?1:0
	this.ie5=(this.ver.indexOf("MSIE 5")>-1 && this.dom)?1:0;
	this.ie4=(document.all && !this.dom)?1:0;
	this.ns5=(this.dom && parseInt(this.ver) >= 5) ?1:0;
	this.ns4=(document.layers && !this.dom)?1:0;
	this.bw=(this.ie5 || this.ie4 || this.ns4 || this.ns5)
	return this
}

bw=new checkBrowser()

function showHide(div,nest){
	obj=bw.dom?document.getElementById(div).style:bw.ie4?document.all[div].style:bw.ns4?nest?document[nest].document[div]:document[div]:0; 
	if(obj.display=='' || obj.display=='') obj.display='none'
	else obj.display=''
}