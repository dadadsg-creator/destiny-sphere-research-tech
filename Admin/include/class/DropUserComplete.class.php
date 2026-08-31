<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/DropInstance.class.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

/**
* Class for deleting userinctance
*/
class DropUserComplete {
	
	/** DB object */
	var $common = '';
	
	/** DropInstance object*/
	var $drop = '';
	
	/** dbId where transactions is started */
	var $transactions = array (0);
	
	function DropUserComplete (){
		$this->common = &ClassFactory::getInstance ( "DS_Common" );
		if(!$this->common->connect()){
			$this->error("Error: Cant connect to DB!");
		};
		
		$this->drop = new DropInstance;
		$this->drop->setOutput (true);
	}
	
	/** Function for error handling */
	function error ($msg){
		die('Error: DropUserComplete: '.$msg);
	}
	
	/** Main method */
	function drop ($name = ''){
		$res = '';
		
		$this->invariant ();
		
		$this->assert ($name, 'Not seted parameter $name');
		
		$sql = sprintf ( "select u.id_user, u.username,
				u.password, u.id_language
				from ds_user u
				where u.username = '%s'",
				$name);
		
		$this->common->setDbId(0);
		$res = $this->common->getQueryArray ( $sql );

		$this->assert($res, 'getRequest1');
					
		$id_user = $res[0]['id_user'];
		$username = $res[0]['username'];
		$password = $res[0]['password'];
		$language = $res[0]['id_language'];
		$this->assert($id_user && $username && $password && $language, 'getRequest2');
		
		if ($language == 1)	{
			$forum = 'forum';
		} elseif ($language == 3) {
			$forum = 'forum_ru';
		}
		
		$commonForum = & ClassFactory::getInstance ( "DS_Common");
		$commonForum->connect($forum);
		$commonForum->executeQuery ( "begin" );
		$commonForum->executeQuery ( "set STATEMENT_TIMEOUT to 0;" );
		$this->forumDelete($language, $username, $password, $commonForum);
		
		
		$sql = sprintf ( "select id_userinstance
							from ds_userinstance
							where id_user = %d",
							$id_user );		
		$res = $this->common->getMultySql ( $sql );
		$this->assert($this->common->errormsg == '', 'getRequest22');
		
		if(is_array($res))
		foreach ( $res as $k=>$v ) {
			if($v['dbId'] == 0) continue;
			
			$this->common->setDbId($v['dbId']);
			$this->common->executeQuery ( "begin" );
			$this->transactions[] = $v['dbId'];
			$this->common->executeQuery ( "set STATEMENT_TIMEOUT to 0;" );
			$this->drop->setCommon ($this->common);
			$this->out('<b>Deleting userinstans '.$v['id_userinstance'].'</b>');
			$this->drop->drop ( $v['id_userinstance'] );
		}
		
		$this->common->setDbId(0);
		$this->common->executeQuery ( "begin;" );
		$this->common->executeQuery ( "set STATEMENT_TIMEOUT to 0;" );
		
		$this->out('Deleting user from Game');
		
		$sql = sprintf ( "delete from ds_useradsstatistics
						where id_user = %d",
						$id_user );
		$res = $this->common->executeQuery ( $sql );
		$this->assert($this->common->errormsg == '', 'getRequest23');

		$sql = sprintf ( "delete from ds_loginhistory
				where id_user = %d",
				$id_user );
		$res = $this->common->executeQuery ( $sql );
		$this->assert($this->common->errormsg == '', 'getRequest23-2');

		$sql = sprintf ( "delete from ds_userprovidedbypartner
				where id_user = %d",
				$id_user );
		$res = $this->common->executeQuery ( $sql );
		$this->assert($this->common->errormsg == '', 'getRequest23-3');
		
		$sql = sprintf ( "delete from ds_userprofidata
						where id_user = %d",
						$id_user );
		$res = $this->common->executeQuery ( $sql );
		$this->assert($this->common->errormsg == '', 'getRequest24');
		
		$sql = sprintf ( "delete from ds_userprofitechdata
						where id_user = %d",
						$id_user );
		$res = $this->common->executeQuery ( $sql );
		$this->assert($this->common->errormsg == '', 'getRequest24');

		$sql = sprintf ( "delete from ds_user
				where id_user = %d",
				$id_user );
		$res = $this->common->executeQuery ( $sql );
		$this->assert($this->common->errormsg == '', 'getRequest24-2');

		$this->incrementDelUserCounter();	
		
		$this->out('Deleting user from Game complit');
		
		foreach($this->transactions as $k=>$v){
			$this->common->setDbId($v);
			$this->common->executeQuery ( "commit" );
		} 
		$commonForum->executeQuery ( "commit" );			
		
		$this->out('Deleting complit');
	}//END OF METHOD
	
	/**
	 Deleting usr from forum
	 */
	
