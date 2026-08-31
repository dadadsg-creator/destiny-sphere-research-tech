<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/09/23 13:57:14 $';

// PHP4 -> PHP5

function domxml_new_xmldoc( $str ){

	return new domXML( $str );
}

function domxml_open_mem( $data ){
	
//$data = str_replace('encoding="utf-8"', 'encoding="windows-1251"', $data);
//SU_DebugTools::LogAll($data, 'XML_ENCODIND.log', 0);
	
	$dom = new domXML();
	$dom->loadXML( $data );
	
	return $dom;
}

class domXML {
	
	public $doc;	// object - link 2 XML document
	
	public $nodes;	// Array of objects
	
	function DomXML($str = "1.0") {
		
		$this->doc = new DOMDocument( $str);
	}
	
	function loadXML($var){
		
		$this->doc->loadXML($var);
	}
	
	function add_root($name){
				
		return  new nodeXML( $this->doc, $this->doc, true, $name );
	}
	
	function document_element(){
		
		return  new nodeXML( $this->doc, $this->doc, false, $this->doc->documentElement);
	}
	
	function get_elements_by_tagname( $name ){
		
		$nodes = $this->doc->getElementsByTagName( $name );
		
		$return = array();
		
		foreach($nodes as $node){
		
			$return[] = new nodeXML( $this->doc, '', false, $node );
		}
		
		return $return;
	}
	
	function dump_mem(){
		
		return $this->doc->saveXML();
	}
	
	function create_text_node( $text ){
		
		return  new nodeXML( $this->doc, $this->doc, false, $this->doc->createTextNode( $text ) );
	}
	
	function create_cdata_section( $text ){
		
		return  new nodeXML( $this->doc, $this->doc, false, $this->doc->createCDATASection( $text ) );
	}
}

class nodeXML {
	
	public $domXML;		// object - link 2 XML document
	public $parentNode;	// object - link 2 XML parent
	public $selfNode;		// object - link 2 XML itself
	
	public $tagname;
	
	public $attribs;
	
	
	/*
	** Конструктор класса.
	** - создает новый элемент
	** - создаем образ уже существующего элемента
	*/	
	function nodeXML( $domXML, $parent = '', $create = false, $name ){
		
		$this->domXML		= $domXML;
		$this->parentNode	= $parent;
		
		if( $create ) {
			
			$this->tagname	= $name;
					
			$this->selfNode	= $this->domXML->createElement( $this->tagname );

			$this->parentNode->appendChild( $this->selfNode );
			
		}else{
			
//			$GLOBALS['oh']->log2File('nodeName', $name->nodeName );
			
			$this->tagname	= $name->nodeName;
		
			$this->selfNode = $name;
		}
	}
	
	
	/*
	** Метод для созданию дочернего элемента
	*/
	function new_child($name, $val = ''){
		
		return  new nodeXML( $this->domXML, $this->selfNode, true, $name, $val );
	}
	
	
	/*
	** Метод для добавления дочернего элемента
	*/
	function append_child( $newChild ){
		
		//$self, 
		$this->selfNode->appendChild( 
				$newChild->selfNode 
			);
		
	}
	
	
	/*
	** Метод для добавления аттрибута элемента
	*/
	function set_attribute($name, $value){
		$value = urldecode($value);
		
		$this->selfNode->setAttribute($name, urlencode($value));
	}
	
	/*
	** Метод для добавления аттрибута элемента без urldecode
	*/
	function set_attribute_without_urldecode($name, $value){
		
		$this->selfNode->setAttribute($name, $value);
	}
	
	
	/*
	** Метод для получения аттрибута по имени
	*/
	function get_attribute( $name ){
		
		return $this->selfNode->getAttribute( $name );
	}
	
	
	/*
	** Метод для получения дочерних элементов по имени
	*/
	function get_elements_by_tagname( $name ){
		
		$nodes = $this->selfNode->getElementsByTagName( $name );
		
		$return = array();
		
		foreach($nodes as $node){
		
			$return[] = new nodeXML( $this->domXML, $this->selfNode, false, $node );
		}
		
		return $return;
	}
	
	
	/*
	** Метод для сожержимого текущего элемента
	*/
	function get_content(){
		
		return $this->selfNode->firstChild->nodeValue;
	}
}
?>