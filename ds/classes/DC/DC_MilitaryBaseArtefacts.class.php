<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * коллекция артефактов в военно йбазе. Создаеться на основание ID здания
 */
class DC_MilitaryBaseArtefacts extends DC_Base
{   
    
    function __construct($id)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        $collection = $id % 2 + 1;
        $id = $id >> 1;
        
        parent::__construct($id, 'DE_Artefact');
        
        //Временная затыка на случай если нет военной БД на сатакуемой соте
        if($id != 111111111)
        {
            $data = DS_Artefact::GetArtefactsByBuildAttachedID($id, $collection);
        
            
            if(is_array($data))
            {
                foreach($data as $k=>$v)
                {
                    $this->_addNewElement(DE_Artefact::CreateFromArtefactInstanceID((int)$v['id_artefact']));
                }
            }
        }               

        $this->_invariant('construct');
    }

    static public function CreateFromBuildID($id, $collection=1)
    {
        $id = ($id << 1) + ($collection-1);
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);             
    }

    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        parent::_invariant($mode);          
    }
    
    public function commit()
    {
        $this->_invariant();
        parent::commit();           
    }
    
    function __destruct()
    {
        $this->_invariant();
        parent::__destruct();
        //$this->commit();
    }


}


?>