<?php $CVS_Version = '$Revision$ ; $Date$';

require_once('./classes/InterfaceBase.class.php');

class InterfaceAllMailru extends InterfaceBase 
{
    protected function _getParticipants($common, $langID, $data)
    {
        
        $startDate = GB_TypeFactory::CreateBase('GB_Date', $data['date_from']);
        $endDate = GB_TypeFactory::CreateBase('GB_Date', $data['date_to']);
        
        $common->executeQuery ( "set STATEMENT_TIMEOUT to 0;");
        assert(empty($common->errormsg));

        $like .= "and (email like '%@mail.ru' or email like '%@inbox.ru' or email like '%@bk.ru' or email like '%@list.ru')";

        $sql = "SELECT 
                    uu.*
                FROM 
                    ds_user as uu
                WHERE
                    (uu.activate_mail = 1)
                    AND
                    (uu.registrationdate >= '".$startDate->getPgFormat()."')
                    AND
                    (uu.registrationdate <= '".$endDate->getPgFormat()."') $like
                    AND uu.id_user not in (SELECT id_user FROM ds_mail_disabled_mails)";
        
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );

        assert(empty($common->errormsg));
        return $res;
    }
    
    protected function _getAdditionalFormPart($data)
    {
        
        ?>
        RegistrationStartPeriod (YYYY-MM-DD):<br> 
        <input type ='text' name = 'date_from' size = '9' maxlength= '100' value=<?=@$data['date_from']?>><br>
        RegistrationEndPeriod (YYYY-MM-DD):<br> 
        <input type ='text' name = 'date_to' size = '9' maxlength= '100' value=<?=@$data['date_to']?>><br>
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
        return "Все игроки, зарегистрированные на Mail.ru";
    }
        
    function getOver()
    {
        return "Рассылка только для Mail.ru";
    }
}

?>