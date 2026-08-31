<?php

require_once 'Main.php';
require_once (constant('INCLUDE_PATH').'/Smarty/Smarty.class.php');

// включаем убийство сессии в случае assert или ds_error
//$GLOBALS['HI_SECURITY_FLAG'] = 1;

SU_GameSessionWork::SessionContinue();
assert(key_exists('uid', $_SESSION));


$quiz = new TMP_QuizController();

$quiz->Exec();

?>