<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";


class DS_StatsViewer
{
	var $commonMaster = '';
	var $worldsList = '';

	function DS_StatsViewer()
	{
		$this->commonMaster = &ClassFactory::getInstance ( "DS_Common" );
		$this->commonMaster->setDbId(0);
		if ( !$this->commonMaster->conn )
		{
			die("can't connect to master");
		}

		$this->showRequestForm();
		if(isset($_POST['get'])) $this->showResult();

	}
	
	
	function showRequestForm()
	{
		$this->writeJS();
		
		$sql = 'SELECT worldname, id_worldstats FROM ds_worldsstatisic';
		$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			die("can't get list of worlds");
		}
		
		$days_to = '';
		$months_to = '';
		$years_to = '';
		for($i=1; $i<=31; $i++)
		{
			if(isset($_POST['td'])&&((int)$_POST['td'] === $i))
			{
				$days_to.= "<OPTION value=".$i." SELECTED>".$i."</OPTION>";
			}
			else 
			{
				$days_to.= "<OPTION value=".$i.">".$i."</OPTION>";
			}
			
		}
		for($i=1;$i<=12;$i++)
		{
			if(isset($_POST['tm'])&&((int)$_POST['tm'] === $i))
			{
				$months_to.= "<OPTION value=".$i." SELECTED>".$i."</OPTION>";
			}
			else 
			{
				$months_to.= "<OPTION value=".$i.">".$i."</OPTION>";
			}
		}
		for ($i=2005;$i<=date('Y');$i++ )
		{
			if(isset($_POST['ty'])&&((int)$_POST['ty'] === $i))
			{
				$years_to.= "<OPTION value=".$i." SELECTED>".$i."</OPTION>";
			}
			else 
			{
				$years_to.= "<OPTION value=".$i.">".$i."</OPTION>";
			}
		}
		
		$days_from = '';
		$months_from = '';
		$years_from = '';
		for($i=1; $i<=31; $i++)
		{
			if(isset($_POST['fd'])&&((int)$_POST['fd'] === $i))
			{
				$days_from.= "<OPTION value=$i SELECTED>$i";
			}
			else 
			{
				$days_from.= "<OPTION value=$i>$i";
			}
		}
		for($i=1;$i<=12;$i++)
		{			
			if(isset($_POST['fm'])&&((int)$_POST['fm'] == $i))
			{
				$months_from.= "<OPTION value=$i SELECTED>$i";				
			}
			else
			{
				$months_from.= "<OPTION value=$i>$i";
			}
		}
		for ($i=2005;$i<=date('Y');$i++ )
		{
			if(isset($_POST['fy'])&&((int)$_POST['fy'] === $i))
			{
				$years_from.= "<OPTION value=$i SELECTED>$i";
			}
			else 
			{
				$years_from.= "<OPTION value=$i>$i";
			}
		}		
				

		echo "<FORM method='POST' action='".$_SERVER['PHP_SELF']."'>		
  				<TABLE border='0' cellspacing=0 cellpadding=0>
								<TR>
								<TD>
								<TABLE border='0' cellspacing=0 cellpadding=0 bgcolor=222233>
								<TR>
								<TD>
								<TABLE  border=0 cellspacing=1 cellpadding=4>
  								<TR>		  									
								<TD align='center' bgcolor='444E6C' class=th_2>World:</TD>
								</TR>
								<TR>
								<TD bgcolor='#444E6C' class=maintxt>
								<SELECT name='world[]' MULTIPLE size=3>";
		
		$world = array();
		if(isset($_POST['world']))
		{
			foreach ($_POST['world'] as $k=>$v)
			{
				$world[$v] = 0;
			}
		}
		
