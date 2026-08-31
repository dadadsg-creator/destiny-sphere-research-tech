class videoPlaylistPanel extends PlaylistPanel {
	private var obj:XMLNode;
	public var hidden:Boolean;
	function videoPlaylistPanel() {
	}
	private function drawObject(obj:XMLNode, pcount:Number, tx:Number, ty:Number) {
		//_global.checkEncode(obj, "video");
		var clip = container.attachMovie("video", "video"+pcount, pcount, {_x:icon_width*tx+abstand*tx, _y:ty*icon_width+y_space*ty});
		clip.info = obj;
		clip.num = pcount;
		clip.classItem = this;
		clip.clas = "video";
		clip.target = obj.attributes.preview;
		clip.indexator.listindexDT.text = start_index+pcount+1;
		if (obj.attributes.id == focus_id) {
			clip.changesign._visible = true;
		} else {
			clip.changesign._visible = false;
		}
		if (obj.attributes.error) {
			clip.enabled = false;
			clip._alpha = 70;
			clip.errare._visible = true;
		} else {
			clip.errare._visible = false;
		}
		addProppPassive(clip);
	}
}
