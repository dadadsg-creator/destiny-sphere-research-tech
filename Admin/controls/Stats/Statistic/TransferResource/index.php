<?php
ini_set('memory_limit', '1024M');
set_time_limit(500);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=TransferResource><font size=3><b>HELP</b></font></a></p>';
class TransferResource
{
	private $action = 'index';

	private $dateFrom;

	private $worldId;
	
	private $dbId;
	
	private $dateTo;

	private $dateFromArr;

	private $dateToArr;

	private $userName = '';
	
	private $category = array(
		0 => 'RESOURCE',
		1 => 'UNIT',
		2 => 'ARTEFACT',
		);
		
	private $categoryNames = array(
		0 => 'RESOURCE',
		1 => 'UNIT',
		2 => 'ARTEFACT',
		);

	public function __construct(array $data)
	{
		$this->_getParams($data);

		switch ($this->action)
		{
			case 'index':
				$this->_showIndex();
				break;
			case 'showStat':
				$this->_showIndex();
				$this->_showStat();
				break;

			case 'show_detail':
				$this->_showDetail();
				break;
		}
	}

	private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			$this->action = $data['action'];
		}
		if(key_exists('username', $data))
		{
			$this->userName = $data['username'];
		}

		if(key_exists('dateFrom', $data))
		{
			assert(is_array($data['dateFrom']));
			$dateFrom = $data['dateFrom'][0].'-';
			$dateFrom .= $data['dateFrom'][1].'-';
			$dateFrom .= $data['dateFrom'][2].' ';
			$dateFrom .= $data['dateFrom'][3].':00:00';

			$this->dateFromArr = $data['dateFrom'];
			$this->dateFrom = $dateFrom;

			$_SESSION['dateFrom'] = $this->dateFrom;
		}

		if(key_exists('dateTo', $data))
		{
			assert(is_array($data['dateTo']));
			$dateTo = $data['dateTo'][0].'-';
			$dateTo .= $data['dateTo'][1].'-';
			$dateTo .= $data['dateTo'][2].' ';
			$dateTo .= $data['dateTo'][3].':00:00';

			$this->dateToArr = $data['dateTo'];
			$this->dateTo = $dateTo;

			$_SESSION['dateTo'] = $this->dateTo;
		}
		
		/*if ( isset($data["id_world"])) 
		{
			$this->id_world = $data["id_world"];
		}*/

		if ( isset($data["world"])) 
		{
			$tmp = explode('_', $data["world"]);
			$this->worldId = (int)$tmp[0];
			$this->dbId = (int)$tmp[1];
		}
		
		if ( isset($data["category"])) 
		{
			$this->category = $data["category"];
		}
	}

	private function _showIndex()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		if(count($this->dateFromArr) > 0)
		{
			$start = $this->dateFromArr;
		} else {

			$week = strtotime("-1 week");

			$start[0] =  date('Y', $week);
			$start[1] = date('m', $week);
			$start[2] = date('d', $week);
			$start[3] = date('H', $week);
		}

		if(count($this->dateToArr) > 0)
		{
			$end = $this->dateToArr;
		} else {
			$end[0] = date('Y');
			$end[1] = date('m');
			$end[2] = date('d');
			$end[3] = date('H') + 1;
		}

		echo "
<table border='0'>
<tr>
	<td>
	<form action='' method='POST'>
	<input type='hidden' name='action' value='showStat'>
	
	<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
		<tr>
			<td>
				
				<table width=100% border=0 cellspacing=1 cellpadding=3>
					<tr class=maintxt_med bgcolor=#444E6C >
						<td colspan='4' class=th_2 align=center>
							Stat Date
						</td>
					</tr>
					<tr class=th_2>
						<td>Year</td>
						<td>month</td>
						<td>day</td>
						<td>hour</td>
					</tr>
					<tr>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=4 name='dateFrom[0]' value=".$start[0].">&nbsp;-&nbsp;</td>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateFrom[1]' value=".$start[1].">&nbsp;-&nbsp;</td>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateFrom[2]' value=".$start[2].">&nbsp; &nbsp;</td>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateFrom[3]' value=".$start[3]."></td>
					</tr>
				</table>
			</td>
			<td bgcolor=#444E6C>
				&nbsp;
			</td>
			<td> 
				<table width=100% border=0 cellspacing=1 cellpadding=3>
					<tr class=maintxt_med bgcolor=#444E6C >
						<td colspan='4' class=th_2 align=center>
							End Date
						</td>
					</tr>
					<tr class=th_2>
						<td class=maintxt_med bgcolor=#444E6C>Year</td>
						<td class=maintxt_med bgcolor=#444E6C>month</td>
						<td class=maintxt_med bgcolor=#444E6C>day</td>
						<td class=maintxt_med bgcolor=#444E6C>hour</td>
					</tr>
					<tr>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=4 name='dateTo[0]' value=".$end[0].">&nbsp;-&nbsp;</td>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateTo[1]' value=".$end[1].">&nbsp;-&nbsp;</td>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateTo[2]' value=".$end[2].">&nbsp; &nbsp;</td>
						<td class=maintxt_med bgcolor=#444E6C><input type='text' size=2 name='dateTo[3]' value=".$end[3]."></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</td>
