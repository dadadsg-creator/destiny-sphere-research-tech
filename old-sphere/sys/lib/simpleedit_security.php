<?
class DBEdit {
var $SimpleEdit;

    function DBEdit (&$Table,&$Fields,&$Options,$Permissions) {
        global $UserManager, $session;
        
        if ($Permissionsissions["List"]["Require"] && !$UserManager->CheckPermission($Permissions["List"]["Require"])) {
            $Options[ForbidListTable] = array("ErrMsg"=>$Permissions["List"][ErrMsg]);
            $Options[ForbidEdit] = array("ErrMsg"=>$Permissions["List"][ErrMsg]);
        }
        if ($Permissions[Insert]["Require"] && !$UserManager->CheckPermission($Permissions[Insert]["Require"])) {
            $Options[ForbidInsert] = array("ErrMsg"=>$Permissions[Insert][ErrMsg]);
        } else {
            $Table[Events]["AfterInsert"] = array ();
            actionLog($Permissions["Insert"][LogTitle]);
        }
        
        if ($Permissions[Update]["Require"] && !$UserManager->CheckPermission($Permissions[Update]["Require"])) {
            $Options[ForbidUpdate] = array("ErrMsg"=>$Permissions[Update][ErrMsg]);
            $Table[Actions]["edit"][Caption] = "mehr";
        } else {
            $Table[Events]["AfterUpdate"] = array ();
            
            actionLog($Permissions["Update"][LogTitle]);
        }

        if ($Permissions["Delete"]["Require"] && !$UserManager->CheckPermission($Permissions["Delete"]["Require"])) {
            $Options[ForbidDelete] = array("ErrMsg"=>$Permissions["Delete"][ErrMsg]);
            $Table[Actions]["delete"] = null;
        } else {
            $Table[Events]["AfterDelete"] = array ();
            actionLog($Permissions["Delete"][LogTitle]);
        }

        $this->SimpleEdit = new SimpleEdit($Table, $Fields, $Options);
    }
    
    function ProcessEvent(&$Params) {
        return $this->SimpleEdit->ProcessEvent($Params);
    }

}

?>