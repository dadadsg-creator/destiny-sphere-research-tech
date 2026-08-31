<?php

$GLOBALS['List_of_Bases'] = array //список баз и параметров к ним
(
/*  'Master'=>array(            
            'host'=>'gamedb14', //сервер, на котором лежит база
            'dbname'=>'destiny3', //имя базы на сервеере
            'user'=>'dsgame', //имя пользователя базы
            'password'=>'REDACTED_DB_PASSWORD', //пароль для доуступа к базе
            'port'=>'5432' //порт на серевере для работы с базой
            ),
    'Slave'=>array(         
            'host'=>'gamedb4',
            'dbname'=>'destiny2',
            'user'=>'dsgame',
            'password'=>'REDACTED_DB_PASSWORD',
            'port'=>'5432'
            )*/

'Master'=>array(            
            'host'=>'localhost', //сервер, на котором лежит база
            'dbname'=>'parusa', //имя базы на сервеере
            'user'=>'dsgame', //имя пользователя базы
            'password'=>'REDACTED_DB_PASSWORD', //пароль для доуступа к базе
            'port'=>'5432' //порт на серевере для работы с базой
            ),
            
'Slave'=>array(         
            'host'=>'localhost', //сервер, на котором лежит база
            'dbname'=>'pulsar_otkat', //имя базы на сервеере
            'user'=>'dsgame', //имя пользователя базы
            'password'=>'REDACTED_DB_PASSWORD', //пароль для доуступа к базе
            'port'=>'5432' //порт на серевере для работы с базой
            ),

);

assert_options(ASSERT_BAIL, TRUE); //настроили функцию проверки параметров

?>