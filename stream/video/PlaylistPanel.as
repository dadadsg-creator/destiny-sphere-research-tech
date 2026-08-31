class PlaylistPanel extends MovieClip {
	//-------------------------------------//
	//private var _global.TaskRoot:MovieClip;
	private var container:MovieClip;
	private var slots:MovieClip;
	private var scroller:MovieClip;
	private var rB:MovieClip;
	private var lB:MovieClip;
	public var zone:MovieClip;
	//---------PRESETS --------------------------//
	private var type:String;
	private var icon_width:Number;
	public var max_icons:Number;
	public var max_slots:Number;
	private var scrollerMAINWIDTH:Number;
	private var scrollStep:Number;
	private var x_dim:Number;
	private var y_dim:Number;
	private var y_space:Number;
	private var slotName:String;
	private var abstand:Number;
	public var ViewMode:Array;
	//----------STAFF----------------------------//
	public var focus_id:String;
	public var anode:XMLNode;
	public var ELEMENTS:Number;
	public var start_index:Number;
	private var build_init:Boolean;
	public var lock:Boolean;
	private var lastIndex:Number;
	private var alignHorizontal:Boolean;
	// номер последнего из судествующих элементов
	private var Playlist:Object;
	//------------------------------------//
	function PlaylistPanel() {
		/*
		this.addProperty("dat", getItems, setItems);
		function setItems(value) {
		start_index = 0;
		create(value);
		}
		function getItems(value) {
		return (value);
		}
		*/
		abstand = (container._width-max_icons*icon_width)/(max_icons);
		lB.onPress = onLeft;
		rB.onPress = onRight;
		//this.onMouseMove = detect;
		//this.onMouseMove = addDetecting;
	}
	public function setInfo(value:XML) {
		start_index = 0;
		create(value.firstChild);
	}
	public function setPlaylist(value:Object) {
		Playlist = value;
	}
	private function INIT() {
		// инициализация переменных
		scroller.MAINWIDTH = scrollerMAINWIDTH;
		scrollStep = 1;
		scroller.step = scrollStep;
	}
	public function create(value:XMLNode):Void {
		destroy();
		if (value) {
			this.anode = value;
		}
		ELEMENTS = this.anode.childNodes.length;
		var visColumns = Math.ceil(max_icons/scrollStep);
		var totalColumns = Math.ceil(ELEMENTS/scrollStep);
		if (start_index+max_icons>ELEMENTS) {
			if (ELEMENTS-max_icons>=0) {
				start_index = (totalColumns-visColumns)*scrollStep;
			} else {
				start_index = 0;
			}
		}
		var pcount = -1;
		if (y_dim>1) {
			alignHorizontal = false;
			var tx = 0;
			var ty = -1;
			for (var c = start_index; c<ELEMENTS; c++) {
				ty++;
				if (ty>y_dim-1) {
					tx++;
					ty = 0;
				}
				pcount++;
				var obj = this.anode.childNodes[c];
				drawObject(obj, pcount, tx, ty);
				if (pcount>=max_icons-1) {
					break;
				}
			}
		} else {
			alignHorizontal = true;
			var tx = -1;
			var ty = 0;
			for (var c = start_index; c<ELEMENTS; c++) {
				pcount++;
				tx++;
				var obj = this.anode.childNodes[c];
				drawObject(obj, pcount, tx, ty);
				if (pcount>=max_icons-1) {
					break;
				}
			}
		}
		lastIndex = pcount;
		scroller.setData(max_icons, start_index, ELEMENTS);
		checkArrows();
		drawSlots();
		RefreshInit();
	}
	private function drawSlots() {
		if (!build_init) {
			build_init = true;
			var t = -1;
			for (var i = 0; i<x_dim; i++) {
				for (var z = 0; z<y_dim; z++) {
					t++;
					slots.attachMovie(slotName, "slot"+t, t, {_x:icon_width*i+abstand*i, _y:icon_width*z+y_space*z, id:t});
				}
			}
		}
	}
	private function addProppPassive(value:MovieClip) {
		value.onDragOut = function() {
			//_global.HINT.hide();
			this.Focus(false);
		};
		value.onMouseUp = function() {
		};
		value.onPress = function() {
		};
		value.onRelease = function() {
			this["classItem"].Playlist.byIdItem(this.info);
		};
		value.onRollOver = function() {
			this.Focus(true);
			//_global.HINT.show(this.info);
		};
		value.onRollOut = function() {
			this.Focus(false);
			//_global.HINT.hide();
		};
		value.Focus = function(value) {
			if (value) {
				this.filters = _global.activeFilter;
			} else {
				this.filters = _global.passiveFilter;
			}
		};
	}
	private function destroy() {
		for (var o in container) {
			container[o].removeMovieClip();
		}
	}
	public function scroll_left() {
		if (start_index-scrollStep>=0) {
			start_index -= scrollStep;
			create();
		}
	}
	public function scroll_right() {
		//if (start_index+scrollStep<=anode.childNodes.length-max_icons) {
		start_index += scrollStep;
		create();
		//}
	}
	private function makeChange(value:XMLNode) {
		value.attributes.changed = "true";
	}
	///////////////////////////Extended functions////////////////////////////////////////
	private function haveToAdd(value:XMLNode) {
		makeChange(value);
		// Вызывается перед добавлением.
	}
	private function drawObject(obj:XMLNode, pcount:Number, tx:Number, ty:Number) {
		// вызывается для аттача клипа на уровень
	}
	private function detect() {
		// Определяет идет ли речь о возможном добавлении
	}
	private function RefreshInit() {
		// вызывается каждое обновление для использования в подклассах
	}
	private function checkArrows() {
		if (start_index == 0) {
			lB.enabled = false;
			lB.gotoAndStop(1);
		} else {
			lB.enabled = true;
			lB.gotoAndStop(2);
		}
		if (start_index>=ELEMENTS-max_icons || ELEMENTS<=max_icons) {
			rB.enabled = false;
			rB.gotoAndStop(1);
		} else {
			rB.enabled = true;
			rB.gotoAndStop(2);
		}
	}
	private function onLeft() {
		this._parent.scroll_left();
	}
	private function onRight() {
		this._parent.scroll_right();
	}
	public function focusOn(value:String, attr:String) {
	}
	private function showDuration(value:Number) {
		var minutes = Math.floor(value/60);
		var seconds = value-minutes*60;
		var str = addNul(minutes)+":"+addNul(seconds);
		return (str);
	}
	private function addNul(value:Number) {
		var num = value.toString();
		return ("00".substr(num.length, 1)+num);
	}
}
