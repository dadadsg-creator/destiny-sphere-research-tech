<?
class UserManager extends PageManager_MYSQL {
public $CookieLogin = "SimSiteLogin";
public $CookiePassword = "SimSitePassword";
public $User = array();
public $Permissions = array();
public $Error;
public $ErrMsg;

    function UserManager ($usr_id) {
        global $_COOKIE;
        $this->setMysqlConnection();
        $this->ReadDetails($usr_id);
    }
    
    function ReadDetails($usr_id) {
        $ret = false;
        if ($usr_id) {
            $query = "SELECT ID, LastName, FirstName, isSuperAdmin, Active, Login FROM Users WHERE ID=".$usr_id;
            $result = @mysql_db_query(DBName, $query, $this->conn_id);
            if ($result) {
                if ($row = mysql_fetch_row($result)) {
                    $ret = true;
                    $this->User['ID'] = $row[0];
                    $this->User['LastName'] = $row[1];
                    $this->User['FirstName'] = $row[2];
                    $this->User['isSuperAdmin'] = $row[3];
                    $this->User['Active'] = $row[4];
                    $this->User['Login'] = $row[5];
                    //$this->InitPermissions();
                }
            } else {
                $this->Error .= $this->getMysqlError($query);
                $this->ErrMsg = local('ADMIN_DATABASE_ACCESS_ERROR');
            }
            
            $query = "SELECT id_database FROM GameDB WHERE id_user = ".$usr_id;
            $result = @mysql_db_query(DBName, $query, $this->conn_id);
            $this->User['dbAccess'] = array();
            if($result)
            {
                for($i=0; $i<mysql_num_rows($result); $i++)
                {
                    if($row = mysql_fetch_row($result)) 
                    {
                        $this->User['dbAccess'][] = $row[0];
                    }
                }
            }
        }
        return $ret;
    }
    
    function Login($login, $password) {
        $ret = false;
        $query = "SELECT ID FROM Users WHERE Login='".$login."' AND Password='".$password."'";
        $result = @mysql_db_query(DBName, $query, $this->conn_id);
        if ($result) {
            if ($row = mysql_fetch_row($result)) {
                if ($this->ReadDetails($row[0])) {
                    if ($this->User['Active']=='Y') { 
                        $ret = true;
                    } else {
                        $this->Logout();
                        $this->ErrMsg = local('ADMIN_ACCESS_USER_TEMPL_CLOSE');
                    }
                } else {
                    $this->ErrMsg = local('ADMIN_ERROR_READ_PROFILE_INFO');
                }
            } else {
                $this->ErrMsg = local('ADMIN_ERROR_LOGIN_AND_PASS');
            }
        } else {
            $this->Error .= $this->getMysqlError($query);
            $this->ErrMsg = local('ADMIN_DATABASE_ACCESS_ERROR');
        }
        return $ret;
    }
    
    function Logout() {
        $this->User = array();
        $this->Permissions = array();
    }
            
    function CheckUser() {
        $ret = isset($this->User['ID']) && $this->User['ID'] && $this->User['Active']=='Y';
        return $ret;
    }   
    
    function CheckSuperAdmin() {
        $ret = ($this->User['isSuperAdmin']=="Y") ? true : false;
        return $ret;
    }
    
    function isSuperAdmin(){
        return $this->CheckSuperAdmin();
    }

    function GetUserName() {
        return $this->User['LastName']." ".$this->User['FirstName'];
    }

    function GetUserID() {
        if ( $this->User['ID'] ) {
            return $this->User['ID'];
        } else {
            return '0';
        }               
    }
    
    function GetAllowedDB()
    {
        return $this->User['dbAccess'];
    }
}
?>
