<?
require_once ('Main.php');

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	if ( !$common->setDbId(0) ) {
		echo "DB-connection failed";
		exit;
	}
}

$sql = sprintf ( "select value 
				from ds_gamemodifier
				where id_world = %d
				and modifiername = 'RESEARCH_BASE_BONUS'",
				10);

$res = $common->getResultArray ( "", $sql, "", "", "" );

if ( !$res )
{
	$err .= " SYSTEM ERROR EconomicalOverview::getPanel3";
	return false;
}
		
$brb = (float)$res[0]['value'];

$sql = sprintf("select ui.id_userinstance, c.id_comb, c.id_combcomplex
				from
					ds_user u,
					ds_userinstance ui,
					ds_comb c
				where
					u.id_user = ui.id_user
					and u.username = '%s'
					and c.id_userinstance = ui.id_userinstance
				",
				'40Min');
				
$resu = $common->getResultArray ( "", $sql, "", "", "" );

		if ( !$resu ) 
		{
			$err .= " SYSTEM ERROR EconomicalOverview::getPanel4a";
			return false;
		}	

$id_userinstance = $resu[0]['id_userinstance'];

foreach ($resu as $k=>$v)
{
	
		$sql = sprintf ( "select sum(chr.value) as combhasunit
				from ds_combhasresource chr,
				ds_attribute a1,
				ds_attributename atn1,
				ds_attributetype att1
				where chr.id_attribute = a1.id_attribute
				and a1.id_attributename = atn1.id_attributename
				and a1.id_attributetype = att1.id_attributetype
				and att1.typename in ( 'CURRENT_AMOUNT', 'AWAY_AMOUNT' )
				and chr.id_comb = %d
				and exists
				(select unitname 
				from ds_unit
				where unitname = atn1.attributename)",
				$v['id_comb'] );
				
		$res = $common->getResultArray ( "", $sql, "","", "" );
		if ( !$res ) 
		{
			$err .= " SYSTEM ERROR EconomicalOverview::getPanel1";
			return false;
		}
		
		$combhasunit = (float)number_format ( $res[0]['combhasunit'], 0, ".", " " );
		
		// среднее значение потребления ресурсов на одного человека

		$sql = sprintf ( "select atn.id_attributename,
				avg(uha.value) as oneupkeep
				from ds_unithasattribute uha,
				ds_unit u,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where u.id_unit = uha.id_unit
				and uha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'TURN_UPKEEP'
				group by atn.id_attributename" );

		$res = $common->getResultArray ( "unit_avg_upkeep", $sql, "", "", "GLOBAL_CACHE" );
		if ( !$res ) 
		{
			$err .= " SYSTEM ERROR EconomicalOverview::getPanel2";
			return false;
		}
		
		$unitupkeep = array();

		for ( $i=0; $i<sizeof ( $res ); $i++ ) 
		{
			$unitupkeep[$res[$i]['id_attributename']] = (float)number_format ( $res[$i]['oneupkeep'], 0, ".", " " );
		}

		// бонус науки
		$sql = sprintf ( "select sban.id_attributename,
				s.level,
				r.exchangerate
				from ds_science s,
				ds_sciencebranch sbr,
				ds_sciencebranchattributename sban,
				ds_resource r,
				(select max(s.id_science) as id_science
				from ds_userhasscience uhs,
				ds_science s,
				ds_sciencebranch sbr
				where uhs.id_science = s.id_science
				and s.id_branch = sbr.id_branch
				and uhs.id_userinstance = %d
				and uhs.inresearch = false
				group by sbr.id_branch) t
				where s.id_science = t.id_science
				and s.id_branch = sbr.id_branch
				and sbr.id_branch = sban.id_sciencebranch
				and sban.id_attributename = r.id_attributename",
				$id_userinstance);

		$res = $common->getResultArray ( "", $sql, "", "", "" );

		if ( !$res ) 
		{
			$err .= " SYSTEM ERROR EconomicalOverview::getPanel4";
			return false;
		}		
		$rb = array ();
		
		for ( $i=0; $i<sizeof ( $res ); $i++ ) 
		{
			$rb[$res[$i]['id_attributename']]['level'] = $res[$i]['level'];
			$rb[$res[$i]['id_attributename']]['exchangerate'] = $res[$i]['exchangerate'];
		}
		
		// производство на соте
		$sql = sprintf ( "select a.id_attribute,
				atn.attributename,
				atn.id_attributename,
				chr.value as production,
				(select count(ab.id_attachedbuilding)
				from ds_attachedbuilding ab,
				ds_buildinghasattribute bha,
				ds_attribute a1,
				ds_attributetype att1
				where ab.id_comb = chr.id_comb
				and ab.id_building = bha.id_building
				and bha.id_attribute = a1.id_attribute
				and a1.id_attributename = atn.id_attributename
				and a1.id_attributetype = att1.id_attributetype
				and att1.typename = 'TURN_PRODUCTION'
				and ab.active = true) as count
				from ds_combhasresource chr,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where chr.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and chr.id_comb = %d
				and att.typename = 'TURN_PRODUCTION'",
				$v['id_comb'] );

		$res = $common->getResultArray ( "", $sql, "", "", "" );

		if ( !$res ) 
		{
			$err .= " SYSTEM ERROR EconomicalOverview::getPanel5";
			return false;
		}
		
		$resource = array ();
		for ( $i=0; $i<sizeof ( $res ); $i++ ) 
		{
			$resource[$res[$i]['id_attributename']]["name"] = $res[$i]['attributename'];
			$production = (int)number_format ( $res[$i]['production'], 0, ".", " " );
			$resource[$res[$i]['id_attributename']]['production'] = $production;
			
			// бонусы к производству

			$sql = sprintf ( "select absolute.value as absolute,
					relative.value as relative
					from
					(select (chr.value +
					coalesce((select sum(ahb.value)
					from ds_artefacthasbonus ahb,
					ds_artefactinstance ai,
					ds_artefactattachedcomplex aacc
					where aacc.id_artefact = ai.id_artefactinstance
					and ai.id_artefact = ahb.id_artefact
					and ahb.id_attribute = chr.id_attribute
					and aacc.id_combcomplex = %d),0)+
					coalesce((select sum(ahb.value)
					from ds_artefacthasbonus ahb,
					ds_artefactinstance ai,
					ds_artefactattachedalliance aaa,
					ds_userhasalliance uha
					where aaa.id_artefact = ai.id_artefactinstance
					and ai.id_artefact = ahb.id_artefact
					and ahb.id_attribute = chr.id_attribute
					and aaa.id_alliance = uha.id_alliance
					and uha.id_userinstance = %d),0)
					) as value,
					chr.id_attribute,
					a.id_attributename
					from ds_combhasresource chr,
					ds_attribute a,
					ds_attributetype att
					where chr.id_attribute = a.id_attribute
					and a.id_attributename = %d
					and a.id_attributetype = att.id_attributetype
					and att.typename = 'BONUS_ABSOLUTE'
					and chr.id_comb = %d) absolute,
					(select (chr.value +
					coalesce((select sum(ahb.value)
					from ds_artefacthasbonus ahb,
					ds_artefactinstance ai,
					ds_artefactattachedcomplex aacc
					where aacc.id_artefact = ai.id_artefactinstance
					and ai.id_artefact = ahb.id_artefact
					and ahb.id_attribute = chr.id_attribute
					and aacc.id_combcomplex = %d),0)+
					coalesce((select sum(ahb.value)
					from ds_artefacthasbonus ahb,
					ds_artefactinstance ai,
					ds_artefactattachedalliance aaa,
					ds_userhasalliance uha
					where aaa.id_artefact = ai.id_artefactinstance
					and ai.id_artefact = ahb.id_artefact
					and ahb.id_attribute = chr.id_attribute
					and aaa.id_alliance = uha.id_alliance
					and uha.id_userinstance = %d),0)
					) as value,
					chr.id_attribute,
					a.id_attributename
					from ds_combhasresource chr,
					ds_attribute a,
					ds_attributetype att
					where chr.id_attribute = a.id_attribute
					and a.id_attributename = %d
					and a.id_attributetype = att.id_attributetype
					and att.typename = 'BONUS_RELATIVE'
					and chr.id_comb = %d) relative",
					$v['id_combcomplex'],
					$id_userinstance,
					$res[$i]['id_attributename'],
					$v['id_comb'],
					$v['id_combcomplex'],
					$id_userinstance,
					$res[$i]['id_attributename'],
					$v['id_comb'] );
					
					$res2 = $common->getResultArray ( "", $sql, "", "", "" );
					if ( $res2 == false ) 
					{
						$err .= " SYSTEM ERROR EconomicalOverview::getPanel6";
						return false;
					}
					
					if ( isset ( $rb[$res[$i]['id_attributename']] ) ) 
					{
						$research_bonus = ( $brb / $rb[$res[$i]['id_attributename']]['exchangerate'] )*( $rb[$res[$i]['id_attributename']]['level']-1 );
						if ( $research_bonus <= 0 ) $research_bonus = 0;
					}				

					$bonusproduction = (int)number_format ( ( $production+$res2[0]['absolute'] ) + ( ( $production+$res2[0]['absolute'] )*( $res2[0]['relative']+$research_bonus ) ) , 0, ".", " " );
					$resource[$res[$i]['id_attributename']]["bonusproduction"] =  $bonusproduction;
					$resource[$res[$i]['id_attributename']]["count"] = (int)number_format ( $res[$i]['count'], 0, ".", " " );
					$bonusproduction = "";
					$production = "";
			
		}
		
		// потребление на соте

		$sql = sprintf ( "select atn.id_attributename,
				chr.value as upkeep
				from ds_combhasresource chr,
				ds_attribute a,	
				ds_attributename atn,					
				ds_attributetype att
				where chr.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and chr.id_comb = %d
				and att.typename = 'TURN_UPKEEP'",
				$v['id_comb'] );

		$res = $common->getResultArray ( "", $sql, "", "", "" );
		if ( !$res ) 
		{
			$err .= " SYSTEM ERROR EconomicalOverview::getPanel7";
			return false;
		}
		
		for ( $i=0; $i<sizeof ( $res ); $i++ ) 
		{

			$resource[$res[$i]['id_attributename']]["upkeep"] = 
			(int)number_format ( $res[$i]['upkeep'], 0, ".", " " );
			
			if ( isset ( $unitupkeep[$res[$i]['id_attributename']] ) ) 
			{
				$resource[$res[$i]['id_attributename']]["upkeep"] += (int)number_format ( $unitupkeep[$res[$i]['id_attributename']]*$combhasunit, 0, ".", " " );
			}

		}
		
		/*foreach ( $resource as $r) 
		{
			$ch = $root->new_child ( $r["name"] );
			$ch->set_attribute ( "production", $r["production"] );			
			$ch->set_attribute ( "bonusproduction", $r["bonusproduction"] );
			$ch->set_attribute ( "count", $r["count"] );

			if ( isset ( $r["upkeep"] ) ) 
			{
				$upkeep = $r["upkeep"];
			} else 
			{
				$upkeep = 0;
			}

			$ch->set_attribute ( "upkeep", $upkeep );
			$diff = $r["bonusproduction"]  - $upkeep;
			$ch->set_attribute ( "diff", $diff );
		}*/
		print_r($resource);
		
}		
?>