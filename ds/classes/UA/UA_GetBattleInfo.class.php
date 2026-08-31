<?php $CVS_Version = '$Revision$ ; $Date$';

// команда получить подробную информацию о бое
class UA_GetBattleInfo extends UA_Action {

	public $id_battle;
	public $round;
	public $report;
	
	private $participantFlag;

	function __construct() 
	{
		UA_Action::UA_Action();
	}

	// проверяем параметны
	function checkLocalParams() 
	{
		// id боя
		$this->id_battle = (int)$this->root->get_attribute ("id_battle");
		assert($this->id_battle > 0);

		$this->round = (int)$this->root->get_attribute("round"); // передавать конкретный раунд
		$this->report = (int)$this->root->get_attribute("report"); // передавать репорт
		
		return true;
	}

	// проверяем допустимость действия
	function checkAction () 
	{
		global $common;

		// учавствовал ли юзер в этой битве
		$sql = "
			select 
				participantflag
			from 
				ds_battleparticipant
			where 
				id_battle = ".$this->id_battle."
				and id_userinstance = ".$this->uiid;
		$res = $common->getResultArray("id_battleparticipant_id_battle_id_userinstance_".$this->id_battle."_".$this->uiid, $sql,  "", "", "LOCAL_CACHE");
		assert(empty($common->errormsg) && count($res) > 0);
		
		$this->participantFlag = $res[0]['participantflag'];

		return true;
	}

	// выполняем действие
	function performAction() 
	{
		if($this->report != 1)
		{
			return $this->outRound();
		}else{
			return $this->outReport();
		}		
	}
	
	function outReport()
	{
		global $common;
		
		$data = DS_Battle::GetReportByBattleIDAndParticipantFlag($this->id_battle, $this->participantFlag);		
		assert(is_array($data));
	
		if(count($data) == 0)
		{
			$out = '';
		} else {
			//$multyLang = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_LOG_MULTY_LANG');
			//if ($multyLang == 1) {
			// Если массивчик можно разсериализовать (сделано для перехода от старых логов к новым)
			if (substr($data['report'], 0, 2) == 'a:') {
				$reportArr = unserialize($data['report']);
				
				// Получаем локализацию
				/*$data = DS_Battle::GetBattleActionsByLang($_SESSION['lang']);
				assert(is_array($data) && count($data)>0);

				foreach($data as $k=>$v)
				{
					$locolizedActions[$v['battleactionname']] = $v['text'];
				}*/

				$battleAction = DE_BattleAction::CreateForCurentLang();
//SU_DebugTools::LogAll($reportArr, 'Annita.log');	

				$out = '';
				foreach ($reportArr as $key => $d)
				{
					if (empty($d)) {
						$out .= '<br>';
					} else {
						$out .= $battleAction->formAction($d['NAME'], $d['PARAMS'], false);
						$out .= '<br>';
					}
				}
			} else {
//SU_DebugTools::LogAll(substr($data['report'], 0, 2), 'Annita.log');	
				
				assert(key_exists('report', $data));
				$out = (string)$data['report'];
			}
			
			$out = str_replace("\r","", $out);
			$out = str_replace("\n","<br>", $out);
		}
//SU_DebugTools::LogAll($out, 'Annita.log');	

		// Выбираем данные для заголовка
		$battle = DE_Battle::CreateFromID($this->id_battle);
		// Оппонент
		$userBattleParticipant = $battle->getBattleParticipantBy(DE_UserInstance::CreateFromID($this->uiid));
		if($userBattleParticipant->getParticipantFlag('PG') == 'D')
		{
			$opponent = $battle->getAttacker()->getUserInstance()->getUser()->getName();
		}else{
			$opponent = $battle->getDefender()->getUserInstance()->getUser()->getName();
		}

		// Пободитель
		$sql = "
          SELECT
           		b.battlestartdate,
               	b.battleenddate,
               	(SELECT 
               		u.username
		         FROM 
		         	ds_user u,
		            ds_userinstance ui,
		            ds_battleparticipant bp
		         WHERE
		             ui.id_user = u.id_user
		              AND               
		             bp.id_userinstance = ui.id_userinstance
		              AND
		             bp.id_battle = b.id_battle AND bp.iswinner = 't'		             
               	) as winner
            FROM
                ds_battle b
          WHERE b.id_battle = ".$this->id_battle;
		
		$res = $common->getResultArray("battle_participant_dada_out_round".$this->id_battle, $sql, "", $this->uiid, "LOCAL_CACHE");
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		$starDate = $res[0]['battlestartdate'];
		$endDate = $res[0]['battleenddate'];
		$winner = $res[0]['winner'];
		
		$out = '#'.$starDate.'#'.$endDate.'#'.$winner.'#'.$opponent.'#'.$out;
		SU_OutputHandler::Singleton()->addString( $out , 'xmldata' );		
		return true;
	}
	
