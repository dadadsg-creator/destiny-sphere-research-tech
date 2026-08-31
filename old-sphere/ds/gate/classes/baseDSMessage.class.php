<?
class BaseDSMessage
{
    /* Переменные внутреннего назначения */
    public $common;

    // список IP адресов с каторых нужно переводить параметры во внутренний формат
    private $translateParams = array(
    // Iricom
    //'212.24.38.229' => 'SMS_TranslateParamsFromIricom',
    // для укранины
    '212.109.44.74' => 'SMS_TranslateParamsFromIricom',
    // для казахстана
    '217.23.137.4'  => 'SMS_TranslateParamsFromIricom',

    // ALPHA TEST
    //'81.19.71.94' => 'SMS_TranslateParamsFromIricom',

    // Литва
    '212.47.102.24' => 'SMS_TranslateParamsFromLithuania',
    //'192.168.0.11' => 'SMS_TranslateParamsFromLithuania',
    '192.168.0.11' => 'SMS_TranslateParamsFromPlasticMedia',

    // Rambler Plus
    '81.19.64.205' => 'SMS_TranslateParamsFromRamblerPlus', // Test
    '172.16.13.10' => 'SMS_TranslateParamsFromRamblerPlus', // RAMBLER
    '81.19.64.196' => 'SMS_TranslateParamsFromRamblerPlus', // RAMBLER
    '81.19.64.197' => 'SMS_TranslateParamsFromRamblerPlus', // RAMBLER
    '81.19.64.194' => 'SMS_TranslateParamsFromRamblerPlus', // RAMBLER
    '81.19.64.195' => 'SMS_TranslateParamsFromRamblerPlus', // RAMBLER
    '81.19.64.199' => 'SMS_TranslateParamsFromRamblerPlus', // RAMBLER
    '81.19.64.200' => 'SMS_TranslateParamsFromRamblerPlus', // RAMBLER

    // Plastic Media без посредников
    '217.199.212.3' => 'SMS_TranslateParamsFromPlasticMedia',
    '81.177.35.242' => 'SMS_TranslateParamsFromPlasticMedia',
    '217.197.122.26' => 'SMS_TranslateParamsFromPlasticMedia',

    // Наш айпишник
    '195.182.156.98' => 'SMS_TranslateParamsFromPlasticMedia',
    );

    private $smsLogOid;

    /**
     * используется для литовцев, чтоб не снимались деньги, если ошибка какая была
     * protected, чтоб было видно в dsmessage.php
     *
     * @see метод sendRsponse();
     *
     * @var bool
     */
    protected $resultOk = false;

    /**
     * Выполнение услуг с ролбеком в конце, для проверки без реального исполнения
     * нужно для Литовцев, с их системой двойного запроса
     *  1) Проверить, подготовить ответ и сказать сколько будет стоить
     *  2) Уточнить хватит ли денег и предоставить услугу, если все ок.
     *
     * @var bool
     */
    protected $testMode = false;

    protected $errorParams = false;

    protected $log;
    protected $offline = false;
    protected $result = false;
    protected $recipient = true;

    /* Для ответа СМС-ком о статусе обработки пришедшего сообщения*/
    protected $response_url = 'http://81.19.68.244:83/sphere/fromDestiny.jsp';
    protected $response_text;
    protected $response_status = 0; //default
    protected $response_statusPM = -1; //default

    // Переменная для записи ошибок
    protected $error = "No Error";


    protected $msgText;

    protected $incomingData = array();

    private $partnerCode;

    // Параметр PAID  может принимать только 2 значения YES или NO
    // Значение YES означает, что запрос оплачен абонентом и партнеру следует его обработать.
    //private $paid = 'YES';

    // Статус сообщения для Plastic Message
    /*   1 - всё ок
    -1 - ошибка во входных данных, при данной ошибке Rambler Plus отвечает агрегатору, что Пользователь ошибся и причина ошибки в тексте смс.
    -2 - внутренняя ошибка сервиса при обработке платежа. При данной ошибке Rambler Plus сообщает агрегатору, что на данный момент сервис испытывает технические проблемы и не может провести платеж. В этом случае, агрегатор формирует повторные сообщения для перепроведения платежа.*/

    function BaseDSMessage()
    {
        SMS_Localization::Init();
    }

    function getCommon()
    {
        return $GLOBALS['common'];
    }

    function setCommon($common)
    {
        $GLOBALS['common'] = $common;
    }

    function setDb($db)
    {
        $GLOBALS['common']->setDbId($db);
    }

