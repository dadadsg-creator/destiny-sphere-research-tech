<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_Patch
{
	static public $common;
	static protected $logFileName = null;
	static public $onlyValidate = false;
	
	protected function _out($msg)
	{
		echo $msg."\n";
		flush();

		DebugTools::WriteToLog($msg."\n", $this->getLogFileName());
	}

	static public function GetLogFileName()
	{
		if (is_null(self::$logFileName))
		{
			self::$logFileName = 'MegaPatch_'.date("Y-m-d H_i_s").'.log';
			return self::$logFileName;
		} else {
			return self::$logFileName;
		}
	}
	
	public function getPatchesList()
	{
		self::_out("USED PATCHES:");
		
		self::_out("For UserInstance:");
		
		foreach(GP_PatchUserInstance::$userInstancePatches as $k=>$v)
		{
			self::_out("	$v");
		}
		
		self::_out("For Comb:");
		foreach(GP_PatchComb::$combPatches as $k=>$v)
		{
			self::_out("	$v");
		}		
	}

}

?>