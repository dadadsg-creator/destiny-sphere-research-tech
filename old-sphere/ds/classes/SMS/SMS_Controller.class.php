<?php
class SMS_Controller
{
    private $servises = array(
    0       =>  'PREMIUM',
    1       =>  'ARMY',
    2       =>  'AINF',
    3       =>  'ARCH',
    4       =>  'ART',
    5       =>  'CREDIT',
    6       =>  'CREDIT500'
    );

    private $action = 'PREMIUM';
    private $world;
    private $smsText;
    private $user;
    private $number;
    private $coast;
    private $viewSms = 'NO';
    private $time;
    private $groupSize;
    private $comb;
    private $picture;
    private $mark;
    private $withPicture;
    private $model;
    private $country;

    function __construct(array $data)
    {
        $this->_getParams($data);

        // создаем объект смарти
        $tpl = new Smarty;
        $tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
        $tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $tpl->compile_check = true;
        $tpl->debugging = false;

        // Локализация
        $language = constant("DEFAULT_LANGUAGE_SHORT");
        require_once("language/".$language."/sms_message.lang");
        $tpl->assign('lang', $lang);

        //Получаем список стран
        $countries = SMS_DataDase::GetCountries();
        $tpl->assign('countries', $countries);
        
        $tpl->assign('servises', $this->servises);
        if ($this->action == 'CHOOSE_COUNTRY')
        {
            $country = SMS_DataDase::GetUserCountry($this->user);
            //Смотрим, была ли у игрока запись
            if (!empty($country))
            {
                //Обновляем сведения о стране игрока в базе
                SMS_DataDase::UpdateUserCountry($this->user->GetId(), $this->country);
            } else {
                //Вставляем сведения о стране игрока в базу
                SMS_DataDase::SetUserCountry($this->user->GetId(), $this->country);

            }
            $this->action = 'PREMIUM';
        } else {
            //Смотрим, есть ли запись о стране игрока в базе(и активна ли сейчас эта страна)
            $country = SMS_DataDase::GetUserCountry($this->user, true);

            if (!empty($country))
            {
                $this->country = $country;
            } else {
                $tpl->assign('ACTION', 'CHOOSE_COUNTRY');
                $tpl->assign('MSG', 'CHOOSE_COUNTRY');
                $tpl->display("sms.tpl");
                return;
            }
        }

        // Если у нас выбрана страна
        if (!empty($this->country))
        {
            $this->prefix = 'DS';
            $tpl->assign('SHOW_SMS_PORTAL', 'yes');

            //Выбираем префикс смски на основе страны и версии игры
            switch ($this->country)
            {
                // Россия
                case 1:
                    break;
                // Украина
                case 2:
                    // Заменяем название сервиса по покупке кредитов
                    $this->prefix = 'UKRDS';
                    $tpl->assign('servises', $this->servises);
                    break;
                // Казахстан
                case 3:
                    break;
                // Беларусь
                case 4:
                    // Делаем ссылку на портал активной (для уточнения цен в рублях)
                    $tpl->assign('SHOW_SMS_PORTAL', 'yes');
                    break;

                // Германия
                case 5:
                    $this->prefix = 'PPDS';         
                    // Если это немецкая версия, то переопределяем количество кредитов, которые можно купить
                    if (constant('GAME_HOST') == 'http://destinysphere.de') {
                        $this->servises[5] = 'CREDIT3';
                        $this->servises[6] = 'CREDIT2';
                        $this->servises[7] = 'CREDIT1';
                        $tpl->assign('servises', $this->servises);
                    }
                    // Делаем ссылку на портал НЕ активной (цены указываются сразу в национальной валюте)
                    $tpl->assign('SHOW_SMS_PORTAL', 'no');
                    break;

                // Эстония
                case 6:
                    $this->prefix = 'PMDDS';
                    // Делаем ссылку на портал НЕ активной (цены указываются сразу в национальной валюте)
                    $tpl->assign('SHOW_SMS_PORTAL', 'no');
                    break;

                // Латвия
                case 7:
                // Литва
                case 8:
                    $this->prefix = '5PMDS';                    
                    // Если это дешевая смс, то триггер другой
                    if ($this->action == 'ARMY' || $this->action == 'AINF' || $this->action == 'ARCH' || $this->action == 'CREDIT500') {
                        $this->prefix = '1PMDS';
                    }
                    // Делаем ссылку на портал НЕ активной (цены указываются сразу в национальной валюте)
                    $tpl->assign('SHOW_SMS_PORTAL', 'no');
                    break;
                // Израиль
                case 9:
                    $this->prefix = 'PMD5DS';
                    // Если это дешевая смс, то триггер другой
                    if ($this->action == 'ARMY' || $this->action == 'AINF' || $this->action == 'ARCH' || $this->action == 'CREDIT500') {
                        $this->prefix = 'PMD1DS';
                    }
                    // Делаем ссылку на портал НЕ активной (цены указываются сразу в национальной валюте)
                    $tpl->assign('SHOW_SMS_PORTAL', 'no');
                    break;
                    
                // Австрия
                case 10:
                    $this->prefix = 'PMDDS';
                    
                    // Переопределяем количество кредитов, которые можно купить
                    $this->servises[5] = 'CREDIT1';
                    unset($this->servises[6]);
                    $tpl->assign('servises', $this->servises);
                    
                    // Делаем ссылку на портал НЕ активной (цены указываются сразу в национальной валюте)
                    $tpl->assign('SHOW_SMS_PORTAL', 'no');
                    break;
                // Швейцария
                case 11:
                    $this->prefix = 'PMDDS';
                    // Переопределяем количество кредитов, которые можно купить
                    $this->servises[5] = 'CREDIT2';
                    unset($this->servises[6]);
                    $tpl->assign('servises', $this->servises);
                
                    // Делаем ссылку на портал НЕ активной (цены указываются сразу в национальной валюте)
                    $tpl->assign('SHOW_SMS_PORTAL', 'no');
                    break;
                    
                default:
                    echo "Wrong Country";
                    exit;
            }

            // Если эта версия анлимитед, добавляем к префиксу 'U'
            if (constant('GAME_HOST') == 'http://ds-game.ru') {
                $this->prefix .= 'U';
            }
            else if (constant('GAME_HOST') == 'http://ds-game.su') {
                $this->prefix .= 'L';
            }
            // Если эта версия DE, добавляем к префиксу 'G'
            if (constant('GAME_HOST') == 'http://destinysphere.de') {
                $this->prefix .= 'G';
            }
        }

        $tpl->assign('selectedCountry', $this->country);

        // Миры, где игрок зареган
        $worlds = SMS_DataDase::GetUserWorlds($this->user);

        switch ($this->action)
        {
            case 'PREMIUM':
                $this->smsText = "$this->prefix PREMIUM ".$this->user->getName();
                self::GetNumberAndCoastBySmsType(1, $lang);
                $tpl->assign('SHOW_PICTURES', 'NO');
                break;

            case 'ARMY':
                if ($this->viewSms == 'Yes')
                {
                    $this->smsText = "$this->prefix ARMY ".$this->user->getName()." <i><font color=red>".$lang['PASSWORD']."*</font></i> $this->world";
                    self::GetNumberAndCoastBySmsType(2, $lang);
                }
                break;

            case 'AINF':
                if ($this->viewSms == 'Yes')
                {
                    $this->smsText = "$this->prefix AINF ".$this->user->getName()." <i><font color=red>".$lang['PASSWORD']."*</font></i> $this->world";
                    self::GetNumberAndCoastBySmsType(3, $lang);
                }
                break;

            case 'ARCH':
                $tpl->assign('time', array(     
                0 => 1,
                1 => 2,
                2 => 3,
                3 => 5,
                4 => 8,
                5 => 12));

                $tpl->assign('comb', array(     'H' => $lang['HOME'],
                'C' => $lang['CENTER'],
                'N' => $lang['NEUTRAL']));

                if ($this->viewSms == 'Yes')
                {
                    if (empty($this->groupSize))
                    {
                        $tpl->assign('MSG', 'CHOOSE_GROUP_SIZE');
                    } elseif (!is_numeric($this->groupSize))
                    {
                        $tpl->assign('MSG', 'WRONG_GROUP_SIZE');
                    }
                    else {
                        $this->smsText = "$this->prefix ARCH $this->comb ".$this->user->getName()." <i><font color=red>".$lang['PASSWORD']."*</font></i> $this->world $this->groupSize $this->time";
                        self::GetNumberAndCoastBySmsType(2, $lang);

                        $tpl->assign('selectedGroupSize', $this->groupSize);
                        $tpl->assign('selectedTime', $this->time);
                        $tpl->assign('selectedComb', $this->comb);
                    }
                }
                break;

            case 'ART':
                $k = 0;
                $combs = '';
                foreach ($worlds as $key => $worldData)
                {
                    $common = DS_Common::CreateForCurentDB();
                    $dbId = $common->getDBIDByName($worldData['dbname']);
                    $combsByWorld = SMS_DataDase::GetUserCombsByWorld($this->user, $dbId, $worldData['id_world']);

                    $comma = '';

                    foreach ($combsByWorld as $key => $data)
                    {
                        $combs[$k] .= $comma.$data['ringnumber'].".".$data['complexnumber'].".".$data['combnumber'];
                        $comma = ',';
                    }

                    $userWorlds[$k]['worldName'] = $worldData['worldname'];
                    $userWorlds[$k]['world_identifier'] = $worldData['world_identifier'];
                    $k ++;
                }

                $tpl->assign('userWorlds', $userWorlds);
                $tpl->assign('combs', $combs);
                $tpl->assign('SELECTED_COMB', 0);

                if ($this->viewSms == 'Yes' && !empty($this->comb))
                {
                    $this->smsText = "$this->prefix ART ".$this->user->getName()." <i><font color=red>".$lang['PASSWORD']."*</font></i> $this->comb $this->world";
                    self::GetNumberAndCoastBySmsType(1, $lang);
                    $tpl->assign('SELECTED_COMB', $this->comb);
                }
                break;

            case 'CREDIT':
                $summ = 3000;
                $tpl->assign('SUM', $summ);
                $tpl->assign('CREDITS', 'SILVER_CREDIT');
                $this->smsText = "$this->prefix CREDIT ".$this->user->getName();
                self::GetNumberAndCoastBySmsType(1, $lang);

                break;

            case 'CREDIT500':
                $summ = 500;
                $tpl->assign('SUM', $summ);
                $tpl->assign('CREDITS', 'SILVER_CREDIT');
                $this->smsText = "$this->prefix 500 ".$this->user->getName();
                self::GetNumberAndCoastBySmsType(2, $lang);
                break;

            case 'CREDIT2500':
                $summ = 2500;
                $tpl->assign('SUM', $summ);
                $tpl->assign('CREDITS', 'SILVER_CREDIT');
                $this->smsText = "$this->prefix CREDIT ".$this->user->getName();
                self::GetNumberAndCoastBySmsType(1, $lang);
                break;

            case 'CREDIT3':
                $summ = 3;
                $tpl->assign('SUM', $summ);
                $tpl->assign('CREDITS', 'GOLD_CREDIT');
                $this->smsText = "$this->prefix CREDIT3 ".$this->user->getName();
                self::GetNumberAndCoastBySmsType(1, $lang);
                break;
                
            case 'CREDIT1':
                $summ = 1;
                $tpl->assign('SUM', $summ);
                $tpl->assign('CREDITS', 'GOLD_CREDIT');
                $this->smsText = "$this->prefix CREDIT1 ".$this->user->getName();
                self::GetNumberAndCoastBySmsType(5, $lang);
                break;
                
            case 'CREDIT2':
                $summ = 2;
                $tpl->assign('SUM', $summ);
                $tpl->assign('CREDITS', 'GOLD_CREDIT');
                $this->smsText = "$this->prefix CREDIT2 ".$this->user->getName();
                self::GetNumberAndCoastBySmsType(4, $lang);
                break;

            

            default:
                $this->smsText = '';
                break;

        }

        $tpl->assign('smsText', $this->smsText);

        $tpl->assign('worlds', $worlds);
        $tpl->assign('ACTION', $this->action);

        /*if (!empty($this->coast) && !empty($this->number))
        {*/
        $tpl->assign('COAST', $this->coast);
        $tpl->assign('number', $this->number);
        /*} else {
        $tpl->assign('ACTION', 'SERVICE_NOT_AVAILABLE');
        }*/

        $tpl->assign('selected_world', $this->world);
        $tpl->assign('GAME_HOST', constant('GAME_HOST'));

        $tpl->display("sms.tpl");
    }

