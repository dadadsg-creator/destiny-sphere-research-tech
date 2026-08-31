<?php $CVS_Version = '$Revision$ ; $Date$';

class Logger {
	
	public $filename;
	public $filehandle;
	
	function Logger ($filename) {
		$this->filename = $filename;		
	}
	
	function open () {
		$this->filehandle = fopen ($this->filename, "a+");
		if ($this->filehandle === false)
		  return false;
		else 
		  return true;
	}
	
	function close () {
		fclose ($this->filehandle);
	}
	
	function log ($user, $text) {		
		$this->open();
		$date_now = date ("m.d.y H:i:s");
		$textrow = $date_now . " ::: " .  $_SERVER['REMOTE_ADDR'] . " ::: " . $user . " ::: " . $text . "\r\n";		
		$res = fwrite ($this->filehandle, $textrow);		
		$this->close();
		return $res;
	}
}

?>