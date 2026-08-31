<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отправить сообщение



class UA_SendMessage2 extends UA_Action {

	public $to;
	public $subject;
	public $id_messagescope;
	public $messagescopename;
	public $messagetext;
	public $to_array;
	public $badlocation;
	public $errorname;
	public $id_message;

	function UA_SendMessage () {
		UA_Action::UA_Action ();
	}

	// провер€ем параметры
	function checkLocalParams() 
	{

		$this->errorname = array ();

		// список получателей
		$ch = $this->root->get_elements_by_tagname ( "to" );

		$node = $ch[0];
		$this->to = trim ( utf8_decode ( $node->get_content() ) );

		// тема
		$ch = $this->root->get_elements_by_tagname ( "subject" );
		$node = $ch[0];
		$this->subject = trim ( utf8_decode ( $node->get_content() ) );

		// messagescope
		$this->id_messagescope = $this->root->get_attribute ( "id_messagescope" );
		if ( empty ( $this->id_messagescope ) ) {
			$this->errormsg .= " SYSTEM ERROR UA_SendMessage::checkLocalParams1";
			return false;
		}

		// текст сообщени€
		$ch = $this->root->get_elements_by_tagname ( "messagetext" );
		$node = $ch[0];
		$this->messagetext = trim(utf8_decode($node->get_content()));

		/**
		 * јнти-ћат спам фильтр сообщений.
		 */
		$this->messagetext = GB_AntiMatFilter::Exec($this->messagetext);
		$this->subject = GB_AntiMatFilter::Exec($this->subject);


		// если указано €вно кому, провер€м получателей
		if  ( strlen ( $this->to ) > 0 ) 
		{

			$arr = array ( "'", "\"", "`", "~", "!", "@", "#", "$", "%", "^", "*", "(", ")", "<", ">", "[", "]", "{", "}", "\n", "\t" );

			$this->to = str_replace ( $arr, "", $this->to );

			if ( strlen ( $this->to ) > 200 ) {
				$this->errorname[] = 'ADDRESSEE_TOO_LONG';
			}

			if ( strlen ( $this->to ) < 2 ) {
				$this->errorname[] = 'ADDRESSEE_TOO_SHORT';
			}
		}

		$arr = array ( "'", "`", "\"","<", ">",  "\n", "\t" );

		// провер€ем тему
		$this->subject = str_replace ( $arr, "", $this->subject );

		if ( strlen ( $this->subject ) > 200 ) {
			$this->errorname[] = 'SUBJECT_TOO_LONG';
		}

		if ( strlen ( $this->subject ) < 3 ) {
			$this->errorname[] = 'SUBJECT_TOO_SHORT';
		}

		$arr = array ( "'", "`", "\"" );

		$this->messagetext = str_replace ( $arr, "", $this->messagetext );

		if ( strlen ( $this->messagetext ) > 10000 ) {
			$this->errorname[] = 'MESSAGE_TOO_LONG';
		}

		if ( strlen ( $this->messagetext ) < 3 ) {
			$this->errorname[] = 'MESSAGE_TOO_SHORT';
		}

		// при reply или forward
		$this->id_message = $this->root->get_attribute ( "id_message" );

		return true;
	}

	// провер€ем допустимость
	function checkAction() {

		global $params;
		global $common;

		if ( sizeof ( $this->errorname ) != 0 ) return true;

		// название messagescope
		$sql = sprintf ( "select messagescopename
				from ds_messagescope
				where id_messagescope = %d",
				$this->id_messagescope );

		$res = $common->getResultArray ( "messagescopename_id_messagescope_".$this->id_messagescope, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_SendMessage::checkAction1";

			return false;
		}

		$this->messagescopename = $res[0]['messagescopename'];

		// частное сообщение
		if ( $this->messagescopename == 'PRIVATE' ) 
		{

			if ( strlen ( $this->to ) == 0 ) {
				$this->errorname[] = 'ADDRESSEE_TOO_SHORT';
				return true;
			}

			$this->to_array = @preg_split ( "/\, *|\; *| +/", $this->to );

			if ( !$this->to_array || sizeof ( $this->to_array ) == 0 ) {
				$this->errormsg .= " SYSTEM ERROR UA_SendMessage::checkAction3";
				return false;
			}

			$this->to_array = array_unique ( $this->to_array );
		}

		$sql = sprintf ( "select id_messagescope
			from ds_userallowedmessagescope
			where id_userinstance = %d
			and id_messagescope = %d",
			$params["uiid"],
			$this->id_messagescope );

		$res = $common->getResultArray ( "id_messagescope_id_userinstance_id_messagescope_".$params["uiid"]."_".$this->id_messagescope, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR UA_SendMessage::checkAction1";

			return false;
		}

		return true;
	}

