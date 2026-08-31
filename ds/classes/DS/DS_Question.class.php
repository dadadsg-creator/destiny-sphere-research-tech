<?php

class DS_Question  extends DS_Base
{
  static public $qperiod = 1800;//30*60 - период задавания вопросов в микросекундах
  static public $maxAttempt  = 3000; //максимальное число попыток ответа на вопрос

  static public function GetAllQuestions($lang, $pg=1)
	{
		$common = self::getCommon();
		$cnt  = 20;
		$start = ($pg-1)*$cnt;
		$sql = "
			SELECT
				id, question, answer_id
			FROM
				ds_antibot_questions where lang={$lang} LIMIT $cnt OFFSET $start";


		$res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
    //echo $common->errormsg;
		assert(empty($common->errormsg));

		for($i = 0; $i<count($res); $i++)
		{
			$r = $res[$i];
		  $sql  = "select id, answer from ds_antibot_answers where question_id={$r['id']}";
   		$answ = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
      //echo $common->errormsg;
  		assert(empty($common->errormsg));
			$res[$i]['answ'] = $answ;
		}

		if(!$res)
		{
			return false;
		}else{
			return $res;
		}
	}//END OF METHOD

	static public function UpdateQuestion($qid, $qtxt, $anum,  $answarr)
	{
	   $common = self::getCommon();

		 //Если у нас пустой текст вопроса или все ответы пустые, то такой
		 //вопрос удаляем.
		 $qtxt  = pg_escape_string($qtxt);
		 $qtxt  = trim($qtxt);
		 $emptyq = empty($qtxt)==true;
		 $emptya = true;
		 foreach ($answarr as $a)
		 {
		   if (trim($a['answtxt'])!='')
			 {
				 $emptya = false;
				 break;
			 }
		 }

		 if ($emptyq || $emptya)
		 {
				self::DeleteQuestion($qid);
				return;
		 }
		 else
		 {
       //обновим ответы и добавим новые
			 $true_aid = 0;
			 for($k=0;$k < count($answarr);$k++)
		   {
		      $answ = $answarr[$k];
					$answtxt = pg_escape_string(trim($answ['answtxt']));
          $aid = $answ['id'];
					if ($aid && !strlen($answtxt))
				  {
             $common->getQuery("DELETE FROM ds_antibot_answers where id={$aid}");
          	 assert(empty($common->errormsg));
					}
					else if ($aid && strlen($answtxt))
				  {
             $sql = "UPDATE ds_antibot_answers set answer='{$answtxt}' where id={$aid}";
						 $common->getQuery($sql);
						 //echo $common->errormsg."<br>";
             assert(empty($common->errormsg));
						 if ($anum==$k)
						   $true_aid = $aid;
					}
					else if (!$aid && strlen($answtxt))
				  {
						 $sql = "INSERT INTO  ds_antibot_answers (question_id, answer) VALUES($qid,'$answtxt')";
             $common->getQuery("$sql");
						 //echo $common->errormsg;
             assert(empty($common->errormsg));
						 if ($anum==$k)
						 {
						   $res = $common->getResultArray( "", "SELECT currval('ds_antibot_answers_seq') as cv", "", "", "NO_CACHE");
							 $true_aid = $res[0]['cv'];
            	 assert(empty($common->errormsg));
						 }
					}
			 }
			 $sql = "UPDATE ds_antibot_questions SET question='{$qtxt}', answer_id=$true_aid where id={$qid}";
			 $common->getQuery($sql);
			 assert(empty($common->errormsg));
		 }
	}


	static public function DeleteQuestion($qid)
	{
	   $common = self::getCommon();


		 $common->getQuery("DELETE FROM ds_antibot_questions where id = {$qid}");
		 assert(empty($common->errormsg));
		 $common->getQuery("DELETE FROM ds_antibot_answers  where question_id = {$qid}");
		 assert(empty($common->errormsg));
	}

