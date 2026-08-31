<?php $CVS_Version = '$Revision$ ; $Date$';

// команда посылается клиентов при перезагрузке страницы

class UA_Empty extends UA_Action {

	function UA_Empty () {
		UA_Action::UA_Action ();
	}

	function performAction ()
	{

		global $common;
		global $params;

		if ( empty ( $_SESSION["s_xmldata"] ) )
		{
			// если в сессии не висит никакой команды

			if(key_exists("refresh", $_SESSION))
			{
				$refresh = @$_SESSION["refresh"];
			}else{
				$refresh = array();
			}
			
			$refresh = array();
			
			if(!isset($refresh[$this->comb."_".$this->celltype] ) )
			{
				// если нет соты в сессии

				// если допустим рефреш
				$sql = sprintf ( "select isAllowRefresh (%d) as ar", $params["uiid"] );

				$res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
				if ( !$res )
				{
					$this->errormsg = "UA_Empty::performAction1";
					return false;
				}

				if ( $res[0]['ar'] != 1 )
				{
					$mparams[0] = $this->worldSettings->get('REFRESH_RATE');
					DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TO_FAST_REFRESH_MESS', $mparams );

					// рефреш по минимуму
					if ( !$this->refresh ( 2, "" ) )
					{
						return false;
					}
				} else {
					// рефреш по максимуму
					if ( !$this->refresh ( "", "", 1, 1 ) )
					{
						return false;
					}
				}

			} else {
				// рефреш по минимуму
				if ( !$this->refresh ( 2, "", "", "" ) ) {
					return false;
				}
			}
		} else {
			// рефреш по минимуму
			if ( !$this->refresh ( 2, "", "", "" ) ) {
				return false;
			}
		}

		return true;
	}
}

?>