	function getStatisticId()
	{
		$today = date("d/m/Y 00:00:00", time());
		$tomorrow = date("d/m/Y 00:00:00", time()+60*60*24);	
		
		$sql = sprintf("SELECT
      							id_statistic
							FROM
    							ds_statistic
							WHERE
     						statsdate < to_timestamp('%s','DD/MM/YYYY SS:MI:HH24')
     						and statsdate > to_timestamp('%s','DD/MM/YYYY SS:MI:HH24')",
							$tomorrow,
							$today);
												
								
		$res = $this->common->getResultArray("", $sql, "", "", "");
		$this->assert($this->common->errormsg == '', 'getRequest1-stats ');
		
  		if(sizeof($res) === 0)
  		{
  			$sql = "INSERT INTO
        							ds_statistic	(statsdate)
           					VALUES
           						(now())";
  				
  			$res = $this->common->executeQuery($sql);  			
  			
  			$this->assert($this->common->errormsg == '', 'getRequest2-stats');
  				  			  			
  			$oid = pg_last_oid($this->common->getRES());
  				  				
  			$this->assert($oid, 'getRequest3-stats');
				
			$sql = 'select id_statistic from ds_statistic where oid = '.$oid;
			$res = $this->common->getResultArray("", $sql, "", "", "");
			$this->assert($this->common->errormsg == '', 'getRequest4-stats');
			$this->assert(!empty($res[0]['id_statistic']), 'getRequest5-stats');
  				
  			$id_stats = $res[0]['id_statistic'];
  			return $id_stats;				
  		}
  		else 
  		{
  			$id_stats = $res[0]['id_statistic'];
  			return $id_stats;
  		}												
	}
	
	function incrementDelUserCounter()
	{
		$statsId = $this->getStatisticId();
		$sql = sprintf("SELECT 
								ious.id_inoutusersstats
							FROM
    							ds_inoutusersstats ious
    						WHERE
        						ious.id_statistic = %d",
							$statsId);
		$res = $this->common->getResultArray("", $sql, "", "", "");
		$this->assert($this->common->errormsg == '', 'getRequest6-stats');	

		if(sizeof($res) === 0)
  		{
  			$sql = sprintf("INSERT INTO
           							ds_inoutusersstats
           							(users_in, users_out, id_statistic)
           							VALUES
           							(0,1,%d)",  									
  									$statsId); 
  		}
  		else 
  		{
  			$sql = sprintf("UPDATE
      								ds_inoutusersstats
      							SET
         							users_out = users_out + 1
       							WHERE
            						id_statistic = %d",
  								$statsId);
  		}	
  		
  		$res = $this->common->executeQuery($sql);
		$this->assert($this->common->errormsg == '', 'getRequest7-stats');	
		
	}
	
