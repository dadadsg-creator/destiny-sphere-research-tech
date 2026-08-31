<?php $CVS_Version = '$Revision$ ; $Date$';

require_once('./classes/InterfaceBase.class.php');

class InterfaceAllUsers extends InterfaceBase 
{
    protected function _getParticipants($common, $langID, $data)
    {
        
        $startDate = GB_TypeFactory::CreateBase('GB_Date', $data['date_from']);
        $endDate = GB_TypeFactory::CreateBase('GB_Date', $data['date_to']);
        
        $common->executeQuery ( "set STATEMENT_TIMEOUT to 0;");
        assert(empty($common->errormsg));

        $notlike = "and (email not like '%@rambler-co.ru%') ";
        $notlike .= "and (email not like '%@ramblermedia.ru%') and (email not like '%@rambler-media.ru%') ";
        $notlike .= "and (email not like '%@rambler-mobile.ru%') and (email not like '%@smxcom.ru%') ";
        $notlike .= "and (email not like '%@index20.ru%') and (email not like '%@lenta.ru%') ";
        $notlike .= "and (email not like '%territory%') and (email not like '%timezero%') ";
        $notlike .= "and (email not like '%@dwar%') and (email not like '%@arena%') ";
        $notlike .= "and (email not like '%@astrum%') and (email not like 'temach@gmail.com') ";
        $notlike .= "and (email not like 'sergey.kleymenov@rambler.ru') ";
        $notlike .= "and (email not like '%@kanobu.ru') ";
        if ($data['no_gmail'])
            $notlike .= "and (email not like '%@gmail.%') and (email not like '%@googlemail.%') ";
        if ($data['no_mailru'])
            $notlike .= "and (email not like '%@mail.ru') and (email not like '%@inbox.ru') and (email not like '%@bk.ru') and (email not like '%@list.ru') ";
        
        $sql = "SELECT 
                    uu.*
                FROM 
                    ds_user as uu
                WHERE
                    (uu.activate_mail = 1)
                    AND
                    (uu.registrationdate >= '".$startDate->getPgFormat()."')
                    AND
                    (uu.registrationdate <= '".$endDate->getPgFormat()."') $notlike
                    AND uu.id_user not in (SELECT id_user FROM ds_mail_disabled_mails)";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );

        assert(empty($common->errormsg));

        if ($data['payers']!='') {
            $payments = array();
            $ids = array();
            foreach ($res as $row) {
                $ids[] = $row['id_user'];
                if (count($ids) == 1000) {
                    $obj = json_decode(file_get_contents("https://billing.destinysphere.ru/gate/payers.php?&v=".VERSION_NAME."&ids=".implode(',',$ids)));
                    foreach ($obj as $key=>$val) $payments[$key] = $val;
                    $ids = array();
                }
            }
            if (count($ids)) {
                $obj = json_decode(file_get_contents("https://billing.destinysphere.ru/gate/payers.php?&v=".VERSION_NAME."&ids=".implode(',',$ids)));
                foreach ($obj as $key=>$val) $payments[$key] = $val;
            }
            if ($data['payers']=='yes') {
                $realRes = array();
                foreach($res as $row) {
                    if (array_key_exists($row['id_user'], $payments)) {
                        $realRes[] = $row;
                    }
                }
                return $realRes;
            }
            if ($data['payers']=='no') {
                $realRes = array();
                reset($res);
                foreach($res as $row) {
                    if (!isset($payments[$row['id_user']])) {
                        $realRes[] = $row;
                    }
                }
                return $realRes;
            }
        }
        
        return $res;
    }
    
    protected function _getAdditionalFormPart($data)
    {
        
        ?>
        RegistrationStartPeriod (YYYY-MM-DD):<br> 
        <input type ='text' name = 'date_from' size = '9' maxlength= '100' value=<?=@$data['date_from']?>><br>
        RegistrationEndPeriod (YYYY-MM-DD):<br> 
        <input type ='text' name = 'date_to' size = '9' maxlength= '100' value=<?=@$data['date_to']?>><br>
        Exclude Gmail: <input type="checkbox" name="no_gmail"><br>
        Exclude Mail.ru: <input type="checkbox" name="no_mailru"><br>
        <select name="payers">
            <option value="">Все игроки</option>
            <option value="yes">Только платящие</option>
            <option value="no">Только не платящие</option>
        </select><br>
        <?
    }
    
    protected function _testAdditionalFormPart($data)
    {
        if(empty($data['date_from']))
        {
            throw new Exception('Empty field RegistrationStartPeriod');
        }
        
        if(empty($data['date_to']))
        {
            throw new Exception('Empty field RegistrationEndPeriod');
        }
        
        
        if((int)strtotime($data['date_from']) <=0)
        {
            throw new Exception('Uncorrect value of RegistrationStartPeriod');
        }
        
        if((int)strtotime($data['date_to']) <=0)
        {
            throw new Exception('Uncorrect value of RegistrationEndPeriod');
        }
    }
    
    function getName()
    {
        return "Все игроки";
    }
        
    function getOver()
    {
        return "Рассылка для всех игроков у которых дата регистрации находится в укзанном диапозоне.";
    }
}

?>