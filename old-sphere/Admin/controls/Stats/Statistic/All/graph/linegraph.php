<?php
  include "class.linegraph.php";
  require_once "DS_CommonLite.php";
  
  class LineGraph
  {

  	var $data = array();
  	var $legend = array(); 
  	var $graph;
  	var $title;
  	var $count_x = 0;
  	var $common;
  	/*var $transliterate = array('à'=>'a','á'=>'b','â'=>'v','ã'=>'g','ä'=>'d','å'=>'e','¸'=>'io','æ'=>'z\'','ç'=>'z',
  								'è'=>'i','é'=>'ii','ê'=>'k','ë'=>'l','ì'=>'m','í'=>'n','î'=>'o','ï'=>'p','ð'=>'r',
  								'ñ'=>'s','ò'=>'t','ó'=>'u','ô'=>'f','õ'=>'h','ö'=>'c','÷'=>'ch','ø'=>'sh','ù'=>'sh',
  								'ú'=>'\'','ü'=>'\'','ý'=>'e','þ'=>'u','ÿ'=>'ya');*/
  	
  	function LineGraph()
  	{
  		
  		//$this->common = &ClassFactory::getInstance ( "DS_Common" );
  		$this->common =  new DS_Common;
	  	$this->common->setDbId(0);
	  	if ( !$this->common->conn )
		{
			die("can't connect to master");
		}
  		
  		$this->parseData();  		
  		$graph = new Graph();
  		$graph->LoadGraph(realpath("./linegraph.def"));
  		$graph->SetData($this->data);  		
  		$graph->SetGraphAreaWidth($this->count_x*40);
  		$graph->SetLegendData($this->legend);
  		$graph->SetLegendVisible(true); 
  		$graph->SetGraphTitle($this->title); 	  		
  		$graph->DrawGraph();
  		  		  		  		
  	}
  	
  	
  	
  	function parseData()
	{
		$res_graphs = array();				
			//var_dump($_GET); exit();
		foreach ($_GET as $k=>$v)
		{
			if((preg_match('/^\([a-zA-Z\,_]*\)\([\-0-9\,]+\)/',$k) > 0) && ($v == 'on'))
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
						if(strlen($v2)>0)
						{
							$res_graphs[$v2][$v3] = 0;							
						}
					}
				}			
			}		
		}
		//var_dump($res_graphs); exit();
		if(sizeof($res_graphs) === 0)
		{
			echo "<FONT color='Red'> choose what you want to draw, please </FONT><br>";			
			exit;
		}
		
		$this->prepareDataSwitcher($res_graphs);
	}
	
	function dateToDays($date)
  	{
  		return ceil((strtotime($date)/(60*60*24)));  		  		
  	}
  	
  	function daysToDate($days)
  	{
  		return date("d/m/Y", $days*60*60*24);
  	}
  	
  	function drawOneGraph($res, $field, $world)
  	{
  		assert(is_array($res));
  		$tmp = array();
  		foreach ($res as $k=>$v)
  		{  			
  			$tmp[$this->dateToDays($v['absciss'])] = $v['ordinate'];  			
  		}		
  		  		  		  		  		
  		$this->data[$field.'-'.$world] = $tmp;
  		$legend_name = $field;
  		if(isset($_GET['legend_'.$field]))
  		{
  			$legend_name = $_GET['legend_'.$field];
  		} 
		
  		$world_name = '';
  		if(isset($res[0]['worldname']))
  		{
  			$world_name = " (".trim($res[0]['worldname']).")";
  		}
  		
  		$this->legend[] = trim($legend_name).$world_name;
  		 		
  	}
  	
  	function prepareDataSwitcher($data)
  	{
  		assert(isset($_GET['type']));
		assert(isset($_GET['startdate']));
		assert(isset($_GET['enddate']));		
		
		
	 	switch ($_GET['type']) 
	 	{	
        	  case 'battles':
        	  	$this->title = 'Battles statistic';
            	$this->prepareDataForMultiWorld($data,'ds_battlestats');
            	break;
              case 'inoutusers':
              	$this->title = 'In/out users statistic';
            	$this->prepareDataForOneWorld($data,'ds_inoutusersstats');
            	break;
              case 'premiumstats':
              	$this->title = 'Premium users statistic';
            	$this->prepareDataForOneWorld($data,'ds_premiumstats');
            	break;
              case 'trades':
              	$this->title = 'Trades statistic';
            	$this->prepareDataForMultiWorld($data,'ds_tradestats');
            	break;
              case 'inoutworldusers':
              	$this->title = 'In/out world users statistic';
            	$this->prepareDataForMultiWorld($data,'ds_inoutworldusers');
            	break;
            
	 	}
  	}
  	
  	function prepareDataForOneWorld($data, $table)
  	{
  		foreach ($data[-1] as $k=>$v)
  		{
  			$sql = sprintf("SELECT
       								%s as ordinate,
       								to_char ( s.statsdate, 'YYYY-MM-DD' ) as absciss
								FROM
    								%s x,
    								ds_statistic s
								WHERE
     								s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.id_statistic = x.id_statistic     								
     							ORDER BY s.statsdate",  								
								'x.'.$k,
								$table,
								$_GET['enddate'],
								$_GET['startdate']
								);
			$res = $this->common->getResultArray("", $sql, "", "", "");
			if ( $this->common->errormsg )
			{	
				echo "can't get data for graph<br>$sql";
				exit;
			}	
				
			if(sizeof($res) === 0)
			{
				continue;
			}
			else 
			{				
				$this->getCountX($res);	
				$this->drawOneGraph($res,$k,'-1');				
			}								
  		}
  	}
  	
  	function getCountX($res)
  	{  		
  		$this->count_x =  	
  			($this->dateToDays($res[sizeof($res)-1]['absciss']) - 
  				$this->dateToDays($res[0]['absciss']) + 1) ; 		
  	}
  	  	
  	
  	function prepareDataForMultiWorld($data, $table)
	{
		                                 
		foreach ($data as $k=>$v)
		{					
		
			foreach ($v as $k2=>$v2)
			{												
												
				$sql = sprintf("SELECT
       								%s as ordinate,
       								to_char ( s.statsdate, 'YYYY-MM-DD' ) as absciss,
       								ws.worldname      								
								FROM
    								%s x,
    								ds_statistic s,
    								ds_worldsstatisic ws
								WHERE
     								s.statsdate < to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.statsdate > to_timestamp('%s','DD/MM/YYYY HH24:MI:SS')
     								AND s.id_statistic = x.id_statistic
     								AND x.id_worldstats = %d
     								AND x.id_worldstats = ws.id_worldstats
     							ORDER BY s.statsdate",								
								'x.'.$k2,
								$table,
								$_GET['enddate'],
								$_GET['startdate'],
								$k
								);
																		
				$res = $this->common->getResultArray("", $sql, "", "", "");
				if ( $this->common->errormsg )
				{	
					echo "can't get data for graph<br>$sql";
					exit;
				}	
								
				
				if(sizeof($res) === 0)
				{
					continue;
				}
				else 
				{				
					$this->getCountX($res);	
					$this->drawOneGraph($res,$k2,$k);
				}
				
			}						
		}
	}
  	
  }
  
  new LineGraph();
  
?>
