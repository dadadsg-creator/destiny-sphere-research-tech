<?

/*<HTML>
<HEAD>
<SCRIPT language="JavaScript">
function resize()
{
	
	window.open('linegraph.php?type=battles', '_blank', 'width=' + (document.myImage.width ) +
				',height=' + (document.myImage.height ) +
				',left=150,top=150,resizable=no,scrollbars=no');
	window.opener='_';				
	window.close();
}
</SCRIPT>
</HEAD>
<BODY onload="resize();">

<img src="linegraph.php?type=battles" name="myImage">

</BODY>
</HTML>*/



//$data = parseData();
//prepareData($data);


function parseData()
{
	$res_graphs = array();
			
	foreach ($_GET as $k=>$v)
	{
		if((preg_match('/^\([a-zA-Z\,]*\)\([0-9\,]+\)$/',$k) > 0) && ($v == 'on'))
		{						
			list($f,$s) = explode(")(",$k);
			$f = substr($f,1);
			$s = substr($s,0,-1);
			$f = explode(",", $f);
			$s = explode(",",$s);
			foreach ($s as $k2=>$v2)
			{
				foreach ($f as $k3=>$v3)
				{
					if($v3 != 'all')
					{
						$res_graphs[$v2][$v3] = 0;
					}
				}
			}			
		}		
	}
	
	return $res_graphs;
}

function prepareData($data)
{
	assert(isset($_GET['type']));
	assert(isset($_GET['startdate']));
	assert(isset($_GET['enddate']));
	
	$table = '';
	 switch ($_GET['type']) 
	 {
          case 'battles':
            $table = 'ds_battlestats';
            break;
	 }
         
            
            
	foreach ($data as $k=>$v)
	{		
		$fields = '';
		
		foreach ($v as $k2=>$v2)
		{					
			$fields .= 'x.'.$k2.',';				
		}
				
		$fields = substr($fields,0,-1);
		
		$sql = sprintf("SELECT
       								%s
								FROM
    								%s x,
    								ds_statistic s
								WHERE
     								s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.id_statistic = x.id_statistic
     								AND x.id_worldstats = %d",
								$fields,
								$table,
								$_GET['enddate'],
								$_GET['startdate'],
								$k
							);
		$res = $this->common->getResultArray("", $sql, "", "", "");
		if ( $this->common->errormsg )
		{
			echo "can't get data for graph<br>";
			return false;
		}					
		
		
	}
}

?>
