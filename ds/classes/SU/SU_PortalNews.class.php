<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс импорта новостей с портала в игровую часть
 *
 */
class SU_PortalNews
{
    static public function Get($mode)
    {
        assert($mode == 'top_news' || $mode == 'rss');
        assert(defined('PHP_PATH'));

        $cache = DS_Cache::Singleton();

        $res = $cache->get(CACHING_PREFIX.'portal_news_'.$mode);
        $res = false;
        if(false === $res)
        {
            $str = file_get_contents("http://".$_SERVER['HTTP_HOST']."/news_export.php?".$mode);
            if($mode == 'top_news')
            {
                require_once $_SERVER['DOCUMENT_ROOT'].'/ds/gate/classes/xml2array.class.php';
                $xml = new assoc_array2xml();
                $res = $xml->xml2array($str);
            } else {
                $res = $str;
            }

            $cache->set(CACHING_PREFIX.'portal_news_'.$mode, 900, $res);
        }

        return $res;
    }
}

?>