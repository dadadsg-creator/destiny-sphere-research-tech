<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_PageBase
{
    protected $tpl;
    
    protected $lang;
    
    function __construct()
    {
        $this->tpl = new Smarty();
        $this->tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/cabinet/";
        $this->tpl->compile_dir = constant('SMARTY_CACHE_PATH');
        $this->tpl->compile_check = true;
        $this->tpl->debugging = true;
        $this->tpl->debugging_ctrl = 'URL';
        $this->tpl->assign("prefix", CACHING_PREFIX);
    }
    
    protected function _getLang($page)
    {
        if(isset($_SESSION) && key_exists('lang', $_SESSION))
        {
            $l = $_SESSION['language'];
        } else {
            $l = constant('DEFAULT_LANGUAGE_SHORT');
        }
    $charset = array ('R' => 'windows-1251', 'G' => 'iso-8859-1', 'E' => 'windows-1251');
        header("Content-Type: text/html; charset=".$charset[$l]);       
        
        $lang = array();
        
        if(file_exists($_SERVER['DOCUMENT_ROOT']."/ds/language/".$l."/user_cabinet_".$page.".lang"))
        {
            include_once $_SERVER['DOCUMENT_ROOT']."/ds/language/".$l."/user_cabinet_".$page.".lang";
            $this->lang = $lang;
        } else {
            return false;
        }
        
        $this->tpl->assign("lang", $lang);
        
        return true;
    }
}

?>