	static public function InsertQuestion($qtxt, $anum,  $answarr, $lang=3)
	{
	   $common = self::getCommon();
		 $qtxt = pg_escape_string($qtxt);
		 $common->getQuery("INSERT INTO ds_antibot_questions(question, lang, answer_id)
		                    values('$qtxt', $lang, 0)");
     //echo $common->errormsg;
		 assert(empty($common->errormsg));
	   $res = $common->getResultArray( "", "SELECT currval('ds_antibot_questions_seq') as cv", "", "", "NO_CACHE");
		 $qid = $res[0]['cv'];
		 assert(empty($common->errormsg));

     //var_export($answarr);
     $true_aid = 0;

     for($k=0;$k < count($answarr);$k++)
     {
				$answtxt = pg_escape_string(trim($answarr[$k]['answtxt']));
			  if (strlen($answtxt))
			  {
				  $sql = "INSERT INTO ds_antibot_answers(question_id, answer) VALUES({$qid},'$answtxt')";
          $common->getQuery("$sql");

          assert(empty($common->errormsg));
				  if ($anum==$k)
				  {
				 	   $res = $common->getResultArray( "", "SELECT currval('ds_antibot_answers_seq') as cv", "", "", "NO_CACHE");
					 	 $true_aid = $res[0]['cv'];
             assert(empty($common->errormsg));
					 }
				 }
		 }
		 $sql = "UPDATE ds_antibot_questions SET question='{$qtxt}', answer_id=$true_aid where id={$qid}";
     $common->getQuery("$sql");
	}

	static public function GetRandomQuestion($userid, $id_world=0)
	{
	   $common = self::getCommon();
     $questions = $common->getResultArray( "", "SELECT id, answer_id from ds_antibot_questions", "", "", "NO_CACHE");
		 assert(empty($common->errormsg));
     $res = $common->getResultArray( "", "SELECT qid, attempt, aid, att_status from ds_antibot_userinstance where user_id={$userid} and id_world={$id_world}", "", "", "NO_CACHE");
		 assert(empty($common->errormsg));
     $qid = $res[0]['qid'];

     //SU_DebugTools::LogAll('antibot res='.var_export($res, true),'antibot.log');
     //SU_DebugTools::LogAll('antibot qid='.$qid,'antibot.log', 0);
     if (($res[0]['att_status']==0) && $qid) //если на предыдущий вопрос нет ответа, то возвращаем тот же
     {
       //проверим, есть ли данный qid среди вопросов, а то вдруг его удалили.
       $question_exists = false;
       for($i=0;$i<count($questions);$i++)
       {
          if ($questions[$i]['id']==$qid)
          {
            $question_exists = true;
            break;
          }
       }
       //SU_DebugTools::LogAll('antibot question_exists='.var_export($question_exists, true),'antibot.log');
       if (!$question_exists)
       {
         $qid = $questions[0]['id'];
         $aid = $questions[0]['answer_id'];
         $res = $common->executeQuery("UPDATE  ds_antibot_userinstance set qid={$qid}, real_aid={$aid} where user_id={$userid} and id_world={$id_world}");
         assert(empty($common->errormsg));
       }

       $res = $common->executeQuery("UPDATE  ds_antibot_userinstance set last_question_date = to_timestamp(".time().") where user_id={$userid} and id_world={$id_world}");
       assert(empty($common->errormsg));
       
       return self::GetQuestion($qid);
     }

     $att_status = $res[0]['att_status'];
     //if ($res[0]['attempt'] >= self::$maxAttempt)
     //  return -1;

     $maxix = count($questions)-1;
     
     while(1 && $maxix)
     {
       $r = mt_rand(0, $maxix);
       $rq = $questions[$r]['id'];
       $real_aid  = $questions[$r]['answer_id'];
        //if ($rq==6) break;

       if ($rq==$qid) //не допускаем повторяющихся вопросов
         continue;
       else
         break;

     }
     $attempt = 0;
     //SU_DebugTools::LogAll('antibot qid='.$rq,'antibot.log', 0);
     //$tm = self::GetTimeBeforeQuestion($userid, $id_world);
     if ($att_status == 0) //предыдущий вопрос не отвечен - попытка продолжается
     {
       $attemptstr = ',attempt = attempt+1';
       $last_question_date = '';
     }
     else
     {
       $attemptstr = ',attempt = 1';
       $last_question_date = ",last_question_date = to_timestamp(".time().")";
     }

     $sql = "UPDATE ds_antibot_userinstance set qid={$rq}, aid=0, real_aid=$real_aid, att_status=0 {$attemptstr} {$last_question_date} where user_id={$userid} and id_world={$id_world}";
     $common->executeQuery($sql);
     return self::GetQuestion($rq);
  }

	static public function GetQuestion($qid)
  {
	  $common = self::getCommon();
    $sql = "
			SELECT
				id, question, answer_id
			FROM
				ds_antibot_questions where id={$qid}";

		$res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
		assert(empty($common->errormsg));

    if ($res)
    {
  		$question = $res[0];

      $sql  = "select id, answer from ds_antibot_answers where question_id={$qid}";
     	$answ = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
  		assert(empty($common->errormsg));
			$question['answ'] = $answ;
      return $question;
    }
    else
      return false;
  }

  static public function checkAnswer($qid, $aid, $uid, $id_world=0)
  {
	    $common = self::getCommon();
      $sql  = "select qid, aid, attempt, real_aid from ds_antibot_userinstance where user_id={$uid} and id_world={$id_world}";
      //SU_DebugTools::LogAll("checkAnswer sql = $sql", 'antibot.log');
     	$q = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
      assert(empty($common->errormsg));
      if (!$q)
        return 0;

  //    SU_DebugTools::LogAll('antibot.php CheckAnswer q='.var_export($q[0], true), 'daniil.log');

      $q = $q[0];
      if ($q['qid'] != $qid) //пытаются прислать ответ не на наш вопрос
        return 0;

      $attempt  = $q['attempt'];
      if ($attempt > self::$maxAttempt) //не может быть попыток больше, чем self::$maxAttempt
      {
        return 0;
      }
      $attempt++;
/*
      $sql = "SELECT answer_id 	FROM ds_antibot_questions where id={$qid}";
	  	$res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
  		assert(empty($common->errormsg));
*/
      $real_aid = $q['real_aid'];

      $att_status = ($real_aid == $aid)?1:0;
      $delta = '';
      $lastactstr  = $attemptstr = '';
      if ($att_status)
      {
        $delta = ', delta='.self::$qperiod;
        $lastactstr  = 'last_action_time = NOW(),';
      }
      $attemptstr = ', attempt='.$attempt;

      $sql = "UPDATE ds_antibot_userinstance SET aid=$aid, $lastactstr att_status = $att_status {$delta} {$attemptstr}   where user_id={$uid} and id_world={$id_world}";
	  	$res = $common->executeQuery($sql);
      //echo $common->errormsg;
  		assert(empty($common->errormsg));

      $ct = time();
      $sql = "INSERT INTO ds_antibot_stat(user_id, qdate, status, id_world) VALUES($uid, to_timestamp({$ct}), $att_status, $id_world)";
	  	$res = $common->executeQuery($sql);
      assert(empty($common->errormsg));

      return array($att_status, $attempt);
  }

  static public function GetTimeAfterLastQuestion($user_id, $id_world)
	{
    	// $arr =  array(1800000, 1);
  		//return $arr;

      $common = self::getCommon();
      $sql  = "SELECT  att_status, date_part('epoch', last_question_date)  as s from ds_antibot_userinstance where user_id={$user_id} and id_world={$id_world}";
   		$res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
      //echo $common->errormsg;
  		assert(empty($common->errormsg));
      $ct = time();
      $bdtime  = $res[0]['s'];
      $curdelta = $ct - $bdtime;
      //SU_DebugTools::LogAll("last_question_date res=".var_export($res, true). ", as sec=$bdtime, curdelta=$curdelta ","antibot.log");
      return array($curdelta, $res[0]['att_status']);
  }

  static public function GetTimeBeforeQuestion($user_id, $id_world=0, $updateDelta = true)
	{
    	// $arr =  array(1800000, 1);
  		//return $arr;

      $common = self::getCommon();
      $sql  = "SELECT id, delta, att_status, date_part('epoch', last_action_time) as s from ds_antibot_userinstance where user_id={$user_id} and id_world={$id_world}";

   		$res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
      //echo $common->errormsg;
  		assert(empty($common->errormsg));

      if (!$res)
        return self::InsertAntibotUserInstance($user_id, $id_world);
      else
      {
        $ct = time();
        $bdtime  = $res[0]['s'];
        $bddelta = $res[0]['delta'];
        $att_status = $res[0]['att_status'];

        if ($updateDelta)
        {
          $curdelta = $ct - $bdtime;
          $newdelta = $bddelta - $curdelta;
        }
        else
        {
          $newdelta  = $bddelta;
        }

        $sql = "UPDATE ds_antibot_userinstance set delta={$newdelta}, last_action_time = to_timestamp({$ct})  WHERE user_id={$user_id} and id_world={$id_world}";
        $common->executeQuery($sql);
   		  assert(empty($common->errormsg));

        return array($newdelta*1000, $att_status);
      }
  }

  static public function InsertAntibotUserInstance($user_id, $id_world=0)
	{
     $common = self::getCommon();
     $tm  = time();

     $sql = "INSERT INTO ds_antibot_userinstance(user_id, last_question_date, aid, delta, att_status,  last_action_time, id_world)
             VALUES({$user_id}, NULL, 0,".self::$qperiod.", 1, to_timestamp(".time()."), $id_world)";
     $common->executeQuery($sql);

     //echo $common->errormsg;
 		 assert(empty($common->errormsg));
     return array(self::$qperiod*1000, true);
  }
}
?>