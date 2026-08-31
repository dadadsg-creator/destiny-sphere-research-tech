<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_UnicArtsStat
{

	static public function  MainInterface()
	{
		if(isset($_REQUEST['action']))
		{
			$action = $_REQUEST['action'];
		}else{
			$action = '';
		}
		
		if(isset($_REQUEST['dbid']))
		{
			$dbid = $_REQUEST['dbid'];
		}else{
			$dbid = 0;
		}
		
		$out = "<h1>UnicArtsStatistic</h1>";
						
		$out .= '<b>Select DB server to get statistic';							
		$out .= '<table width=300 border=1>';							
		foreach($GLOBALS['dbWorldAccess'] as $k=>$v)
		{
			$out .= '<tr>';
			$out .= '<td>&nbsp;'.$v['server'].'&nbsp;</td><td align=center> <a href="?action=getstat&dbid='.$k.'">Get DB statistic </a> </td>';
			$out .= '</tr>';
		}
		$out .= '</table>';
				
		
		if($action ==  'getstat')
		{
			$out .= self::GetDBStat($dbid);
		}
		
		return $out;
	}
		
	static public function GetDBStat($DBID)
	{
		$dbData = $GLOBALS['dbWorldAccess'][$DBID];
		
		$common = DS_Common::CreateForCurentDB((int)$DBID);
		
		$common->setDbID($DBID);

		$sql = "SELECT
       				ltl.text,
       				count(*) AS colvo
				FROM
    				ds_artefactinstance AS ai
    				INNER JOIN ds_artefact AS a ON (a.id_artefact = ai.id_artefact)
    				INNER JOIN ds_artefactscope AS ascop ON (ascop.id_artefactscope = a.id_artefactscope)
    				INNER JOIN ds_artefactprobability AS aprob ON (aprob.id_artefactprobability = a.id_artefactprobability)
    				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = a.id_localizedname AND ltl.id_language = 3)
				WHERE
      				aprob.prefix IN ('UNIQUE', 'GOLD', 'SILVER', 'NORMAL')
      				OR
      				ascop.scopename = 'SPHERE'
				GROUP BY ltl.text
				ORDER BY colvo DESC";	
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		$out .= "<br><br><table border=1 width=500>";
		
		$out .= "
			<tr>
				<th colspan=2> ServerName: ".$dbData['server']."</th>
			</tr>
			<tr>				
				<th>ArtefactName</th>
				<th>Count</th>
			</tr>";
		
		$total = 0;		
		foreach($res as $k=>$v)
		{
			$out .= "
			<tr>
				<td>&nbsp;".$v['text']."</td>
				<td align=center>".$v['colvo']."</td>
			</tr>";
			
			$total += (int)$v['colvo'];
		}
		
		$out .= "
			<tr>
				<th>TOTAL</th>
				<th>".$total."</th>
			</tr>
		";
		
		$out .= "</table>";
		
		return $out;
	}	
}

?>