	function outRound()
	{
		global $params;
		global $common;

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><battlinfo />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();

		$sql = "
			SELECT
				u.username
				-- ,b.participantflag
			FROM
				ds_battleparticipant as b
				INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = b.id_userinstance)
				INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
			WHERE
				b.id_battle = $this->id_battle
				AND
				NOT b.id_userinstance = $this->uiid
			";
		$res = $common->getResultArray("battle_participant_openent".$this->id_battle.$this->uiid, $sql, "", $this->uiid, "LOCAL_CACHE");
		assert(empty($common->errormsg));
		assert(count($res) > 0);
		foreach($res as $k => $participant)
		{
			$nick[] = $participant['username'];
		}
		
		$root->set_attribute('nick', implode(',', $nick));
		
		$root->set_attribute('id_battle', $this->id_battle);
		
		if ( $this->round != "" ) {
			$s = " and br.roundnumber = ".$this->round;
		} else {
			$s = "";
		}

		// раунды и репорты
		$sql = "
				select 
					br.roundnumber,
					br.id_battleround,
					(
						select 
							report
						from 
							ds_roundreport
						where 
							id_battleround = br.id_battleround
					) as report
				from 
					ds_battleround br
				where 
					br.id_battle = ".$this->id_battle."
					".$s."
				order by br.roundnumber";
		$res = $common->getResultArray ( "roundnumber_id_battleround_report_round_id_battle_".$this->round."_".$this->id_battle, $sql,  "", $params["uiid"], "LOCAL_CACHE" );
		assert(empty($common->errormsg));
		
		// если шпионаж выключен, то все видят одно и то же
		$fullLog = (bool)DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_ONE_ROUND_DEATH_LOG');
		
		for($i=0; $i<sizeof($res); $i++) 
		{
			if(!$fullLog && $this->participantFlag == 'A' && count($res) < 3)
			{
				break;
			}
			
			$ch = $root->new_child("round");
			$ch->set_attribute("number", $res[$i]['roundnumber']);
			
			// информация по раунду
			$sql = sprintf ( "
					SELECT 
						bres.*,
						(
							SELECT
								bp.participantflag
							FROM
								ds_battleparticipant bp,
								ds_battleparticipanthasunitcontainer bphuc
							WHERE 
									bp.id_battleparticipant = bphuc.id_battleparticipant
								AND bphuc.id_unitcontainer = bres.id_unitcontainer
								AND id_battle = b.id_battle
						) AS side,
						(
							SELECT unitname
							FROM ds_unit
							WHERE id_unit = bres.id_unit
						) AS unitname/*,
						(
							SELECT DISTINCT bphuc.unitcontainername
							FROM ds_battleparticipanthasunitcontainer bphuc
							WHERE bphuc.id_unitcontainer = 	bres.id_unitcontainer							
						) AS unitcontainername*/
					FROM 
						ds_battle b,
						ds_battleround br,
						ds_battleresult bres
					WHERE 
							b.id_battle = br.id_battle
						AND br.id_battleround = bres.id_battleround
						AND br.id_battleround = %d",
					$res[$i]['id_battleround'] );						
			$res2 = $common->getResultArray ( "", $sql,  "", "", "");
			assert(empty($common->errormsg));				
			
			if(count($res2) == 0)
			{
				continue;
			}
			
			for($e=0; $e < sizeof($res2); $e++) 
			{					
				if($res2[$e]['amount'] == 0)
				{
					continue;
				}
					
				$sql = "SELECT bphuc.unitcontainername
						FROM ds_battleparticipanthasunitcontainer bphuc
						WHERE bphuc.id_unitcontainer = 	".$res2[$e]['id_unitcontainer'];
				$rest = $common->getResultArray ( "UNITCONTAINER_NAME_FROM_ROUND_RESULT_BY_ID_UNITCONTAINER_".$res2[$e]['id_unitcontainer'], $sql,  "", "", "GLOBAL_CACHE");
				assert(empty($common->errormsg));	
				
				if(count($rest) == 0)
				{
					continue;
				}
					
				$armyName = $rest[0]['unitcontainername'];
					
				$ch2 = $ch->new_child("unit");
				$ch2->set_attribute("id_army", $res2[$e]['id_unitcontainer']);
				$ch2->set_attribute("army", $armyName);					
				$ch2->set_attribute("name", $res2[$e]['unitname']);					
				$ch2->set_attribute("amount", GB_Integer::Format(ceil((real)$res2[$e]['amount'])));
				$ch2->set_attribute("side", $res2[$e]['side']);
			}
			
			$elem = $doc->create_text_node(trim($res[$i]['report']));
			$ch->append_child($elem);
		}

		//$panel = "xmldata=";
		//$panel .= $doc->dump_mem();
		//$this->logResponse ( $panel );
		// print $panel;
		
		$str = $doc->dump_mem();		
		SU_OutputHandler::Singleton()->addString( $str, 'xmldata' );


		$res = "";
		$res2 = "";

		return true;		
	}
}