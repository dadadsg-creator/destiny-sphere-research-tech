<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Сущность альянса игрока
 */

class DE_Alliance extends DE_Base 
{
    /**
     * Полное Название Альянса
     *
     * @var string
     */
    private $name;
    
    /* Короткое название альянса - [GODS]*/
    /**
     * Короткое название альянса - [GODS]
     *
     * @var string
     */
    private $shortname;

    /**
     * Картинка альянса
     *
     * @var string
     */
    private $image;
    
    /**
     * Пароль на вступление
     *
     * @var string
     */
    private $password;
    
    /**
     * Кол-во игроков в альянсе
     *
     * @var int
     */
    private $membersCount;
    
    function __construct($id)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_integer($id) && $id>0);
        
        parent::__construct($id);
        
        $data = DS_Alliance::getByID($id);      
        assert(is_array($data) && count($data)>0);      
                
        assert(key_exists('id_alliance', $data));
        
        assert(key_exists('alliancename', $data));      
        $this->name = $data['alliancename'];
        
        assert(key_exists('alliancetag', $data));       
        $this->shortname = $data['alliancetag'];
        
        assert(key_exists('image_url', $data));     
        $this->image = $data['image_url'];
        
        assert(key_exists('joinpassword', $data));
        $this->password = $data['joinpassword'];
        
        assert(key_exists('members_amount', $data));
        $this->membersCount = (int)$data['members_amount'];
        
        assert(key_exists('id_global_alliance', $data));        
        $this->idGlobalAlliance = $data['id_global_alliance'];
        
        $this->_invariant('construct');
    }//END OF CONTRUCTOR
    
    /**
     * Enter description here...
     *
     * @param integer $id
     * @return DE_Alliance
     */
    static public function CreateFromID($id)
    {
        assert(is_integer($id) && $id>0);
        
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);     
    }//END OF METHOD
    
    /**
     * Enter description here...
     *
     * @param integer $id
     * @return DE_Alliance
     */
    static public function CreateFromUserInstanceID($id)
    {
        assert(is_integer($id));
        
        $alliance = DS_Alliance::GetByUserInstanceID($id);

        if(!$alliance)
        {
            return DE_AllianceEmpty::CreateFromID($alliance);;
        }else{
            return DE_Alliance::CreateFromID($alliance);
        }//END IF
    }//END OF METHOD
    
    
    public function isEmpty()
    {
        
        return false;
    }//END OF METHOD
    
    public function getName()
    {
        
        return $this->name;
    }//END OF METHOD

    public function getShortName()
    {
        
        return $this->shortname;
    }//END OF METHOD
    
    
    public function getImage()
    {
        
        return $this->image;
    }
    
    public function getPassword()
    {
        
        return $this->password;
    }
    
    public function getMembersCount()
    {
        
        return $this->membersCount;
    }
    
    public function isGlobal()
    {
        if ($this->idGlobalAlliance > 0)
        {
            return true;
        } else {
            return false;
        }
    }

    protected function _invariant($mode = 'public')
    {
        parent::_invariant($mode);
        
        assert(!empty($this->name));
        assert(!empty($this->shortname));
    }//END OF METHOD    

} //END OF CLASS


?>