	function forumDelete($language, $username, $password, $commonForum){
		
		$user_id = '';
		$forum = '';
		$res = '';
		
		$this->invariant ();
		$this->warning(FORUM_PREFIX,'getRequest5a');
		$this->assert($username && $password && $language, 'getRequest3');
		$this->assert(strtolower(get_class($commonForum)) == 'ds_common', '$this->forumDelete bad $commonForum');
		
		$this->out('Deleting forum user');
		
		$sql = sprintf ( "select user_id
							from %s_users
							where username = '%s'
							and user_password = '%s'",
							FORUM_PREFIX,														
							$username,
							$password );
		$res = $commonForum->getQueryArray ( $sql );
		$this->warning($res, 'getRequest5');		
		
		$user_id = $res[0]['user_id'];

		$sql = sprintf ( "SELECT g.group_id
				FROM %s_user_group ug, 
				%s_groups g
				WHERE ug.user_id = %d
				AND g.group_id = ug.group_id
				AND g.group_single_user = 1",
				FORUM_PREFIX,
				FORUM_PREFIX,
				$user_id );

		$res = $commonForum->getQueryArray ( $sql );
		$this->warning($res, 'getRequest7');
		$group_id = $res[0]['group_id'];

		$sql = sprintf ( "UPDATE %s_posts
				SET poster_id = -1, 
				post_username =  '%s'
				WHERE poster_id = %d",
				FORUM_PREFIX,
				$username,
				$user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest8');						
		
		$sql = sprintf ( "UPDATE %s_topics
					SET topic_poster = -1
					WHERE topic_poster = %d",
					FORUM_PREFIX,
					$user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest9');

		$sql = sprintf ( "UPDATE %s_vote_voters
				SET vote_user_id = -1
				WHERE vote_user_id = %d",
				FORUM_PREFIX,
				$user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest10');

		$sql = sprintf ( "SELECT group_id
				FROM %s_groups
				WHERE group_moderator = %d",
				FORUM_PREFIX,
				$user_id );
		$res = $commonForum->getQueryArray ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest11');
					
		
		$group_moderator = array ();
		
		if(is_array($res))
		foreach ( $res as $k2=>$v2 ) {
			$group_moderator[] = $v2['group_id'];
		}

		if ( sizeof ( $group_moderator ) > 0 ) {
			$update_moderator_id = implode(', ', $group_moderator);
				
			$sql = sprintf ( "UPDATE %s_groups
					SET group_moderator = %d
					WHERE group_moderator IN (%s)",
					FORUM_PREFIX,
					0, $update_moderator_id );
			$res = $commonForum->executeQuery ( $sql );
			$this->warning($commonForum->errormsg == '', 'getRequest12');
		}

		$sql =	sprintf ( "DELETE FROM %s_users
				WHERE user_id = %d",
				FORUM_PREFIX,
				$user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest13');		
		
		$sql = sprintf ( "DELETE FROM %s_user_group
				WHERE user_id = %d",
				FORUM_PREFIX,
				$user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest14');

		$sql = sprintf ( "DELETE FROM %s_groups
				WHERE group_id = %d",
				FORUM_PREFIX,
				$group_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest15');
		
		$this->out('Deleting from forum auth system');
		
        $sql = sprintf ( "DELETE FROM %s_auth_access
        		WHERE group_id = %d",
        		FORUM_PREFIX,
				$group_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest16');

		$sql = sprintf ( "DELETE FROM %s_topics_watch
					WHERE user_id = %d",
						FORUM_PREFIX,
    					$user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest17');

		$sql = sprintf ( "DELETE FROM %s_banlist
               		WHERE ban_userid = %d",
					FORUM_PREFIX,
					$user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest18');

		$sql = sprintf ( "SELECT privmsgs_id
        				FROM %s_privmsgs
       					WHERE privmsgs_from_userid = %d
        				OR privmsgs_to_userid = %d",
						FORUM_PREFIX,
				$user_id, $user_id );
		$res = $commonForum->executeQuery ( $sql );
		$this->warning($commonForum->errormsg == '', 'getRequest19');

		// This little bit of code directly from the private messaging section.
		$this->out('Deleting forum private messages');
		if(is_array($res)){
            foreach (  $res as $k=>$v)	{
            	$mark_list[] = $v['privmsgs_id'];
            }
			  
    		if ( @count($mark_list) ) {
                $delete_sql_id = implode(', ', $mark_list);				
				$delete_text_sql = sprintf ( "DELETE FROM %s_privmsgs_text
        		    	   		 				WHERE privmsgs_text_id IN (%s)",
															FORUM_PREFIX,
                   			          						$delete_sql_id );
				$res = $commonForum->executeQuery ( $delete_text_sql );
				$this->warning($commonForum->errormsg == '', 'getRequest20');

				$delete_sql = sprintf ( "DELETE FROM %s_privmsgs
										WHERE privmsgs_id IN (%s)",
											FORUM_PREFIX,
         		          					$delete_sql_id );
				$res = $commonForum->executeQuery ( $delete_sql );
				$this->warning($commonForum->errormsg == '', 'getRequest21');
			}
		}
		
		$this->out('Forum delete complit');
	}//END OF METHOD
	
	/** Testing input params */
	function assert ($val, $code){
		if(!$val){
			die('Error: DropUserComplete->assert: '.$code);
		}
	}//END OF METHOD

	function warning ($val, $code){
		if(!$val){
			echo('Warning: DropUserComplete->assert: '.$code."<BR>\n");
		}
	}//END OF METHOD
	
	/** Test of object global status */
	function invariant (){
		if (!$this->common){
			die ('Error: DropUserComplete->invariant: Dont set attribute $this->common');
		} 
		
		if (strtolower(get_class($this->common)) != 'ds_common'){
			die ('Error: DropUserComplete->invariant: Uncorrect type of $this->common - '.get_class($this->common));
		}
		
		foreach ($this->common->dbWorldAccess as $k=>$v){
			if(!$v['conn']){
				die('Error: DropUserComplete->invariant: DS_Common cant connect to DB with dbId = '.$k);
			}
		}
		
		if (!$this->drop){
			die ('Error: DropUserComplete->invariant: Dont set attribute $this->drop');
		} 
		
		if (strtolower(get_class($this->drop)) != 'dropinstance'){
			die ('Error: DropUserComplete->invariant: Uncorrect type of $this->common - '.get_class($this->drop));
		}
	}//END OF METHOD
	
	/** Output during deleting */
	function out($msg){
	
		echo '<b>'.date('Y-m-d H:i:s').'</b> '.$msg.'<br>';
		echo '<!-- '; for($i=0;$i<256;$i++) echo '!!!!'; echo ' -->'; 
		echo "<script language=\"JavaScript\">
   							<!--
       						window.scrollTo(0,100000000);
   							// -->
   							</script>";
		flush();
	}
	
}//END OF CLASS

?>