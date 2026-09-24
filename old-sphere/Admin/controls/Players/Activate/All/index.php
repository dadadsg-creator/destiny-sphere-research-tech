<?
set_time_limit(0);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

echo '<p align="right"><a target="_blank" title="help" href='.constant("GAME_HOST").'/Admin/help.php?action=ActivationAll><font size=3><b>HELP</b></font></a></p>';

	if ( !isset ( $GLOBALS["common"] ) ) {
		$common = & ClassFactory::getInstance ( "DS_Common" );
		if(!$common->setDbId(0)){
			die("Cant connect to DB!");
		};
	}

	/**
	 * Класс для активации пользователей с рассылкой им оповещения о регистрации
	 */
	class ActivateUsers {
	
		/** Объект дляработы с БД */
		var $common = '';
		
		/** Счетчик активаций */
		var $activateCounter = 1;
		
		/** Перерыв между активациями */
		var $sleep = 1;
		
		/** Соответсвие кодов ошибок, ошибкам */
		var $errors = array (
			1 => 'Parameter "regdate" not recived by ActivateUsers->activate()',
			2 => 'No field "From"  in activate latter',
			3 => 'No field "Reply"  in activate latter',
			4 => 'No field "Subject"  in activate latter',
			5 => 'No field "Message"  in activate latter',
			6 => 'Cant get users for activation',
			7 => 'Cant activate user',
			8 => 'Cant send latter to user'
		);
		
		var $scrolScript = "<script language=\"JavaScript\">
   							<!--
       						window.scrollTo(0,10000000);
   							// -->
   							</script>";
		
		function ActivateUsers(){
			
			$this->errors = array (
				1 => local( 'DS_ACTIVATE_USERS_ERROR_REGDATE'),
				2 => local( 'DS_ACTIVATE_USERS_ERROR_NO_FIELD', array( 'From' ) ),
				3 => local( 'DS_ACTIVATE_USERS_ERROR_NO_FIELD', array( 'Reply' ) ),
				4 => local( 'DS_ACTIVATE_USERS_ERROR_NO_FIELD', array( 'Subject' ) ),
				5 => local( 'DS_ACTIVATE_USERS_ERROR_NO_FIELD', array( 'Message' ) ),
				6 => local( 'DS_ACTIVATE_USERS_ERROR_USERS' ),
				7 => local( 'DS_ACTIVATE_USERS_ERROR_CANT' ),
				8 => local( 'DS_ACTIVATE_USERS_ERROR_SEND_LETTER' )
			);

			;
		}
		
		function setCommon($common){
			$this->common = $common;
		}
		
		function main($request){
			
			$action = trim(@$request['action']);
			$regDate = trim(@$request['regdate']);
			
			$latter['from'] = @$request['from'];
			$latter['reply'] = @$request['reply'];
			$latter['subject'] = @$request['subject'];
			$latter['message'] = @$request['message'];
			
			if($action == 'activate'){
				$this->activate($regDate, $latter);
			}else{
				$this->outForm();
			}
		}
		
		/**
		 * метод активации пользователей
		 */
		function activate($regDate, $latter){
			
			actionLog('Activating users for registration date - '.$regDate." Admin : ".$UserManager->User[Login] . "");
			
			if(!$regDate){
				$this->error(1);
			}
			
			if(!$latter['from']){
				$this->error(2);
			}
			
			if(!$latter['reply']){
				$this->error(3);
			}
			
			if(!$latter['subject']){
				$this->error(4);
			}
			
			if(!$latter['message']){
				$this->error(5);
			}
			
			$users = $this->getUsers($regDate);
			
			if(!$users){
				$this->msgOut( ShowCMSMessage(local( 'DS_ACTIVATE_USERS_ERROR_NO_USERS' ), 2) );
			};
			
			$this->outHeader($users);
			
			foreach ($users as $k=>$v){
				$this->common->executeQuery('begin');
				if($this->activateUser($v)){
				
					if(!$this->sendLatter($v, $latter)){
						$this->warning(8, ' <b>'.$v['username'].'</b> ('.$v['email'].')');
					};

		                        actionLog('Activate_users from(' .$_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'] .") : '".$v['username']."' Admin : ".$UserManager->User[Login] . "");
					$this->outOkString($v);
					$this->common->executeQuery('commit');
					sleep($this->sleep);
					$this->activateCounter++;
					
				}else{
					$this->error(7, $v['username']);
				}
			}
			
			$this->outFooter();
		}
		
		function outOkString($user){
			echo date('Y-m-d H:i:s')." <strong>".$this->activateCounter."</strong> ".local( 'DS_ACTIVATE_USERS_USER_ACTIVATED', array( $user['username'] ) )."<br>";
			echo $this->scrolScript;
			flush();
			ob_flush();
		}
		
		function activateUser($user){
			$sql = "update ds_user set accountactive = 't' where username = '".$user['username']."'";
//			$sql = "update ds_user set accountactive = 't', activate_mail = 1 where username = '".$user['username']."'";
			return $this->common->executeQuery($sql);
			return true;
		}
		
		function sendLatter($user, $latter){
			$header = "MIME-Version: 1.0\n";
    		$header .= "Content-type: text/plain; charset=windows-1251\n";
			$header .= "From: ".$latter["from"]."\n";
			$header .= "Reply-To: ".$latter["reply"]."\n";
			$header .= "X-Mailer: PHP/" . phpversion();
			$message = $latter["message"];
			$message = str_replace ( "%1", $user['username'], $message );
			$message = str_replace ( "%2", md5($user['username']), $message );
			return mail ( trim ( $user['email'] ), $latter["subject"], $message, $header ); 
		}
		
		/** Вывод шапки активации */
		function outHeader(&$users){
			$colvo = count($users);
			echo "<span class=maintxt>".local( 'DS_ACTIVATE_USERS' )."<br>
			".local( 'DS_ACTIVATE_USERS_FOR_ACTIVATION', array( $colvo ) ).".<br>
			<br>
			".local( 'DS_ACTIVATE_USERS_START_ACTIVATION' )."</span><br>
			<br>";
		}
		
		/** Вывод футера активации */
		function outFooter(){
			$this->activateCounter = $this->activateCounter - 1;
			echo "
				<br><span class=maintxt>
				".local( 'DS_ACTIVATE_USERS_ACTIVATED', array( $this->activateCounter ) ).".<br>
				<br>
				".local( 'DS_ACTIVATE_USERS_COMPLETE' )."</span><br>
				<a href=ActivateUsers.php >".local( 'DS_ACTION_BACK' )."</a><br><br><br>";
			echo $this->scrolScript;
		}
		
		/** Вывод сообщений */
		function msgOut($out){
			echo "<h2>".local( 'DS_ACTIVATE_USERS' )."</h2>";
			echo $out."<br>";
			echo "<br>";
			echo '<a href="ActivateUsers.php">'.local( 'DS_ACTION_BACK' ).'</a>';
			exit;
		}
		
		/**
		 * Выборка списка пользователей для активации 
		 */
		function getUsers($regDate){
			$strDate = " ";
			$sql = "SELECT  
						email,
						username
					FROM 
					 	ds_user
					WHERE 
					 	accountactive = false AND
						id_language = 3 AND
						date_trunc ( 'day', registrationdate ) = to_date ( '".$regDate."', 'YYYY-MM-DD' )";

			$rs = $this->common->getQueryArray($sql);

			if($this->common->errormsg){
				$this->error(6);
			}
			
			if(is_array($rs)){
				return $rs;
			}else{
				return 0;
			};
		}
		
		/**
		 * Метод обработки ошибок
		 */
		function error ($msgId, $msg = ''){
			$str = '<strong>ERROR!</strong><br>
					Code: '.$msgId.'<br>
					Msg: '.$this->errors[$msgId].' '.$msg;
			die($str);
		}
		
		function warning ($msgId, $msg = ''){
			echo '<b>WARNING!: </b>'.$this->errors[$msgId].' '.$msg.'<br>';
		}
		
		/** Вывод почты с письмом */
		function outForm(){
			?>				
				<form method='post' action='<?=$_SERVER["PHP_SELF"]?>'>
  				<span class=maintxt><b><? echo local( 'DS_ACTIVATE_USERS' )." ".local( 'DS_ACTIVATE_USERS_REGDATE' ); ?> :</b></span><br> 
    			<input name='regdate' type="text" value="<?=date( "Y-m-d" )?>" size = '15' maxlength= '10' ><br>
			  	<span class=maintxt><b><? echo local( 'DS_ACTIVATE_USERS_FROM' ); ?> :</b></span><br>
    			<input name = 'from' type ='text' value="noreply@destinysphere.com" size = '50' maxlength= '100'><br>
		      <span class=maintxt><b><? echo local( 'DS_ACTIVATE_USERS_REPLYTO' ); ?> :</b></span><br>
    			<input name = 'reply' type ='text' value="support@ru.destinysphere.com" size = '50' maxlength= '100'><br>
		      <span class=maintxt><b><? echo local( 'DS_ACTIVATE_USERS_SUBJECT' ); ?> :</b></span><br>
    			<input name='subject' type = 'text' value="Destiny Sphere - Ваш игровой аккаунт был только что активирован!" size='75' maxlength='250'><br>
				<span class=maintxt><b><? echo local( 'DS_ACTIVATE_USERS_BODY' ); ?> :</b></span> <BR>
    			<textarea name='message' cols='72' rows='30'>Здравствуйте и добро пожаловать в игру Destiny Sphere!
				
Ваш игровой аккаунт был только что активирован! 

С этого момента вы можете начать играть в Destiny Sphere. Желаем успеха и удачи! 

Ваш логин в игре: %1

ВНИМАНИЕ! Вы должны активировать свой аккаунт ссылкой http://www.destinysphere.ru/ds/index_registration.php?action=activate&key=%2
Иначе вы не сможете начать игру.

Несколько советов:

1. http://www.destinysphere.ru/forum/infos.php - Здесь вы сможете найти разнообразную информацию об игре, в частности, советы новичкам и описание игрового мира.

2. Мы не рекомендуем открывать Центральные ворота до того, как вы достаточно разовьётесь и построите сильную армию, так как в противном случае вы не сможете противостоять возможному нападению со стороны других игроков.

3. Постарайтесь как можно скорее установить контакт с вашими соседями по сотовому комплексу. Дружеские отношения помогут вам совместно противостоять врагам и повысить производство ресурсов. Встроенная в игру почта поможет вам в этом.

4. В Форуме игры (https://forum.destinysphere.ru/forum/index.php) вы найдёте последнте новости и объявления, сможете обсудить последние события и найти ответы на все ваши возможные вопросы по игре.

5. Информацию о свойствах и различиях между расами, населяющими мир колец, вы можете найти здесь - https://forum.destinysphere.ru/forum/races.php.


Ещё раз желаем вам удачи! Однако, помните, что Destiny Sphere - это ещё пока только бета-версия. В будущем игра будет дополнена многими новыми свойствами и возможностями. 
 

Искренне ваша, 
команда поддержки Destiny Sphere.
 
</textarea>
  <br>
  			<input type="hidden" name="action" value="activate">
		      <input type='submit' class=Submit name='start' value = '<? echo local( 'DS_ACTIVATE_USERS_ACTIVATE' ); ?>'>
		    </form>
			<?
		}//END OF METHOD outForm();
		
		
	}//END OF CLASS
	
	$au = new ActivateUsers();
	$au->setCommon($common);
	$au->main($_REQUEST);
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>