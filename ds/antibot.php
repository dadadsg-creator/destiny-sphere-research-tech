<?php
	require_once "Main.php";

	//Старт сессии
	if(!SU_GameSessionWork::SessionContinue())
	{
       $str =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<question qid=\"0\" >
<qtxt><![CDATA[Ваша сессия истекла. Перелогиньтесь.]]></qtxt>";

       $str .= "<answer aid=\"1\">";
       $str .= "<![CDATA[Да]]>";
       $str .= "</answer>";
       $str .= "<answer aid=\"1\">";
       $str .= "<![CDATA[Нет]]>";
       $str .= "</answer>";
       $str .= "<answer aid=\"1\">";
       $str .= "<![CDATA[Так точно]]>";
       $str .= "</answer>";

       $str .= "</question>";
       echo $str;
       exit;
	}
  $act = @$_REQUEST['cmd'];

  if (($act ==1) || (!$act)) //получение вопроса
  {
    ShowQuestion();
  }
  else if ($act ==2) //получение ответа на вопрос
  {
    GetAnswer();
  }

  function GetAnswer()
  {
    $user_id = $_SESSION['uid'];
    $qid  = intval($_REQUEST['qid']);
    $aid  = intval($_REQUEST['aid']);

    if (empty($_SESSION['world']))
    {
    echo "turing=<?xml version=\"1.0\" encoding=\"utf-8\"?>
<answer res=\"0\" />";
      exit;
    }
    list($res, $attempt_num) = DS_Question::checkAnswer($qid, $aid, $user_id, $_SESSION['world']);
    //    SU_DebugTools::LogAll('antibot.php GetAnswer. REQUEST='.var_export($_REQUEST, true).",\n //qid={$qid}, aid={$aid}, res=$res", 'daniil.log');

    if (!$res)
    {
      //поместим в таблицу подозрительных

      if ($attempt_num >= 5)
        DS_Botlist::AddBot($user_id);
      //$_SESSION[] = array();
      //header("Location: index_start.php");
    echo "turing=<?xml version=\"1.0\" encoding=\"utf-8\"?>
<answer res=\"$res\" />";

    }
    else
    {
    echo "turing=<?xml version=\"1.0\" encoding=\"utf-8\"?>
<answer res=\"$res\" />";

    }
  }

  function ShowQuestion()
  {
    header('Content-type: text/xml');
    $user_id = $_SESSION['uid'];
    if (!empty($_SESSION['uid']) && !empty($_SESSION['world']))
    {
      list($qdelta,$att_status) = DS_Question::GetTimeAfterLastQuestion($_SESSION['uid'],
          $_SESSION['world']);

      //SU_DebugTools::LogAll("qdelta=$qdelta, att_status=$att_status","daniil.log");
      if (($qdelta < 25*60 ) && $att_status) //ранее, чем за 5 минут (30-25=5) нельзя вопрос задавать
                                             //но если предыдущая попытка ($attstatus==0) была безуспешной, то можно
      {
       $str =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<question qid=\"0\" >
<qtxt>А не рано ли мы хотим вопрос получить?</qtxt>";
       $str .= "<answer aid=\"1\">";
       $str .= "<![CDATA[Да]]>";
       $str .= "</answer></question>";
        $str = iconv("cp1251", "UTF-8", $str);
        //SU_DebugTools::LogAll("str=$str","daniil.log");

        echo "turing=".$str;
        exit;
      }
      $q = DS_Question::GetRandomQuestion($user_id, $_SESSION['world']);

    }
    else
      $q = -1;

    if (!is_array($q) && ($q==-1))
    {
       $str =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<question qid=\"0\" >
<qtxt><![CDATA[Если вы видите это сообщение, значит ваша сессия по каким-то причинам слетела. Система антибота к этому не имеет отношения. Перелогиньтесь.]]></qtxt>";

       $str .= "<answer aid=\"1\">";
       $str .= "<![CDATA[Перелогиньтесь]]>";
       $str .= "</answer>";

       $str .= "</question>";
    }
    else
    {
       $q['question'] = str_replace('+','%2B',$q['question']);
       $str =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<question qid=\"{$q['id']}\" >
<qtxt><![CDATA[{$q['question']}]]></qtxt>";

       foreach ($q['answ'] as $a)
       {
         $str .= "<answer aid=\"{$a['id']}\">";
         $str .= "<![CDATA[{$a['answer']}]]>";
         $str .= "</answer>";
       }
        $str .= "</question>";
    }


   $str = iconv("cp1251", "UTF-8", $str);
   echo "turing=".$str;
  }
?>