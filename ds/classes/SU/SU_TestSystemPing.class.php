<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestSystemPing extends SU_TestSystemBase
{
	private $serversList = array();	
	
	private $testPacketsCount = 3; 
	
	public function __construct()
	{
		assert(key_exists('TEST_SYSTEM_SERVERS_LIST', $GLOBALS) && is_array($GLOBALS['TEST_SYSTEM_SERVERS_LIST']));
		
		$this->serversList = $GLOBALS['TEST_SYSTEM_SERVERS_LIST'];
		
		$this->testPacketsCount = constant('TEST_SYSTEM_PING_PACKETS_COUNT');
	}
	
	public function exec()
	{
		foreach($this->serversList as $IP => $serverName)
		{
			$string = 'Ping ['.$serverName.'] send '.$this->testPacketsCount.' packets';
			$this->_outString($string, 4);
			$packetLoses = $this->_ping($IP);

			if ( $packetLoses == -1)
			{
				$result = "Cant do ping <<<<<<<<<<<<< ERROR!";
				$this->_writeError('P', "Cant do ping of server [$serverName]", $serverName);
				$this->_outString($result, 4);
				continue;
			}
			
			if ( $packetLoses < 0 || $packetLoses > 100)
			{
				$result = "Wrong amoutn loses packadges - $packetLoses% server [$serverName] <<<<<<<<<<<<< ERROR!";
				$this->_writeError('P', "Wrong amoutn loses packadges", $serverName);
				$this->_outString($result, 4);
				continue;
			}

			if($packetLoses > 70)
			{
				$result = "Loses: $packetLoses% server [$serverName] <<<<<<<<<<<<< ERROR!";
				$this->_writeError('P', "Ping failed!", $serverName);
			}else{
				$result = "Loses: $packetLoses% - OK!";
			}
			$this->_outString($result, 4);

		}
	}

	private function _ping($IP)
	{
		if(empty($IP))
		{
			$result = "Empty IP <<<<<<<<<<<<< ERROR!";
			$this->_writeError('Ping', "Empty IP!");
			$this->_outString($result, 4);
		}

		$out = '';

		$command = "ping -c ".$this->testPacketsCount." $IP";
		$str = exec($command, &$out);

		$packetLoses = $this->_parseResult($out);

		return $packetLoses;
	}

	private function _parseResult($out)
	{
		if(!is_array($out))
		{
			return -1;
		}
		
		$str = implode(' ', $out);
		ereg ("[0-9]{0,3}\%", $str, $regs);
		
		if (count($regs) != 1)
		{
			return -1;
		}

		return (int)$regs[0];
	}
}

?>