<?php
/**
 * TextFileTraceListener class.
 * 
 * @author Akzhan Abdulin, Anton Fedchin DestinySphere GmbH
 * @copyright DestinySphere GmbH
 * 
 */

if(!defined('DS_SCRIPT_ROOT'))
{
	define('DS_SCRIPT_ROOT', '.', true);
}

require_once constant('DS_SCRIPT_ROOT').'/classes/Core/TraceListener.class.php';

class TextFileTraceListener
	extends TraceListener
{	
	/** @var string text trace file name */
	var $fileName;
	
	/** @var integer */
	var $maxFileSize;
	
	function _GetRealPath($fileName)
	{
/*
		if(!strpos($fileName, '/'))
		{
			$fileName = '././'. $fileName;
		}
echo "Construct::fileName = ".$fileName."<br>";
		$fileName = realpath($fileName);
echo "Construct::fileName2 = ".$fileName."<br>";	
*/
		return $fileName;
	}
	
	function TextFileTraceListener($fileName, $maxFileSize = 0)
	{

		assert(!empty($fileName) && is_string($fileName));
		
		assert(@touch($fileName));
		$fileName = TextFileTraceListener::_GetRealPath($fileName);
		assert(!empty($fileName));
		assert(is_integer($maxFileSize) && ($maxFileSize >= 0));		

		$this->fileName = $fileName;
		$this->maxFileSize = $maxFileSize;
	}

	function _handleGrow()
	{
		
	}
	
	function _formatMessage($message)
	{
		return $message."\r\n";
	}
	
	function writeMessage($message)
	{
		if($this->maxFileSize > 0 && (filesize($this->fileName) > $this->maxFileSize))
		{
			$this->_handleGrow();
		}
		$message = $this->_formatMessage($message);
		$fd = fopen($this->fileName, "at");
		assert($fd != false);
		
		flock($fd, LOCK_SH);
		fwrite($fd, $message);
		fflush($fd);
		flock($fd, LOCK_UN);
		fclose($fd);
	}
			
}

?>