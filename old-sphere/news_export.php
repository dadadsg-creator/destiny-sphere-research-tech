<?php

$sapi = php_sapi_name();

if($sapi != "apache")
{
    //$_SERVER['DOCUMENT_ROOT'] = '/usr/local/www/htdocs/portal';
    $_SERVER['DOCUMENT_ROOT'] = '/home/www/';
    $_SERVER['HTTP_HOST'] = 'www.ds-game.ru';
    exec("cd ".$_SERVER['DOCUMENT_ROOT']);
}

define("ERROR_LOG_NAME", "");
define("DB_LOG_NAME", "");
define("LOG_PATH", "");
define("CHAR_SET", "cp1251");


require_once($_SERVER['DOCUMENT_ROOT']."/sys/define.php");
require_once($_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/Logger.class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/Error.class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/Object.class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/MYSQLInterface.class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/DBInterface.class.php");


class PortalNewsExport
{
    private $dbObject;
    
    private $newsTypes = array('news', 'newssphere', 'newsweek');
    
    private $action; // 'top_news', 'rss'
    
    private $outXMLData;
        
    function __construct($data)
    {
        $this->dbObject = new DBInterface(DBHostName, DBUserName, DBPassword, DBName);
        
        $this->dbObject->query("SET character_set_results = ".constant('CHAR_SET'));
        $this->dbObject->query("SET character_set_connection = ".constant('CHAR_SET'));
        $this->dbObject->query("SET character_set_client = ".constant('CHAR_SET'));
        
        $this->_getParams($data);
        
        switch ($this->action) {
            case 'top_news':
                $this->_getTopNews();
                break;
                
            case 'rss':
                $this->_getRss();
                break;  
        
            default:
                die("no action");
                break;
        }
    }
    
    
    private function _getParams($data)
    {
        assert(is_array($data));
        
        if((key_exists('1', $data) && $data[1] == 'top_news') || key_exists('top_news', $data))
        {
            $this->action = 'top_news';
        }
        
        if((key_exists('1', $data) && $data[1] == 'rss') || key_exists('rss', $data))
        {
            $this->action = 'rss';
        }
    }
    
    
    private function _getNews($type, $count, $dateFormat = '%d.%m.%Y')
    {
        assert(is_int($count) && $count > 0);
        
        if(is_array($type))
        {
            $cond = " IN ('".implode("', '", $type)."')";
        } else {
            $cond = " = '$type'";
        }
        
        if($this->action == 'rss')
        {
            $auth = '`dc:creator`';
        } else {
            $auth = 'author';
        }
        
        $sql = "
            SELECT
                nc.alert as title,
                CONCAT(CONCAT(CONCAT('/?p=', placer), '&amp;pubMode=showPub&amp;pubId='), nc.ID) as link,
                nc.Descript as description,
                -- nc.body,
                CONCAT(CONCAT(CONCAT('/?p=', placer), '&amp;pubMode=showPub&amp;pubId='), nc.ID) as guid,
                DATE_FORMAT(nc.NewsDate, '$dateFormat') as pubDate,
                nc.MenuImgAct as image,
                nc.authors as $auth,
                nct.Title as category
                
            FROM
                NewsCompany as nc
                INNER JOIN NewsCategories AS nct ON (nc.PageCategory_ID = nct.Id)
            WHERE
                placer $cond
            ORDER BY nc.ID DESC
            LIMIT $count
        ";
        $res = $this->dbObject->query($sql);
        
        foreach($res as $k => $v)
        {
            foreach($v as $l => $f)
            {
                if(is_int($l))
                {
                    unset($res[$k][$l]);
                }
            }
        }
        
        if($count == 1 && count($res) == 1)
        {
            return $res[0];
        }
         
        return $res;
    }
    
    
    private function _getTopNews()
    {
        $topNews = $this->_getNews('news', 3);

                
        $news['news'] = $topNews[0];
        $news['sphere'] = $topNews[1];
        $news['week'] = $topNews[2];
                
        $this->_arrayToXml($news);
        $s = "<?xml version=\"1.0\" encoding=\"windows-1251\"?>\n\n<top_news>".$this->outXMLData."</top_news>";
        
        die($s);
    }
    
    
    private function _getRss()
    {
        
        $rssHeader = "<?xml version=\"1.0\" encoding=\"windows-1251\"?>
    
            <rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">
                <channel>
                    <title>DestinySphere</title>
                    <link>http://www.destinysphere.ru/</link>
                    <description>Новости DestinySphere</description>
                    <language>ru-ru</language>
                    <pubDate>".date('r')."</pubDate>
                    <lastBuildDate>".date('r')."</lastBuildDate>
                    <generator>DS news RSS export</generator>
            ";
            
        $rssFooter = "</channel></rss>";

        $format = '%a, %d %b %Y %H:%i:%s +0300';
        
        $alterProcTags = array('body' => 'skip', 'image' => 'skip', 'guid__' => 'url', 'dc:creator' => 'html', 'title' => 'html', 'description' => 'html');
        
        $news = $this->_getNews(array('news', 'newssphere', 'newsweek'), 10, $format);
    
        $this->outXMLData = '';
        
        foreach($news as $k => $n)
        {
            $this->outXMLData .= "<item>\n";
            foreach($n as $key => $value)
            {
                if(key_exists($key, $alterProcTags))
                {
                    switch ($alterProcTags[$key]) {
                        case 'skip':
                            continue 2;
                            break;
                    
                        case 'url':
                            $value = urlencode($value);
                            break;  
                            
                        case 'html':
                            $replace = array("\0", "\n", "\r");
                            $value = str_replace($replace, " ", $value);
                            $value = htmlentities($value, ENT_NOQUOTES, "cp1251");
                            break;
                    }
                }
                $this->outXMLData .= "<$key>".$value."</$key>\n";
            }
            $this->outXMLData .= "</item>\n";
        }
        
        die($rssHeader.$this->outXMLData.$rssFooter);
    }
    
    private function _arrayToXml($arr)
    {
        foreach($arr as $key => $value)
        {
            if(is_array($value))
            {
                $this->outXMLData .= "<$key>";
                $this->_arrayToXml($value);
                $this->outXMLData .= "</$key>";
            } else {
                $this->outXMLData .= "<$key>";
                $this->outXMLData .= "<![CDATA[".$value."]]>";      
                //$this->outXMLData .= $value;
                $this->outXMLData .= "</$key>";
            }
        }
    }
}

if($sapi=='apache2handler')
{
    $data = $_REQUEST;
} else {
    $data = $_SERVER['argv'];
}

$newsExport = new PortalNewsExport($data);
?>
