<?
  $a = iconv("UTF-8", "windows-1251", "Тест русского шрифта");
  if (empty($a)) {
     print "Error";
  }
?>