</tr>
</table>
	<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233><tr><td><table width=100% border=0 cellspacing=1 cellpadding=3>
			<tr>
				<td bgcolor='444E6C' class=th_2>
					".local( 'DS_USER_WORLD' )."
				</td>
				<td bgcolor='444E6C' class=th_2>
					".local( 'DS_USER_USERNAME' )."
				</td>
			</tr>
			<tr>
				<td bgcolor='444E6C'>
					<select name='world' style=\"width:250px;\">";
		
		$sql = sprintf ( "select * from ds_world" );
		$res = $common->getMultySql ($sql);
		
		if ( !$res ) {
			$this->errormsg = "addUnits::showFirstMenu !!! ".$sql;
			return false;
		}
		
		
		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			
			echo "<option value='".$res[$i]['id_world']."_".$res[$i]['dbId']."'".($this->worldId."_".$this->dbId==$res[$i]['id_world']."_".$res[$i]['dbId'] ? " selected" : "").">".$res[$i]['worldname']."</option>";
		}
		
		echo "</select><br>
				</td>
				<td bgcolor='444E6C'>
					<input type='text' name='username' size=30 value='".$this->userName."'>
				</td>
			</tr>			
		</table></td></tr></table>";
		echo "<br>";
		
		echo "
		<font color=#F8F8FF>Category:</font><br>
		";

		foreach ($this->categoryNames as $key => $name)
		{
			if (!empty($this->category) && in_array($name, $this->category)) {
				$c = 'checked';
			} else {
				$c = '';
			}
			
			echo "
			<input type='checkbox' name='category[]' value='$name' $c> <font color=#F8F8FF>$name</font><br>";
		}
		echo "<br>
		<input type='submit' class=submit name='next' value='".local( 'DS_ACTION_SEARCH' )."'>
		</form><br>
		";

	}

	private function _showStat()
	{		 	
		assert(is_int($this->dbId) && $this->dbId >= 0);
		assert(is_int($this->worldId) && $this->worldId > 0);
		
		$common = DS_Common::CreateForCurentDB(0);
		$common->setDbId( $this->dbId );

		$where = "";
		
		if (empty($this->category)) {
			echo '<font color="Red">Choose category!</font>';
			return;
		}
		if (empty($this->userName)) {
			echo '<font color="Red">Choose User Name!</font>';
			return;
		}
		
		
		if (!empty($this->userName)) {
			$where .= "
				 AND
				u.username = '$this->userName'";
		}
		$where .= '
		AND
		 ( ';
		$or = '';
		
		foreach ($this->category as $key => $name)
		{
			$where .= "
				 $or
				tl.categoryname = '$name' ";
			$or = ' OR ';
		}
		
		$where .= ' ) ';

		$sql = "
			SELECT 
				u.username,
				tl.amount,
				CASE
				-- 1 recource
		        WHEN tl.categoryname = 'RESOURCE' THEN
		        (
		            SELECT
		                ltl.text
		            FROM
		                ds_attributename an,
		                ds_localizedtextlanguage ltl
		            WHERE
		                tl.id_article = an.id_attributename
		                AND an.id_localizedname = ltl.id_localizedtext
		                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
		        )
		        
		        -- 2 units
		        WHEN tl.categoryname = 'UNIT' THEN
		        (
		            SELECT
		                ltl.text
		            FROM
		                ds_unit uni,
		                ds_localizedtextlanguage ltl
		            WHERE
		                tl.id_article = uni.id_unit
		                AND uni.id_localizedname = ltl.id_localizedtext
		                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
		        )
		
		        -- 3 arts
		        WHEN tl.categoryname = 'ARTEFACT' THEN
		        (
		            SELECT
		                ltl.text
		            FROM
		                ds_artefact art,
		                ds_localizedtextlanguage ltl
		            WHERE
		                tl.id_article = art.id_artefact
		                AND art.id_localizedname = ltl.id_localizedtext
		                AND ltl.id_language = ".constant("DEFAULT_LANGUAGE")."
				)
        
                ELSE
					tl.categoryname
           		 	END AS resourse_name,
				cnf.combname as comb_from,
				cnt.combname as comb_to,
				tl.start_date,
                tl.end_date
		FROM 
			ds_transfer_log tl
		LEFT JOIN ds_comb cf on (tl.id_comb_from = cf.id_comb)
		LEFT JOIN ds_comb ct on (tl.id_comb_to = ct.id_comb)
		LEFT JOIN ds_combname cnf on (cnf.id_comb = cf.id_comb)
		LEFT JOIN ds_combname cnt on (cnt.id_comb = ct.id_comb)
		LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = cf.id_userinstance)
		LEFT JOIN ds_user u ON (u.id_user = ui.id_user)
		WHERE
				'".$_SESSION['dateFrom']."' < tl.start_date
				 AND
				tl.start_date < '".$_SESSION['dateTo']."'
				 AND 
                ui.id_world = ".$this->worldId."
		$where
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if (empty($res)) {
			echo '<font color="Red">NO DATA</font>';
			return;
		}		
	
			echo '
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
			<tr>
				<td>
					<table width=100% border=0 cellspacing=1 cellpadding=3>
						<tr class=th_2 >
							<th>
								User
							</th>
							<th>
								Amount
							</th>
							<th>
								Resourse
							</th>
							<th>
								Comb from
							</th>
							<th>
							    Combto
							</th>
							<th>
							 	Start Date
							</th>
							<th>
							 	End Date
							</th>
						</tr>';
			
			foreach ($res as $key => $data)
			{
				if (empty($data['end_date'])) {
					$data['end_date'] = '-';
				} else {
					$data['end_date'] = date('Y-m-d H:m:i', strtotime($data['end_date']));
				}

				$data['start_date'] = date('Y-m-d H:m:i', strtotime($data['start_date']));

				echo '
						<tr>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['username'].'
							</td>
							<td align="left" class=maintxt_med bgcolor=#444E6C>
								'.$data['amount'].'
							</td>
							<td align="left" class=maintxt_med bgcolor=#444E6C>
								'.$data['resourse_name'].'
							</td>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['comb_from'].'
							</td>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['comb_to'].'
							</td>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['start_date'].'
							</td>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['end_date'].'
							</td>
						</tr>';
				$sc += $data['sc_price'];
				$gc += $data['gc_price'];
				$total ++;
			}
			echo '
					</table>
				</td>
			</tr>
		</table>';

		return;
	}

	private function _showDetail()
	{
		if (empty($this->action)) {
			echo '<font color="Red">WRONG SERVICE!!!!</font>';
			return;
		}

		if (!empty($this->userName)) {
			$where = "  AND
			           u.username = '".$this->userName."'
						AND
					   pu.is_initiator = 't' ";

			//Для ссылки
			$userData = '&userName='.$this->userName;
			$desc = 'User: '.$this->userName;
		}

		switch ($this->sortBy)
		{
			case 'service':
				$orderBy = " ORDER BY
								p.action_name ";
				break;
			case 'user':
				$orderBy = " ORDER BY
								u.username ";
				break;
			case 'SC':
				$orderBy = " ORDER BY
								sc_price DESC ";
				break;
			case 'GC':
				$orderBy = " ORDER BY
								gc_price DESC ";
				break;
			case 'date':
				$orderBy = " ORDER BY
								p.paid_date DESC ";
				break;
			default:
				$orderBy = " ORDER BY
							p.paid_date DESC";
				break;
		}

		$common = DS_Common::CreateForCurentDB(0);

		$sql = "
			SELECT 
				*
			FROM 
				ds_paymentserviceslog p
			LEFT JOIN ds_paymentserviceslog_users pu ON (p.id_paymentserviceslog = pu.id_paymentserviceslog)
			LEFT JOIN ds_user u ON (pu.id_user = u.id_user)
			$join
			WHERE
				'".$_SESSION['dateFrom']."' < p.paid_date
				 AND
				p.paid_date < '".$_SESSION['dateTo']."'
				 AND
				p.action_name = '".$this->service."'
				$where
			$orderBy
			";
		$res = $common->getResultArray("", $sql, "", "", "");

		assert(empty($common->errormsg));

		$sc = 0;
		$gc = 0;

		echo "<font color=#F8F8FF>Service: ".$this->service." ".$desc."</font><br>";

		echo
		'
		<table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
			<tr>
				<td>
					<table width=100% border=0 cellspacing=1 cellpadding=3>
						<tr class=th_2>
							<th>
								<a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBy=user" >User</a>
							</th>
							<th>
								<a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBySC" >Silver Credits</a>
							</th>
							<th>
								<a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBy=GC" >Gold Credits</a>
							</th>
							<th>
								<a href="?action=show_detail&service='.$this->service.$userData.'&show=all&sortBy=date" >Date</a>
							</th>
							<th>
								Detail	
							</th>
						</tr>';
		foreach ($res as $key => $data)
		{
			echo '
						<tr>
							<td align="left" class=maintxt_med bgcolor=#444E6C>
								'.$data['username'].'
							</td>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['sc_price'].'
							</td>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['gc_price'].'
							</td>
							<td align="center" class=maintxt_med bgcolor=#444E6C>
								'.$data['paid_date'].'
							</td>
							<td align="left" class=maintxt_med bgcolor=#444E6C>
								'.$data['info'].'
							</td>
						</tr>';
			$sc += $data['sc_price'];
			$gc += $data['gc_price'];
		}
		echo '
						<tr>
							<td align="left" class=maintxt_med bgcolor=#2A3E66>
								Total:
							</td>
							<td align="center" class=maintxt_med bgcolor=#2A3E66>
								'.$sc.'
							</td>
							<td align="center" class=maintxt_med bgcolor=#2A3E66>
								'.$gc.'
							</td>
							<td align="center" class=maintxt_med bgcolor=#2A3E66>
								&nbsp;
							</td>
							<td align="center" class=maintxt_med bgcolor=#2A3E66>
								&nbsp;
							</td>
						</tr>
					</table>
				</td>
			</tr>	
		</table>
		';
	}

}

$s = new TransferResource($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>