<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_ProtocolLog
{
	static public function Exec($out)
	{
		//Логируем протокол
		if(constant('LOGING_PROTOCOL_FLAG') || in_array($_SESSION['USERNAME'], $GLOBALS['USERS_FOR_LOG_PROTOCOL']))
		{

			if(key_exists('s_xmldata', $_SESSION))
			{
				$sessionXmlData = $_SESSION['s_xmldata'];
			} else {
				$sessionXmlData = '';
			}

			$tmpArray = explode('&', $out);
			$outTmp = '';
			foreach($tmpArray as $k=>$v)
			{
				$pos = strpos($v, '=');
				$key = substr($v, 0, $pos);
				$preVal = substr($v, $pos+1);

				$outTmp .= $key." = ".$preVal."\n";
			}

			$xmlData = SU_ProtocolAccumulator::GetXmlData();

			$str  = "REQUEST:\n";
			$str .= $xmlData."\n";
			$str .= "RESPONSE:\n";
			$str .= $outTmp."\n";


			if(in_array($_SESSION['USERNAME'], $GLOBALS['USERS_FOR_LOG_PROTOCOL']))
			{
				$fileName = $_SESSION['USERNAME'].'_Protocol.log';
			}else{
				$fileName = 'Protocol.log';
			}

			SU_DebugTools::logAll($str, $fileName, 0);
		}

	}
}

?>