<?PHP
// 27.03.2003 version 1.2
//27.03.2003 v1.4
require_once "SessionContinue.php";
if ( !sessionContinue () ) {
	exit;
}

if ( !isset ( $_SESSION["uiid"] ) ) {
	
	if ( ! ( ereg ( "portal.php", @$_SERVER['HTTH_REFERER'] )
	|| ereg ( "ranking2.php", @$_SERVER['HTTH_REFERER'] )
	|| ereg ( "ranking_alliance.php", @$_SERVER['HTTH_REFERER'] )
	|| ereg ( "ranking.php", @$_SERVER['HTTH_REFERER'] ) ) ) {

		exit;
	}
}

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	$common->connect();
}

class Ranking {

	var $errormsg;
	var $db;
	var $limit;
	var $trans;
	var $max_trans = 10;

	function Ranking () {

		$this->limit = 25;
		if ( !$this->showRanking ( $_GET ) ) {
			die ( $this->errormsg );
		}
	}

	function showRanking ( $data ) {

		global $common;

		if ( !isset ( $_SESSION["lang"] ) ) {
			$lang = DEFAULT_LANGUAGE;			
		} else {
			$lang = $_SESSION["lang"];
		}

		$uiid = 0;			
		if ( ( isset ( $_SESSION["uiid"] ) ) && ( !isset ( $_GET["player"] ) ) ) {
			$uiid = $_SESSION["uiid"];
		} 

		if ( !isset ( $_SESSION["world"] ) && !isset ( $_GET["world"] ) ) {

			$world = DEFAULT_WORLD;

		} else if ( isset ( $_GET["world"] ) ) {

			$world = $_GET["world"];

		} else if ( isset ( $_SESSION["world"] ) ) {

			$world = $_SESSION["world"];
		}

		$sql = sprintf ( "select id_world
				from ds_worldhaslanguage
				where id_world = %d
				and id_language = %d",
				$world,
				$lang );

		$res = $common->getResultArray ( "id_world_id_language_".$world."_".$lang, $sql, "", "", "GLOBAL_CACHE" );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR Ranking::ShowRanking1";
			return false;
		}

		if ( sizeof ( $res ) == 0 ) $world = DEFAULT_WORLD;

		$sql = sprintf ( "select count(*) as total_trans 
				from pg_stat_activity 
				where datname = '%s'",
				$common->dbname );
		
		$res = $common->getResultArray ( "total_trans", $sql, "", "", "" );
		if ( !$res ) {
			$this->errormsg = "SYSTEM ERROR Ranking::ShowRanking2";
			return false;
		}

		$trans = $res[0]['total_trans'];
		
