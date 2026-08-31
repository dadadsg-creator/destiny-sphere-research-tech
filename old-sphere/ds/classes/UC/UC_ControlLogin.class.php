<?php

class UC_ControlLogin implements UC_ControlInterface
{
    public static function Exec(DE_User $user, array $data)
    {
        $res = UC_ExecLogin::execLogin($data);
        if($res !== true)
        {
            $data['MSG'] = $res;
            UC_OutUserCabinet::Out('Error', $data);
            return;
        }
        
        UC_ControlIndex::Exec($user, $data);
    }
}

?>