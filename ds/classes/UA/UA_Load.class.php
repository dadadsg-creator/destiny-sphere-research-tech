<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/07/01 08:56:38 $';
// Первичная загрузка

class UA_Load extends UA_Action
{
	public $locations;

	function __construct()
	{
		UA_Action::UA_Action ();
	}

	function checkLocalParams ()
	{
		global $params;
		global $common;

		$this->locations = $this->root->get_attribute ( "locations" );
		return	true;
	}

	function performAction ()
	{
		global $params;
		global $common;

		$vs = new SU_FlashVersions();
		$vs->getVersions();

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><load/>";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		$root->set_attribute ( "v", $vs->files['localdata']['version'] );

		// список сот игрока
		if ( $this->locations == 3 )
		{
			$sql = "SELECT id_comb
					FROM ds_comb
					WHERE id_userinstance = ".$params['uiid'];
			$res = $common->getResultArray ( "users_combs", $sql, "", "", "" );
			assert(empty($common->errormsg));

			$str = '';

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$str .= $res[$i]['id_comb'].",";
			}

			$str = substr ( $str, 0, strlen ( $str )-1 );

			$ch = $root->new_child ( "comb" );
			$ch->set_attribute ( "id", $str );
		}


		if ( $this->locations == 0 || $this->locations == 1 || $this->locations == 3 )
		{
			// информация по зданиям
			$sql = "
					SELECT 
						b.buildingname AS internalname,
						b.id_building,
						b.isunique,
						attributename AS attname, 
						value,
						(
							SELECT text
							FROM ds_localizedtextlanguage
							WHERE
								id_language = ".$params["lang"]."
								AND
								id_localizedtext = bcbr.id_localizedname
						) AS localizedname,
						(
							SELECT text
							FROM ds_localizedtextlanguage
							WHERE 
								id_language = ".$params["lang"]."
								AND
								id_localizedtext = bcbr.id_localizeddescription
						) AS localizeddescription,
						bt.type
					FROM
						ds_buildingcanbuildbyrace AS bcbr,
						ds_building AS b
						LEFT JOIN ds_buildingtype AS bt ON (bt.id_buildingtype = b.id_buildingtype) 
						LEFT JOIN
							(
								SELECT * 
								FROM 
									ds_buildinghasattribute AS bha 
									RIGHT JOIN
										(
											SELECT 
												atn.attributename, 
												a.id_attribute 
											FROM
												ds_attribute AS a,
												ds_attributetype AS att,
												ds_attributename AS atn
											WHERE
												a.id_attributename = atn.id_attributename
												AND
												a.id_attributetype = att.id_attributetype
												AND 
												(att.typename = 'PRODUCTION_COSTS'
	                           						OR
                            					(att.typename = 'MAX_AMOUNT' AND  atn.attributename = 'ARTEFACT_SLOTS'))
										) AS attr 
									ON bha.id_attribute = attr.id_attribute
							) AS bul1
						ON b.id_building = bul1.id_building
					WHERE 
						b.id_building = bcbr.id_building
						AND 
						bcbr.id_race = ".$params["race"]."
					ORDER BY b.id_building";
			$res = $common->getResultArray ( "buildinginfo_id_language_id_race_".$params["lang"]."_".$params["race"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			assert(empty($common->errormsg));

			$prevname = "";
			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				if ( $prevname != $res[$i]['internalname'] )
				{
					$ch = $root->new_child ( "b" );
					$ch->set_attribute ( "id", $res[$i]['id_building'] );
					$ch->set_attribute ( "name", $res[$i]['internalname'] );
					$ch->set_attribute ( "type", $res[$i]['type'] );
					$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
					$ch->set_attribute ( "unique", $res[$i]['isunique'] == 't' ? 1 : 0 );

					if ( $res[$i]['attname'] != "" )
					{
						$ch->set_attribute ( substr ( trim ( $res[$i]['attname'] ), 0, 1 ), $res[$i]['value'] );
					}

					$elem = $doc->create_text_node ( urlencode ( trim ( $res[$i]['localizeddescription'] ) ) );
					$ch->append_child ( $elem );
					$prevname = $res[$i]['internalname'];
				} else {
					if ( $res[$i]['attname'] != "" )
					{
						$ch->set_attribute ( substr ( trim ( $res[$i]['attname'] ), 0, 1 ), $res[$i]['value'] );
					}
				}
			}
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//Информация для торговх фильтров списко скоупов
			/*$sql = "SELECT ats.id_artefactscope, ltl.text
			FROM ds_artefactscope AS ats
			INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ats.id_localizedscope)
			WHERE ltl.id_language = ".$params["lang"];
			*/
			$sql = "
				SELECT
    				ats.id_artefactscope,
    				ltl.text,
    				(
        				SELECT
            				ARRAY_TO_STRING(
                				ARRAY(
                    				SELECT
                        				DISTINCT id_artefactprobability
                    				FROM
                        				ds_artefact AS a
                    				WHERE
                        				a.id_artefactscope = ats.id_artefactscope
                        			ORDER BY id_artefactprobability
                				),
                				','
            				)
    				) as size,
    
				    (
        				SELECT
            				ARRAY_TO_STRING(
                				ARRAY(
                    				SELECT
                        				DISTINCT atr.id_attributename
                    				FROM
                        				ds_artefact AS a
                        				INNER JOIN ds_artefacthasbonus AS ahb USING (id_artefact)
                        				INNER JOIN ds_attribute AS atr ON (atr.id_attribute = ahb.id_attribute)
                    				WHERE
                        				a.id_artefactscope = ats.id_artefactscope
                        			ORDER BY atr.id_attributename
                				),
                				','
            				)
    				) as bonus
				FROM
    				ds_artefactscope AS ats
					INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ats.id_localizedscope)
				WHERE
    				ltl.id_language = ".$params["lang"];
			$res = $common->getResultArray("ARTEFACT_SCOPES_LIST_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
			assert(empty($common->errormsg));

			$artefactScopes = array();
			foreach($res as $k=>$v)
			{
				$artefactScopes[] = $v['text'].'|'.$v['id_artefactscope'].'|'.$v['size'].'|'.$v['bonus'];
			}

			$ch = $root->new_child ("artscope");
			$elem = $doc->create_text_node(urlencode(implode(';', $artefactScopes)));
			$ch->append_child($elem);

			//Информация для торговх фильтров списко размеров артов
			$sql = "SELECT apb.id_artefactprobability, ltl.text
					FROM
    					ds_artefactprobability AS apb
    					INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = apb.id_localizedprefix)
					WHERE ltl.id_language = ".$params["lang"]."
					ORDER BY apb.id_artefactprobability";
			$res = $common->getResultArray("ARTEFACT_SIZE_LIST_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
			assert(empty($common->errormsg));

			$artefactSizes = array();
			foreach($res as $k=>$v)
			{
				$artefactSizes[] = $v['text'].'|'.$v['id_artefactprobability'];
			}

			$ch = $root->new_child ("artsize");
			$elem = $doc->create_text_node(urlencode(implode(';', $artefactSizes)));
			$ch->append_child($elem);

			//Информация для торговх фильтров списко бонусов артов
			$sql = "SELECT DISTINCT atn.id_attributename, ltl.text
					FROM ds_artefacthasbonus AS ahb
     					INNER JOIN ds_attribute AS atr ON (atr.id_attribute = ahb.id_attribute)
     					INNER JOIN ds_attributename AS atn ON (atn.id_attributename = atr.id_attributename)
     					INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = atn.id_localizedname)
					WHERE ltl.id_language = ".$params["lang"]."
					ORDER BY atn.id_attributename";
			$res = $common->getResultArray("ARTEFACT_BONUS_LIST_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
			assert(empty($common->errormsg));

			$artefactBonuses = array();
			foreach($res as $k=>$v)
			{
				$artefactBonuses[] = $v['text'].'|'.$v['id_attributename'];
			}

			$ch = $root->new_child ("artbonus");
			$elem = $doc->create_text_node(urlencode(implode(';', $artefactBonuses)));
			$ch->append_child($elem);

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// информация по аттрибутам
			$sql = sprintf ( "select atn.attributename,
					ltl.text as localizedname
					from DS_AttributeName atn,
					DS_LocalizedText lt,
					DS_LocalizedTextLanguage ltl
					where atn.ID_LocalizedName = lt.ID_LocalizedText
					and ltl.ID_LocalizedText = lt.ID_LocalizedText
					and ltl.ID_Language = %d",
			$params["lang"] );

			$res = $common->getResultArray("attributename_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction3";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$ch = $root->new_child ( "att" );
				$ch->set_attribute ( "id", trim ( $res[$i]['attributename'] ) );
				$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
			}

			// информация по текстовым полям в меню
			$sql = "SELECT
						int.interfacename,
						ltl.text AS localizedname
					FROM
						ds_interface AS int,
						ds_localizedText AS lt,
						ds_localizedTextLanguage AS ltl
					WHERE 
						int.id_LocalizedName = lt.id_LocalizedText
						AND
						ltl.id_LocalizedText = lt.id_LocalizedText
						AND 
						ltl.id_Language = ".$params["lang"];			
			$res = $common->getResultArray("interfacename_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");
			assert(empty($common->errormsg));

			/**$str = "
			Lang = ".$params["lang"]."
			Count = ".count($res)."
			";
			SU_DebugTools::LogAll($str, 'InterfaceLocalizationSend.log');
			*/

			foreach($res AS $k=>$v)
			{
				$ch = $root->new_child("interface");
				$ch->set_attribute("id", trim($v['interfacename']));
				$ch->set_attribute("ln", urlencode(trim($v['localizedname'])));
			}

			// типы почвы
			$sql = sprintf ( "select gn.text as localizedname,
					gd.text as localizeddescription,
					g.groundname
					from ds_groundtype g
					left join
					(select gr.id_groundtype,
					ltl.text
					from DS_GroundType gr,
					DS_LocalizedText lt,
					DS_LocalizedTextLanguage ltl
					where gr.ID_LocalizedName = lt.ID_LocalizedText
					and ltl.ID_LocalizedText = lt.ID_LocalizedText
					and ltl.ID_Language = %d) gn
					on g.id_groundtype = gn.id_groundtype
					left join
					(select gr.id_groundtype,
					ltl.text
					from DS_GroundType gr,
					DS_LocalizedText lt,
					DS_LocalizedTextLanguage ltl
					where gr.ID_LocalizedDescription = lt.ID_LocalizedText
					and ltl.ID_LocalizedText = lt.ID_LocalizedText
					and ltl.ID_Language = %d) gd
					on g.id_groundtype = gd.id_groundtype",
			$params["lang"], $params["lang"] );

			$res = $common->getResultArray ( "groundtype_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction5";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$ch = $root->new_child ( "groundtype" );
				$ch->set_attribute ( "id", trim ( $res[$i]['groundname'] ) );
				$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
				$elem = $doc->create_text_node ( urlencode ( trim ( $res[$i]['localizeddescription'] ) ) );
				$ch->append_child ( $elem );
			}

			// названия частей соты
			$sql = sprintf ( "select ct.celltypename,
					ltl.text as localizedname
					from DS_CellType ct,
					DS_LocalizedText lt,
					DS_LocalizedTextLanguage ltl
					where ct.ID_LocalizedName = lt.ID_LocalizedText
					and ltl.ID_LocalizedText = lt.ID_LocalizedText
					and ltl.ID_Language = %d",
			$params["lang"] );

			$res = $common->getResultArray ( "celltype_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction6";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$ch = $root->new_child ( "celltype" );
				$ch->set_attribute ( "id", trim ( $res[$i]['celltypename'] ) );
				$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
			}

			// названия артефактов
			$sql = sprintf ( "
					SELECT
						a.id_artefact,
						a.artefactname,
						ltl.text AS localizedname,
						(
							select text
							from ds_localizedtextlanguage
							where 
								id_localizedtext = a.id_localizeddescription
								and 
								id_language = %d
						) as localizeddescription
					from ds_artefact a,
					ds_localizedtextlanguage ltl
					where a.id_localizedname = ltl.id_localizedtext
					and ltl.id_language = %d",
			$params["lang"], $params["lang"] );

			$res = $common->getResultArray ( "artefacts_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction7";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$ch = $root->new_child ( "artefact" );
				$ch->set_attribute ( "id", trim($res[$i]['artefactname']));
				$ch->set_attribute ( "idn", $res[$i]['id_artefact']);
				$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
				$elem = $doc->create_text_node ( urlencode ( trim ( $res[$i]['localizeddescription'] ) ) );
				$ch->append_child ( $elem );

				/**
				 * 
				 * Вставляем в локализацию бонусы артефактов. 
				 * Вот только совершенно нет поддержки возможности задавать несколько 
				 * бонусов для одного артефакта.
				 * Да и отрицательные бонусы не предусмотрены 
				 * # = +, $ = %
				 * 
				 */
				$bonuses = DS_Artefact::GetArtefactAtrributes((int)$res[$i]['id_artefact']);
				$bonusStr = '';
				$bonusAttr = '';
				if(is_array($bonuses))
				{
					foreach ($bonuses as $k => $bonus)
					{
						$bonusStr = $bonus['value'];
						if($bonus['typename'] == 'BONUS_RELATIVE')
						{
							$bonusStr = $bonusStr*100;
							$bonusStr .= "$";
						}
						$bonusStr = "#".$bonusStr;
						$bonusAttr = trim($bonus['attributename']);
					}
				}
				//				SU_DebugTools::LogAll($res[$i]['artefactname']." ". $bonusAttr." ".$bonusStr, 'vasmik', 0);

				$ch->set_attribute ( "bn", $bonusAttr);
				$ch->set_attribute ( "bv", $bonusStr);
			}

			// названия колец
			$sql = sprintf ( "select distinct ( ri.ringname ) as ringname,
					ri.ringnumber,
					ltl.text as localizedname
					from ds_ring ri,
					DS_LocalizedTextLanguage ltl
					where ri.ID_LocalizedName = ltl.ID_LocalizedText
					and ltl.ID_Language = %d",
			$params["lang"] );

			$res = $common->getResultArray ( "ringname_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction8";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$ch = $root->new_child ( "ring" );
				$ch->set_attribute ( "id", trim ( $res[$i]['ringname'] ) );
				$ch->set_attribute ( "ringnumber", trim ( $res[$i]['ringnumber'] ) );
				$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
			}

			// названия юнитов
			$sql = sprintf("
					select 
						u.id_unit,
						u.unitname as unitname, 
						attributename as attname, 
						value,
						(
							select 
								text
							from 
								ds_localizedtextlanguage
							where 
								id_language = %d
								and 
								id_localizedtext = u.id_localizedname
						) as localizedname,
						(
							select 
								text
							from 
								ds_localizedtextlanguage
							where 
								id_language = %d
								and 
								id_localizedtext = u.id_localizeddescription
						) as localizeddescription,
						(
							select
								classnumber
							from
								ds_unithasclass as uhc
								inner join ds_unitclass as uc using (id_unitclass)
							where
								uhc.id_unit = u.id_unit
						) as unitclass,
						(
							select
								array_to_string(
									array(
										select
											id_race
										from
											ds_unitcanbebuildbyrace
										where
											id_unit = u.id_unit
									),
									','
								)
						) as unitrace
						from 
							ds_unit u 
							left join (
								select 
									* 
								from 
									ds_unithasattribute uha 
									right join (
										select 
											atn.attributename, 
											a.id_attribute 
										from 
											ds_attribute a,
											ds_attributetype att,
											ds_attributename atn
										where 
											a.id_attributename = atn.id_attributename
											and 
											a.id_attributetype = att.id_attributetype
											and 
											att.typename = 'PRODUCTION_COSTS'
									) as attr
									on uha.id_attribute = attr.id_attribute
							) as bul1
							on u.id_unit = bul1.id_unit
						order by u.id_unit",
			$params["lang"],
			$params["lang"] );
			$res = $common->getResultArray ( "unitname_id_language2_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if(!$res)
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction9";
				return false;
			}

			$prevname = "";
			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				if ( $prevname != $res[$i]['unitname'] )
				{
					if(empty($res[$i]['localizedname']))
					{
						continue;
					}

					$ch = $root->new_child ( "unit" );
					$ch->set_attribute ( "id", $res[$i]['unitname'] );
					$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
					$ch->set_attribute ( "level", $res[$i]['unitclass'] );
					if(strlen($res[$i]['unitrace']) == 1)
					{
						$ch->set_attribute ( "race", $res[$i]['unitrace'] );
					} else {
						$ch->set_attribute ( "race", 0 );
					}

					// боевые аттрибуты юнитов
					$sql = sprintf ( "select u.unitname,
							att.typename,
							atn.attributename,
							uha.value
							from ds_unit u,
							ds_unithasattribute uha,
							ds_attribute a,
							ds_attributename atn,
							ds_attributetype att
							where u.id_unit = uha.id_unit
							and uha.id_attribute = a.id_attribute
							and a.id_attributename = atn.id_attributename
							and a.id_attributetype = att.id_attributetype
							and typename = 'FIGHT_ATTRIBUTE'
							and u.id_unit = %d",
					$res[$i]['id_unit'] );

					$res2 = $common->getResultArray ( "unit_fight_attributes_id_unit_".$res[$i]['id_unit'], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
					if ( $res2 === false )
					{
						$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction10";
						return false;
					}

					for($e=0; $e<sizeof($res2); $e++)
					{
						if($res2[$e]['attributename'] == 'ATTACK')
						{
							$a = ($res2[$e]['value']*100)."%25";
							// Защитное поле
						} elseif($res2[$e]['attributename'] == 'DEFENCE') {
							if(DE_GlobalSettings::Get('BATTLE_CALC_USE_DEFENCE_FIELD'))
							{
								$a = round((1/(1 - $res2[$e]['value']) - 1)*100)."%25";
							} else {
								$a = ($res2[$e]['value']*100)."%25";
							}
						} else {
							$a = $res2[$e]['value'];
						}
						$ch->set_attribute($res2[$e]['attributename'], urlencode($a));
					}

					if ( $res[$i]['attname'] != "" )
					{
						$ch->set_attribute ( substr ( trim ( $res[$i]['attname'] ), 0, 1 ),  number_format($res[$i]['value'], 6, '.', ''));
					}

					$elem = $doc->create_text_node ( urlencode ( trim ( $res[$i]['localizeddescription'] ) ) );
					$ch->append_child ( $elem );
					$prevname = $res[$i]['unitname'];
				} else {
					if ( $res[$i]['attname'] != "" )
					{
						$ch->set_attribute ( substr ( trim ( $res[$i]['attname'] ), 0, 1 ), number_format($res[$i]['value'], 6, '.', '') );
					}
				}
			}

			// названия мессагескопов
			$sql = sprintf ( "select ms.messagescopename,
					ltl.text as localizedname
					from DS_MessageScope ms,
					DS_LocalizedTextLanguage ltl
					where ms.ID_LocalizedName = ltl.ID_LocalizedText
					and ltl.ID_Language = %d",
			$params["lang"] );

			$res = $common->getResultArray ( "messagescopename_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR - UA_Load::performAction11";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ )
			{
				$ch = $root->new_child ( "messagescope" );
				$ch->set_attribute ( "id", trim ( $res[$i]['messagescopename'] ) );
				$ch->set_attribute ( "ln", urlencode ( trim ( $res[$i]['localizedname'] ) ) );
			}

  		$ch = $root->new_child ( "dataintegrity" );
	  	$ch->set_attribute ( "id", "dataintegrity" );
	  	$ch->set_attribute ( "ln", "OK" );
			$str = $doc->dump_mem();

			SU_OutputHandler::Singleton()->addString($str , 'loaddata');

			$doc = "";
			$d = "";
			$res = "";
			$root = "";

		}

		if($this->locations == 0 || $this->locations == 3 || $this->locations == 2)
		{
			$_SESSION['cells'] = 1;
		} else {
			$_SESSION['cells'] = 0;
		}

		if ( empty ($_SESSION["s_xmldata"] ) )
		{
			if ( !$this->refresh ( 0, $_SESSION['cells'], 1 ) )
			{
				return false;
			}
			unset ( $_SESSION['cells'] );
		}

		//Оповещения о том, какие бонусы есть у игрока
		$res = DS_Bonus::IsUserHasBonuser($params['uiid']);
		if(count($res) > 0)
		{
			$messparams = array();

			$userInstance = DE_UserInstance::CreateFromID($params['uiid']);

			// Проходимся по каждому ресу
			foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
			{
				// Проценты
				//$combProduction = (float)GL_CountCombProductionOfResource::Count(DE_Comb::CreateFromID((int)$this->comb), $resourceName);
				$relative = DS_Economical::GetUserBonus($userInstance, $resourceName);
				if ($relative == 0) {
					continue;
				}

				$messparams[0] = $relative*100;

				// Ресурс
				$messparams[1] = DE_Resource::CreateFromName($resourceName)->getLocolizedName();

				// Время окончания
				$end = DS_Bonus::GetUserBonusEndDate($params['uiid'], $resourceName);
				$now = GB_TypeFactory::CreateBase('GB_Date', time());
				$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $end->value - $now->value);

				$messparams[2] = $period->getDSFormat();

				//Пишем мессагу
				// До окончания действия бонуса % '%' на % осталось %
				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'BONUS_END_DATE', $messparams, 0);
			}

		}

		// Сообщение об окончании временной защиты колонии
		$usersColonies = DS_Comb::GetCombsByUserInstance($params['uiid']);
		foreach ($usersColonies as $key => $data)
		{
			if ($data['is_capture'] == 't') {
				$defend = DE_CombProtectiveField::CreateFromCombID((int)$data['id_comb']);
				if ($defend->getTemporaryDefend()->value > time())
				{
					$messparams[0] = array('{b}'.$data['combname'].'('.$data['coords'].'){/b}');
					$messparams[1] = array('{b}'.$defend->getTemporaryDefend()->getFormatedFull(DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta()).'{/b}');
					//Колония % находитесь под временной защитой, которая будет действовать до %, если вы не выполните действия, которое снимет данную защиту раньше.
					DE_SystemMessages::AddMessageFromAction(__CLASS__, "COLONY_TEMPORARY_DEFEND_MESS", $messparams, 0);
				}
			}
		}

		$userName = '';
		// Имя, мир и комплекс игрока
		if (key_exists('s_login', $_SESSION)) {
			$userName = $_SESSION['s_login'];
		}

		$combData = DS_Comb::GetByID($_SESSION['comb']);
		$combCoords = $combData['combname'].'('.$combData['ringnumber'].'.'.$combData['complexnumber'].'.'.$combData['combnumber'].')';

		$worldData = DS_World::GetByID($_SESSION['world']);
		$worldName = $worldData['worldname'];
		
		$league =  DE_League::CreateFromID((int)DE_UserInstance::CreateFromID($params['uiid'])->getLeagueId())->getLeagueName();
		
		// Достаем локализацию
		// информация по зданиям
		$sql = "
					SELECT 
						l.text as comb,
						l2.text as name,
						l3.text as world,
						l4.text as league
					FROM 
						ds_localizedtextlanguage l ,
						ds_localizedtextlanguage l2,
						ds_localizedtextlanguage l3,
						ds_localizedtextlanguage l4
					WHERE 
						l.id_localizedtext = 403 AND l.id_language = ".$params["lang"]."
						AND
						l2.id_localizedtext = 340 AND l2.id_language = ".$params["lang"]."
						AND
						l3.id_localizedtext = 251616 AND l3.id_language = ".$params["lang"]."
						AND
						l4.id_localizedtext = 252100 AND l4.id_language = ".$params["lang"]."
					";
		$res = $common->getResultArray ( "localizationcombinfo_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		assert(empty($common->errormsg));

		$textD = "nick=".$res[0]['name'].": $userName&complex=".$res[0]['comb'].": $combCoords&world=".$res[0]['world'].": $worldName&league=".$res[0]['league'].": $league";
		SU_OutputHandler::Singleton()->addReadyString($textD);

		return true;
	}
}

?>