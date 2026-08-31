<?php

/**
 * Выводит помощь новичкам по запросу от клиента
 * 
 * @author vasmik
 *
 */
class UA_GetNubHelp extends UA_Action
{
	private $id;
	private $stat;

	function __construct()
	{
		UA_Action::UA_Action();
	}

	function checkLocalParams()
	{
		$this->id = $this->root->get_attribute ( "id" );
		$this->stat = $this->root->get_attribute ( "stat" );


		return true;
	}

	function checkAction()
	{
		return true;
	}

	function performAction()
	{
		//SU_DebugTools::LogAll("id=".$this->id.',stat='.$this->stat.',langid='.$this->langId, 'help.log');

		if ($this->stat)
		{
			$hinttitle = '';
			$building = '';
			$to_build = '';
			$surface = 3;
			$helptext = '';
			$helplist_str = '';

			if ($this->stat == "FULL")
			{
				$helplist = GH_NubHelper::getHelpList($this->langId);
				$total = count($helplist);
				//SU_DebugTools::LogAll("helplist= ".var_export($helplist, true).', total='.$total, 'help.log');

				for($i=0; $i < $total; $i++)
				{
					$n = $i+1;
					$helplist_str .= "<art id=\"{$n}\"><name><![CDATA[{$n}. {$helplist[$i]['text']}]]></name></art>\n";
				}
			} else {
				$total = GH_NubHelper::getHelpCount();
			}

			$params = array();
			if ($this->id==1)
			{
				$params = array(0 => $_SESSION['s_login'], 1 => DE_World::CreateForCurrentWorld()->getName());
			}

			$iconInfo = GH_NubHelper::getIconInfo($this->id);
			if ($iconInfo)
			{
				$building = $iconInfo['building'];
				$surface  = $iconInfo['surface'];
				$to_build = $iconInfo['to_build'];
			}

			$hinttitle =  GH_NubHelper::GetHelpTitle($this->id, $this->langId);
//SU_DebugTools::LogAll('NUB_HELP_LEVEL_'.$this->id, 'AnnitaHelp.log');			
			$helptext = urlencode(DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NUB_HELP_LEVEL_'.$this->id, $params, null, null, false));

			$advicestr = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<adv title=\"{$hinttitle}\" total=\"{$total}\" number=\"{$this->id}\" build=\"{$building}\" surface=\"{$surface}\" tobuild=\"{$to_build}\" icon=\"\">
<txt><![CDATA[{$helptext}]]></txt>\n";

			$nubHelpText = 'advice='.$advicestr.$helplist_str;
		}
		else
		{
			$nubHelpText = "outside=".GH_NubHelper::getHelpMessage($this->uiid, $this->world, true);
		}

		if(!empty($nubHelpText)) {
			SU_OutputHandler::Singleton()->addReadyString($nubHelpText);
		}

		return true;
	}

}

?>