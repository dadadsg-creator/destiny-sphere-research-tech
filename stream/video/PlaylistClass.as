dynamic class PlaylistClass {
	private var PlaylistXML:XML;
	private var Player:Object;
	private var cItem:XMLNode;
	private var UI:MovieClip;
	private var autostart:Boolean;
	function PlaylistClass(value:MovieClip, as) {
		UI = value;
		if (as) {
			autostart = true;
		}
	}
	public function Init(xml, pl) {
		PlaylistXML = xml;
		Player = pl;
		Player.setPlaylist(this);
		UI.setPlaylist(this);
		UI.setInfo(PlaylistXML);
		if (PlaylistXML.firstChild.childNodes.length<2) {
			Player.singleMode();
		}
	}
	public function nextItem():Boolean {
		if (cItem.nextSibling) {
			cItem = cItem.nextSibling;
			currItem();
			return (true);
		} else {
			UI.create();
			return (false);
		}
	}
	public function prevItem():Boolean {
		if (cItem.previousSibling) {
			cItem = cItem.previousSibling;
			currItem();
			return (true);
		} else {
			UI.create();
			return (false);
		}
	}
	public function firstItem():Void {
		cItem = PlaylistXML.firstChild.firstChild;
		currItem();
	}
	public function currItem() {
		if (!cItem) {
			cItem = PlaylistXML.firstChild.firstChild;
		}
		UI.focus_id = cItem.attributes.id;
		UI.create();
		if (!autostart) {
			Player.playListItem(cItem, PlaylistXML.firstChild.childNodes.length);
		} else {
			autostart = false;
		}
	}
	public function byIdItem(value:XMLNode) {
		if (cItem != value) {
			cItem = value;
			currItem();
		}
	}
	////////////////// дополнительно
	public function isLastItem():Boolean {
		if (cItem.nextSibling) {
			return (true);
		} else {
			return (false);
		}
	}
	public function getItemsNumber():Number {
		return (PlaylistXML.firstChild.childNodes.length);
	}
}
