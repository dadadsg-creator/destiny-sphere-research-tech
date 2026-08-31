class videoPlayerClass {
	private var UI:MovieClip;
	private var Playlist:Object;
	///////////////////
	public var videoObject:MovieClip;
	private var mediaStream:NetStream;
	private var mediaConnection:NetConnection;
	public var onStartPlay:Boolean;
	public var onStartPlayValue:Number;
	//////// Meta Data/////////////////
	public var totalPlayTime:Number = 24;
	public var VideoWidth:Number;
	public var VideoHeight:Number;
	/////////////////////////////////
	public var playstatus:Boolean;
	public var attached:Boolean;
	public var commonstatus:String;
	public var mainbuffertime:Number;
	private var file:String;
	private var eventtarget:Object;
	private var classInstance:Object;
	private var ex_size:Number;
	private var buffertime:Number = 5;
	private var buffertime2:Number = 2;
	private var info:XMLNode;
	private var closed:Boolean;
	private var SingleMode:Boolean;
	function videoPlayerClass(_UI:MovieClip) {
		UI = _UI;
		this.videoObject = UI.getVideoObject();
		this.mediaConnection = new NetConnection();
		this.mediaConnection.connect(null);
		this.mediaStream = new NetStream(this.mediaConnection);
		this.mediaStream["classInstance"] = this;
		this.mediaStream.setBufferTime(buffertime);
		this.videoObject.attachVideo(this.mediaStream);
		this.mediaStream.onStatus.classInstance = this;
		this.mediaStream.onStatus = this.statusMedia;
		this.mediaStream.onMetaData.classInstance = this;
		this.mediaStream.onMetaData = this.onMeta;
		this.mainbuffertime = buffertime2;
	}
	public function setPlaylist(value:Object) {
		Playlist = value;
	}
	private function onMeta(infoObject:Object) {
		///if (infoObject.duration && !this.classInstance.totalPlayTime) {
		this.classInstance.totalPlayTime = Number(infoObject.duration);
		this.classInstance.VideoWidth = Number(infoObject.width);
		this.classInstance.VideoHeight = Number(infoObject.height);
		//this.classInstance.controller._parent.audioSign.Line.setText("Duration: "+this.classInstance.controller.timeConvert(this.classInstance.totalPlayTime));
		//this.classInstance.controller._parent.audioSign.Line.Init();
		//}
	}
	///////// получение куска ХМЛ
	function playListItem(xm:XMLNode, maxnodex) {
		delete closed;
		this.videoObject.clear();
		this.playstatus = false;
		this.file = xm.attributes.url;
		this.info = xm;
		UI.showName(xm.attributes.name);
		UI.showIndex(xm.attributes.index, maxnodex);
		playMedia();
	}
	public function playMedia() {
		//		if (!this.attached) {
		//this.mediaConnection.connect(null);
		////////////////////////////////-------------------------
		playstatus = true;
		this.attached = true;
		this.mediaStream.play(this.file);
		////----------------------------------------------------- 
		//stopMedia();
		//		}
	}
	public function enablePlay() {
		Playlist.currItem();
	}
	public function stopMedia() {
		//if (playstatus) {
		this.pauseMedia(true);
		this.resumeMedia(0);
		this.videoObject.clear();
		playstatus = false;
		//}
	}
	public function brakeLoad() {
		//stopMedia()
		closed = true;
		mediaStream.close();
	}
	public function pauseMedia(value) {
		this.mediaStream.pause(value);
		playstatus = false;
	}
	public function resumeMedia(value) {
		if (!(value+1)) {
			if (closed) {
				trace("Тут заново восстанавливаем проиграывание");
				playMedia();
			} else {
				trace("Играем");
				this.playstatus = true;
				this.mediaStream.pause();
			}
		} else {
			trace("Ищем "+value+"  "+totalPlayTime);
			// проверка погрешности и ошибки ос временем
			var buffer = 3;
			if (value+buffer<=totalPlayTime) {
				this.mediaStream.seek(value);
			}
		}
		//
	}
	function statusMedia(infoObject) {
		for (var prop in infoObject) {
			//trace("\t"+prop+":\t"+infoObject[prop]);
			this.classInstance.UI.analyzer(infoObject[prop]);
		}
	}
	/////////////////////////////////
	public function getLoadedTime() {
		return (this.mediaStream.bufferLength);
	}
	public function getLoaded() {
		return (this.mediaStream.bytesLoaded);
	}
	public function getTotal() {
		if (Math.round(this.mediaStream.bytesTotal) != ex_size) {
			//controller._parent.DT.text = "Changed!!!!";
		}
		return (this.mediaStream.bytesTotal);
	}
	public function getCurrentPosition() {
		return (this.mediaStream.time);
	}
	////////////////////////////////////////////////////////
	public function getNextItem():Boolean {
		return (Playlist.nextItem());
	}
	public function getPrevItem():Boolean {
		return (Playlist.prevItem());
	}
	public function getFirstItem() {
		Playlist.firstItem();
	}
	public function singleMode() {
		SingleMode = true;
		UI.singleMode();
	}
}