		foreach ($res as $k=>$v)
		{
			if(isset($world[$v['id_worldstats']]))
			{
				echo "<OPTION value=".$v['id_worldstats']." selected>".$v['worldname']."</OPTION>";
			}
			else 
			{
				echo "<OPTION value=".$v['id_worldstats'].">".$v['worldname']."</OPTION>";
			}
		}
		echo "</SELECT>
								</TD>
								</TR>
								</TABLE>
								</TD>
								</TR>
								</TABLE>				
				<TD	align='center'>	
									<TABLE border='0' cellspacing=0 cellpadding=0 bgcolor=222233>
									<TR>
									<TD>
									<TABLE border=0 cellspacing=1 cellpadding=4>
											<TR>
												<TD colspan='3' align='center' bgcolor='444E6C' class=th_2> Показать статистику по:</TD>
											</TR>
											<TR>";
		$checked = '';
		if(isset($_POST['show_battles']))
		{
			$checked = 'checked';
		}
		echo "<TD align='left' class=maintxt bgcolor=#444E6C>Боям &nbsp&nbsp&nbsp&nbsp<INPUT type='checkbox' $checked name='show_battles' class='check'></TD>";
		$checked = '';
		if(isset($_POST['show_users']))
		{
			$checked = 'checked';
		}
		echo "<TD align='center' class=maintxt bgcolor=#444E6C>Притоку игроков &nbsp <INPUT type='checkbox' $checked name='show_users' class='check'></TD>";
		$checked = '';
		if(isset($_POST['show_premium']))
		{
			$checked = 'checked';
		}
		echo "<TD align='right' class=maintxt bgcolor=#444E6C>Премиум игрокам &nbsp <INPUT type='checkbox' $checked name='show_premium' class='check'></TD>
											</TR>
											<TR>";
		$checked = '';
		if(isset($_POST['show_combs']))
		{
			$checked = 'checked';
		}
		echo "<TD align='left' class=maintxt bgcolor=#444E6C>Сотам &nbsp <INPUT type='checkbox' $checked name='show_combs' class='check'></TD>";
		$checked = '';
		if(isset($_POST['show_trades']))
		{
			$checked = 'checked';
		}		
		echo "<TD align='right'  class=maintxt bgcolor=#444E6C>Торговле <INPUT type='checkbox' name='show_trades' $checked class='check'></TD>";
		$checked = '';
		if(isset($_POST['show_iou_worlds']))
		{
			$checked = 'checked';
		}	
		echo "<TD align='right'  class=maintxt bgcolor=#444E6C>Заселению миров<INPUT type='checkbox' name='show_iou_worlds' $checked class='check'></TD>
											</TR>
									</TABLE>
									</TD>
									</TR>
									</TABLE>	
				</TD>	
			</TR>
			<TR>		
  				<TD colspan ='2'>
  				<br>
  				<TABLE border='0' cellspacing=0 cellpadding=0 bgcolor=222233>
				<TR>
				<TD>
  				<TABLE border='0'>
  								<TR>
  									<TD colspan ='2' class=th_2 align='center'>  									
  									Дата
  									</TD>
  								</TR>
  								<TR>		
  									<TD class=maintxt bgcolor=#444E6C>
  										C:&nbsp
  										<SELECT name='fd'>$days_from</SELECT>&nbsp
  										<SELECT name='fm'>$months_from</SELECT>&nbsp
  										<SELECT name='fy'>$years_from</SELECT>&nbsp		
  										&nbsp По:&nbsp
  										<SELECT name='td'>$days_to</SELECT>&nbsp
  										<SELECT name='tm'>$months_to</SELECT>&nbsp
  										<SELECT name='ty'>$years_to</SELECT>  	
  									</TD>
  									<TR>
  									<TD align='left' colspan ='2' class=maintxt bgcolor=#444E6C>			
  										<INPUT type='submit' class=Submit name='get' value='Получить'>
  									</TD>
  									</TR>
  								</TR>
  						</TABLE>
  						</TD>
						</TR>
						</TABLE>
  					</TD>
  				</TR>
  		</TABLE>  		
  		</FORM>";  		
		
	}
	
	function writeJS()
	{
		echo "<SCRIPT language='JavaScript'>
				function openGraph(fn,count_x)
				{	
					count_x = count_x * 40 + 140;	
																			
					if(count_x <= 0) 
					{
						count_x = 800;
					}
					if(count_x > screen.width)	
					{
						count_x = screen.width;
					}
					
					count_y = 500;							
											
					var str = getFormData(fn);										
					window.open('./graph/linegraph.php'+str, '_blank', 'width='+count_x+', height='+count_y+ 
								',left=80,top=80,resizable=no,scrollbars=yes');
								
				}
				
				function getFormData(fn)
				{											
					var str = '?';					 
					 for(i=0; i<document.forms[fn].length; i++)
					 {
					 	if (i == 0)
					 	{
					 		str = str + 
					 				document.forms[fn].elements[i].name + '=' +
					 				document.forms[fn].elements[i].value;
					 	}
					 	else
					 	{					 	
					 		str = str + '&' +
					 				document.forms[fn].elements[i].name + '=' +
					 				document.forms[fn].elements[i].value;
					 	}
					 }
					 					
					 return str;
					 
				}
				
				
				function setCheckBox(fn,en)
				{		
					if(document.forms[fn].elements[en].value == 'on')
					{						
						document.forms[fn].elements[en].value = 'off';						
					}
					else
					{						
						document.forms[fn].elements[en].value = 'on';					
					}
				}
			</SCRIPT>'";
	}

	function showResult()
	{	
		assert(isset($_POST['fd'])&&isset($_POST['fm'])
		&&isset($_POST['fy'])&&isset($_POST['td'])&&isset($_POST['tm'])
		&&isset($_POST['ty']));
				
		$this->worldsList = join(',',$_POST['world']);			
		$from_date = $_POST['fd'].'/'.$_POST['fm'].'/'.$_POST['fy'];
		$to_date = $_POST['td'].'/'.$_POST['tm'].'/'.$_POST['ty'];
				
		$from_date .= ' 00:00:00';
		$to_date .= ' 23:59:59';
				 						
		if(isset($_POST['show_users'])) $this->showInOutUsers($from_date,$to_date);
		if(isset($_POST['show_battles'])) $this->showBattles($from_date,$to_date);
		if(isset($_POST['show_trades'])) $this->showTrades($from_date,$to_date);
		if(isset($_POST['show_combs'])) $this->showCombs($from_date,$to_date);
		if(isset($_POST['show_premium'])) $this->showPremiumStats($from_date,$to_date);		
		if(isset($_POST['show_iou_worlds'])) $this->showInOutWorldUsers($from_date,$to_date);
	}

	function showInOutUsers($from_date,$to_date)
	{
		
		$sql = sprintf("SELECT
      							ious.users_in,
      							ious.users_out,
      							to_char(s.statsdate,'DD.MM.YY') as date,
      							to_char ( s.statsdate, 'YYYY-MM-DD' ) as date2
						FROM
    							ds_inoutusersstats ious,
    							ds_statistic s
						WHERE
     							s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     							AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     							AND s.id_statistic = ious.id_statistic",
		$to_date,
		$from_date);		
				
		$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
		if ( $this->commonMaster->errormsg )
		{
			echo "can't get in/out user stats<br>";
			return false;
		}

		$count_x = $this->getCountX($res);				
		$sum_in = 0;
		$sum_out = 0;
		$form_elements_counter = 5;
		
		echo "
			<FORM id='graph_inoutusers'>
					<INPUT type='hidden' name='type' value='inoutusers'>
					<INPUT type='hidden' name='startdate' value='".$from_date."'>
					<INPUT type='hidden' name='enddate' value='".$to_date."'>
					<INPUT type='hidden' name='legend_users_in' value='Зарегистрировано'>
					<INPUT type='hidden' name='legend_users_out' value='Удалено'>
			<TABLE width='420' border='0' bgcolor=222233 cellspacing='0' cellpadding='0'>
				<TR>
				<TD>
				<TABLE table width=100% border='0' cellspacing='1' cellpadding='4'>
				<TR>
				<TD colspan='3' class=th_2 align='center'>Приток/отток игроков</TD>
				</TR>
				<TR>
					<TD class=th_2 align='center'>Дата</TD>
					<TD class=th_2 align='center'>Приток</TD>
					<TD class=th_2 align='center'>Отток</TD>					
				</TR>";

		if(sizeof($res) > 0)
		{
			foreach ($res as $k=>$v)
			{
				echo "<TR>							
							<TD align='center' class=maintxt bgcolor=#444E6C>". $v['date']."</TD>
							<TD align='center' width='20' class=maintxt bgcolor=#444E6C>".$v['users_in']."</TD>
							<TD align='center' width='20' class=maintxt bgcolor=#444E6C>".$v['users_out']."</TD>							
						</TR>";
				$sum_in += $v['users_in'];		
				$sum_out += $v['users_out'];	
			}
		}
		echo "<TR bgcolor='#FFDFDF'>
					<TD align='center' class=th_2 >&nbsp</TD>
					<TD align='center' class=th_2 >
							Всего прибыло ".$sum_in."<br>
							<INPUT type='checkbox' value='off' class=check name='(users_in)(-1)' onchange='setCheckBox(\"graph_inoutusers\",".$form_elements_counter++.")'>
					</TD>
					<TD align='center' class=th_2 >
							Всего убыло ".$sum_out."<br>
							<INPUT type='checkbox' value='off' class=check name='(users_out)(-1)' onchange='setCheckBox(\"graph_inoutusers\",".$form_elements_counter++.")'>
					</TD>					
				</TR>	
				<TR>
						<TD colspan='3' class=th_2 align='left'>
						<INPUT type='button' onclick='openGraph(\"graph_inoutusers\",". $count_x.");' class=Submit value='Показать графики'>
						</TD>
				</TR>
				</TABLE>
				
				</TD>	
				</TR>			
				</TABLE></FORM>";
		
		return true;
	}
	
	function dateToDays($date)
  	{
  		return ceil((strtotime($date)/(60*60*24)));  		  		
  	}
  	
  	function getCountX($res)
  	{  		
  		return 	
  			($this->dateToDays($res[sizeof($res)-1]['date2']) - 
  				$this->dateToDays($res[0]['date2']) + 1) ; 		
  	}

	function showBattles($from_date, $to_date)
	{
		if(!isset($this->worldsList))
		{
			echo  "<FONT color='Red'>choose any worlds please for show battles</FONT>";
		}
		else 
		{											
			$sql = sprintf("SELECT
      								bs.currentbattles,
      								bs.endbattles,
      								ws.worldname,
      								ws.id_worldstats,
      								to_char(s.statsdate,'DD.MM.YY') as date,
      								to_char ( s.statsdate, 'YYYY-MM-DD' ) as date2
      								
								FROM 
    	   							ds_statistic s,
       								ds_battlestats bs,
       								ds_worldsstatisic ws
								WHERE
     								s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.id_statistic = bs.id_statistic
	     							AND bs.id_worldstats = ws.id_worldstats
    	 							AND ws.id_worldstats IN (%s)
     							ORDER BY ws.id_worldstats, s.statsdate",																
								$to_date,
								$from_date,
								$this->worldsList);		
								
							
			$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
			if ( $this->commonMaster->errormsg )
			{
				echo "can't get battle stats<br>".$this->commonMaster->errormsg;
				return false;
			}									
			$count_x = $this->getCountX($res);				
			$sum_notEndBattles = 0;
			$sum_endBattles = 0;						
			
			$sum_endBattlesInWorld = 0;
			$sum_notEndBattlesInWorld = 0;
			$cur_world = '';
			$curIdworld;
			
			$id_worldstats = '';
			$form_elements_counter = 5;
			
			echo "<FORM id='graph_battles'>
					<INPUT type='hidden' name='type' value='battles'>
					<INPUT type='hidden' name='startdate' value='".$from_date."'>
					<INPUT type='hidden' name='enddate' value='".$to_date."'>
					<INPUT type='hidden' name='legend_endbattles' value='Завершенных боёв'>
					<INPUT type='hidden' name='legend_currentbattles' value='Незавершенных боёв'>
					<TABLE width='420' border='0' bgcolor=222233 cellspacing='0' cellpadding='0'>
					<TR>
					<TD>
					<TABLE table width=100% border='0' cellspacing='1' cellpadding='4'>
					<TR>
						<TD colspan='4' class=th_2 align='center'>Статистика боев</TD>
					</TR>
				
					<TR>
						<TD class=th_2 align='center'> Дата</TD>
						<TD class=th_2 align='center'> Мир</TD>
						<TD class=th_2 align='center'> Завершенных боев</TD>
						<TD class=th_2 align='center'> Незавершенных боев</TD>						
					</TR>";
		
			if(sizeof($res) > 0)
			{						
			
				foreach ($res as $k=>$v)
				{
				
					if(strcmp($cur_world,$v['worldname']) === 0)
					{									
						$sum_endBattlesInWorld += $v['endbattles'];
						$sum_notEndBattlesInWorld += $v['currentbattles'];						
					}
					elseif ($cur_world === '')
					{
						$cur_world = $v['worldname'];
						$sum_endBattlesInWorld += $v['endbattles'];
						$sum_notEndBattlesInWorld += $v['currentbattles'];	
						$id_worldstats .= $v['id_worldstats'].','; 	
						$curIdworld = $v['id_worldstats'];			
					}
					else 
					{
						echo "<TR>		
									
									<TD class=th_2 align='center' colspan='2'>
										Всего для мира $cur_world 
										<br>
										<INPUT type='checkbox' value='off' class=check name='(endbattles,currentbattles)(".$curIdworld.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>						
									</TD>
									<TD class=th_2 align='center'>
										$sum_endBattlesInWorld
										<br>
										<INPUT type='checkbox' value='off' class=check name='(endbattles)(".$curIdworld.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
									</TD>
									<TD class=th_2 align='center'>
										$sum_notEndBattlesInWorld
										<br>
										<INPUT type='checkbox' value='off' class=check name='(currentbattles)(".$curIdworld.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
									</TD>									
								</TR>";
						$cur_world = $v['worldname'];
						$curIdworld = $v['id_worldstats'];
						$sum_endBattlesInWorld = $v['endbattles'];
						$sum_notEndBattlesInWorld = $v['currentbattles'];
						$id_worldstats .= $v['id_worldstats'].','; 
					}
				
					echo "<TR>
								<TD class=maintxt bgcolor=#444E6C align='center'>".$v['date']."</TD>
								<TD class=maintxt bgcolor=#444E6C align='center'>".$v['worldname']."</TD>
								<TD class=maintxt bgcolor=#444E6C align='center'>".$v['endbattles']."</TD>
								<TD class=maintxt bgcolor=#444E6C align='center'>".$v['currentbattles']."</TD>								
							</TR>";
				
					$sum_endBattles += $v['endbattles'];
					$sum_notEndBattles += $v['currentbattles'];
				}
			}
								
			echo "<TR>											
					  <TD class=th_2 align='center' colspan='2'>
							Всего для мира $cur_world 
							<br>
							<INPUT type='checkbox' value='off' class=check name='(endbattles,currentbattles)(".$curIdworld.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
					  </TD>
					  <TD class=th_2 align='center'>
							$sum_endBattlesInWorld
							<br>
							<INPUT type='checkbox' value='off' class=check name='(endbattles)(".$curIdworld.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
					  </TD>
					  <TD class=th_2 align='center'>
							$sum_notEndBattlesInWorld
							<br>
							<INPUT type='checkbox' value='off' class=check name='(currentbattles)(".$curIdworld.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
						</TD>									
				</TR>
				<TR bgcolor='#FFDFDF'>
					<TD class=th_2 colspan='2' align='center'>
						Всего боёв 
						<br>
						<INPUT type='checkbox' value='off' class=check name='(endbattles,currentbattles)(".$id_worldstats.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
					</TD>
					<TD align='center' class=th_2>
						$sum_endBattles
						<br>
						<INPUT type='checkbox' value='off' class=check name='(endbattles)(".$id_worldstats.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
					</TD>
					<TD align='center' class=th_2>
						$sum_notEndBattles
						<br>
						<INPUT type='checkbox' value='off' class=check name='(currentbattles)(".$id_worldstats.")' onchange='setCheckBox(\"graph_battles\",".$form_elements_counter++.")'>
						
					</TD>					
				</TR>
				<TR>
						<TD colspan='4' class=th_2 align='left'>
						<INPUT type='button' onclick='openGraph(\"graph_battles\",". $count_x.");' class=Submit value='Показать графики'>
						</TD>
				</TR>
				</TABLE>
				</TD>	
				</TR>							
				</TABLE></FORM>";			
		}
		return true;
	}
	
	function showPremiumStats($from_date, $to_date)
	{
		$sql = sprintf("SELECT
      							ps.premiumsbuy,
      							ps.premiumsdie,
      							ps.totalsum,
      							ps.trialsregister, 								
      							to_char(s.statsdate,'DD.MM.YY') as date,
      							to_char ( s.statsdate, 'YYYY-MM-DD' ) as date2
 							FROM
     							ds_statistic s,
     							ds_premiumstats ps     							
     						WHERE
                                s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     							AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     							AND s.id_statistic = ps.id_statistic
     							ORDER BY s.statsdate",
							$to_date,
							$from_date);
		$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
		if ( $this->commonMaster->errormsg )
		{
			echo "can't get premium stats<br>";
			return false;
		}									
		
		$form_elements_counter = 7;
		$count_x = $this->getCountX($res);				
		
		echo "
			<FORM id='graph_premiums'>
					<INPUT type='hidden' name='type' value='premiumstats'>
					<INPUT type='hidden' name='startdate' value='".$from_date."'>
					<INPUT type='hidden' name='enddate' value='".$to_date."'>
					<INPUT type='hidden' name='legend_trialsregister' value='Зарегистрировано триалов'>
					<INPUT type='hidden' name='legend_premiumsdie' value='Закончился срок премиума'>
					<INPUT type='hidden' name='legend_premiumsbuy' value='Купило премиум'>
					<INPUT type='hidden' name='legend_totalsum' value='Сумма покупок'>
			<TABLE width='420' border='0' bgcolor=222233 cellspacing='0' cellpadding='0'>
				<TR>
				<TD>
				<TABLE table width=100% border='0' cellspacing='1' cellpadding='4'>
				<TR>
				<TD colspan='5' class=th_2 align='center'>Премиум статистика</TD>
				</TR>
				<TR>
					<TD class=th_2 align='center'>Дата</TD>
					<TD class=th_2 align='center'>Купило премиум</TD>
					<TD class=th_2 align='center'>Не продлило премиум</TD>
					<TD class=th_2 align='center'>Сумма покупок премиума</TD>
					<TD class=th_2 align='center'>Зарегистрировано триалов</TD>					
				</TR>";
		
		if(sizeof($res) > 0)
		{
			$sum_premiumsbuy = 0;
			$sum_premiumsdie = 0;
			$sum_totalsum = 0;
			$sum_trialsregister = 0;
			
			foreach ($res as $k=>$v)
			{
				echo "<TR>							
							<TD align='center' class=maintxt bgcolor=#444E6C>". $v['date']."</TD>
							<TD align='center'  class=maintxt bgcolor=#444E6C>".$v['premiumsbuy']."</TD>
							<TD align='center'  class=maintxt bgcolor=#444E6C>".$v['premiumsdie']."</TD>
							<TD align='center'  class=maintxt bgcolor=#444E6C>".$v['totalsum']."</TD>
							<TD align='center'  class=maintxt bgcolor=#444E6C>".$v['trialsregister']."</TD>							
						</TR>";
				$sum_premiumsbuy += $v['premiumsbuy'];
				$sum_premiumsdie += $v['premiumsdie'];
				$sum_totalsum += $v['totalsum'];
				$sum_trialsregister += $v['trialsregister'];	
			}
		}
		
		echo "<TR>
					<TD class=th_2>&nbsp</TD>
					<TD align='center' class=th_2>"
					.$sum_premiumsbuy."<br>
					<INPUT type='checkbox' value='off' class=check name='(premiumsbuy)(-1)' onchange='setCheckBox(\"graph_premiums\",".$form_elements_counter++.")'>
					</TD>
					<TD align='center' class=th_2>".
					$sum_premiumsdie."<br>
					<INPUT type='checkbox' value='off' class=check name='(premiumsdie)(-1)' onchange='setCheckBox(\"graph_premiums\",".$form_elements_counter++.")'>
					</TD>					
					<TD align='center' class=th_2>".
					$sum_totalsum."<br>
					<INPUT type='checkbox' value='off' class=check name='(totalsum)(-1)' onchange='setCheckBox(\"graph_premiums\",".$form_elements_counter++.")'>
					</TD>	
					<TD align='center' class=th_2>".
					$sum_trialsregister."<br>
					<INPUT type='checkbox' value='off' class=check name='(trialsregister)(-1)' onchange='setCheckBox(\"graph_premiums\",".$form_elements_counter++.")'>
					</TD>						
				</TR>	
				<TR>
						<TD colspan='5' class=th_2 align='left'>
						<INPUT type='button' id='2' onclick='openGraph(\"graph_premiums\",". $count_x.");' class=Submit value='Показать графики'>						
						</TD>
				</TR>
				</TABLE>
				
				</TD>	
				</TR>			
				</TABLE></FORM>";
		
		return  true;
	}
	
	function showTrades($from_date, $to_date)
	{
		
		if(!isset($this->worldsList))
		{
			echo  "<br><FONT color='Red'>choose any worlds please for show trades</FONT><br>";
		}
		else 
		{
			$sql = sprintf("SELECT
 									ts.trades,
 									ws.worldname,
 									to_char(s.statsdate,'DD.MM.YY') as date,
 									ws.id_worldstats,
 									to_char ( s.statsdate, 'YYYY-MM-DD' ) as date2
 								FROM
     								ds_statistic s,
     								ds_tradestats ts,
	     							ds_worldsstatisic ws
    	 						WHERE s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.id_statistic = ts.id_statistic
     								AND ts.id_worldstats = ws.id_worldstats
     								AND ws.id_worldstats IN (%s)
     							ORDER BY ws.id_worldstats, s.statsdate",
								$to_date,
								$from_date,
								$this->worldsList);		
							
			$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
			if ( $this->commonMaster->errormsg )
			{
				echo "can't get trade stats<br>";
				return false;
			}		
 
			$sum_trades = 0;	
			$count_x = $this->getCountX($res);	
			$cur_world = '';
			$sumTradesInWorld = 0;
			$curIdworld;
			$id_worldstats = '';
			$form_elements_counter = 4;
		
			echo "
					<FORM id='graph_trades'>
					<INPUT type='hidden' name='type' value='trades'>
					<INPUT type='hidden' name='startdate' value='".$from_date."'>
					<INPUT type='hidden' name='enddate' value='".$to_date."'>
					<INPUT type='hidden' name='legend_trades' value='Количество торгов'>					
					<br><TABLE width='420' border='0' bgcolor=222233 cellspacing='0' cellpadding='0'>
					<TR>
					<TD>
					<TABLE table width=100% border='0' cellspacing='1' cellpadding='4'>
					<TR>
						<TD colspan='3' class=th_2 align='center'>Статистика торгов</TD>
					</TR>
					<TR>
						<TD class=th_2 align='center'> Дата </TD>	
						<TD class=th_2 align='center'> Мир</TD>
						<TD class=th_2 align='center'> Торговых сделок</TD>									
					</TR>";
			
			if(sizeof($res) > 0 )
			{							
				foreach ($res as $k=>$v)
				{
				
					if(strcmp($cur_world,$v['worldname']) === 0)
					{									
						$sumTradesInWorld += $v['trades'];					
					}
					elseif ($cur_world === '')
					{
						$cur_world = $v['worldname'];
						$sumTradesInWorld += $v['trades'];	
						$curIdworld = $v['id_worldstats'];		
						$id_worldstats .= $v['id_worldstats'].',';			
					}
					else 
					{
						echo "<TR>								
								<TD class=th_2 align='center' colspan='2'>Всего для мира $cur_world</TD>
								<TD class=th_2 align='center'>
									$sumTradesInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(trades)(".$curIdworld.")' onchange='setCheckBox(\"graph_trades\",".$form_elements_counter++.")'>
								</TD>																
							</TR>";
						$cur_world = $v['worldname'];		
						$curIdworld = $v['id_worldstats'];			
						$sumTradesInWorld = $v['trades'];
						$id_worldstats .= $v['id_worldstats'].','; 
					}
				
				
					echo "<TR>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['date']."</TD>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['worldname']."</TD>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['trades']."</TD>														
						</TR>";
					$sum_trades += $v['trades'];
				}	
			}
				
		
			echo "<TR>								
					<TD class=th_2 align='center' colspan='2'>Всего для мира $cur_world</TD>
					<TD class=th_2 align='center'>
					$sumTradesInWorld <br>
					<INPUT type='checkbox' value='off' class=check name='(trades)(".$curIdworld.")' onchange='setCheckBox(\"graph_trades\",".$form_elements_counter++.")'>
					</TD>																
				  </TR>
					<TR >
					<TD class=th_2 colspan='2' align='center'>Всего торгов</TD>
					<TD class=th_2 align='center'>
					$sum_trades </br>
					<INPUT type='checkbox' value='off' class=check name='(trades)(".$id_worldstats.")' onchange='setCheckBox(\"graph_trades\",".$form_elements_counter++.")'>
					</TD>										
					</TR>
					<TR>
						<TD colspan='4' class=th_2 align='left'>
						<INPUT type='button' onclick=\"openGraph('graph_trades',". $count_x.");\" class=Submit value='Показать графики'>
						</TD>
					</TR>
					</TABLE>
				
					</TD>	
					</TR>				
					</TABLE>
					</FORM>";
		}
		
		return true;
	}
	
	function showCombs($from_date, $to_date)
	{
		if(!isset($this->worldsList))
		{
			echo  "<br><FONT color='Red'>choose any worlds please for show combs</FONT><br>";
		}
		else 
		{
			$sql = sprintf("SELECT      					
      						to_char(s.statsdate,'DD.MM.YY') as date,
      						ws.worldname,
      						cs.freecombs,
      						cs.occupycombs,
      						cs.id_ring
					FROM
    						ds_statistic s,
    						ds_combstats cs,
    						ds_worldsstatisic ws
					WHERE s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
							AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
							AND s.id_statistic = cs.id_statistic
							AND cs.id_worldstats = ws.id_worldstats
							AND ws.id_worldstats IN (%s)
							ORDER BY  ws.worldname, s.statsdate",
							$to_date,
							$from_date,
							$this->worldsList);			
		
			$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
			if ( $this->commonMaster->errormsg )
			{
				echo "can't get comb stats<br>";
				return false;
			}		
		
			echo "<br><TABLE width='420' border='0' bgcolor=222233 cellspacing='0' cellpadding='0'>
					<TR>
					<TD>
					<TABLE table width=100% border='0' cellspacing='1' cellpadding='4'>
					<TR>
						<TD colspan='5' class=th_2 align='center'>Статистика по сотам </TD>
					</TR>
					<TR>
						<TD class=th_2 align='center'> Дата </TD>
						<TD class=th_2 align='center'> Мир</TD>
						<TD class=th_2 align='center'> Кольцо</TD>					
						<TD class=th_2 align='center'> Свободных сот</TD>
						<TD class=th_2 align='center'> Занятых сот </TD>						
					</TR>";
			foreach ($res as $k=>$v)
			{
				echo "<TR>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['date']."</TD>					
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['worldname']."</TD>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['id_ring']."</TD>	
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['freecombs']."</TD>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['occupycombs']."</TD>							
						</TR>";
			}	
		
			echo '	
					</TABLE>
					</TD>	
					</TR>				
				</TABLE>';
					
		}
		
		return true;
	}
	
	function showInOutWorldUsers($from_date, $to_date)
	{
		if(!isset($this->worldsList))
		{
			echo  "<br><FONT color='Red'>choose any worlds please to show in/out users stats</FONT><br>";
		}
		else 
		{
			$sql = sprintf("SELECT
 									iowu.newusers,
        							iowu.del_users,
        							iowu.newpremiumusers,
        							iowu.delpremiumusers,
 									ws.worldname,
 									ws.id_worldstats,
 									to_char(s.statsdate,'DD.MM.YY') as date,
 									to_char ( s.statsdate, 'YYYY-MM-DD' ) as date2
								FROM
	    							ds_statistic s,
	    							ds_inoutworldusers iowu,
     								ds_worldsstatisic ws
								WHERE
        							s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
      								AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.id_statistic = iowu.id_statistic
     								AND iowu.id_worldstats = ws.id_worldstats
     								AND ws.id_worldstats IN (%s)
     								ORDER BY ws.id_worldstats, s.statsdate",
								$to_date,
								$from_date,
								$this->worldsList);																				
							
			$res = $this->commonMaster->getResultArray("", $sql, "", "", "");
			if ( $this->commonMaster->errormsg )
			{
				echo "can't get in/out users in world stats<br>";
				return false;
			}								
		
			$sumDelUsers = 0;	
			$sumNewUsers = 0;
			$sumDelPremiumUsers = 0;	
			$sumNewPremiumUsers = 0;
			$count_x = $this->getCountX($res);				
			
			$cur_world = '';
			$sumNewUsersInWorld = 0;
			$sumDelUsersInWorld = 0;
			$sumNewPremiumUsersInWorld = 0;
			$sumDelPremiumUsersInWorld = 0;
			$curIdworld;			
			$id_worldstats = '';
			$form_elements_counter = 7;
		
			echo "
				<FORM name='graph_inoutworldusers'>
					<INPUT type='hidden' name='type' value='inoutworldusers'>
					<INPUT type='hidden' name='startdate' value='".$from_date."'>
					<INPUT type='hidden' name='enddate' value='".$to_date."'>
					<INPUT type='hidden' name='legend_newusers' value='Заcелилось игроков'>
					<INPUT type='hidden' name='legend_del_users' value='Съехало игроков'>
					<INPUT type='hidden' name='legend_newpremiumusers' value='Заcелилось премиум-игроков'>
					<INPUT type='hidden' name='legend_delpremiumusers' value='Съехало премиум-игроков'>
				<br><TABLE width='420' border='0' bgcolor=222233 cellspacing='0' cellpadding='0'>
					<TR>
					<TD>
					<TABLE table width=100% border='0' cellspacing='1' cellpadding='4'>
					<TR>
						<TD colspan='6' class=th_2 align='center'>Статистика заселения/выселения юзеров по мирам</TD>
					</TR>
					<TR>
						<TD class=th_2 align='center'> Дата </TD>	
						<TD class=th_2 align='center'> Мир</TD>
						<TD class=th_2 align='center'> Заселилось  игроков</TD>	
						<TD class=th_2 align='center'> Заселилось премиум-игроков</TD>			
						<TD class=th_2 align='center'> Съехало игроков</TD>									
						<TD class=th_2 align='center'> Съехало премиум-игроков</TD>									
					</TR>";
			
			if(sizeof($res) > 0 )
			{
							
				foreach ($res as $k=>$v)
				{
				
					if(strcmp($cur_world,$v['worldname']) === 0)
					{									
						$sumNewUsersInWorld += $v['newusers'];	
						$sumDelUsersInWorld += $v['del_users'];		
						$sumNewPremiumUsersInWorld += $v['newpremiumusers'];	
						$sumDelPremiumUsersInWorld += $v['delpremiumusers'];		
					}
					elseif ($cur_world === '')
					{
						$cur_world = $v['worldname'];
						$sumNewUsersInWorld += $v['newusers'];	
						$sumDelUsersInWorld += $v['del_users'];	
						$sumNewPremiumUsersInWorld += $v['newpremiumusers'];	
						$sumDelPremiumUsersInWorld += $v['delpremiumusers'];	
						$id_worldstats .= $v['id_worldstats'].','; 	
						$curIdworld = $v['id_worldstats'];					
					}
					else 
					{
						echo "<TR>								
								<TD class=th_2 align='center' colspan='2'>
									Всего для мира $cur_world <br>
									<INPUT type='checkbox' value='off' class=check name='(newusers,del_users,newpremiumusers,delpremiumusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>						
								</TD>
								<TD class=th_2 align='center'>
									$sumNewUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(newusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>			
								<TD class=th_2 align='center'>
									$sumNewPremiumUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(newpremiumusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>					
								<TD class=th_2 align='center'>
									$sumDelUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(del_users)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>																		
								<TD class=th_2 align='center'>
									$sumDelPremiumUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(delpremiumusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>														
							</TR>";
						$cur_world = $v['worldname'];					
						$sumNewUsersInWorld = $v['newusers'];	
						$sumDelUsersInWorld = $v['del_users'];		
						$sumNewPremiumUsersInWorld = $v['newpremiumusers'];	
						$sumDelPremiumUsersInWorld = $v['delpremiumusers'];	
						$curIdworld = $v['id_worldstats'];
						$id_worldstats .= $v['id_worldstats'].','; 
					}
				
				
					echo "<TR>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['date']."</TD>						
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['worldname']."</TD>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['newusers']."</TD>
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['newpremiumusers']."</TD>									
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['del_users']."</TD>															
							<TD class=maintxt bgcolor=#444E6C align='center'>".$v['delpremiumusers']."</TD>								
						</TR>";
					$sumNewUsers += $v['newusers'];
					$sumDelUsers += $v['del_users'];
					$sumNewPremiumUsers += $v['newpremiumusers']; 
					$sumDelPremiumUsers += $v['delpremiumusers'];
				}	
			}
		
			//$id_worldstats = substr($id_worldstats,0,-1);
			
			echo "<TR>								
								<TD class=th_2 align='center' colspan='2'>
									Всего для мира $cur_world <br>
								<INPUT type='checkbox' value='off' class=check name='(newusers,del_users,newpremiumusers,delpremiumusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>
								<TD class=th_2 align='center'>
									$sumNewUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(newusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>			
								<TD class=th_2 align='center'>
									$sumNewPremiumUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(newpremiumusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>					
								<TD class=th_2 align='center'>
									$sumDelUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(del_users)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>																		
								<TD class=th_2 align='center'>
									$sumDelPremiumUsersInWorld <br>
									<INPUT type='checkbox' value='off' class=check name='(delpremiumusers)(".$curIdworld.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
								</TD>														
							</TR>
					<TR >
					<TD class=th_2 colspan='2' align='center'>
						Всего  <br>
						<INPUT type='checkbox' value='off' class=check name='(newusers,del_users,newpremiumusers,delpremiumusers)(".$id_worldstats.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
					</TD>
					<TD class=th_2 align='center'>
						$sumNewUsers <br>
						<INPUT type='checkbox' value='off' class=check name='(newusers)(".$id_worldstats.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
					</TD>		
					<TD class=th_2 align='center'>
						$sumNewPremiumUsers <br>
						<INPUT type='checkbox' value='off' class=check name='(newpremiumusers)(".$id_worldstats.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
					</TD>									
					<TD class=th_2 align='center'>
						$sumDelUsers <br>
						<INPUT type='checkbox' value='off' class=check name='(del_users)(".$id_worldstats.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
					</TD>															
					<TD class=th_2 align='center'>
						$sumDelPremiumUsers <br>
						<INPUT type='checkbox' value='off' class=check name='(delpremiumusers)(".$id_worldstats.")' onchange='setCheckBox(\"graph_inoutworldusers\",".$form_elements_counter++.")'>
					</TD>	
					</TR>
					<TR>
						<TD colspan='6' class=th_2 align='left'>
						<INPUT type='button' id='1' onclick='openGraph(\"graph_inoutworldusers\",". $count_x.");' class=Submit value='Показать графики'>
						</TD>
					</TR>
					</TABLE>
				
					</TD>	
					</TR>				
					</TABLE></FORM>";
		}
		
		return true;
				
	}	
	
}

$obj = new DS_StatsViewer();
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>
				
