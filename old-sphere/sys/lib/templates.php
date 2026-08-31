<?php

// template class v1.1 by Dennis S.Davidoff (mailto: null@inbox.ru)
// thanks goes to Fil <fil@apb.farlep.net> for some hints and changes
// v1.1 - changed function parseit and getparsed

class template 
{
	var $ver = "Class_template v1.2 by Andrew";
	var $filelist = array(), $assign = array();
	var $root, $arr, $delimiters;
	
	function template ($delimit = "") {
		if (!empty($delimit)) {
			if (preg_match ("/^.{2}$/i",$delimit)) {
				$this->delimiters = $delimit[0];
				$this->delimiters = $delimit[1];
			} else $this->killme("Specify only two chars for delimiter!");
		} else $this->delimiters(); 
	}

	function init($arr = "", $root = "") {
		if (empty($arr) || !is_array($arr)) $this->killme("init(): Gimme an associative array first");
		$this->setroot($root); $this->listit($arr);
	}

	function delimiters($d1 = "", $d2 = "") {
		if (!empty($d1) && !empty($d2)) {
			$this->delimiters[0] = preg_quote($d1);
			$this->delimiters[1] = preg_quote($d2);
		} else { 
			$this->delimiters[0] = preg_quote("<!--");
			$this->delimiters[1] = preg_quote("-->");
		}
	}
	
	function exist($arr) {
		while (list($var1, $file) = each ($arr)) 
			if (!file_exists($file)) $this->killme("exist(): Error: File doesn't exist: \"$file\"!\n");	
	}
	
	function setroot($root = "") {
		if (is_string($root)) {
			$this->root = preg_replace ("/([^\/])$/", "\\1/", (empty($root) ? "./" : $root));
			if (!is_dir($this->root)) $this->killme("setroot(): $this->root is not a directory!");
		} else $this->killme("setroot(): Specified parameter for setroot() is not a string!\n");
	}
	
	function killme($msg = "") {
		die ((empty($msg)?"killed":$msg)."<br>\n".$this->ver);
	}
	
	function assign($arr = "", $val = "") {
//		if (empty($arr)) $this->killme("assign(): no given parameters");
		if (!is_array($arr)) $this->assign["$arr"] = $val;
		else while (list($key, $val) = each ($arr)) if (!empty($key)) $this->assign["$key"] = $val;
	}
	
	function listit($list = "") { 
		if (!is_array($list) || empty($list)) $this->killme("listit(): template filelist is empty!");
		else {
			while(list($descr, $file) = each($list)) if (!empty($descr)) $this->filelist["$descr"] = $this->root.$file;
			$this->exist($this->filelist);
		}
	}
	
	function parseit($data = "", $temp = "") {
		if (empty($data)) $this->killme("parseit(): Dunno what to do!");
		
		$f = (!empty($this->filelist["$data"])) ? file($this->filelist["$data"]) : $data;
		
		if (!empty($temp)) $this->assign["$temp"] .= $this->getparsed($f);
		else $this->arr .= $this->getparsed($f);
	}
	
	function getparsed($data = "") {
		//if (empty($data)) $this->killme("getparsed(): Gimme some data to parse it :)");
		if (empty($data)) return "";
		if (is_array($data)) $data = implode("", $data);
		reset($this->assign);
		while (list($key, $val) = each ($this->assign)) {
			$val = str_replace("\$","\$&nbsp;",$val);
			$data = preg_replace("/".$this->delimiters[0]."\s*".
			preg_quote($key)."\s*".$this->delimiters[1]."/", $val, $data);
		}
		return $data;
	}

	function addtolist ($d = "", $name = "") {
		if (empty($d) || !is_string($name)) $this->killme("addtolist: Descriptor or filename is not a string!");
		else {
			if (!empty($d) && !empty($name)) { 
				$this->filelist["$d"] = $this->root.$name;
				$this->exist($this->filelist);
			} else $this->killme("addtolist: Descriptor or filename is empty!");
		}
	}
	
	function freshall() {
		while(list($descr, $file) = each($this->filelist)) unset($this->filelist["$descr"]);
		while(list($descr, $tag) = each($this->assign)) unset($this->filelist["$tag"]);
		unset($this->root);
		unset($this->arr);
	}
	
	function fresh() { unset($this->arr); }
	function printit($me = "") { echo (empty($me)?$this->arr : $me); }

}

?>