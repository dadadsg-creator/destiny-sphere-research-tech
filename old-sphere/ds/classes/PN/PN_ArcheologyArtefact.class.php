<?php $CVS_Version = '$Revision: 1.12 $ ; $Date: 2009/03/16 14:26:12 $';

// список артефактов в хранилище

class PN_ArcheologyArtefact extends UA_GetBuildMenu {

	private $maxArtCountForFullShow = 500;

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel () {

		global $params;
		global $common;

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><archeology_artefact />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();

		// значения для сортировки артефактов
		$sortTypes = $this->_getSortTypes();

		$combArts = $this->_getArtefactCountOnComb();

		// если на соте много артефактов, то показываем крохотные локальные на кадериум
		if((count($this->art_sort) == 0 ||
		(empty($this->art_sort['scope']) &&
		empty($this->art_sort['size']) &&
		empty($this->art_sort['type']))))
		{

			if($combArts > $this->maxArtCountForFullShow)
			{
				$this->art_sort['scope'] = 'BUILDING';
				$this->art_sort['size']  = 'MINOR';
				$this->art_sort['type']  = 'KADERIUM';
			} else {
				$this->art_sort['scope'] = 'ALL';
				$this->art_sort['size']  = 'ALL';
				$this->art_sort['type']  = 'ALL';
			}
		}

		foreach($sortTypes as $k => $sortType)
		{
			$ch = $root->new_child($sortType['sortname']);

			$subSort = $this->_getOrdersBySortType($sortType['sortname']);

			foreach($subSort as $k => $sort)
			{
				$ch2 = $ch->new_child('order');
				$ch2->set_attribute("id", $sort['id']);
				$ch2->set_attribute("ln", $sort['ln']);

				if(key_exists($sortType['sortname'], $this->art_sort))
				{
					if($this->art_sort[$sortType['sortname']] == $sort['id'])
					{
						$ch2->set_attribute("cur", 1);
					}
				} elseif($sort['id'] == 'ALL') {
					$ch2->set_attribute("cur", 1);
				} else {
					$ch2->set_attribute("cur", 0);
				}
			}
		}


		$sortStr = "";
		if(key_exists('scope', $this->art_sort) && $this->art_sort['scope'] != 'ALL')
		{
			$sortStr .= " AND ars.scopename = '".$this->art_sort['scope']."'";
		}

		if(key_exists('size', $this->art_sort) && $this->art_sort['size'] != 'ALL')
		{
			$sortStr .= " AND ap.prefix = '".$this->art_sort['size']."'";
		}

		if(key_exists('type', $this->art_sort) && $this->art_sort['type'] != 'ALL')
		{
			$sortStr .= " AND atn.attributename = '".$this->art_sort['type']."'";
		}

		$sql = sprintf ( "select ltl.text
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where ltl.id_localizedtext = sm.id_localizedtext
						and ltl.id_language = %d
						and sm.messagename = 'ARTEFACT_DEATH_TIME'", $params['lang'] );

		$loc_death_text_res = $common->getResultArray ( "system_message_art_finished_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		$loc_death_text = $loc_death_text_res[0]['text'];


		// артефакты для отображения
		$sql = "
			SELECT
				ai.id_artefactinstance,
				ai.immortal,
				to_char(ai.death_time, 'DD.MM.YYYY') AS death_time,
				to_char(ai.birth_time, 'DD.MM.YYYY') AS birth_time,
				ap.prefix,
				ars.scopename,
				at.typename,
				a.destroyable,
				a.quest,
				COALESCE(atn.attributename, '') as attributename,
				COALESCE(att.typename, '') as atr_typename,
				COALESCE(ahb.value, 0) as value

		----- DTF PROMO ------
		,dtf.public_id
		---  END DTF PROMO ---

			FROM
				ds_artefact AS a
				INNER JOIN ds_artefactprobability  AS ap ON (a.id_artefactprobability = ap.id_artefactprobability)
				INNER JOIN ds_artefactscope  AS ars ON (a.id_artefactscope = ars.id_artefactscope)
				INNER JOIN ds_artefacttype AS at ON (a.id_artefacttype = at.id_artefacttype)
				INNER JOIN ds_artefactinstance AS ai ON (a.id_artefact = ai.id_artefact)
				INNER JOIN ds_combhasartefact AS cha ON (ai.id_artefactinstance = cha.id_artefact)
				LEFT JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
				LEFT JOIN ds_attribute AS atr ON (ahb.id_attribute = atr.id_attribute)
				LEFT JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
				LEFT JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)

		----- DTF PROMO ------
		LEFT JOIN ds_promo_dtf_artefactbox AS dtf ON (dtf.id_artefactinstance = ai.id_artefactinstance)
		---  END DTF PROMO ---

			WHERE
				cha.id_comb = ".$this->comb."
				".$sortStr."
			ORDER BY ap.id_artefactprobability DESC, ars.id_artefactscope DESC, at.id_artefacttype DESC";
		$res = $common->getResultArray ( "combhasartefact_id_language_id_comb_orderby_".$params["lang"]."_".$this->comb."_".$this->orderby, $sql, "", $params["uiid"], "LOCAL_CACHE");
		assert(empty($common->errormsg));

		for($i=0; $i<sizeof($res); $i++)
		{
			$ch = $root->new_child("artefact");
			$ch->set_attribute("id", $res[$i]['id_artefactinstance']);
			$ch->set_attribute("prefix", $res[$i]['prefix']);
			$ch->set_attribute("scope", $res[$i]['scopename']);
			if(preg_match("/^IGROMIR_/", $res[$i]['typename'])) {
				$res[$i]['typename'] = preg_replace(array('/COMB_/', '/COMBCOMPLEX_/', '/ALLIANCE_/', '/WAR_/'), array('', '', '', ''), $res[$i]['typename']);
			}
			if($res[$i]['immortal'] == 'f') {
				list($d,$m,$y) = explode('.', $res[$i]['birth_time']);
				$lt = mktime(0, 0, 0, $m, $d, $y);
				list($d,$m,$y) = explode('.', $res[$i]['death_time']);
				$lt .= '|'.mktime(0, 0, 0, $m, $d, $y);
				$res[$i]['typename'] .= "_".$lt;
				$ch->set_attribute("death_message", sprintf(str_replace('%', '%s', $loc_death_text), $res[$i]['death_time']));
				$ch->set_attribute("lifetime", $lt);
			}
			$ch->set_attribute("postfix", $res[$i]['typename']);

			// Можно ли удалить артефакт
			if($res[$i]['destroyable'] == 't')
			{
				$ch->set_attribute("remove", "1");
			} else {
				$ch->set_attribute("remove", "0");
			}

			/*********** ПРОМО АКЦИЯ DTF ***************/
			if($res[$i]['quest'] == 't')
			{
				// Ссылка
				$ch2 = $ch->new_child("link");
				$ch2->set_attribute("id", $res[$i]['public_id']);

				// Текст кнопки с активацией
				$activateMsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ACTIVATE_ARTEFACT_LINK', array(), '', null, false);
				$ch2->set_attribute("txt", $activateMsg);

				// Текст всплывающей подсказки
				$activateHint = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ACTIVATE_ARTEFACT_HINT', array(), '', null, false);
				$elem = $doc->create_text_node($activateHint);
				$ch2->append_child ( $elem );
			} else {
				/******** END ПРОМО АКЦИЯ DTF END ***********/
				if($res[$i]['attributename'] != '')
				{
					$ch2 = $ch->new_child("bonus");
					$bonus = (float)$res[$i]['value'];
					if( $res[$i]['atr_typename'] == 'BONUS_RELATIVE')
					{
						$bonus *= 100;
						$bonus .= "%25";
					}

					$bonus = "%2B".$bonus;
					$ch2->set_attribute("name", trim($res[$i]['attributename']));
					$ch2->set_attribute("value", urlencode($bonus));
				}
			}
		}

		// закладки для здания
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );

		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

		$res = "";
		$res2 = "";
		$doc = "";
		$root = "";

		return true;
	}


