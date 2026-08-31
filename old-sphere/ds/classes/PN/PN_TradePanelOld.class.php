<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка торговли (выставление товара на торги)

class PN_TradePanel extends UA_GetBuildMenu
{

	function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel ()
	{

		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><trade />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		//$root->set_attribute ( "c", $this->cellnum );

		// tradescope доступные на этой соте
		$sql = "SELECT
					ts.id_tradescope,
					ts.tradescopename,
					(
						SELECT 
							text
						FROM 
							ds_localizedtextlanguage
						WHERE 
								id_localizedtext = ts.id_localizedname
							AND id_language = ".$this->params["lang"]."
					) AS localizedname
				FROM 
					ds_tradescope ts,
					ds_comballowedtradescope cats
				WHERE 
						ts.id_tradescope = cats.id_tradescope
					AND cats.id_comb = ".$this->comb;
		$res = $this->common->getResultArray("", $sql, "", "", "" );
		assert(empty($this->common->errormsg));

		for($i=0; $i<sizeof ( $res ); $i++)
		{
			if($res[$i]['tradescopename'] == 'ALLIANCE_TRADING')
			{
				// состоит ли в альянсе
				$sql = "SELECT
							id_alliance
						FROM 
							ds_userhasalliance
						WHERE 
							id_userinstance = ".$this->params['uiid'];
				$res2 = $this->common->getResultArray("", $sql, "", "", "");
				assert(empty($this->common->errormsg));

				if(count($res2)==0)
				{
					continue;
				}
			}

			$ch = $root->new_child ( "allowedtradescope" );
			$ch->set_attribute ( "id_tradescope", $res[$i]['id_tradescope'] );
			$ch->set_attribute ( "tradescopename", $res[$i]['tradescopename'] );
			$ch->set_attribute ( "lname", urlencode ( $res[$i]['localizedname'] ) );
		}

		// ресурсы
		$sql = sprintf ( "select id_tradecategory
				from ds_tradecategory
				where categoryname = 'RESOURCE'");

		$res = $this->common->getResultArray ( "id_tradecategory_resource", $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel2";
			return false;
		}

		$id_tradecategory = $res[0]['id_tradecategory'];


		// можно ли торговать ресурсами - ресурсы в наличии


		$sql = "
				SELECT 
					ab.abilityname
				FROM
					ds_userhasability uhab,
					ds_ability ab
				WHERE
					(uhab.id_ability = ab.id_ability) AND 
					(uhab.id_userinstance = ".$this->params["uiid"].")
			";
		$res = $this->common->getResultArray ( "resources_for_trade_id_comb_id_userinstance_".$this->comb."_".$this->params["uiid"], $sql, "", $this->params["uiid"], "LOCAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel3";
			return false;
		}

		foreach ($res as $k=>$resourceName)
		{
			//Прверяем, можем ли мы торговать данным видом ресурса с аукционов.
			
			$resourceName = str_replace("_TRADE_AVAILABLE","",$resourceName['abilityname']);
			if (!in_array($resourceName, GB_ResourcesList::$allResources))
			{
				continue;
			}
			if ( sizeof ( $res ) > 0 )
			{
				// можно торговать
				$amount = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);

				$ch = $root->new_child ( "article" );
				$ch->set_attribute ( "id", DS_Misc::GetResourceIDByName($resourceName) );
				$ch->set_attribute ( "id_category", $id_tradecategory );
				$ch->set_attribute ( "libname", $resourceName );
				$ch->set_attribute ( "amount", number_format ( $amount, 0, ".", "" ) );

			} else {
				// нельзя торговать
				$sql = sprintf ( "select ltl.text as localizedtext
							from ds_localizedtextlanguage ltl,
							ds_systemmessage sm
							where sm.id_localizedtext = ltl.id_localizedtext
							and sm.messagename = 'CANT_TRADE_RESOURCE'
							and ltl.id_language = %d",
				$this->params["lang"] );

				$res = $this->common->getResultArray ( "message_cant_trade_resource_id_language_".$this->params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$res )
				{
					$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel4";
					return false;
				}
				$ch = $root->new_child ( "article" );
				$ch->set_attribute ( "id_category", $id_tradecategory );
				$elem = $doc->create_text_node ( urlencode ( trim ( $res[0]['localizedtext'] ) ) );
				$ch->append_child ( $elem );
			}
		}


		/*********************************************************************************************************/

		$id_tradecategory = "";

		// артефаки
		$sql = sprintf ( "select id_tradecategory
				from ds_tradecategory
				where categoryname = 'ARTEFACT'");

		$res = $this->common->getResultArray ( "id_tradecategory_artefact", $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel5";
			return false;
		}

		$id_tradecategory = $res[0]['id_tradecategory'];

		// можно ли торговать артефактами
		$sql = sprintf ( "select uhab.id_ability
				from ds_userhasability uhab,
				ds_ability ab
				where uhab.id_ability = ab.id_ability
				and uhab.id_userinstance = %d
				and ab.abilityname = 'ARTEFACT_TRADE_AVAILABLE'",
		$this->params["uiid"] );

		$res = $this->common->getResultArray ( "user_has_artefact_trade_ability_id_userinstance_".$this->params["uiid"], $sql, "", $this->params["uiid"], "LOCAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel6";
			return false;
		}

		if ( sizeof ( $res ) > 0 )
		{
			// можно

			// список имеющихся артефактов
			$sql = sprintf ( "select ai.id_artefactinstance,
					ap.prefix||':'||ars.scopename||':'||at.typename as libname
					from ds_artefact a,
					ds_artefactprobability ap,
					ds_artefactscope ars,
					ds_artefacttype at,
					ds_artefactinstance ai,
					ds_combhasartefact cha
					where a.id_artefact = ai.id_artefact
					and ai.id_artefactinstance = cha.id_artefact
					and a.id_artefactprobability = ap.id_artefactprobability
					and a.id_artefactscope = ars.id_artefactscope
					and a.id_artefacttype = at.id_artefacttype
					and cha.id_comb = %d",
			$this->comb );

			$res2 = $this->common->getResultArray ( "artefacts_for_trade_id_comb_".$this->comb, $sql, "", $this->params["uiid"], "LOCAL_CACHE" );
			if ( $res2 === false ) {
				$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel7";

				return false;
			}

			if ( sizeof ( $res2 ) > 0 )
			{

				for ( $e=0; $e<sizeof ( $res2 ); $e++ )
				{

					$ch = $root->new_child ( "article" );
					$ch->set_attribute ( "id", $res2[$e]['id_artefactinstance'] );
					$ch->set_attribute ( "id_category", $id_tradecategory );
					$ch->set_attribute ( "libname", trim ( $res2[$e]['libname'] ) );
					$ch->set_attribute ( "amount", "1" );

					// бонусы атрефакта
					$sql = sprintf ( "select atn.attributename,
							att.typename,
							ahb.value
							from ds_artefacthasbonus ahb,
							ds_artefact a,
							ds_artefactinstance ai,
							ds_attribute at,
							ds_attributename atn,
							ds_attributetype att
							where ahb.id_attribute = at.id_attribute
							and ahb.id_artefact = a.id_artefact
							and a.id_artefact = ai.id_artefact
							and at.id_attributename = atn.id_attributename
							and at.id_attributetype = att.id_attributetype
							and ai.id_artefactinstance = %d",
					$res2[$e]['id_artefactinstance'] );

					$res3 = $this->common->getResultArray ( "artefact_bonus_id_artefactinstance_".$res2[$e]['id_artefactinstance'], $sql, "", "", "GLOBAL_CACHE" );
					if ( $res3 === false )
					{
						$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel8";

						return false;
					}

					for ( $j=0; $j<sizeof ( $res3 ); $j++ )
					{

						$bonus = (float)$res3[$j]['value'];

						if ( $res3[$j]['typename'] == 'BONUS_RELATIVE' )
						{
							$bonus = $bonus*100;
							$bonus .= "%25";
						}
						$bonus = "%2B".$bonus;
						$ch->set_attribute ( "bonusname", trim ( $res3[$j]['attributename'] ) );
						$ch->set_attribute ( "bonusvalue", urlencode ( $bonus ) );
					}
				}

			} else {
				// нет артефактов в наличии

				$sql = sprintf ( "select ltl.text as localizedtext
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where sm.id_localizedtext = ltl.id_localizedtext
						and sm.messagename = 'NO_ARTEFACT_FOR_TRADE'
						and ltl.id_language = %d",
				$this->params["lang"] );

				$res2 = $this->common->getResultArray ( "message_no_artefacts_for_trade_id_language_".$this->params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$res2 )
				{
					$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel9";

					return false;
				}

				$ch = $root->new_child ( "article" );
				$ch->set_attribute ( "id_category", $id_tradecategory );
				$elem = $doc->create_text_node ( urlencode ( trim ( $res2[0]['localizedtext'] ) ) );
				$ch->append_child ( $elem );

			}

		} else {
			// нельзя торговать артефактами

			$sql = sprintf ( "select ltl.text as localizedtext
					from ds_localizedtextlanguage ltl,
					ds_systemmessage sm
					where sm.id_localizedtext = ltl.id_localizedtext
					and sm.messagename = 'CANT_TRADE_ARTEFACT'
					and ltl.id_language = %d",
			$this->params["lang"] );

			$res = $this->common->getResultArray ( "message_cant_trade_artefact_id_language_".$this->params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res ) {
				$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel10";

				return false;
			}

			$ch = $root->new_child ( "article" );
			$ch->set_attribute ( "id_category", $id_tradecategory );
			$elem = $doc->create_text_node ( urlencode ( trim ( $res[0]['localizedtext'] ) ) );
			$ch->append_child ( $elem );
		}

		$id_tradecategory = "";

		// юниты
		$sql = sprintf ( "select id_tradecategory
				from ds_tradecategory
				where categoryname = 'UNIT'");

		$res = $this->common->getResultArray ( "id_tradecategory_unit", $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel11";

			return false;
		}

		$id_tradecategory = $res[0]['id_tradecategory'];

		// можно ли торговать юнитами
		$sql = sprintf ( "select uhab.id_ability
				from ds_userhasability uhab,
				ds_ability ab
				where uhab.id_ability = ab.id_ability
				and uhab.id_userinstance = %d
				and ab.abilityname = 'UNIT_TRADE_AVAILABLE'",
		$this->params["uiid"] );

		$res = $this->common->getResultArray ( "user_has_unit_trade_ability_id_userinstance_".$this->params["uiid"], $sql, "", $this->params["uiid"], "LOCAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel12";

			return false;
		}

		if ( sizeof ( $res ) > 0 )
		{
			// можно

			// список юнитов в наличии
			$sql = sprintf ( "select u.id_unit,
					u.unitname,
					uchu.amount
					from ds_unit u,
					ds_unitcontainerhasunit uchu,
					ds_unitcontainer uc
					where uc.id_basecomb = %d
					and uc.isdefault = true
					and uc.id_unitcontainer = uchu.id_army
					and uchu.id_unit = u.id_unit
					and not exists
					(select id_battle
					from ds_battle
					where id_battlecomb = uc.id_basecomb
					and battleenddate is null)",
			$this->comb );

			$res2 = $this->common->getResultArray ( "units_for_trade_id_comb_".$this->comb, $sql, "", $this->params["uiid"], "LOCAL_CACHE" );
			if ( $res2 === false ) {
				$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel13";

				return false;
			}

			if ( sizeof ( $res2 ) > 0 )
			{
				for ( $e=0; $e<sizeof ( $res2 ); $e++ )
				{
					$id_unit = (int)$res2[$e]['id_unit'];
					$uiid = (int)$this->params['uiid'];

					assert($id_unit > 0);
					assert($uiid > 0);

					$liveUpkeep = GL_LiveUpkeep::CountUnits(DE_Unit::CreateFromID($id_unit), 1 ,DE_UserInstance::CreateFromID($uiid));

					$food = round($liveUpkeep['FOOD'], 10);
					$energy = round($liveUpkeep['STASIS_ENERGY'], 10);
					$kad = round($liveUpkeep['KADERIUM'], 10);
					$nano = round($liveUpkeep['NANO_CRYSTAL'], 10);
					$prod = round($liveUpkeep['PRODIUM'], 10);
					
					$ch = $root->new_child ( "article" );
					$ch->set_attribute ( "id", $id_unit );
					$ch->set_attribute ( "id_category", $id_tradecategory );
					
					$ch->set_attribute ( "food",  $food); 
					$ch->set_attribute ( "energy", $energy );
					$ch->set_attribute ( "kad", $kad );
					$ch->set_attribute ( "nano", $nano );
					$ch->set_attribute ( "prod", $prod );	
					
					$ch->set_attribute ( "libname", $res2[$e]['unitname'] );
					$ch->set_attribute ( "amount", number_format ( $res2[$e]['amount'], 0, ".", "" ) );
				}

			} else {
				// нет юнитов для продажи

				$sql = sprintf ( "select ltl.text as localizedtext
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where sm.id_localizedtext = ltl.id_localizedtext
						and sm.messagename = 'NO_UNIT_FOR_TRADE'
						and ltl.id_language = %d",
				$this->params["lang"] );

				$res2 = $this->common->getResultArray ( "message_no_units_for_trade_id_language_".$this->params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$res2 )
				{
					$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel14";
					return false;
				}

				$ch = $root->new_child ( "article" );
				$ch->set_attribute ( "id_category", $id_tradecategory );
				$elem = $doc->create_text_node ( urlencode ( trim ( $res2[0]['localizedtext'] ) ) );
				$ch->append_child ( $elem );

			}

		}  else {
			// нельзя торговать юнитами

			$sql = sprintf ( "select ltl.text as localizedtext
					from ds_localizedtextlanguage ltl,
					ds_systemmessage sm
					where sm.id_localizedtext = ltl.id_localizedtext
					and sm.messagename = 'CANT_TRADE_UNIT'
					and ltl.id_language = %d",
			$this->params["lang"] );

			$res = $this->common->getResultArray ( "message_cant_trade_unit_id_language_".$this->params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( !$res )
			{
				$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel15";

				return false;
			}

			$ch = $root->new_child ( "article" );
			$ch->set_attribute ( "id_category", $id_tradecategory );
			$elem = $doc->create_text_node ( urlencode ( trim ( $res[0]['localizedtext'] ) ) );
			$ch->append_child ( $elem );
		}

		$id_tradecategory = "";

		// список ресурсов, за которые можно вести торговлю
		$sql = sprintf ( "select a.id_attributename,
				atn.attributename
				from ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'CURRENT_AMOUNT'
				and atn.attributename in ( 'KADERIUM', 'NANO_CRYSTAL', 'STASIS_ENERGY', 'FOOD', 'PRODIUM' )
				order by a.id_attributename" );

		$res = $this->common->getResultArray ( "trade_by", $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR TradePanel::getPanel16";

			return false;
		}

		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{

			$ch = $root->new_child ( "tradeby" );
			$ch->set_attribute ( "id", $res[$i]['id_attributename'] );
			$ch->set_attribute ( "libname", $res[$i]['attributename'] );
		}

		// закладки
		$this->getBuildingTabs ( $root, $this->bid, $this->params["lang"], $this->tab );

		//$panel = "xmldata=";
		//$panel .= $doc->dump_mem();

		//$this->logResponse ( $panel );

		// print $panel;


		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );


		$res = "";
		$res2 = "";
		$res3 = "";
		$doc = "";
		$root = "";

		return true;
	}
}

?>