    private function GetNumberAndCoastBySmsType($typeNumber, $lang)
    {
        switch ($typeNumber)
        {
            case 1:
                switch ($this->country)
                {
                    //Россия
                    case 1:
                        $this->number = '1005';
                        $this->coast = '3 '.$lang['DOLLARS'];
                        break;

                        //Украина
                    case 2:
                        $this->number = '2404';
                        $this->coast = '25 '.$lang['HRYVNAS'];
                        break;

                        //Казахстан
                    case 3:
                        $this->number = '7504';
                        $this->coast = '3 '.$lang['DOLLARS'];
                        break;

                        //Беларусь
                        /*case 4:
                        $this->number = '3335';
                        $this->coast = ' 2,34 '.$lang['DOLLARS'];
                        break;*/

                        //Германия
                    case 5:
                        $this->number = '81130';
                        $this->coast = '4,99 '.$lang['EURO'];;
                        break;

                        //Эстония
                    case 6:
                        $this->number = '13015';
                        $this->coast = '35 EEK';
                        break;

                        //Латвия
                    case 7:
                        $this->number = '1897';
                        $this->coast = '2,58 LVL';
                        break;

                        //Литва
                    case 8:
                        $this->number = '1337';
                        $this->coast = '10,01 LTL';
                        break;

                        //Израиль
                    case 9:
                        $this->number = '4070';
                        $this->coast = '20 шекелей';
                        break;
                        
                        //Австрия
                    case 10:
                        $this->number = '0900506506';
                        $this->coast = '2 '.$lang['EURO'];;
                        break;
                        
                        //Швейцария
                    case 11:
                        $this->number = '9292';
                        $this->coast = '3,60 CHF';
                        break;
                }
                break;

            case 2:
                switch ($this->country)
                {
                    //Россия
                    case 1:
                        $this->number = '7099';
                        $this->coast = '1 '.$lang['DOLLAR'];
                        break;

                        //Украина
                    case 2:
                        $this->number = '294';
                        $this->coast = '7 '.$lang['HRYVNAS'];
                        break;

                        //Казахстан
                    case 3:
                        $this->number = '7502';
                        $this->coast = '1 '.$lang['DOLLAR'];
                        break;

                        /*//Беларусь
                        case 4:
                        $this->number = '3355';
                        $this->coast = '89 '.$lang['CENTS'];
                        break;*/

                        //Германия
                    case 5:
                        $this->number = '83094';
                        $this->coast = '0,99 '.$lang['EURO'];;
                        break;

                        //Эстония
                    case 6:
                        $this->number = '13015';
                        $this->coast = '35 EEK';
                        break;

                        //Латвия
                    case 7:
                        $this->number = '1897';
                        $this->coast = '0.77 LVL';
                        break;

                        //Литва
                    case 8:
                        $this->number = '1337';
                        $this->coast = '3.03 LTL';
                        break;

                        //Израиль
                    case 9:
                        $this->number = '4070';
                        $this->coast = '5 шекелей';
                        break;
                        
                        //Австрия
                    case 10:
                        $this->number = '0900506506';
                        $this->coast = '2 '.$lang['EURO'];;
                        break;
                        
                        //Швейцария
                    case 11:
                        $this->number = '9292';
                        $this->coast = '3,60 CHF';
                        break;
                }
                break;

            case 3:
                switch ($this->country)
                {
                    //Россия
                    case 1:
                        $this->number = '7099';
                        $this->coast = '1 '.$lang['DOLLAR'];
                        break;

                        //Украина
                    case 2:
                        $this->number = '7099';
                        $this->coast = '1 '.$lang['DOLLAR'];
                        break;

                        //Казахстан
                    case 3:
                        $this->number = '7502';
                        $this->coast = '1 '.$lang['DOLLAR'];
                        break;

                        //Беларусь
                        /*case 4:
                        $this->number = '3355';
                        $this->coast = '89 '.$lang['CENTS'];
                        break;*/

                        //Германия
                    case 5:
                        $this->number = '83094';
                        $this->coast = '0,99 '.$lang['EURO'];;
                        break;

                        //Эстония
                    case 6:
                        $this->number = '13015';
                        $this->coast = '35 EEK';
                        break;

                        //Латвия
                    case 7:
                        $this->number = '1897';
                        $this->coast = '0.77 LVL';
                        break;

                        //Литва
                    case 8:
                        $this->number = '1337';
                        $this->coast = '3.03 LTL';
                        break;

                        //Израиль
                    case 9:
                        $this->number = '4070';
                        $this->coast = '5 шекелей';
                        break;
                    //Австрия
                    case 10:
                        $this->number = '0900506506';
                        $this->coast = '2 '.$lang['EURO'];;
                        break;
                        
                        //Швейцария
                    case 11:
                        $this->number = '9292';
                        $this->coast = '3,60 CHF';
                        break;
                }
                break;
                
            case 4:
                $this->number = '86669';
                $this->coast = '2,99 '.$lang['EURO'];
                break;
            case 5:
                $this->number = '83090';
                $this->coast = '1,99 '.$lang['EURO'];
                break;
        }
    }

