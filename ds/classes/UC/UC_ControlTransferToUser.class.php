<?php

class UC_ControlTransferToUser implements UC_ControlInterface 
{
    public static function Exec(DE_User $user, array $data)
    {
        $res = true;
        if(UC_ControlUserCabinet::$loginProtection)
        {
            $res = UC_ExecLogin::confirmLogin($data);
        }

        if($res !== true)
        {
            UC_ExecUserCabinetIndex::Exec($user, $res);
        } else {
            UC_ExecMoneyTransfer::ExecTransferToUser($user, $data);
        }
    }
}

?>