	private function _getArtefactCountOnComb()
	{
		$common = DS_Common::CreateForCurrentDB();

		$sql = "
		SELECT
		count(*)
		FROM
		ds_combhasartefact
		WHERE
		id_comb = $this->comb
			";
		$res = $common->getResultArray("artefact_sort_type_lang".$this->langId, $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));

		return (int)$res[0]['count'];
	}


	private function _getSortTypes()
	{
		$common = DS_Common::CreateForCurrentDB();

		$sql = "
		SELECT
		ast.sortname,
		ltl.text
		FROM
		ds_artefactsorttype AS ast
		INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ast.localizedname)
		WHERE
		ltl.id_language = $this->langId
			";
		$res = $common->getResultArray("artefact_sort_type_lang".$this->langId, $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));

		return $res;
	}

	private function _getOrdersBySortType($type)
	{
		$common = DS_Common::CreateForCurrentDB();

		switch ($type) {
			case 'size':
				$sql = "
				SELECT
				apr.prefix as id,
				ltl.text as ln
				FROM
				ds_artefactprobability AS apr
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = apr.id_localizedprefix)
				WHERE
				ltl.id_language = $this->langId
				ORDER BY apr.id_artefactprobability
				";
				break;

			case 'scope':
				$sql = "
				SELECT
				ars.scopename as id,
				ltl.text as ln
				FROM
				ds_artefactscope AS ars
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ars.id_localizedscope)
				WHERE
				ltl.id_language = $this->langId
				ORDER BY ars.id_artefactscope";
				break;

			case 'type':
				$sql = "
				SELECT
				arttype.attributename as id,
				arttype.text as ln
				FROM
				(
				SELECT DISTINCT
				an.attributename,
				ltl.text,
				at.id_attributename
				FROM
				ds_artefacthasbonus AS ahb
				INNER JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
				INNER JOIN ds_attributename AS an ON (an.id_attributename = at.id_attributename)
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = an.id_localizedname)
				WHERE
				ltl.id_language = $this->langId
				) AS arttype
				ORDER BY arttype.id_attributename";
				break;

			default:
				ds_error('ArcheologyArtefact::getPanel - Unknown sort type!');
				break;
		}

		$res = $common->getResultArray("artefact_sort_type_".$type."_lang".$this->langId, $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));

		$sql = "
		SELECT
		'ALL' AS id,
		ltl.text AS ln
		FROM
		ds_localizedtextlanguage as ltl
		WHERE
		ltl.id_language = $this->langId
		AND
		ltl.id_localizedtext = 30550
			";
		$res2 = $common->getResultArray("art_sort_type_all".$this->langId, $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));

		$res[] = $res2[0];

		return $res;
	}
}

?>