	// выполн€ем действие
	function performAction () {

		global $params;
		global $common;

		$common->clearLocalCache ( $params["uiid"] );

		$sent = array ();

		if ( sizeof ( $this->errorname ) == 0 ) 
		{

			// создаем сообщение
			$id_message = $this->createMessage ( $this->id_messagescope, $params["uiid"], $this->subject, $this->messagetext );

			if ( !$id_message ) {
				return false;
			}

			$m = 0;
			$b = 0;

			// отправл€ем сообщение
			switch ( $this->messagescopename ) {

				// частное сообщение
				case 'PRIVATE':

					foreach ( $this->to_array as $to_array ) {

						// указаны координаты
						if ( ereg ( "^[0-9]+\.[0-9]+\.[0-9]+$", $to_array ) ) { // location

							list ( $ringnum, $complexnum, $combnum ) = split ( "\.", $to_array );

							if ( $ringnum == "" || $complexnum == "" || $combnum == "" ) {
								$this->errormsg .= " SYSTEM ERROR UA_CreateMessage::performAction1";
								return false;
							}

							$sql = sprintf ( "select ui.id_userinstance
									from ds_userinstance ui,
									ds_comb co,
									ds_combcomplex cc,
									ds_ring ri
									where ui.id_userinstance = co.id_userinstance
									and co.id_combcomplex = cc.id_combcomplex
									and cc.id_ring = ri.id_ring
									and ri.id_world = %d
									and ri.ringnumber = %d
									and cc.complexnumber = %d
									and co.combnumber = %d",
									$this->world,
									$ringnum,
									$complexnum,
									$combnum );

							$res = $common->getResultArray ( "id_userinstance_id_world_ringnumber_complexnumber_combnumber_".$this->world."_".$ringnum."_".$complexnum."_".$combnum, $sql, "", "", "GLOBAL_CACHE" );

						} else { // указано им€

							$sql = sprintf ( "select ui.id_userinstance
									from ds_userinstance ui,
									ds_user u
									where ui.id_user = u.id_user
									and u.username = '%s'
									and ui.id_world = %d",
									$to_array,
									$this->world );

							$res = $common->getResultArray ( "id_userinstance_username_is_world_".$to_array."_".$this->world, $sql, "", "", "GLOBAL_CACHE" );
						}


						if ( $res === false ) {
							$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction2";

							return false;
						}

						if ( sizeof ( $res ) == 0 ) {
							$badlocation[] = $to_array;
							$b++;
							continue;
						}

						if ( in_array( $res[0]['id_userinstance'], $sent ) ) continue;

						if ( !$this->sendMessage ( $res[0]['id_userinstance'], $id_message ) ) {
							return false;
						}

						$sent[] = $res[0]['id_userinstance'];

						$m++;

						// сообщение gamehelp-еру

						if ( $this->id_message != "" ) {

							$sql =sprintf ( "select id_message
									from ds_message m,
									ds_messagescope ms
									where m.id_messagescope = ms.id_messagescope
									and m.id_message = %d
									and ms.messagescopename = 'GAME_HELPER'",
									$this->id_message );

							$res = $common->getResultArray ( "id_message_game_helper_id_message_".$this->id_message, $sql, "", "", "" );
							if ( $res === false ) {
								$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction3";

								return false;
							}

							if ( sizeof ( $res ) > 0 ) {

								$sql = sprintf ( "delete from
										ds_userhasmessage
										where id_message = %d
										and id_userinstance != %d",
										$this->id_message,
										$params['uiid'] );

								$res = $common->executeQuery ( $sql );
								if ( $res === false ) {
									$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction4";

									return false;
								}
							}
						}
					}

				break;

				// сооббщение сосед€м по комплексу
				case 'COMB_COMPLEX':

					$sql = sprintf ( "select id_userinstance
							from ds_comb
							where id_combcomplex = %d
							and id_userinstance is not null
							and id_userinstance != %d",
							$this->combcomplex,
							$params["uiid"] );

					$res = $common->getResultArray ( "id_userinstance_id_combcomplex_id_userinstance_".$this->combcomplex."_".$params["uiid"], $sql, "", "", "GLOBAL_CACHE" );
					if ( $res === false ) {
						$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction5";

						return false;
					}

					for ( $i=0; $i<sizeof ( $res ); $i++ ) {

						if ( !$this->sendMessage ( $res[$i]['id_userinstance'], $id_message ) ) {
							return false;
						}
						$m++;
					}

				break;

				// всем игрокам на всех кольцах игрового мира
				case 'WORLD':
					$sql = sprintf ( "select id_userinstance
							from ds_userinstance
							where id_world = %d
							and id_userinstance != %d",
							$this->world,
							$params["uiid"] );

					$res = $common->getResultArray ( "id_userinstance_id_world_id_userinstance_".$this->world."_".$params["uiid"], $sql, "", "", "GLOBAL_CACHE" );
					if ( $res === false ) {
						$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction6";
						return false;
					}

					for ( $i=0; $i<sizeof ( $res ); $i++ ) {

						if ( !$this->sendMessage ( $res[$i]['id_userinstance'], $id_message ) ) {
							return false;
						}
						$m++;
					}

				break;

				// всем членам аль€нса
				case 'INTERNAL_ALLIANCE':

					$sql = sprintf ( "select id_userinstance
							from ds_userhasalliance
							where id_alliance =
							(select id_alliance
							from ds_userhasalliance
							where id_userinstance = %d)
							and id_userinstance != %d",
							$params["uiid"],
							$params["uiid"] );

					$res = $common->getResultArray ( "id_userinstance_id_userinstance_id_userinstance_".$params["uiid"]."_".$params["uiid"], $sql, "", "", "GLOBAL_CACHE" );
					if ( $res === false ) {
						$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction7";

						return false;
					}

					for ( $i=0; $i<sizeof ( $res ); $i++ ) {
						if ( !$this->sendMessage ( $res[$i]['id_userinstance'], $id_message ) ) {
							return false;
						}
						$m++;
					}

				break;

				// мастерам игры
				case 'GAME_MASTER':

					$sql = sprintf ( "select gm.id_userinstance
							from ds_gamemaster gm,
							ds_userinstance ui
							where gm.id_userinstance = ui.id_userinstance
							and ui.id_world = %d",
							$this->world );

					$res = $common->getResultArray ( "game_masters", $sql, "", "", "GLOBAL_CACHE" );
					if ( $res === false ) {
						$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction8";

						return false;
					}

					for ( $i=0; $i<sizeof ( $res ); $i++ ) {
						if ( !$this->sendMessage ( $res[$i]['id_userinstance'], $id_message ) ) {
							return false;
						}
						$m++;
					}

				break;

				// помошникам игры
				case 'GAME_HELPER':

					$sql = sprintf ( "select gh.id_userinstance
							from ds_gamehelper gh,
							ds_userinstance ui
							where gh.id_userinstance = ui.id_userinstance
							and ui.id_world = %d",
							$this->world );

					$res = $common->getResultArray ( "game_helpers", $sql, "", "", "GLOBAL_CACHE" );
					if ( $res === false ) {
						$this->errormsg .= " SYSTEM ERROR UA_SendMessage::performAction9";

						return false;
					}

					for ( $i=0; $i<sizeof ( $res ); $i++ ) {
						if ( !$this->sendMessage ( $res[$i]['id_userinstance'], $id_message ) ) {
							return false;
						}
						$m++;
					}

				break;
			}

			$messparams[0] = $m;

			// системное сообщение об успешно отправленных сообщени€х
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "SEND_MESSAGE_MESS1", $messparams );

			// системное сообщение о неотправленных сообщени€х
			if ( $b != 0 ) 
			{

				$messparams[0] = $b;

				DE_SystemMessages::AddMessageFromAction(__CLASS__, "SEND_MESSAGE_MESS2", $messparams );
			}

		} else { // если ошибка - выводим сообщение и выходим

			$messparams[0] = "";
			for($i=0; $i < sizeof ( $this->errorname ); $i++) 
			{
				DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errorname[$i], $messparams );
			}
		}

		

		if ( $_SESSION['profi_game'] )
		{
			$noCellData = true;
		} else {
			$noCellData = false;
		}

		// минимальный рефреш
		if(!$this->refresh(2, "", "", "", $noCellData)) 
		{
			return false;
		}

		return true;
	}

	// создает сообщение
	function createMessage($id_messagescope, $id_user_from, $subject, $messagetext) 
	{
		settype($id_messagescope, 'integer');		
		assert($id_messagescope > 0);
		
		settype($id_user_from, 'integer');		
		assert($id_user_from >= 0);
		
		assert(!empty($subject));
		assert(!empty($messagetext));	
		
		$sql = "select nextval('ds_message_id_message_seq') as id";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg) && count($res) > 0);
		
		$messageID = (int)$res[0]['id'];
		assert($messageID > 0);
		
		$sql = "INSERT INTO ds_message 
				(
					id_message,
					id_messagescope,
					id_from_userinstance,
					subject,
					messagetext,
					messagedate
				) VALUES (
					".$messageID.",
					".$id_messagescope.",
					".$id_user_from.",
					E'".pg_escape_string(urldecode($subject))."',
					E'".pg_escape_string(urldecode($messagetext))."',
					now() 
				)";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
						
		return $messageID;
	}

	// отправл€ет сообщение
	function sendMessage($id_user_to, $id_message) 
	{						
		settype($id_message, 'integer');
		assert($id_message > 0);
		
		if($id_user_to == 0)
		{
			return true;
		}
		
		$sql = "INSERT INTO ds_userhasmessage
				(id_message, id_userinstance )
				VALUES 
				( $id_message, $id_user_to )";
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
		
		return true;
	}
}

?>