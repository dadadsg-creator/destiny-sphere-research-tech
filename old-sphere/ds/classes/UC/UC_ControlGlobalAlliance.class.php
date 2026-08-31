<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_ControlGlobalAlliance implements UC_ControlInterface
{
    private $user;
    
    private $data;
    
    public static function Exec(DE_User $user, array $data)
    {
        $this->user = $user;
        
        $control = UC_LogicGetPageControllerForUser::Get($user);

        switch ($control) 
        {           
            //Если человек не сотоит в глобальном альянсе
            case 'REGISTRATION_PANEL':
                UC_ControlGlobalAllianceRegistration::Exec($user, $data);
                break;
        
            //Если человек состоит в глобальном альянсе и является его лидером
            case 'LEADER_CONTROL_PANEL':
                UC_ControlGlobalAllianceControlPanel::Exec($user, $data);
                break;
                
            //Если человек состоит в глобальном альянсе, но не является его лидером
            case 'MEMBER_PANEL':
                UC_ControlGlobalAllianceMemberPanel::Exec($user, $data);
                break;
                
            case 'ASSISTANT_CONTROL_PANEL':
                
                break;
        }
    }
    
    static private function _checkGlobalAllianceActive()
    {
        $globalAllianceActive = DE_GlobalSettings::Get('GLOBAL_ALLIANCE_ACTIVE');
        
        if ($globalAllianceActive != 1)
        {
            echo "Эта страница находится в разработке";
            exit;
        }
    }
    
}

    
/*class UC_ControlGlobalAllianceControlPanel implements UC_ControlInterface 
{
    public function Exec(DE_User $user, array $data)
    {
        
    }
}*/

?>