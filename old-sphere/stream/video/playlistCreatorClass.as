class playlistCreatorClass {
	// создание внутреннего ХМЛ//
	public var MainXML:XML;
	public var ItemsBank:Object;
	private var indexator:Number = -1;
	function playlistCreatorClass() {
	}
	public function Init(value:String) {
		MainXML = new XML("<playlist type='"+value+" ' />");
	}
	/*
	d    2908159
	ordernum 4000
	type image
	level_id 0
	parent_id null
	user_id 1001
	cryptid fszf4ivdl8
	*/
	// Стартовый конвертер
	public function convert(value:Object) {
		var ansNode = makeANode(value);
		indexator++;
		ansNode.attributes.index = indexator;
		MainXML.firstChild.appendChild(ansNode);
		trace(MainXML);
	}
	private function makeANode(value:Object) {
		// возвращаем готовую нодину
		trace(value.Id+"  "+value.TypeId);
		switch (value.TypeId) {
		case "1" :
			return (makeImageNode(value));
			break;
		case "2" :
			return (makeVideoNode(value));
			break;
		case "3" :
			return (makeAudioNode(value));
			break;
		}
	}
	///////////////////////////////////////
	private function makeImageNode(value:Object) {
		var TNODE = new XML("<object type='image' id='"+value.Id+"' user_id='"+value.UserId+"' crypt_id='"+value.CryptId+"' name='"+value.Name+"' ordernum ='"+value.OrderNum+"'/>");
		return (TNODE.firstChild);
	}
	private function makeVideoNode(value:Object) {
		var TNODE = new XML("<object type='video' id='"+value.Id+"' user_id='"+value.UserId+"' name='"+value.Name+"' duration='"+value.Duration+"' url='"+value.filename+"'/>");
		return (TNODE.firstChild);
	}
	private function makeAudioNode(value:Object) {
		for (var o in value) {
			trace(o+"   "+value[o]);
		}
		var TNODE = new XML("<object type='audio' id='"+value.Id+"' user_id='"+value.UserId+"' crypt_id='"+value.CryptId+"' name='"+value.Name+"' ordernum ='"+value.OrderNum+"' duration='"+value.Duration+"'/>");
		return (TNODE.firstChild);
	}
	////////////////////////////////////////////////////////////////
	public function getXML() {
		return (MainXML);
	}
}