		if ($trans < $this->max_trans) {
		
			$playerSearch = @$_GET["player"];

			if ( @$playerSearch ) {
				$playerSearch2 = " and u.username ilike '%" . $playerSearch . "%'";
			} else {
				$playerSearch2 = "";
			}

			$sql = sprintf ( "select ui.id_userinstance
					from
					ds_userinstance ui,
					ds_user u,
					ds_localizedtextlanguage ltl,
					ds_localizedtext lt,
					ds_race r,
					ds_combname cn,
					ds_comb co,
					ds_combcomplex cc,
					ds_combcomplextype cct,
					ds_ring ri
					where ui.id_user = u.id_user
					and ui.id_race = r.id_race
					and ltl.id_localizedtext = lt.id_localizedtext
					and r.id_localizedname = lt.id_localizedtext
					and cn.id_comb = co.id_comb
					and co.id_combcomplex = cc.id_combcomplex
					and cc.id_complextype = cct.id_complextype
					and cct.complextype = 'HABITABLE'
					and co.id_userinstance = ui.id_userinstance

					and cc.id_ring = ri.id_ring
					and ri.id_world = %d
					and ltl.id_language = %d
					%s
					order by ui.userscore desc",
					$world, $lang, $playerSearch2);

			$res = $common->getResultArray ( "ranking2_1_all_users_id_world_id_language_search_".$world."_".$lang."_".$playerSearch, $sql, "", "", "GLOBAL_CACHE" );
			if ( $res === false ) {
				$this->errormsg = "SYSTEM ERROR Ranking::ShowRanking3";
				return false;
			}

			$count = sizeof ( $res );

			if ( isset ( $data["offset"] ) ) {
				if (empty ($data["offset"])) {
					$offset = 0;
				}
				else { 
					$offset = $data["offset"];
				}
			} else {
				if ( $uiid == 0 ) {
					$offset = 0;
				} else {
					$e = 0;
					$a = 0;

					for ( $i=0; $i<sizeof ( $res ); $i++ ) {

						$e++;
						$c = 0;
						if ( $e == $this->limit ) {
							$a++;
							$e=0;
							$c = 1;
						}			
						if ( $res[$i]['id_userinstance'] == $uiid ) {
							if ( $c == 1 ) $a--;
							$offset = ($a)*$this->limit;
							if ( $offset<0 ) $offset = 0;
						}
					}
				}
			}

			if ( !isset ( $offset ) ) $offset = 0;
?>

			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html dir="ltr">
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
			<meta http-equiv="Content-Style-Type" content="text/css">
			<link rel="top" href="index.php" title="" />
			<link rel="search" href="search.php" title="" />
			<link rel="help" href="faq.php" title="" />
			<link rel="author" href="memberlist.php" title="" />

			<title>DestinySphere :: Рейтинги</title>
			<link rel="stylesheet" href="DestinySphere.css" type="text/css">

			</head><body bgcolor="#000000" text="#DDDDDD" link="#8BC7FF" vlink="#5493B4">
			<a name="top"></a> 
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
    			<td width="30" height="30" background="images/corner1.gif" >&nbsp;</td>
    			<td height="30" background="images/corner_bg1.gif">&nbsp;</td>
    			<td height="30" width="30" background="images/corner2.gif">&nbsp;</td>
			</tr>
			<tr>
    			<td background="images/corner_bg2.gif" width="30">&nbsp;</td>
    			<td>
			<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#000000">
			<tr>
		  	<td valign="bottom" class="bodyline"> 
            		<table width="100%" cellspacing="0" cellpadding="0" border="0">
              		<tr> 
                		<td align="center"><a href="../index.php"><span class="maintitle"><img src="images/logo_ds.gif" border="0" alt="Главная страница Destiny Sphere" vspace="1" width="196" height="60" /></span></a><a target="_blank" href="http://www.rambler.ru"><span class="maintitle"><img src="images/logorambler.gif" border="0" alt="Rambler" vspace="1" width="122" height="60" /></span></a><br><br>
			</td>
              		</tr>
            		</table>        
		           <div align="center">
              		<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#000000">
                		<tr> 
                  		<td class="bodyline"> 
                    		<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">
                      		<tr> 
                        	<th class="thHead"  height="25" >
<?
			$sql = sprintf("select wo.ID_World, 
					wo.WorldName
					from DS_World wo
					where exists
					(select id_language
					from ds_worldhaslanguage
					where id_language =%d
					and id_world = wo.id_world)",
					$lang );

			$res = $common->getResultArray ( "world_has_language_id_language_".$lang, $sql, "", "", "GLOBAL_CACHE" );
			if ( $res === false ) {
				echo $sql;
				$this->errormsg = "SYSTEM ERROR Ranking::ShowRanking4";
				return false;
			}

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {
				if ( $res[$i]['id_world'] == $world ) {
					print $res[$i]['worldname']." &nbsp; ";
				}else {
					print "<a href=" . $_SERVER['PHP_SELF'] . "?world=".$res[$i]['id_world'].">".$res[$i]['worldname']."</a> &nbsp; ";
				}
			}	
?>
			</th>
			</tr>                   
			</table>

			<? include ('ranking_bar.php') ?>

			</td>
                		</tr>
              		</table>
              		<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#000000">
                		<tr> 
                  		<td class="bodyline"> 
                    		<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">
                      		<tr> 
                        	<th class="thHead"  height="25" width="1%" >&nbsp;</th>
                        	<th class="thHead"  height="25" width="16%" >Игрок</th>
                        	<th class="thHead"  height="25" valign="middle" width="16%">Название соты</th>
                        	<th class="thHead"  height="25" valign="middle" width="16%">Координаты</th>
                        	<th class="thHead"  height="25" valign="middle" width="16%">Альянс</th>                        
                        	<th class="thHead"  height="25" valign="middle" width="16%">Раса</th>
                        	<th class="thHead"  height="25" valign="middle" width="16%">Очки</th>
			</tr>
<?
			$sql = sprintf ( "select ui.id_userinstance,
					u.username,
					ui.userscore,
					ltl.text as racename,
					cn.combname,
					co.combnumber,
					ri.ringnumber,
					cc.complexnumber,
					(select a.alliancetag
					from ds_alliance a,
					ds_userhasalliance uha
					where a.id_alliance = uha.id_alliance
					and uha.id_userinstance = ui.id_userinstance) as alliancetag
					from ds_user u,
					ds_userinstance ui,
					ds_localizedtextlanguage ltl,
					ds_localizedtext lt,
					ds_race r,
					ds_combname cn,
					ds_comb co,
					ds_combcomplex cc,
					ds_combcomplextype cct,
					ds_ring ri						
					where ui.id_user = u.id_user
					and ui.id_race = r.id_race
					and ltl.id_localizedtext = lt.id_localizedtext
					and r.id_localizedname = lt.id_localizedtext
					and cn.id_comb = co.id_comb
					and co.id_combcomplex = cc.id_combcomplex
					and cc.id_complextype = cct.id_complextype
					and cct.complextype = 'HABITABLE'
					and co.id_userinstance = ui.id_userinstance
					and cc.id_ring = ri.id_ring
					and ri.id_world = %d
					and ltl.id_language = %d
					%s
					order by ui.userscore desc
					limit $this->limit offset $offset",
					$world, $lang, $playerSearch2 );

			$res = $common->getResultArray ( "ranking2_2_all_users_id_world_id_language_search_limit_offset".$world."_".$lang."_".$playerSearch."_".$this->limit."_".$offset, $sql, "", "", "GLOBAL_CACHE" );
			
			if ( $res === false ) {
				$this->errormsg = "SYSTEM ERROR Ranking::ShowRanking5";
				return false;
			}

			$ofs = $offset;

			for ( $i=0; $i<sizeof ( $res ); $i++ ) {

				$ofs++;
?>
				<tr> 
				<td class="row<? print ( $ofs%2 == 0 ? 1 : 2 )?>" width="1%"><span class="gen">
<? 
				if ( $res[$i]['id_userinstance'] == $uiid ) {
					print "&nbsp;<b><font color='#FFCC00'>$ofs</font></b>";
				} else {
					print "&nbsp;".$ofs;
				}
?>
				</span></td>
			 	<td class="row<? print ( $ofs%2 == 0 ? 1 : 2 )?>" width="1%"><span class="gen">
<? 
				if ( $res[$i]['id_userinstance'] == $uiid ) {
					print "&nbsp;<b><font color='#FFCC00'>".$res[$i]['username']."</font></b>";
				} else {
					print "&nbsp;".$res[$i]['username'];
				}
?>
				</span></td>
			 	<td class="row<? print ( $ofs%2 == 0 ? 1 : 2 )?>" width="1%"><span class="gen">

<? 
				if ( $res[$i]['id_userinstance'] == $uiid ) {
					print "&nbsp;<b><font color='#FFCC00'>".$res[$i]['combname']."</font></b>";
				} else {
					print "&nbsp;".$res[$i]['combname'];
				}
?>
				</span></td>

				<td class="row<? print ( $ofs%2 == 0 ? 1 : 2 )?>" width="1%"><span class="gen">

<? 
				if ( $res[$i]['id_userinstance'] == $uiid ) {
					print "&nbsp;<b><font color='#FFCC00'>" .$res[$i]['ringnumber'].".".$res[$i]['complexnumber'].".".$res[$i]['combnumber']."</font></b>";
				} else {
					print "&nbsp;".$res[$i]['ringnumber'].'.'.$res[$i]['complexnumber'].'.'.$res[$i]['combnumber'];
				} 
?>
				</span></td>			 

				<td class="row<? print ( $ofs%2 == 0 ? 1 : 2 )?>" width="1%"><span class="gen">

<? 
				if ( $res[$i]['id_userinstance'] == $uiid ) {
					print "&nbsp;<b><font color='#FFCC00'>".$res[$i]['alliancetag']."</font></b>";
				} else {
					print "&nbsp;".$res[$i]['alliancetag'];
				} 
?>
				</span></td>			 			 
			 
			 	<td class="row<? print ( $ofs%2 == 0 ? 1 : 2 )?>" width="1%"><span class="gen">

<? 
				if ( $res[$i]['id_userinstance'] == $uiid ) {
					print "&nbsp;<b><font color='#FFCC00'>".$res[$i]['racename']."</font></b>";
				} else {
					print "&nbsp;".$res[$i]['racename'];
				} 
?>
				</span></td>

			 	<td class="row<? print ( $ofs%2 == 0 ? 1 : 2 )?>" width="1%"><span class="gen">

<? 
				if ( $res[$i]['id_userinstance'] == $uiid ) {
					print "&nbsp;<b><font color='#FFCC00'>".$res[$i]['userscore']."</font></b>";
				} else {
					print "&nbsp;".$res[$i]['userscore'];
				}

?>
				</span></td>
				</tr>
<?
			}
?>
			<tr>
			<th class="thHead"  height="25" colspan="2">&nbsp;</th>
                        	<th class="thHead"  height="25" colspan="4"><span class="copyright">    
			<table width='100%' border=0 cellpadding=0 cellspacing=0>
			<tr><td>
<?
			if ( $offset - $this->limit >= 0 ) {
?>
				<a href="<?=$_SERVER["PHP_SELF"]?>?world=<?=$world?>&offset=<? print ( $offset - $this->limit >= 0 ? $offset - $this->limit : 0 ) ?>&player=<?= $_GET['player'] ?>">
				<img src='img/button1.gif' border=0></a>
<?
			} else {
?>
				<img src='img/button1.gif' border=0>
<?
			}
?>
			</td>
			<td align=left>
<?
			if ( $offset - $this->limit >= 0 ) {
?>	
				<a href="<?=$_SERVER["PHP_SELF"]?>?world=<?=$world?>&offset=0&player=<?= $_GET["player"] ?>"><font color="#3366CC"><b>[1]</b></font>
<?
			} else {
				print "&nbsp;";
			}
?>
			</td>
			<td align=center><font color="#FFCC33">
			<b>[
<? 
			$a = 0;
			for ($i=0; $i<=$count; $i=$i+$this->limit) {
				$a++;
				if ( $i == $offset ) echo $a;
			}
?>						
			]</b></font></td>
			<td align=right>
<?
			if ( $offset + $this->limit < $count ) {
?>
				<a href="<? print ( $_SERVER["PHP_SELF"]."?world=".$world."&offset=".(ceil($count/$this->limit)-1)*$this->limit ."&player=".$_GET['player'] )?>">
				<font color="#3366CC"><b>[<?=ceil($count/$this->limit)?>]</b></font></a>
<?
			} else {
				print "&nbsp;";
			}
?>
			</td>
			<td align=right>
<?
			if ( $offset + $this->limit < $count ) {
?>
		                      <a href="<?=$_SERVER["PHP_SELF"]?>?world=<?=$world?>&offset=<? print ( $offset + $this->limit >= $count ? $offset : $offset + $this->limit ) ?>&player=<?= $_GET['player'] ?>">
				<img src='img/button2.gif' border=0></a>
<?
			} else {
?>
				<img src='img/button2.gif' border=0>
<?
			}
?>
			</td></tr>
			</table>
			</span></th>
			<th class="thHead"  height="25">&nbsp;</th>
			</tr>
			<form action="<?=$_SERVER["PHP_SELF"]?>" method="get" name="searchPlayer">
              		<input name="world" type="hidden" value="<?=$world?>">
			<tr>
			<th class="thHead"  height="25" colspan="7">
			<font class="searchtext">Поиск игрока</font> <input name="player" class="inputbox" type="text" size="15">&nbsp;<input NAME="submit" TYPE="image" src="img/button2.gif" border=0>
			</th>
			</tr>
			</form>               
			</table>
			</td>
			</tr>
			</table>
			<div align="center"><span class="copyright"> &nbsp;&nbsp; 
			</span></div>
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td align="center" class="copyright">&copy; Copyright 2003-2004 by 
			DestinySphere GmbH</td>
			</tr>
			<tr>
			<td align="center" class="copyright">&nbsp;</td>
			</tr>
			</table>
			<span class="copyright"><br>
			<br />
			</span></div>
			</td></tr></table>
			</td>
			<td width="30" background="images/corner_bg3.gif">&nbsp;</td>
			</tr>
			<tr>
			<td height="30" width="30" background="images/corner3.gif">&nbsp;</td>
			<td height="30" background="images/corner_bg4.gif">&nbsp;</td>
			<td height="30" width="30" background="images/corner4.gif">&nbsp;</td>
			</table>
			</body>
			</html>

<?
			$res = "";

			return true;

		} else {
	 
			include ('ranking_unavailable.php') ?>

<?
		}
  	}
}

$r = new Ranking;

?>