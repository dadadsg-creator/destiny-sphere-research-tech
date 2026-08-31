<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/09/11 07:56:57 $';
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

class TextFileTraceListener
	extends TraceListener
{	
	
	function TextFileTraceListener($args)
	{
		$this->exp;
	}
	
	function writeMessage($category, $code, $args)
	{
		
	}
			
}

?>