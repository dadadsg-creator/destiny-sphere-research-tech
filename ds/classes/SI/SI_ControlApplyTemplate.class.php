<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2009/10/28 11:21:58 $';

/**
 * ћега затыка.
 * –аспаковка шаблона соты на засел€емую соту.
 *
 */
class SI_ControlApplyTemplate
{
    static private $common;
    
    static public function Apply($userName, $templateName, $dbId, $uid, $combName, $id_world, $id_race, $id_comb = null)
    {
        assert(is_string($templateName));
        assert(is_string($userName) && strlen($userName));
        assert(is_int($uid) && $uid > 0);
        assert(is_string($combName));
        assert(is_int($id_world) && $id_world > 0);
        assert(is_int($id_race) && $id_race > 0);
        assert(is_int($dbId) && $dbId >= 0);
        
        $userData = file_get_contents($_SERVER['DOCUMENT_ROOT']."/ds/UI_templates/".$templateName);
        $userInstance = unserialize($userData);
        $userInstance->setUserID($uid);
        $userInstance->setUserName($userName);
        
        $combs = $userInstance->getComb();
        $combCount = (int) count($combs);
        // пока в шаблоне может быть только одна сота
        assert($combCount == 1);
    
        $oldName = key($combs);
        
        $_SESSION['dbId'] = $dbId;
        self::$common = DS_Common::CreateForCurentDB($dbId);
        SI_SearchComb::SetCommon(self::$common);        
        
        $combNewName = array($oldName => $combName);
        $userInstance->setNewCombNames($combNewName);
        if(is_null($id_comb))
        {
            $combId = GL_GetCombForPlacement::Get($id_race, $id_world);
        } else {
            $combId = $id_comb;
        }
        $combData = SI_SearchComb::GetCombDataByID($combId);
        $combCoords[$combName]['RING'] = $combData['ringnumber'];
        $combCoords[$combName]['COMPLEX'] = $combData['complexnumber'];
        $combCoords[$combName]['COMB'] = $combData['combnumber'];

        SI_ControlRestoreUserInstance::SetCommon(self::$common);
        self::$common->executeQuery('begin');
        $newUIID = SI_ControlRestoreUserInstance::Restore($id_world, $combCoords, $userInstance, true, false);
        self::$common->executeQuery('COMMIT');
        return $newUIID;
    }
}
?>