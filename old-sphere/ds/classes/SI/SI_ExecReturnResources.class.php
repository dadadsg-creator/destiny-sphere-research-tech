<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecReturnResources extends SI_Base 
{
    static public function Exec($combToID, $categoryID, $articleID, $amount)
    {   
        settype($combToID, 'integer');
        if ($combToID <= 0) return;
        // assert($combToID > 0);
        
        settype($categoryID, 'integer');
        assert($categoryID > 0);
        
        settype($articleID, 'integer');
        assert($articleID > 0);
        
        settype($amount, 'float');
        assert($amount >= 0);
        
        
        $categoryname = SI_SearchReturnResources::GetCategoryNameByID($categoryID);     
        
        self::_out("Start return reosurces categoryname = $categoryname | combToID = $combToID | categoryID = $categoryID | articleID = $articleID | amount = $amount");
        
        switch($categoryname)
        {
            case "RESOURCE":                                                            
                SI_SearchReturnResources::DeltaCombCurrentAmount($combToID, $articleID, $amount);
                                
                break;
            case "ARTEFACT":                                
                $artefactInstanceID = SI_SearchComb::CreateArtInstance($articleID);
                assert($artefactInstanceID > 0);
                
                SI_SearchComb::InsertArtInToArchStore($combToID, $artefactInstanceID);
                
                break;
            case "UNIT":
                                
                $unitGroupData = SI_SearchReturnResources::HaveUnitsInHomeArmy($combToID, $articleID);

                if((int)$unitGroupData['id_unitcontainerhasunit'] == 0)
                {                   
                    
                    $groupData = array
                    (
                        'id_unit' => $articleID,
                        'amount' => $amount
                    );      
                    
                    SI_SearchComb::InsertNewUnitsGroup($unitGroupData['id_unitcontainer'], $groupData); 
                }else{
                                                                        
                    SI_SearchReturnResources::DeltaUnitsInUnitGroup($unitGroupData['id_unitcontainerhasunit'], $amount);
                }
                
                break;
        }           
    }
}

?>