    function checkWorldID($world)
    {
        $common = $this->getCommon();
        if (strlen($world) < 3)
        {
            $this->response_statusPM = -1;
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_WORLD');
            $this->sendResponse();
            return false;
        }

        $world = DSMessage::wordProcess($world);
        $sql = "SELECT id_world, dbname FROM ds_worldcollection WHERE upper(world_identifier) = upper('".pg_escape_string($world)."')";

        $res = $common->getResultArray("",$sql,"","","");
        if ($res === false)
        {
            $this->response_statusPM = -1;
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_WORLD');
            $this->sendResponse();
            return false;
        }

        if (sizeof($res)>0)
        {
            $this->worldId = (int)$res[0]['id_world'];
            $this->dbName = $res[0]['dbname'];
            $this->worldName = $world;


            $this->dbId = $common->getDbIdByName($this->dbName);
            $this->setDb($this->dbId);
            $common->setDbId($this->dbId);
        } else {
            $this->response_statusPM = -1;
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_WORLD_2');
            $this->sendResponse();
            return false;
        }
        return true;
    }

    function formStat($trigger = null, $smstext = null, $emulator = null, $shortcode = null)
    {

    }


    function getUserInstanceByLogin($username)
    {
        $common = $this->getCommon();

        if (empty($username))
        {
            $this->errorParams = true;
            $this->response_text = 'Wy ne ukazali imya igroka, kotoromu budet dostawleno soobschenie';
            $this->sendResponse();
            return false;
        }

        $username = DSMessage::wordProcess($username);

        if (!empty($username))
        {
            $sql = "
                    SELECT
                        ui.id_userinstance
                    FROM
                        ds_userinstance ui
                        INNER JOIN ds_user u ON (ui.id_user=u.id_user)
                    WHERE
                        (upper(u.username) = upper('".$username."')) AND
                        (ui.id_world = ".$this->worldId.")
            ";

            $res = $common->getResultArray("",$sql,"","","");
            if ($res === false)
            {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->response_text = 'GetUserName Error! -2';
                $this->sendResponse();
                return false;
            }

            if (sizeof($res)>0)
            {
                $this->userInstanceTo = (int)$res[0]['id_userinstance'];
            } else {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->errorParams = true;
                $this->response_text = 'Takogo adresata ne suschestwuet!';
                $this->sendResponse();
                return false;
            }

        }
        return true;
    }

    function getUserInstanceByLoginAndPassword($username, $password)
    {
        $common = $this->getCommon();

        if (empty($username) || empty($password))
        {
            $this->response_statusPM = -1;
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_LOGIN_OR_PASSWORD');
            $this->sendResponse();
            return false;
        }

        $username = DSMessage::wordProcess($username);

        if (!empty($username) && !empty($password))
        {
            $sql = "
                    SELECT
                        u.time_delta,
                        ui.id_userinstance
                    FROM
                        public.ds_userinstance ui
                        INNER JOIN ds_user u ON (ui.id_user=u.id_user)
                    WHERE
                        (upper(u.username) = upper('".$username."')) AND
                        (u.password = md5('".$password."')) AND
                        (ui.id_world = ".$this->worldId.")
            ";

            $res = $common->getResultArray("",$sql,"","","");
            if ($res === false)
            {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->response_text = 'GetUserName Error! -13';
                $this->sendResponse();
                return false;
            }

            if (sizeof($res)>0)
            {
                $GLOBALS['time_delta'] = (int)$res[0]['time_delta'];
                $this->userInstanceFrom = (int)$res[0]['id_userinstance'];
            } else {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->errorParams = true;
                $this->response_text = SMS_Localization::Get('ERROR_LOGIN_OR_PASSWORD_2');
                $this->sendResponse();
                return false;
            }

        }
        return true;
    }



    function getResult()
    {
        return $this->result;
    }

    function getUserIDByUserNameAndPassword($username, $password)
    {
        $common = $this->getCommon();


        if (empty($username))
        {
            $this->response_statusPM = -1;
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_LOGIN_OR_PASSWORD');
            $this->sendResponse();
            return false;
        }

        $username = DSMessage::wordProcess($username);

        assert(empty($password));

        if (!empty($username))
        {
            $sql = "
                    SELECT
                        u.id_user
                    FROM
                        ds_user u
                    WHERE
                        (upper(u.username) = upper('".$username."'))
                    AND
                        u.password = md5('".$password."')
            ";
            $res = $common->getResultArray("",$sql,"","","");
            if ($res === false)
            {
                $this->response_statusPM = -1;
                $this->response_text = 'GetUserName Error! -1';
                $this->sendResponse();
                return false;
            }

            if (sizeof($res)>0)
            {
                return (int)$res[0]['id_user'];
            } else {
                $this->response_statusPM = -1;
                $this->errorParams = true;
                $this->response_text = SMS_Localization::Get('ERROR_LOGIN_OR_PASSWORD_2');
                $this->sendResponse();
                return false;
            }

        }
    }

