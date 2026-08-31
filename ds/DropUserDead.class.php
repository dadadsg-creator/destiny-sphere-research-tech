<?php

include_once "DropInstance.class.php";
require_once( 'Main.php' );

/**
* Class for deleting userinctance
*/
class DropUserDead {
	
	/** DB object */
	var $common = '';
	
	/** DropInstance object*/
	var $drop = '';
	
	/**  Path to log file */
	var $logFile = '/www/logs/delete_inactive.log';
	
	function DropUserDead (){
		$this->common = &ClassFactory::getInstance ( "DS_Common" );
		if(!$this->common->connect()){
			$this->error("Cant connect to DB!");
		};
		
		$this->drop = new DropInstance;
		$this->drop->setOutput (true);
	}
	
	/** Function for error handling */
	function error ($msg){
		die('Error: DropUserDead: '.$msg);
	}
	
	function logIt($msg){
		error_log($msg, 3, $this->logFile);
		if(php_sapi_name() == "apache"){
			echo '<b>'.date('Y-m-d H:i:s').'</b> '.$msg."<br>";
			flush();
		} else echo date('Y-m-d H:i:s').$msg."\n";
	}
	
	/** Main method */
	function drop (  ){
		$this->invariant ();
		
		$res = '';
		
		$this->logIt("DELETING ALL ZERO USERS FOR >3 DAYS");
		
		$sql = "SELECT 
					ui.id_userinstance,
					co.combscore,
					u.username,
					u.email,
					u.registrationdate,
					w.worldname,
					w.id_world,
					ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber as coords
				FROM 
					ds_userinstance ui,
					ds_user u,
					ds_world w,
					ds_comb co,
					ds_combcomplex cc,
					ds_ring ri
				WHERE 
					admin = false AND
					co.id_combcomplex = cc.id_combcomplex AND
					cc.id_ring = ri.id_ring AND
					co.id_userinstance = ui.id_userinstance AND
					ri.ringnumber != 4 AND
					u.id_user = ui.id_user AND
					(ui.id_userinstance > 0) and 
					(co.combscore = '0') and 
					(ui.lastrefresh < now() - interval '3 days') 
					AND (w.id_world=ui.id_world) 
				ORDER BY 
					ui.lastrefresh ASC";
		$res = $this->common->getMultySql ($sql);
		$this->assert($this->common->errormsg == '', 'GetSet1');
		
		$this->logIt("Selected ".count($res)." users to delete");
	
		if(is_array($res))
		foreach ( $res as $k=>$v ) {
			if($GLOBALS['dbWorldAccess'][$v['dbId']]['server'] == 'turnir') continue;
			$this->logIt("deleting user instance ...");
			$this->logIt("id: ".$v['id_userinstance']);
			$this->logIt("reason: ZERO user after 3-days period.");
			$this->logIt("name: ".$v['username']."|user score: ".$v['combscore']);
			$this->logIt("registration date: ".$v['registrationdate']);
			$this->logIt("world name: ".$v['worldname']."|Coords: ".$v['coords']);
			$this->logIt("......");
			
			$this->common->setDbId($v['dbId']);						
			
			$this->common->executeQuery ( "begin;" );
			$this->common->executeQuery ( "set STATEMENT_TIMEOUT to 0;" );
			
			$this->drop->setCommon ($this->common);
			
			$premium = $this->drop->checkUserOnPremium($v['id_userinstance']);
			
			$this->drop->drop ($v['id_userinstance']);
			
			$this->common->setDbId(0);
			$this->drop->setCommon ($this->common);
			$this->drop->incrementDelInstanceCounter($v['dbId'], $v['worldname'], $v['id_world'], $premium);
			$this->common->setDbId($v['dbId']);
			$this->drop->setCommon ($this->common);
			
			$this->common->executeQuery ( "commit" );
		}
		
		$this->logIt("DELETING INACTIVE USERS FOR >50 DAYS");
		$this->common->executeQueryMulty ( "set STATEMENT_TIMEOUT to 0;" );
		$sql="select 
		                ui.id_userinstance,
						(
						SELECT
						      SUM( c.combscore ) as score
						FROM
						    ds_comb c
						WHERE
						     c.id_userinstance = ui.id_userinstance
						GROUP BY
						      c.id_userinstance 
						) as userscore,
						u.username,
						u.email,
						u.registrationdate,
						w.worldname,
						w.id_world,
						ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber as coords
		                    from	ds_userinstance ui,
	 					ds_user u,
						ds_world w,
						ds_comb co,
						ds_combcomplex cc,
						ds_ring ri
				    where	admin = false AND
						(ui.id_userinstance > 0) AND
						co.id_combcomplex = cc.id_combcomplex AND
						cc.id_ring = ri.id_ring AND
						co.id_userinstance = ui.id_userinstance AND
						ri.ringnumber != 4 
						and (co.combscore > '0')
						and vacationmode = false
						and (ui.lastrefresh < now() - interval '60 days')
						AND (w.id_world=ui.id_world)
	     					AND (u.id_user = ui.id_user)
						and not exists
						(select id_user
						from ds_userprofidata
						where id_user = ui.id_user
						and active = true
						and enddate > now())
						order by ui.lastrefresh ASC";
		$res = $this->common->getMultySql ($sql);
		$this->assert($this->common->errormsg == '', 'GetSet2');
		
		$this->logIt("Selected ".count($res)." users to delete");
		
		if(is_array($res))
		foreach ( $res as $k=>$v ) {
			if($GLOBALS['dbWorldAccess'][$v['dbId']]['server'] == 'turnir') continue;
			$this->logIt("deleting user instance ...");
			$this->logIt("id: ".$v['id_userinstance']);
			$this->logIt("reason: inactive user more then 50 days.");
			$this->logIt("name: ".$v['username']."|user score: ".$v['userscore']);
			$this->logIt("registration date: ".$v['registrationdate']);
			$this->logIt("world name: ".$v['worldname']."|Coords: ".$v['coords']);
			$this->logIt("......");
//			if($v['username'] === 'HomerSimpson') continue;
			$this->common->setDbId($v['dbId']);
			$this->common->executeQuery ( "begin;" );
			$this->common->executeQuery ( "set STATEMENT_TIMEOUT to 0;" );
			
			$this->drop->setCommon ($this->common);
			$premium = $this->drop->checkUserOnPremium($v['id_userinstance']);
			$this->drop->drop ($v['id_userinstance']);
			
			$this->common->setDbId(0);
			$this->drop->setCommon ($this->common);
			$this->drop->incrementDelInstanceCounter($v['dbId'], $v['worldname'], $v['id_world'], $premium);
			$this->common->setDbId($v['dbId']);
			$this->drop->setCommon ($this->common);
			
			$this->common->executeQuery ( "commit" );
		}
		
		$this->logIt("DELETING NOT LOGGED IN USERS FOR >50 DAYS");
		set_time_limit(0);
		$this->common->executeQueryMulty ( "set STATEMENT_TIMEOUT to 0;" );
		$sql = "
				select
	            	ui.id_userinstance,
						(
						SELECT
						      SUM( c.combscore ) as score
						FROM
						    ds_comb c
						WHERE
						     c.id_userinstance = ui.id_userinstance
						GROUP BY
						      c.id_userinstance 
						) as userscore,
					u.username,
					u.email,
					u.registrationdate,
					w.worldname,
					w.id_world,
					ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber as coords
				from
			            	ds_userinstance ui,
	   				ds_user u,
					ds_world w,
					ds_comb co,
					ds_combcomplex cc,
					ds_ring ri
				where
				co.id_combcomplex = cc.id_combcomplex AND
				cc.id_ring = ri.id_ring AND
				co.id_userinstance = ui.id_userinstance AND
				ri.ringnumber != 4  AND
				vacationmode = false AND
	     			ui.id_user in
				 (select  DISTINCT id_user
				    from  ds_loginhistory
				   where  id_user > 0
				   group by id_user
				   having max(lastvisit) < (now() - interval '60 days') OR
					max(lastvisit) is null
				 )
			                and not exists
			                (select id_user from ds_userprofidata where id_user = ui.id_user and active = true and enddate > now())
			     		AND (w.id_world=ui.id_world)
			     		AND (u.id_user = ui.id_user)
					AND admin = false
					order by
				ui.lastrefresh ASC";
		$res = $this->common->getMultySql ($sql);
		$this->assert($this->common->errormsg == '', 'GetSet3 '.$this->common->errormsg);
		
		$this->logIt("Selected ".count($res)." users to delete");
		
		if(is_array($res))
		foreach ( $res as $k=>$v ) {
			if($GLOBALS['dbWorldAccess'][$v['dbId']]['server'] == 'turnir') continue;
		        if(!$this->common->setDbId($v['dbId'])) die("unable to select user db:".$v['dbId']);
			$this->logIt("deleting user instance ...");
			$this->logIt("id: ".$v['id_userinstance']);
			$this->logIt("reason: Not loged more then 60 days.");
			$this->logIt("name: ".$v['username']."|user score: ".$v['userscore']);
			$this->logIt("registration date: ".$v['registrationdate']);
			$this->logIt("world name: ".$v['worldname']."|Coords: ".$v['coords']);
			$this->logIt("......");
			
			$this->common->executeQuery ( "begin;" );
			$this->common->executeQuery ( "set STATEMENT_TIMEOUT to 0;" );
			
			$this->drop->setCommon ($this->common);
			$premium = $this->drop->checkUserOnPremium($v['id_userinstance']);
			$this->drop->drop ($v['id_userinstance']);
			
			$this->common->setDbId(0);
			$this->drop->setCommon ($this->common);
			$this->drop->incrementDelInstanceCounter($v['dbId'], $v['worldname'], $v['id_world'], $premium);
			$this->common->setDbId($v['dbId']);
			$this->drop->setCommon ($this->common);
			
			$this->common->executeQuery ( "commit" );
		}
		$this->logIt('COMPLIT');
	}//END OF METHOD
		
	
	/** Testing input params */
	function assert ($val, $code){
		if(!$val){
			$this->error(' assert: '.$code);
		}
	}//END OF METHOD
	
	/** Test of object global status */
	function invariant (){
		if (!$this->common){
			$this->error(' invariant: Dont set attribute $this->common');
		} 
		
		if (strtolower(get_class($this->common)) != 'ds_common'){
			$this->error(' invariant: Uncorrect type of $this->common - '.get_class($this->common));
		}
		
		foreach ($this->common->dbWorldAccess as $k=>$v){
			if(!$v['conn']){
				$this->error(' invariant: DS_Common cant connect to DB with dbId = '.$k);
			}
		}
		
		if (!$this->drop){
			$this->error(' invariant: Dont set attribute $this->drop');
		} 
		
		if (strtolower(get_class($this->drop)) != 'dropinstance'){
			$this->error(' invariant: Uncorrect type of $this->drop - '.get_class($this->drop));
		}
	}//END OF METHOD
	
}//END OF CLASS

?>