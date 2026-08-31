class videoUIClass extends MovieClip {
	public var toolline:MovieClip;
	public var sc:MovieClip;
	public var Player:Object;
	///////////////////////////////////////
	public var playlistUI:MovieClip;
	public var hitZone:MovieClip;
	public var volumeControll:MovieClip;
	public var progressbar:MovieClip;
	private var begun:MovieClip;
	private var openB:MovieClip;
	private var PauseB:MovieClip;
	private var PlayB:MovieClip;
	private var NextB:MovieClip;
	private var PrevB:MovieClip;
	private var muteonB:MovieClip;
	private var muteoffB:MovieClip;
	private var maxscreenB:MovieClip;
	private var closemaxscreen:MovieClip;
	private var replayWin:MovieClip;
	private var tlCover:MovieClip;
	private var vi:MovieClip;
	private var TimeDT:TextField;
	private var ListDT:TextField;
	private var NameDT:TextField;
	private var buffer_pc:Number;
	private var play_pc:Number;
	private var progresswidth:Number;
	private var dragFlag:Boolean;
	private var lastVolume:Number;
	private var lastPlayHeadTime:Number;
	public var SoundVolume:Sound;
	private var Init:Boolean;
	private var SO:SharedObject;
	private var SingleMode:Boolean;
	//////////////////////////////////////////////
	function videoUIClass() {
		this._visible = false;
		toolline.leftblock.xx = toolline.leftblock._x;
		toolline.centerblock.xx = toolline.centerblock._x;
		toolline.rightblock.xx = toolline.rightblock._x;
		//////////////////////////////////////////////////////
		SO = SharedObject.getLocal("videoPlayerVolume", "/");
		SoundVolume = new Sound(_root);
		if (SO.data.videoPlayerVolume) {
			var rate = Number(SO.data.videoPlayerVolume);
			setVolume(rate);
		} else {
			setVolume(10);
		}
		progressbar = toolline.leftblock.progressbar;
		//		progressbar.onPress = LineOnPress;
		progresswidth = progressbar._width;
		////////////////////////////////////////////////////
		begun = toolline.leftblock.begun;
		PauseB = toolline.centerblock.PauseB;
		PlayB = toolline.centerblock.PlayB;
		NextB = toolline.centerblock.NextB;
		PrevB = toolline.centerblock.PrevB;
		vi = toolline.centerblock.vi;
		openB = toolline.rightblock.openB;
		maxscreenB = toolline.rightblock.maxscreenB;
		closemaxscreen = toolline.rightblock.closemaxscreen;
		muteonB = toolline.rightblock.muteonB;
		muteoffB = toolline.rightblock.muteoffB;
		volumeControll = toolline.rightblock.volumeControll;
		volumeControll.classItem = this;
		volumeControll.gotoAndStop(11-rate);
		TimeDT = toolline.rightblock.TimeDT;
		ListDT = toolline.rightblock.ListDT;
		NameDT = toolline.leftblock.NameDT;
		////////////////////////////////////////
		muteonB._visible = true;
		muteoffB._visible = false;
		closemaxscreen._visible = false;
		////////////////////////////////////////
		begun.onPress = BegunPress;
		begun.onRelease = BegunRelease;
		begun.onReleaseOutside = BegunRelease;
		begun.classItem = this;
		////////////////////////////////////////
		progressbar.onPress = LineOnPress;
		progressbar.classItem = this;
		////////////////////////////////////////
		PlayB.onPress = onPlayPress;
		PlayB.classItem = this;
		///////////////////////////////////////
		PauseB.onPress = onPausePress;
		PauseB.classItem = this;
		///////////////////////////////////////
		openB.onPress = onOpenPress;
		openB.classItem = this;
		////////////////////////////////
		maxscreenB.onPress = onMaxPress;
		maxscreenB.classItem = this;
		closemaxscreen.onPress = closeWindow;
		closemaxscreen.classItem = this;
		///////////////////////////////////////////
		muteonB.onPress = onMute;
		muteonB.classItem = this;
		muteoffB.onPress = onUnMute;
		muteoffB.classItem = this;
		////////////////////////////////////////
		NextB.onPress = onNextPress;
		NextB.classItem = this;
		PrevB.onPress = onPrevPress;
		PrevB.classItem = this;
		//////////////////////////////////
		hitZone.target = playlistUI;
		hitZone.useHandCursor = false;
		hitZone.onMouseMove = checkRollOver;
		//////////////////////////////////////
		replayWin._visible = false;
		replayWin.onPress = onReplayPress;
		replayWin.classItem = this;
		/////////////////////////////////////
		tlCover.useHandCursor = false;
		tlCover.onPress = function() {
		};
		////////////////////////////////
		vi._visible = false;
		PauseB._visible = false;
		PlayB._visible = true;
		////////////////////////////////////
		/////// Проверка на полноэкранный режим.
		if (_global.TaskRoot.fullscreen) {
			maxscreenB._visible = false;
			closemaxscreen._visible = true;
		}
	}
	function checkRollOver() {
		var cx = this._width/2;
		var cy = 0;
		var dist = Math.sqrt((_parent._xmouse-cx)*(_parent._xmouse-cx)+(_parent._ymouse-cy)*(_parent._ymouse-cy));
		if (dist<Stage.width/2+10) {
			this["target"].onEnterFrame = nextFrame;
		} else {
			this["target"].onEnterFrame = prevFrame;
		}
	}
	public function setPlayerClass(value:Object) {
		Player = value;
	}
	public function makeFit() {
		var _w = Stage.width;
		var _h = Stage.height;
		toolline._y = _h-40;
		if (_w>313) {
			toolline.back._width = _w;
			toolline.leftblock._x = _w/2-toolline.leftblock._width/2;
			//toolline.leftblock._x = toolline.leftblock.xx;
			//toolline.centerblock._x = _w/2-toolline.centerblock._width/2;
			toolline.rightblock._x = _w-toolline.rightblock._width;
			tlCover._x = toolline._x;
			tlCover._y = toolline._y;
			tlCover._width = _w;
			///-------------------------------------------
			//playlistUI._x = _w/2-156;
			playlistUI.listpanel.back._width = _w;
			playlistUI.listpanel.M.scroller.MAINWIDTH = _w-30;
			playlistUI.listpanel.M.endNum._x = playlistUI.listpanel.M.scroller._x+_w-30;
			playlistUI.listpanel.M.rB._x = playlistUI.listpanel.M.endNum._x+8;
			///-------------------------------------------
			hitZone._width = _w;
			hitZone._height = _h-40;
			hitZone._x = hitZone._y=0;
			replayWin._x = (_w-replayWin._width)/2;
			replayWin._y = ((_h-40)-replayWin._height)/2;
		} else {
			playlistUI._x = 0;
			toolline.leftblock._x = toolline.leftblock.xx;
			toolline.centerblock._x = toolline.centerblock.xx;
			toolline.rightblock._x = toolline.rightblock.xx;
			hitZone._width = 313;
			hitZone._height = 234.8;
		}
		if (_w<(_h-40)) {
			sc._xscale = sc._yscale=_w/313*100;
		} else {
			sc._xscale = sc._yscale=(_h-40)/(234.8/100);
		}
		sc._x = _w/2-sc._width/2;
		sc._y = (_h-40)/2-sc._height/2;
	}
	/////////////////////////////////////////////////////
	function onEnterFrame() {
		showBuffer();
		if (!dragFlag) {
			showPlayProgressPassive();
		} else {
			if (lastPlayHeadTime != getPlayHeadSeconds()) {
				// Тут чтобы не отсылать когда головкап плеера не перемещается
				lastPlayHeadTime = getPlayHeadSeconds();
				Player.resumeMedia(getPlayHeadSeconds());
				showTime();
			}
		}
	}
	function showBuffer() {
		buffer_pc = (Player.getLoaded()/(Player.getTotal()/100));
		if (buffer_pc) {
			progressbar.bufferbar._visible = true;
			begun._visible = true;
			progressbar.bufferbar._width = ((progresswidth/100)*buffer_pc);
		} else {
			progressbar.bufferbar._visible = false;
			begun._visible = false;
		}
	}
	function showPlayProgressPassive() {
		var play_pc = showTime();
		if (!play_pc && play_pc != 0) {
			return;
		}
		var tx = progressbar._x+((progressbar._width-begun._width)/100)*play_pc;
		if (tx>=progressbar._x+progressbar._width-begun._width) {
			begun._x = progressbar._x+progressbar._width-begun._width;
		} else {
			begun._x = tx;
		}
	}
	function showTime() {
		var pc = (Player.getCurrentPosition()/(Player.totalPlayTime/100));
		if (!pc && pc != 0) {
			return (false);
		}
		play_pc = Math.round(pc);
		if (Player.getLoaded()) {
			if (Player.getCurrentPosition()>Player.totalPlayTime) {
				TimeDT.htmlText = timeConvert(Player.totalPlayTime)+" / <FONT color='#275CAA'>"+timeConvert(Player.totalPlayTime)+"</FONT>";
			} else {
				TimeDT.htmlText = timeConvert(Player.getCurrentPosition())+" / <FONT color='#275CAA'>"+timeConvert(Player.totalPlayTime)+"</FONT>";
			}
		} else {
			TimeDT.htmlText = "00:00 / <FONT color='#275CAA'>00:00</FONT>";
		}
		return (play_pc);
	}
	function getPlayHeadPosition() {
		var realPercent = (begun._x-progressbar._x)/((progressbar._width-begun._width)/100);
		return (realPercent);
	}
	function getPlayHeadSeconds() {
		var realSeconds = (Math.round((Player.totalPlayTime/100)*getPlayHeadPosition()));
		if (realSeconds>Player.totalPlayTime) {
			realSeconds == Player.totalPlayTime;
		}
		return (realSeconds);
	}
	////////////////////////////////////////////////////////////////
	function stopStartOnClick() {
		this._parent.Player.pauseMedia();
	}
	/// BUTTONES////////////////////////////////////////////////////////////////////////////////
	function BegunPress() {
		this.nextFrame();
		this.startDrag(false, this["classItem"].progressbar._x, this._y, this["classItem"].progressbar._x+this["classItem"].progressbar._width-this._width, this._y);
		this["classItem"].dragFlag = true;
	}
	function BegunRelease() {
		this.prevFrame();
		this.stopDrag();
		this._parent.Player.commonstatus = "full";
		this._parent.Player.resumeMedia(this._parent.getPlayHeadSeconds());
		this["classItem"].dragFlag = false;
		//////////////////////////////////////
		if (this._parent.Player.getCurrentPosition()>=this._parent.Player.totalPlayTime) {
			//this._parent.endOfPlay();
		}
	}
	function onReplayPress() {
		this["classItem"].sc._visible = true;
		this._visible = false;
		this["classItem"].PauseB._visible = true;
		this["classItem"].PlayB._visible = false;
		this["classItem"].Player.resumeMedia(0);
		this["classItem"].Player.pauseMedia();
		this["classItem"].tlCover.onEnterFrame = prevFrame;
	}
	function LineOnPress() {
		this["classItem"].dragFlag = true;
		var pc = Math.round(this._xmouse/(this._width/100));
		this["classItem"].begun._x = this._x+((this._width-this["classItem"].begun._width)/100)*pc;
		this["classItem"].Player.resumeMedia(this["classItem"].getPlayHeadSeconds());
		this["classItem"].dragFlag = false;
	}
	public function setVolume(value) {
		SoundVolume.setVolume(value*10);
		lastVolume = value*10;
		SO.data.videoPlayerVolume = value;
	}
	public function onNextPress() {
		this["classItem"].Player.getNextItem();
	}
	public function onPrevPress() {
		this["classItem"].Player.getPrevItem();
	}
	public function onUnMute() {
		this["classItem"].muteonB._visible = true;
		this["classItem"].muteoffB._visible = false;
		this["classItem"].volumeControll.enabled = true;
		this["classItem"].volumeControll._alpha = 100;
		this["classItem"].SoundVolume.setVolume(this["classItem"].lastVolume);
	}
	public function onMute() {
		this["classItem"].SoundVolume.setVolume(0);
		this["classItem"].volumeControll.enabled = false;
		this["classItem"].volumeControll._alpha = 30;
		this["classItem"].muteonB._visible = false;
		this["classItem"].muteoffB._visible = true;
	}
	function onPlayPress() {
		this._visible = false;
		this["classItem"].PauseB._visible = true;
		/// стартовало ли видео
		if (!this["classItem"].Player.attached) {
			this["classItem"].Player.enablePlay();
		} else {
			this["classItem"].Player.resumeMedia();
		}
		if (!Init) {
			Init = true;
		}
	}
	function onPausePress() {
		this["classItem"].Player.pauseMedia(true);
		this["classItem"].PlayB._visible = true;
		this["classItem"].PauseB._visible = false;
	}
	public function onMaxPress() {
		this["classItem"].PauseB.onPress();
		this["classItem"].Player.brakeLoad();
		_global.EX.openMax();
	}
	public function closeWindow() {
		_global.EX.closeMax();
	}
	public function onOpenPress() {
		trace("OPen");
	}
	function fullWin() {
		this._parent._parent.resizer();
	}
	private function endOfPlay() {
		Player.pauseMedia();
		if (!Player.getNextItem()) {
			if (SingleMode) {
				replayWin._visible = true;
				sc._visible = false;
				PlayB._visible = true;
				PauseB._visible = false;
				Player.pauseMedia(true);
				tlCover.gotoAndPlay(2);
			} else {
				Player.getFirstItem();
			}
		}
	}
	public function analyzer(value:String):Void {
		switch (value) {
		case "NetStream.Play.Stop" :
			if (!dragFlag) {
				this.Player.commonstatus = "stop";
				endOfPlay();
			}
			break;
		case "NetStream.Play.Start" :
			//_global.TaskRoot.screen.preview.onEnterFrame = _global.TaskRoot.screen.preview.fadeOut;
			//_global.TaskRoot.controllbar.minscreen.enabled = _global.TaskRoot.controllbar.maxscreen.enabled=true;
			this.Player.commonstatus = "start";
			PlayB._visible = false;
			PauseB._visible = true;
			/*
			preloader.swapDepths(0);
			preloader.removeMovieClip();
			
			BigPlay._alpha = 100;
			
			BufferDT.text = "";
			*/
			break;
		case "NetStream.Buffer.Empty" :
			if (this.Player.commonstatus != "stop") {
				this.Player.mediaStream.setBufferTime(this.Player.mainbuffertime);
			}
			break;
		case "NetStream.Play.StreamNotFound" :
			this.Player.commonstatus = "error";
			this.Player.info.attributes.error = "true";
			this.Player.getNextItem();
			break;
		case "NetStream.Buffer.Full" :
			this.Player.commonstatus = "full";
			break;
		}
	}
	public function timeConvert(value:Number):String {
		var t_seconds = value;
		var mins = Math.floor(t_seconds/60);
		var hours = Math.floor(mins/60);
		var secs = Math.floor(t_seconds-mins*60);
		return ("0".substr(String(mins).length-1, 1)+mins+":"+"0".substr(String(secs).length-1, 1)+secs);
	}
	public function getVideoObject() {
		return (sc.screen.myvideo);
	}
	public function showName(value:String) {
		NameDT.text = value;
	}
	public function showIndex(value1, value2) {
		ListDT.text = (value1+1)+" / "+value2;
	}
	public function singleMode() {
		SingleMode = true;
		playlistUI._visible = false;
		ListDT._visible = false;
		//TimeDT._x = 39;
		NextB._visible = PrevB._visible=openB._visible=false;
	}
}