    function getUserIDByUserName($username)
    {
        $common = $this->getCommon();


        if (empty($username))
        {
            $this->response_statusPM = -1;
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_LOGIN');
            $this->sendResponse();
            return false;
        }

        $username = DSMessage::wordProcess($username);

        /* Проверяем на существование юзверя с указанным логином */
        if (!empty($username))
        {
            $sql = "
                    SELECT
                        u.id_user
                    FROM
                        ds_user u
                    WHERE
                        (upper(u.username) = upper('".$username."'))
            ";
            $res = $common->getResultArray("",$sql,"","","");
            if ($res === false)
            {
                $this->response_statusPM = -1;
                $this->response_text = 'GetUserName Error! -12';
                $this->sendResponse();
                return false;
            }

            if (sizeof($res)>0)
            {
                return (int)$res[0]['id_user'];
            } else {
                $this->response_statusPM = -1;
                $this->errorParams = true;
                $this->response_text = SMS_Localization::Get('ERROR_LOGIN');
                $this->sendResponse();
                return false;
            }

        }

    }

    function getUserInstanceFromUIIDFrom($allianceTag)
    {
        $common = $this->getCommon();

        $sql = "
                SELECT
                    a.id_alliance
                FROM
                    ds_alliance AS a
                WHERE
                    upper(a.alliancetag) = upper('[".pg_escape_string($allianceTag)."]')
        ";
        $res = $common->getResultArray( "", $sql, "", "", "");
        if ($res === false)
        {
            $this->response_statusPM = -1;
            $this->response_status = 0;
            $this->errorParams = true;
            $this->response_text = 'GetAllianceID Error!';
            $this->sendResponse();
            return false;
        }

        if (sizeof($res)>0)
        {
            $id_alliance = $res[0]['id_alliance'];
        } else {
            $this->response_statusPM = -1;
            $this->response_status = 0;
            $this->errorParams = true;
            $this->response_text = 'Ukazannogo alyansa ne sushestvuet!';
            $this->sendResponse();
            return false;
        }



        $sql = "
                SELECT
                    uha.id_userinstance
                FROM
                    ds_userhasalliance AS uha
                WHERE
                    uha.id_alliance = ".$id_alliance."
                    AND uha.id_userinstance = ".$this->userInstanceFrom."
        ";

        $res = $common->getResultArray("",$sql,"","","");
        if ($res === false)
        {
            $this->response_statusPM = -1;
            $this->response_status = 0;
            $this->errorParams = true;
            $this->response_text = 'GetAllianceParcipiant Error!';
            $this->sendResponse();
            return false;
        }


        if (sizeof($res) > 0)
        {
            //Если юзверь состоит в альяснсе, тэг которого написал

            $sql = "
                    SELECT
                        uha.id_userinstance
                    FROM
                        ds_userhasalliance AS uha
                    WHERE
                        uha.id_alliance = ".$id_alliance."
            ";
            $res = $common->getResultArray( "", $sql, "", "", "");
            if ($res === false)
            {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->errorParams = true;
                $this->response_text = 'GetAllianceParcipiant Error2!';
                $this->sendResponse();
                return false;
            }

            if (sizeof($res) > 0)
            {
                foreach ($res as $value)
                {
                    if ($value['id_userinstance']>0)
                    {
                        $this->messageParcipiants[] = $value['id_userinstance'];
                    }
                }
            } else {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->errorParams = true;
                $this->response_text = 'Wy ne sostoite v alyanse ili v vashem alyanse net drugih uchastnikov.';
                $this->sendResponse();
                return false;
            }

        } else {

            //Если юзверь не состоит в альяснсе, тэг которого написал

            //Ставим что это сообщение имеет статус внеальянсного сообщения
            $this->messageScope = 5;

            $sql = "
                    SELECT
                        uhas.id_userinstance
                    FROM
                        ds_userhasalliancestatus AS uhas
                    WHERE
                        uhas.id_alliance = ".$id_alliance."
                        AND uhas.id_status = 1
            ";

            $res = $common->getResultArray( "", $sql, "", "", "");
            if ($res === false)
            {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->errorParams = true;
                $this->response_text = 'Wy ne sostoite v alyanse ili v vashem alyanse net drugih uchastnikov.';
                $this->sendResponse();
                return false;
            }

            if (sizeof($res) > 0)
            {
                foreach ($res as $value)
                {
                    if ($value['id_userinstance']>0)
                    {
                        $this->messageParcipiants[] = $value['id_userinstance'];
                    }
                }
            } else {
                $this->response_statusPM = -1;
                $this->response_status = 0;
                $this->errorParams = true;
                $this->response_text = 'W etom alyanse net lidera.';
                $this->sendResponse();
                return false;
            }

        }
        return true;
    }