    private function _getParams(array $data)
    {
        if(key_exists('action', $data))
        {
            $this->action = $data['action'];
        } else {
            $this->action = 'PREMIUM';
        }

        if(key_exists('country', $data))
        {
            $this->country = $data['country'];
        }

        if(key_exists('world', $data))
        {
            $this->world = $data['world'];
        } elseif (key_exists('dbId' , $_SESSION) && key_exists('world' , $_SESSION) && $_SESSION['world'] > 0) {
            //Если мы пришли из игры получаем мир
            assert($_SESSION['dbId'] >= 0);
            $this->world = SMS_DataDase::GetWorldIdentifierById($_SESSION['dbId'], $_SESSION['world']);
        }
        
        if(key_exists('viewSms', $data))
        {
            $this->viewSms = $data['viewSms'];
        } else {
            $this->viewSms = 'No';
        }

        if(key_exists('time', $data))
        {
            $this->time = $data['time'];
        }

        if(key_exists('comb', $data))
        {
            $this->comb = $data['comb'];
        }

        if(key_exists('groupSize', $data))
        {
            $this->groupSize = $data['groupSize'];
        }

        if(key_exists('picture', $data))
        {
            $this->picture = $data['picture'];
        }

        if(key_exists('mark', $data))
        {
            $this->mark = $data['mark'];
        }

        if(key_exists('model', $data))
        {
            $this->model = $data['model'];
        }

        if(key_exists('WITHOUT_PICTURE', $data))
        {
            $this->withPicture = 'NO';
        }

        if(key_exists('WITH_PICTURE', $data))
        {
            $this->withPicture = 'YES';
        }

        assert(key_exists('uid', $_SESSION));
        $this->user = DE_User::CreateFromID((int) $_SESSION['uid']);
    }
}

?>