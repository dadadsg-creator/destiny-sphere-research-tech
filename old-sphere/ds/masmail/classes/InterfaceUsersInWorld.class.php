<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2007/06/07 08:45:56 $';

require_once('./classes/InterfaceBase.class.php');

class InterfaceUsersInWorld extends InterfaceBase 
{
    
    protected function _getParticipants($common, $langID, $data)
    {
        assert((int)$langID > 0);

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

        $sql = "SELECT *
                FROM
                    ds_user AS u                    
                    INNER JOIN ds_userinstancecollection AS uic ON (uic.id_user = u.id_user)
                    INNER JOIN ds_worldcollection AS wc ON (wc.id_world = uic.id_world AND wc.dbname = uic.dbname)
                WHERE
                    u.activate_mail = 1
                    and wc.world_identifier = '".$data['world_identifier']."'
                    and uic.id_user > 0 $notlike
                    AND u.id_user not in (SELECT id_user FROM ds_mail_disabled_mails)";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        
        return $res;
    }

    protected function _getAdditionalFormPart($data)
    {
        $common = DS_Common::CreateForCurentDB(0);
        
        $sql = "SELECT * FROM ds_worldcollection ORDER BY worldname";
        $res = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
        assert(empty($common->errormsg));
        
        ?>
        WorldToSend:<br>
        <select name="world_identifier">
        <?
        foreach($res as $k => $v)
        {
            ?><option value="<?=$v['world_identifier']?>"><?=$v['worldname']?></option><?
        }           
        ?></select><br>
        Exclude Gmail: <input type="checkbox" name="no_gmail"><br>
        Exclude Mail.ru: <input type="checkbox" name="no_mailru"><br>
        <?       
    }
    
    protected function _testAdditionalFormPart($data)
    {
        if(empty($data['world_identifier']))
        {
            throw new Exception('Empty field WorldToSend');
        }               
    }
    
    function getName()
    {
        return "Рассылка пользователям в одном мире";
    }
        
    function getOver()
    {
        return "Здесь можно разослать письмо пользователям из одного мира на выбор.";
    }
}


?>