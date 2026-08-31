<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecOutInterface
{
    /**
     * Объект Smarty
     *
     * @var unknown_type
     */
    private $tpl = false;
    
    public function __construct($mode)
    {
        $this->tpl = new Smarty();
        $this->tpl->template_dir = "./templates/";
        $this->tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
        $this->tpl->compile_check = true;
        $this->tpl->debugging = false;
    }
    
    public function UserSearch()
    {
        assert($this->tpl); 
        $this->tpl->display("UserSearch.tpl");
    }
    
    public function UserMenu($userName, $userBan, $UIIDs, $combNameList, $worldList)
    {
        assert($this->tpl);
        
        $this->tpl->assign("USER_NAME", $userName);
        $this->tpl->assign("USER_BAN", $userBan);
        $this->tpl->assign("UIID", $UIIDs);
        $this->tpl->assign("COMB_NAMES", $combNameList);
        $this->tpl->assign("WORLD", $worldList);
        $this->tpl->display("UserMenu.tpl");
    }
    
    public function UserTransfer($userName, $world, $worldTo, $storeUserCombs, $id_userdata, $oldCombs, $worldToName)
    {
        assert($this->tpl);
        assert($id_userdata);
        
        $this->tpl->assign("USER_NAME", $userName);
        $this->tpl->assign("USER_WORLD", $world);
        $this->tpl->assign("WORLD_TO_NAME", $worldToName);
        $this->tpl->assign("WORLD_TO", $worldTo);
        $this->tpl->assign("COMB", $storeUserCombs);
        $this->tpl->assign("OLD_COMB", $oldCombs);
        $this->tpl->assign("ID_STORE_USER_DATA", $id_userdata);
        
        $this->tpl->display("UserTransfer.tpl");
    }
}

?>