<?php $CVS_Version = '$Revision: 1.4 $ ; $Date: 2008/02/14 07:54:48 $';

/**
 * $Author: defo $
 * $Date: 2008/02/14 07:54:48 $
 * $Revision: 1.4 $
 * 
 * Класс гейта для смены логина 
 *
 */
class BG_ChangeLogin extends BG_Common 
{
    private $oldLogin;
    
    public function __construct($data)
    {
        parent::__construct($data);     
    }
    
    /**
     * Смена логина
     *
     */
    public function proceed()
    {       
        if($this->error_status != 0)
        {
            return;
        }
        
        if( !key_exists('login', $this->incoming_info) ||
            !key_exists('id_user', $this->incoming_info) ) 
        {
            $this->error_status = 1;
            $this->outputAnswer(); 
            return;
        }
        
        $common = DS_Common::CreateForCurentDB(0);
        
        if(!VD_UserName::Validate($this->incoming_info['login'], true, $common))
        {
            $this->error_status = 2;
            $this->outputAnswer(); 
            return;
        }
        
        if(!is_numeric($this->incoming_info['id_user']) ||
            (int)$this->incoming_info['id_user'] <= 0)
        {
            $this->error_status = 3;
            $this->outputAnswer(); 
            return;
        }
        
        $userData = DS_User::GetByID($this->incoming_info['id_user']);
        if($userData === false)
        {
            $this->error_status = 6;
            $this->outputAnswer(); 
            return;
        }
        
        $this->oldLogin = $userData['username'];
        
        if($this->updateLogin())
        {
            $this->checkIfUpdated();
            
            $this->updateForumLogin();
        }
                
        $this->outputAnswer(); 
    }
    
    private function updateLogin()
    {
        $common = DS_Common::CreateForCurentDB();

        $sql = "UPDATE
                    ds_user
                SET
                    username = '".pg_escape_string($this->incoming_info['login'])."'
                WHERE
                    id_user = ".$this->incoming_info['id_user'];
        
        $res = $common->executeQuery ($sql);
        
        if(!empty($common->errormsg))
        {
            $this->error_status = 4;
            return false;
        }
        
        return true;
    }
    
    private function updateForumLogin()
    {       
        $common = DS_Common::CreateForCurentDB();
        if(!$common->connect('forum'))
        {
            $this->addToResult('forum_change_success', 0);
            $common->errormsg = '';
            return false;
        }
        
        $sql = "
                UPDATE
                    ".constant("FORUM_PREFIX")."_users
                SET
                    username = '".pg_escape_string($this->incoming_info['login'])."'
                WHERE
                    username = '".$this->oldLogin."'
            ";
        $common->executeQuery($sql);
        
        if(!empty($common->errormsg))
        {
            $this->addToResult('forum_change_success', 0);
            return false;
        }
        
        $this->addToResult('forum_change_success', 1);
        
        return true;
    }
    
    private function checkIfUpdated()
    {
        $common = DS_Common::CreateForCurentDB();
        
        $sql = "
            SELECT
                count(*) as count
            FROM
                ds_user
            WHERE
                id_user = ".$this->incoming_info['id_user']."
                AND 
                username = '".pg_escape_string($this->incoming_info['login'])."'
            ";
        
        $res = $common->getResultArray("", $sql, "", "", "NO_CACHE");   
        if(!empty($common->errormsg) || !$res)
        {
            $this->error_status = 5;
            return false;
        }
        
        $this->addToResult('change_success', $res[0]['count']); 
        
        return true;
    }
}
?>