    function sendResponse()
    {
        if($this->offline == true)
        {
            if ($this->errorParams == true)
            {
                $this->result = true;
            } else {
                $this->result = false;
            }

            // Логируем ответ (если смс уже оплачена)
            if ($this->paid == 'YES')
            {
                $this->_logRequestResult('OFFLINE | '.$this->response_text);
            }

            return true;
        } else {

            //var_dump($this->emulator);
            $this->formStat($this->trigger, $this->smsText, $this->emulator,$this->shortcode);

            $this->log .= "time=".date("Y-m-d H:i:s")."; IP=".getenv('REMOTE_ADDR')."; TRIGGER".$this->trigger."; RES: ".$this->resultOk."\n";
            $this->log .= "response_url=".$this->response_url."\n";
            $this->log .= "response_status=".$this->response_status."\n";
            $this->log .= "response_text=".$this->response_text."\n";
            $this->log .= "paid=".$this->paid."\n";
            $this->log .= "ERROR:".$this->error."\n";

            $fp = fopen(constant('LOGING_SMS_PATH').'messages.log','a+');
            fwrite($fp,$this->log);
            fclose($fp);

            if (strlen($this->response_text) > 150)
            {
                $text = substr($this->response_text, 0, 150);
            } else {
                $text = $this->response_text;
            }

            if ($this->response_status === false)
            {
                $str = $text;
            } else {
                $str = $this->response_status.'|'.$text;
            }

            // спец обработка для Rambler Plus
            if($this->partnerCode == 10)
            {
                $str = $this->response_statusPM;

                if (!empty($text)) {
                    $str .= '|'.$text;
                }
            }

            // спец обработка для Plastic Media
            if($this->partnerCode == 11)
            {
                if (key_exists('UNLIMITED', $this->incomingData) || key_exists('LIMITED', $this->incomingData) || key_exists('GERMANY', $this->incomingData)) {
                    $str = $text;
                } else {
                    // Если у нас косяк в обработке, и мы хотим, что бы сообщение пришло еще раз, но по позже
                    if ($this->response_statusPM == -2) {

                        if (constant('VERSION_NAME') != 'DE_TEMP') {
                            $str = iconv("windows-1251", "UTF-8", "Servise Unabible");
                        } else {
                            $str =  "Servise Unabible";
                        }
                    } else {
                        // Все хорошо, шлем ответ
                        header("Ort: OK");
                        header("Content-Type: text/plain; charset=utf-8");

                        //echo "\n";

                        if (constant('VERSION_NAME') != 'DE_TEMP') {
                            $str = iconv("windows-1251", "UTF-8", $text);
                        } else {
                            $str =  $text;
                        }
                    }
                }
            }

            // спец обработка для Литвы. пока нету нормальной системы смсок, делаем как быстрее
            if($this->partnerCode == 2)
            {
                if(!$this->resultOk || !key_exists('LT_'.$this->trigger, $this->priceFunction))
                {
                    $price = '0.00';
                } else {
                    $price = $this->priceFunction['LT_'.$this->trigger];
                }

                $str = "
<Re_Lithuania_1398>
   <tariff>$price</tariff>
   <message>$text</message>
   <pam_id>$this->smsLogOid</pam_id>
</Re_Lithuania_1398>
                ";
            }


            // Посылаем ответ
            echo $str;

            // спец обработка для Plastic Media
            if($this->partnerCode == 11)
            {
                // Что бы не пихать базу сообщение в кодировке
                $str = $text;
            }

            // Логируем ответ (если смс уже оплачена)
            if ($this->paid == 'YES')
            {
                $this->_logRequestResult($str);
            }

        }
    }

    function sendMSG()
    {
        $common = $this->getCommon();

        if ($this->messageText == false)
        {
            return true;
        }
        $error = false;

        $this->setDb($this->dbId);
        $common->setDbId($this->dbId);


        $sql = "select nextval('ds_message_id_message_seq') as mess";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        if (!empty($common->errormsg))
        {
            $this->log .= $common->errormsg.' => '.$sql."\n";
            $error = true;
        }
        if ( $res[0]['mess'] <= 0 )
        {
            $this->sendResponse();
        }
        $messId = $res[0]['mess'];
        $messageTable = array (1=>'ds_message',2=>'ds_messagereserv');
        $messageTableMessages = array(1=>'ds_userhasmessage', 2=>'ds_userhasmessagereserv');
        $i = 1;//mt_rand(1,2);
        $sql = "INSERT INTO ".$messageTable[$i]." (
                    id_message,
                    id_messagescope,
                    id_from_userinstance,
                    subject,
                    messagetext,
                    messagedate
                ) VALUES (
                    ".$messId.",
                    ".$this->messageScope.",
                    ".$this->userInstanceFrom.",
                    '".pg_escape_string($this->messageSubject)."',
                    '".pg_escape_string($this->messageText)."',
                    now() )";

        $res = $common->executeQuery ($sql);
        if (!empty($common->errormsg))
        {
            $this->log .= $common->errormsg.' => '.$sql."\n";
            $error = true;
        }




