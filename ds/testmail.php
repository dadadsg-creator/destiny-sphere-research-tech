<?php
 /*if (mail('annita@destinysphere.com','test letter','123 test'))
 {
    echo "MAIL OK";
 }
 else 
 {
    echo "MAIL FAIL";
 }
 */
 		$latter = "MAIL OK";
 
 echo "start<br>";
 mail_utf8('annita@destinysphere.com', 'TEST', $latter, array('name'=>'Destiny Sphere', 'email' => 'noreply@destinysphere.com'), 'windows-1251');

 
  function mail_utf8($to, $subject = '(No subject)', $message = '', $from, $enc)
  {
    
    if (strtolower($enc)!='utf-8')
    {
      $message = iconv($enc, 'utf-8', $message); 
      $subject = iconv($enc, 'utf-8', $subject); 
    }
  
    $header = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/plain; charset=UTF-8'
    . "\n" . 'From: '.$from['name'].' <' . $from['email'] . ">\n";
  echo "<pre>".$header."!!!<br>";

  
  return mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header);

  
  }
  echo "OK";
  
?>