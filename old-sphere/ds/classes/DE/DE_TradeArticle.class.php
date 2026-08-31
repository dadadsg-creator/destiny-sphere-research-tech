<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_TradeArticle extends DE_Base
{
    private $tradeCategoryID;
    
    private $typeCategoryID;
    
    private $articleID;

    private $localizedNameID;
    
    /**
     * Вес в стазисе. Мы от этого отошли. Так что стаив м такое значние что бы застукать где он еще используеться
     *
     * @var unknown_type
     */
    private $stazis = -1;
    
    private $internalName;
    
    public function __construct($id, $data)
    {
        assert(is_array($data) && count($data) > 0);        
        assert(!empty($id));

        parent::__construct($id);
        
        assert(key_exists('category_id',$data));
        $this->tradeCategoryID = (int)$data['category_id'];
        assert($this->tradeCategoryID > 0);
        
        assert(key_exists('type_id',$data));
        $this->typeCategoryID = (int)$data['type_id'];
        assert($this->typeCategoryID > 0);
        
        assert(key_exists('article_id',$data));
        $this->articleID = (int)$data['article_id'];
        assert($this->articleID > 0);
        
        assert(key_exists('localizedname_id',$data));
        $this->localizedNameID = (int)$data['localizedname_id'];
        assert($this->localizedNameID > 0);
        
        /*assert(key_exists('stazis',$data));
        $this->stazis = (float)$data['stazis'];
        assert($this->stazis > 0);*/
        
        assert(key_exists('internal_name', $data));
        $this->internalName = trim($data['internal_name']);
    }
    
    public static function CreateFromTradeCategoryIDAndArticleIDForCurrentWorld($categoryID, $articleID)
    {
        assert(is_int($categoryID) && $categoryID > 0);
        assert(is_int($articleID) && $articleID > 0);
        
        $world = DE_World::CreateForCurrentWorld();
        
        $id = $categoryID."_".$articleID."_".$world->getID();
        
        $categoryName = DS_Trade::GetTradeCategoryNameByID($categoryID);
        assert(!empty($categoryName));

        switch($categoryName)
        {
            case 'RESOURCE':
                $data = DS_Trade::GetTradeCategoryResource($categoryID, $articleID, $world);
                break;
            case 'UNIT':
                $data = DS_Trade::GetTradeCategoryUnit($categoryID, $articleID, $world);
                break;
            case 'ARTEFACT':
                $data = DS_Trade::GetTradeCategoryArtefact($categoryID, $articleID, $world);
                break;
            case 'MONEY':
                $data = DS_Trade::GetTradeCategoryMoney($categoryID, $articleID, $world);
                break;
        }
        assert(is_array($data) && count($data) > 0);
                
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data, '');
    }
    
    
    public function getTradeCategoryID()
    {
        return $this->tradeCategoryID;
    }
    
    public function getTypeCategoryID()
    {
        return $this->typeCategoryID;
    }

    public function getLocolizedName()
    {
        $this->_invariant();        
        return DE_Localization::CreateForCurentLang()->get($this->localizedNameID);
    }
    
    public function getArticleID()
    {
        return $this->articleID;
    }
    
    public function getStazis()
    {
        return $this->stazis;
    }
    
    public function getInternalName()
    {
        return $this->internalName;
    }
    
    
    private function _formID($categoryID, $articleID)
    {
        return $categoryID."_".$articleID;
    }
}

?>