        foreach ($this->messageParcipiants as $value)
        {
            $sql = sprintf ( "insert into ".$messageTableMessages[$i]."
                            ( id_message, id_userinstance )
                            values ( %d, %d )",
            $messId, $value );
            $res = $common->executeQuery ($sql);
            if (!empty($common->errormsg))
            {
                $this->log .= $common->errormsg.' => '.$sql."\n";
                $error = true;
            }
            //var_dump($sql);
        }

        if ($error == true)
        {
            $this->response_statusPM = -1;
            $this->response_text = 'Proizoshla oshibka pri dostawke soobscheniya poluchatelyu!';
            $this->sendResponse();

        } else {
            $this->response_statusPM = -1;
            $this->response_status = 0;
            $this->response_text = 'Soobschenie dostawleno!';
            $this->errorParams = true;
            $this->sendResponse();

        }

    }

    function sendOnSMS($usernameFrom, $usernameTo)
    {
        $common = $this->getCommon();
        $this->setDb(0);
        $common->setDbId(0);
        $sql = "
                SELECT
                    um.phone,
                    um.mgroup
                FROM
                    ds_user u
                    LEFT JOIN ds_userhasmobile um ON (u.id_user=um.id_user)
                WHERE
                    (upper(u.username) = upper('".pg_escape_string($usernameTo)."'))
        ";

        $res = $common->getResultArray ( "", $sql, "", "", "" );
        if (!empty($common->errormsg))
        {
            $this->log .= $common->errormsg.' => '.$sql."\n";
            $error = true;
        }

        if ( sizeof($res) > 0 )
        {
            $message = 'DS soobschenie ot '.$usernameFrom.': '.$this->messageText;


            $this->getContent->setUrl($this->response_url);
            $this->getContent->setMethod("POST");
            $this->getContent->setPostVar("phone", $res[0]['phone']);
            $this->getContent->setPostVar("shortcode", $this->shortcode);
            $this->getContent->setPostVar("group", urlencode($res[0]['mgroup']));
            $this->getContent->setPostVar("message", $message);
            $this->getContent->Start();
            //$this->log .= $this->getContent->showResponse();
        }
        return true;
    }

    function wordProcess($str)
    {
        $wordMap = array( // Ключ это русские буквы, значение в массиве, это латинские
        'М'=>'M',
        'м'=>'m',
        'Т'=>'T',
        'С'=>'C',
        'с'=>'c',
        'В§'=>'_', // на некоторых мобилках всесто _ приходит черти-что
        'В'=>'B',
        'А'=>'A',
        'а'=>'a',
        'Р'=>'P',
        'р'=>'p',
        'О'=>'O',
        'о'=>'o',
        'К'=>'K',
        'к'=>'k',
        'Е'=>'E',
        'е'=>'e',
        'Н'=>'H',
        'Х'=>'X',
        'х'=>'x',
        '§'=>'_', // на некоторых мобилках всесто _ приходит черти-что
        ''=>'_', // на некоторых мобилках всесто _ приходит черти-что
        );

        foreach ($wordMap as $wordFrom => $wordTo)
        {
            while (ereg($wordFrom, $str))
            {
                //echo "Change ".$wordFrom." on ".$wordTo."\n";
                $str = str_replace($wordFrom, $wordTo, $str);
            }
        }

        return $str;
    }

    function parseBody($body)
    {
        $flag = true;
        $this->smsText = $body;

        while (ereg('  ', $body))
        {
            $body = str_replace('  ', ' ', $body);
        }

        $text = trim($body);

        $text_sms = DSMessage::wordProcess($text);

        foreach ($this->sms_functions as $key => $value)
        {
            if(preg_match("/^".$value."/Usi", $text_sms, $reg))
            {
                if (key_exists('0', $reg))
                {
                    $this->trigger = $value;
                    $len = strlen($reg[0]);
                    $con = substr($text, 0, $len);
                    $text = str_replace($con,'TRIGGER',$text);
                    $this->msgText = $text;
                }
            }
        }

        if($this->trigger == false || !key_exists($this->trigger, $this->priceFunction))
        {
            $this->response_statusPM = -1;
            $this->response_text = SMS_Localization::Get('ERROR_MESAGE');
            $this->error = 'Wrong Trigger';
            $flag = false;
        } else

        //Проверяем ценность СМС. На правильный ли номер была отправленна смс.
        if ($this->basePriceShortCode[$this->shortcode] < $this->priceFunction[$this->trigger])
        {
            $this->response_statusPM = -1;
            $this->response_text = SMS_Localization::Get('ERROR_SHORT_NUMBER');
            $this->error = 'Wrong shot number';
            $flag = false;
        } else


        /* Проверяем можем ли мы обработать такой тип сообщения */
        /* Если в массиве $this->sms_functions в элементе стоит ! то этот тип сообщений обрабатываться не должен!*/
        if (!in_array($this->trigger, $this->sms_functions))
        {
            $this->response_statusPM = -1;
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_MESAGE_2');
            $this->error = 'Disable shot number';
            $flag = false;
        }

        if ($flag == true)
        {
            $array = explode(' ', $text);

            foreach ($this->function_mask[ $this->trigger ] as $key => $value)
            {
                // Проводим типизацию переменных, чтобы избежать подлога переменных типа int подставляя
                //вместо цифр буквы или другие символы
                settype($array[$key], $value);

                // собираем заголовок с данными из пришедшего сообщения
                $this->header[ $key ] = trim( $array[$key] );

                // Удаляем из массива заголовки, для того чтобы исчленить из пришедшего запроса
                unset($array[$key]);

            }
        }
        $this->body = $text;
        return $flag;
    }

    function saveMessageInFile($data)
    {
        if ($this->offline == false)
        {
            $fp = fopen('messages.cvs','a+');
            fwrite($fp,serialize($data)."\n");
            fclose($fp);
            return true;
        } else {
            $this->result = false;
            return false;
        }

    }

    function ParseData($data)
    {
        $flag = true;

        // Проверяем входные данные
        $flag = $this->_checkIncomeParams($data);

        $common = DS_Common::CreateForCurentDB(0);

        // спец обработка для Rambler Plus
        if($this->partnerCode == 10)
        {
            if (empty($this->trans_id)) {
                $this->errorParams = true;
                $this->error = 'Empty transaction';
            } else {

                // Прверяем уникальность транзакции
                $sql = "
                    SELECT
                        *
                    FROM
                        ds_sms_log
                    WHERE
                        trans = ".$this->trans_id."
                        AND
                        id_provider = 10";

                $res = $common->getResultArray("", $sql);
                assert(empty($common->errormsg));

                // Если транзакция не уникальная
                if (count($res) > 0) {
                    $this->smsLogOid = $res[0]['id_sms_log'];
                    $this->error = 'Not unique transaction';

                    // Если ответ на предыдущий запрос с этим id был успешным (в проттивном случае отправляем на доработку)
                    if (!empty($res[0]['result']) && !preg_match('/-2/i', $res[0]['result']))
                    {
                        // Выплевываем ошибку
                        $this->error = 'Not unique transaction';

                        echo "-2";

                        // Логируем
                        $fp = fopen(constant('LOGING_SMS_PATH').'messages.log','a+');
                        fwrite($fp, "ERROR:".$this->error."\n");
                        fclose($fp);

                        exit;
                    }
                }
            }
        }

        // спец обработка для Plastic Media
        if($this->partnerCode == 11)
        {
            if (empty($this->trans_id)) {
                $this->errorParams = true;
                $this->error = 'Empty transaction';
                $flag = false;
            } else {

                // Прверяем уникальность транзакции
                $sql = "
                    SELECT
                        *
                    FROM
                        ds_sms_log
                    WHERE
                        trans = ".$this->trans_id."
                        AND
                        id_provider = 11";

                $res = $common->getResultArray("", $sql);
                assert(empty($common->errormsg));

                // Если транзакция не уникальная
                if (count($res) > 0) {
                    $this->smsLogOid = $res[0]['id_sms_log'];
                    $this->error = 'Not unique transaction';

                    // Если ответ на предыдущий запрос с этим id был успешным
                    if (!empty($res[0]['result']) && !preg_match('/Servise Unabible/i', $res[0]['result'])) {
                        // Повторно высылаем ответ, но уже без обработки сообщения
                        header("Ort: OK");
                        header("Content-Type: text/plain; charset=utf-8");

                        if (constant('VERSION_NAME') != 'DE_TEMP') {
                            $str = iconv("windows-1251", "UTF-8", $res[0]['result']);
                        } else {
                            $str =  $res[0]['result'];
                        }

                        echo $str;

                        // Логируем
                        $fp = fopen(constant('LOGING_SMS_PATH').'messages.log','a+');
                        fwrite($fp, "ERROR:".$this->error."\n");
                        fclose($fp);

                        exit;
                    }
                }
            }
        }

        if($this->emulator)
        {
            $emulator = 't';
        } else {
            $emulator = 'f';
        }

        if($this->testMode)
        {
            $testMode = 't';
        } else {
            $testMode = 'f';
        }

        // логируем запрос в базу (в том случае, если запрос оплачен)
        // Если у нас пока отсучтвует smsLogOid
        if (empty($this->smsLogOid) && $this->paid == 'YES') {
            // Получаем ID следующей записи
            $sql = "select nextval('ds_spy_log_id_spy_log_seq') as id";
            $res = $common->getResultArray ( "", $sql, "", "", "" );
            assert(empty($common->errormsg) && count($res) == 1);
            $id = (int)$res[0]['id'];
            assert($id > 0);

            $this->smsLogOid = $id;

            // Записываем пришедшие данные
            $sql = "
            INSERT INTO ds_sms_log
                (id_sms_log, id_provider, phone, short_code, company, trans, message, emulator, result, request, test_stage)
            VALUES
                (
                    $this->smsLogOid,
                    ".$this->partnerCode.",
                    '".$this->phone."',
                    '".$this->shortcode."',
                    '".$this->group."',
                    ".(float)$this->trans_id.",
                    E'".pg_escape_string($this->smsText)."',
                    '".$emulator."',
                    '',
                    E'".pg_escape_string(print_r($data, true))."',
                    '".$testMode."'
                );
            ";
            $common->executeQuery($sql);
            assert(empty($common->errormsg));
        }

        if($this->_checkUnlimitedVersion($this->incomingData))
        {
            exit();
        }

        if($this->_checkLimitedVersion($this->incomingData))
        {
            exit();
        }

        if($this->_checkGermanyVersion($this->incomingData))
        {
            exit();
        }

        // Возвращаем результат обработки
        return $flag;
    }


    private function _checkIncomeParams($data)
    {
        if (!is_array($data))
        {
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_PARAMS');
            $this->error = 'Empty reques';
            return false;
        }

        $fp = fopen(constant('LOGING_SMS_PATH').'messages.log','a+');
        fwrite($fp, print_r($data, true));
        fclose($fp);

        // проверка не надо ли делать перевод входных данных
        $ip = getenv('REMOTE_ADDR');
        //$ip = '212.109.44.74';
        //$ip = '172.16.13.10'; // Rambler +
        //$ip = '217.199.212.3';

        // Если к нам пришли данные о партнере
        if (key_exists('partner', $data))
        {
            $this->partnerCode = (int)$data['partner'];
        } else {
            $this->partnerCode = 0;
        }

        $originalData = $data;

        if(key_exists($ip, $this->translateParams))
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/ds/gate/classes/'.$this->translateParams[$ip].'.class.php';
            $translator = new $this->translateParams[$ip];
            $data = $translator->Translate($data);

            if($data === false)
            {
                $this->errorParams = true;
                $this->response_text = SMS_Localization::Get('ERROR_PARAMS');
                $this->error = $translator->error;
                return false;
            }

            $this->partnerCode = $translator->getPartnerCode();
            $this->testMode = $translator->getTestMode();
        } /*else {
        $this->errorParams = true;
        $this->error = 'Wrong IP: '.getenv('REMOTE_ADDR');
        return false;
        }*/

        $this->incomingData = $data;

        // Если не получилось растарсить данные
        if (empty($this->incomingData)) {
            $this->errorParams = true;
            $this->error = 'Cant parse data';
            return false;
        }

        if (!key_exists('trans_id', $data))
        {
            $this->trans_id = '';
        } else $this->trans_id = $data['trans_id'];

        if (!key_exists('phone', $data) || strlen($data['phone']) < 5)
        {
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_PARAMS');
            $this->error = 'Empty phone';
            return false;
        } else $this->phone = $data['phone'];

        if (!key_exists('shortcode', $data))
        {
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_PARAMS');
            $this->error = 'Empty shortcode';
            return false;
        } else {
            $this->shortcode = $data['shortcode'];
            $diezPos = strpos($this->shortcode, '#');
            if($diezPos !== false)
            {
                $this->shortcode = substr($this->shortcode, 0, $diezPos);
            }
        }

        if (!key_exists('group', $data))
        {
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_PARAMS');
            $this->error = 'Empty group';
            return false;
        } else $this->group = $data['group'];

        if (!key_exists('body', $data))
        {
            $this->errorParams = true;
            $this->response_text = SMS_Localization::Get('ERROR_PARAMS');
            $this->error = 'Empty body';
            return false;
        } else {
            if (!$this->parseBody($data['body']) )
            {
                return false;
            }
        }

        $this->emulator = false;
        if (key_exists('emulator', $data))
        {
            if ($data['emulator'] == 'TRUE')
            {
                $this->emulator = true;
            }
        }

        if (key_exists('PAID', $data))
        {
            $this->paid = $data['PAID'];
        }

        return true;
    }

    private function _checkUnlimitedVersion(array $data)
    {
        if((!preg_match('/^dsru/i', $this->trigger)
        && !preg_match('/^dsu/i', $this->trigger)
        && !preg_match('/^ukrdsu/i', $this->trigger)
        && !preg_match('/^sdu/i', $this->trigger)
        && !preg_match('/^ppdsu/i', $this->trigger)
        && !preg_match('/^pmddsu/i', $this->trigger)
        && !preg_match('/^5pmdsu/i', $this->trigger)
        && !preg_match('/^1pmdsu/i', $this->trigger)
        && !preg_match('/^pmd5dsu/i', $this->trigger)
        && !preg_match('/^pmd1dsu/i', $this->trigger)
        ) || key_exists('UNLIMITED', $data))
        {
            return false;
        }

        // чтоб покругу не ходило
        $data['UNLIMITED'] = '1';

        // УРЛ новой версии
        $url = "http://www.ds-game.ru/ds/gate/dsmessage.php";

        // Отправляем запрос на адрес
        $this->_sendRequestToAnotherVersuion($data, $url);

        return true;
    }

    private function _checkGermanyVersion(array $data)
    {
        if((!preg_match('/^ppdsg/i', $this->trigger)
        && !preg_match('/^pmddsg/i', $this->trigger)
        ) || key_exists('GERMANY', $data))
        {
            return false;
        }

        // чтоб покругу не ходило
        $data['GERMANY'] = '1';

        // УРЛ немецкой версии
        $url = "http://www.destinysphere.de/ds/gate/dsmessage.php";

        // Отправляем запрос на адрес
        $this->_sendRequestToAnotherVersuion($data, $url);

        return true;
    }

    private function _checkLimitedVersion(array $data)
    {
        if((!preg_match('/^dsl/i', $this->trigger)
        && !preg_match('/^ppdsl/i', $this->trigger)
        && !preg_match('/^pmddsl/i', $this->trigger)
        && !preg_match('/^5pmdsl/i', $this->trigger)
        && !preg_match('/^1pmdsl/i', $this->trigger)
        && !preg_match('/^pmd5dsl/i', $this->trigger)
        && !preg_match('/^pmd1dsl/i', $this->trigger)
        && !preg_match('/^ukrdsl/i', $this->trigger)
        ) || key_exists('LIMITED', $data))
        {
            return false;
        }


        // чтоб покругу не ходило
        $data['LIMITED'] = '1';

        // УРЛ новой версии
        $url = "http://www.ds-game.su/ds/gate/dsmessage.php";

        // Отправляем запрос на адрес
        $this->_sendRequestToAnotherVersuion($data, $url);

        return true;
    }

    private function _sendRequestToAnotherVersuion(array $data, $url)
    {
        assert(!empty($url));

        $data['partner'] = $this->partnerCode;
        $content = http_build_query($data);

        $options = array(
        'method' => 'GET',
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'user_agent' => '',
        'content' => $content,
        'proxy' => '',
        'request_fulluri' => '',
        'max_redirects' => ''
        );

        $opts = array('http' => $options);

        $context  = stream_context_create($opts);

        $result = file_get_contents($url."?".$content, false, $context);

        if(strlen($result) > 0)
        {
            $response_text = $result;

            // спец обработка для Plastic Media
            if($this->partnerCode == 11)
            {
                // Все хорошо, шлем ответ
                header("Ort: OK");
                header("Content-Type: text/plain; charset=utf-8");

                if (constant('VERSION_NAME') != 'DE_TEMP') {
                    $str = iconv("windows-1251", "UTF-8", $response_text);
                } else {
                    $str =  $response_text;
                }
            }
        } else {
            // спец обработка для Rambler Plus
            if($this->partnerCode == 10 || $this->partnerCode == 11)
            {
                $response_text = '-2';
            } else {
                $response_text = '0|'.SMS_Localization::Get('SERVICE_UNAVAILABLE_2');
            }
        }

        // Шлем ответ
        echo $response_text;
        // Логируем ответ (если смс уже оплачена)
        if ($this->paid == 'YES')
        {
            $this->_logRequestResult($result);
        }

        // спец обработка для Plastic Media(чтоб в логе не было кракозябр)
        if($this->partnerCode == 11)
        {
            if (constant('VERSION_NAME') != 'DE_TEMP') {
                $str = iconv("UTF-8", "windows-1251", $response_text);
            } else {
                $str =  $response_text;
            }
        }

        // Записываем ответ в файл
        $this->formStat($this->trigger, $this->smsText, $this->emulator,$this->shortcode);

        $this->log .= "time=".date("Y-m-d H:i:s")."; IP=".getenv('REMOTE_ADDR')."; TRIGGER".$this->trigger."; RES: ".$this->resultOk."\n";
        $this->log .= "response_url=".$this->response_url."\n";
        $this->log .= "response_status=".$this->response_status."\n";
        $this->log .= "response_text=".$response_text."\n";
        $this->log .= "paid=".$this->paid."\n";
        $this->log .= "ERROR:".$this->error."\n";

        $fp = fopen(constant('LOGING_SMS_PATH').'messages.log','a+');
        fwrite($fp,$this->log);
        fclose($fp);
    }


    private function _logRequestResult($res)
    {
        $common = DS_Common::CreateForCurentDB(0);
        $sql = "
                UPDATE
                    ds_sms_log
                SET
                    result = E'".pg_escape_string($res)."'
                WHERE
                    id_sms_log = ".$this->smsLogOid."
                ";
        $common->executeQuery($sql);
    }
}

?>