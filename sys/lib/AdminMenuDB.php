<?php
class AdminMenuDB
{
    var $db = '';
    
    var $userManager = '';
    
    function AdminMenuDB($userManager)
    {       
        assert (is_a($userManager, 'UserManager'));
        $this->db = new DBInterface ( constant('DB_HOST'), constant('DB_USER'),
                            constant('DB_PASS'), constant('DB_NAME'));

        $this->userManager = $userManager;
    }
    
    function get1LavelMenu()
    {
        if($this->userManager->isSuperAdmin()){
            $sql = "SELECT *
                FROM AdminMenu 
                WHERE 
                    Active = 'Y' AND
                    Parent_ID = 0
                ORDER BY ShowOrder";
        }else{
            $sql = "
                SELECT DISTINCT
                    l1.ID,
                    l1.Title,
                    l1.Description,
                    l1.Url,
                    l1.Active,
                    l1.Parent_ID,
                    l1.ShowOrder
                FROM
                    AdminMenu AS l1 LEFT JOIN AdminMenu AS l2 ON (l1.ID = l2.Parent_ID)
                        LEFT JOIN AdminMenu AS l3 ON (l2.ID = l3.Parent_ID),
                    Users_AdminMenu AS ua
                WHERE 
                    l1.Parent_ID = 0 AND
                    (l2.ID = ua.Menu_ID OR l3.ID = ua.Menu_ID) AND
                    ua.User_ID = '".$this->userManager->GetUserID()."' AND
                    l1.Active = 'Y'
                ORDER BY l1.ShowOrder";
        }       
        //echo $sql;
        return $this->db->query($sql);
    }
    
    function get2LavelMenu($id)
    {
        assert(!empty($id));
                
        if($this->userManager->isSuperAdmin()){
            $sql = "SELECT *
                FROM AdminMenu 
                WHERE 
                    Active = 'Y' AND
                    Parent_ID = $id
                ORDER BY ShowOrder";
        }else{
            $sql = "
                SELECT DISTINCT
                    l2.ID,
                    l2.Title,
                    l2.Description,
                    l2.Url,
                    l2.Active,
                    l2.Parent_ID,
                    l2.ShowOrder
                FROM
                    AdminMenu AS l1 LEFT JOIN AdminMenu AS l2 ON (l1.ID = l2.Parent_ID)
                        LEFT JOIN AdminMenu AS l3 ON (l2.ID = l3.Parent_ID),
                    Users_AdminMenu AS ua
                WHERE 
                    l2.Parent_ID = $id AND
                    (l2.ID = ua.Menu_ID OR l3.ID = ua.Menu_ID) AND
                    ua.User_ID = ".$this->userManager->GetUserID()." AND
                    l2.Active = 'Y'
                ORDER BY l2.ShowOrder";
        }       
        return $this->db->query($sql);
    }
    
    function get3LavelMenu($id)
    {
        assert(!empty($id));
        if($this->userManager->isSuperAdmin()){
            $sql = "SELECT *
                FROM AdminMenu 
                WHERE 
                    Active = 'Y' AND
                    Parent_ID = $id
                ORDER BY ShowOrder";
        }else{
            $sql = "
                SELECT DISTINCT
                    l3.ID,
                    l3.Title,
                    l3.Description,
                    l3.Url,
                    l3.Active,
                    l3.Parent_ID,
                    l3.ShowOrder
                FROM
                    AdminMenu AS l1 LEFT JOIN AdminMenu AS l2 ON (l1.ID = l2.Parent_ID)
                        LEFT JOIN AdminMenu AS l3 ON (l2.ID = l3.Parent_ID),
                    Users_AdminMenu AS ua
                WHERE 
                    l3.Parent_ID = $id AND
                    (l2.ID = ua.Menu_ID OR l3.ID = ua.Menu_ID) AND
                    ua.User_ID = ".$this->userManager->GetUserID()." AND
                    l3.Active = 'Y'
                ORDER BY l3.ShowOrder";
        }       
        return  $this->db->query($sql);
    }
    
    function getFullNameByID($id){
//        assert($id > 0);
        $sql = '
        SELECT DISTINCT
            l1.Title AS l1,
            l2.Title AS l2,
            l3.Title AS l3
        FROM
            AdminMenu AS l1 LEFT JOIN AdminMenu AS l2 ON (l1.ID = l2.Parent_ID)
                LEFT JOIN AdminMenu AS l3 ON (l2.ID = l3.Parent_ID)
        WHERE 
            l1.Parent_ID = 0 AND 
            (l1.ID = '.$id.' OR l2.ID = '.$id.' OR l3.ID = '.$id.')';
        return  $this->db->query($sql);
    }
}


?>