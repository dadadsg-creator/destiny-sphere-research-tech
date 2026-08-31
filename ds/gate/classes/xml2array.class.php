<?
class xml2Array {
   
   var $arrOutput = array();
   var $resParser;
   var $strXmlData;
   
   function parse($strInputXML) {
           $this->resParser = xml_parser_create ();
           xml_set_object($this->resParser,$this);
           xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
           
           xml_set_character_data_handler($this->resParser, "tagData");
       
           $this->strXmlData = xml_parse($this->resParser,$strInputXML );
           if(!$this->strXmlData) {
           		die(sprintf("XML error: %s at line %d, col %d",
           xml_error_string(xml_get_error_code($this->resParser)),
           xml_get_current_line_number($this->resParser), xml_get_current_column_number($this->resParser)));        		
           }
                           
           xml_parser_free($this->resParser);
           
           return $this->arrOutput;
   }
   function tagOpen($parser, $name, $attrs) {
       $tag=array("name"=>$name,"attrs"=>$attrs); 
       array_push($this->arrOutput,$tag);
   }
   
   function tagData($parser, $tagData) {
       if(trim($tagData)) {
           if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
               $this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $tagData;
           } 
           else {
               $this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
           }
       }
   }
   
   function tagClosed($parser, $name) {
       $this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
       array_pop($this->arrOutput);
   }
}


class assoc_array2xml
{
	var $text;
	var $arrays, $keys, $node_flag, $depth, $xml_parser;
	/*Converts an array to an xml string*/
	function array2xml($array)
	{
		//global $text;
		$this->text="<?xml version=\"1.0\" encoding=\"iso-8859-1\"?><array>";
		$this->text.= $this->array_transform($array);
		$this->text .="</array>";
		return $this->text;
	}

	function array_transform($array)
	{
		//global $array_text;
		foreach($array as $key => $value)
		{
			if(!is_array($value))
			{
				$this->text .= "<$key>$value</$key>";
			} else {
				$this->text.="<$key>";
				$this->array_transform($value);
				$this->text.="</$key>";
			}
		}
		return $array_text;
	}

	/*Transform an XML string to associative array "XML Parser Functions"*/
	function xml2array($xml)
	{
		$this->depth = -1;
		$this->xml_parser = xml_parser_create();
		xml_set_object($this->xml_parser, $this);
		xml_parser_set_option ($this->xml_parser,XML_OPTION_CASE_FOLDING,0);//Don't put tags uppercase
		xml_set_element_handler($this->xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($this->xml_parser,"characterData");
		
		if(!xml_parse($this->xml_parser,$xml,true)) 
		{
           return (sprintf("XML error: %s at line %d, col %d",
           		xml_error_string(xml_get_error_code($this->xml_parser)),
           		xml_get_current_line_number($this->xml_parser), 
           		xml_get_current_column_number($this->xml_parser)));        		
        }
		
		xml_parser_free($this->xml_parser);
		return $this->arrays[0];
	}

	function startElement($parser, $name, $attrs)
	{
		$this->keys[]=$name; //We add a key
		$this->node_flag=1;
		$this->depth++;
	}

	function characterData($parser,$data)
	{
		$key=end($this->keys);
		$this->arrays[$this->depth][$key]=iconv('utf-8', 'cp1251', $data);
		$this->node_flag=0; //So that we don't add as an array, but as an element
	}

	function endElement($parser, $name)
	{
		$key=array_pop($this->keys);
		//If $node_flag==1 we add as an array, if not, as an element
		if($this->node_flag == 1)
		{
			$this->arrays[$this->depth][$key] = $this->arrays[$this->depth+1];
			unset($this->arrays[$this->depth+1]);
		}
		$this->node_flag = 1;
		$this->depth--;
	}

}//End of the class

?>