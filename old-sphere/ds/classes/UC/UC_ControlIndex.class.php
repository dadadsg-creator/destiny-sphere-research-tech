<?php

class UC_ControlIndex implements UC_ControlInterface 
{
    public static function Exec(DE_User $user, array $data)
    {       
        UC_ExecUserCabinetIndex::Exec($user);
    }
}

?>