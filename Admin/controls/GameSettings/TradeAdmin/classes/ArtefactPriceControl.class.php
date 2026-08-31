<?php

class ArtefactPriceControl
{

    static private $out = '';

    static private $action = 'art_search';

    static private $worldID;

    static private $dbID;

    static private $scope = -1;

    static private $size = -1;

    static private $artefactID;

    static private $arts;
    
    static private $columnCourse;

    static private $csvFile = '';

    static private $oldData = array();

    static private $title = array();

    static public function Exec($data)
    {
        assert(is_array($data));

        self::_getParams($data);

        self::_execAction();

        return self::$out;
    }

    static private function _getParams($data)
    {
        if(key_exists('action', $data))
        {
            self::$action = $data['action'];
        }

        if(key_exists('world', $data))
        {
            $arr = explode('_', $data['world']);
            self::$dbID = (int)$arr[0];
            assert(self::$dbID >= 0);

            self::$worldID = (int)$arr[1];
            assert(self::$worldID > 0);
        }

        if(key_exists('scope', $data))
        {
            self::$scope = $data['scope'];
            assert(self::$scope > 0 || self::$scope == 'all');
        }

        if(key_exists('size', $data))
        {
            self::$size = $data['size'];
            assert(self::$size > 0 || self::$size == 'all');
        }

        if(key_exists('artefact', $data))
        {
            self::$artefactID = (int) $data['artefact'];
            assert(self::$artefactID > 0);
        }

        if(key_exists('art', $data))
        {
            self::$arts = $data['art'];
            assert(is_array(self::$arts));
        }

        if(key_exists('course', $data))
        {
            self::$columnCourse = $data['course'];
            assert(is_array(self::$columnCourse));
        }

        if(key_exists('csv', $_FILES) && key_exists('tmp_name', $_FILES['csv']))
        {
            self::$csvFile = $_FILES['csv']['tmp_name'];
            assert(file_exists(self::$csvFile));
        }
    }

    static private function _execAction()
    {
        switch (self::$action) {
            case 'art_search':
                self::_showArtSearchMenu();
                break;

            case 'edit':
                self::_editArtPrices();
                //              break;
            case 'art_list':
                self::_showArtList();
                break;

            case 'history':
                self::_showArtHistory();
                break;
                
            case 'edit_column':
                self::_editColumn();
                //self::_showArtSearchMenu();
                self::_showArtList();
                break;

            case 'export_csv':
                self::_showArtSearchMenu();
                self::_exportCSV();
                break;
                
            case 'import_csv':
                self::_showArtSearchMenu();
                self::_importCSV();
                break;

            default:
                break;
        }
    }

    static private function _showArtSearchMenu()
    {

        $worlds = TypeSearch::GetAllWorldsList();

        $scopes = ArtefactPriceSearch::getArtefactScopes(0);

        $sizes = ArtefactPriceSearch::getArtefactSizes(0);

        $filters['scope'] = $scopes;
        $filters['size'] = $sizes;

        if (empty(self::$dbID) AND empty(self::$worldID))
        {
            $selectedWorld = '';
        }else
        {
            $selectedWorld = self::$dbID.'_'.self::$worldID;
        }

        $selected = array('scope' => self::$scope, 'size' => self::$size, 'world' => $selectedWorld);

        self::$out = ArtefactPriceOut::outArtSearch($worlds, $filters, $selected);
    }

    static private function _showArtList()
    {
        $artefacts = ArtefactPriceSearch::getArtefactsByScopeAndSize(self::$scope, self::$size, self::$dbID, self::$worldID);
        $lottery = ArtefactPriceSearch::getLotteryArtefactsByScopeAndSize(self::$scope, self::$size, self::$dbID, self::$worldID);

        $title = ArtefactPriceSearch::getTitle(self::$dbID, self::$worldID);

        $rithTitle = ArtefactPriceSearch::getRithTitle(self::$dbID, self::$worldID, self::$scope, self::$size);

        self::_showArtSearchMenu();

        self::$out .= ArtefactPriceOut::outArtefactList($title, $rithTitle, $artefacts, $lottery, self::$dbID.'_'.self::$worldID, self::$scope, self::$size);
    }

    static private function _showArtHistory()
    {
        $history = ArtefactPriceSearch::getArtefactCourseHistoryByID(self::$artefactID, self::$dbID, self::$worldID);

        $title = ArtefactPriceSearch::getArtefactCourseHistoryTitleByID(self::$artefactID, self::$dbID, self::$worldID);

        self::$out = ArtefactPriceOut::outHistory($title, $history);
    }

    static private function _editArtPrices()
    {
        $log = '';

        foreach(self::$arts as $id => $art)
        {
            if (key_exists('course', $art))
            {
                $tempArr = explode("/", $id);
                $id_artefact = $tempArr[0];
                $id_tradecategory = $tempArr[1];
                $id_article = $tempArr[2];

                settype($id_artefact, 'integer');
                settype($id_article, 'integer');
                settype($id_tradecategory, 'integer');
                assert($id_artefact >= 0);
                assert($id_article > 0);
                assert($id_tradecategory > 0);

                if($art['course'] == self::_getOldCourse($id_artefact, $id_tradecategory, $id_article))
                {
                    continue;
                }

                if ($art['course'] == NULL)
                {
                    continue;
                }
                $artName = ArtefactPriceSearch::GetArtefactNameByID(self::$dbID, $id_artefact);
                $categoryBy = ArtefactPriceSearch::GetNameByArticleAndTradecategory(self::$dbID, self::$worldID, $id_tradecategory, $id_article);

                $log .= "Set $artName/$categoryBy course=".$art['course']."<br>";

                ArtefactPriceSearch::updateArtefactCourse($id_artefact, $id_tradecategory, $id_article, (float)$art['course'], self::$dbID, self::$worldID);
            }
        }
        foreach ($_REQUEST['prob'] as $artID=>$prob) {
            ArtefactPriceSearch::updateLotteryArtefact($artID, $prob, isset($_REQUEST['active'][$artID]), self::$dbID);
        }

        //Логируем действие
        if ($log != '')
        {
            $worldName = ArtefactPriceSearch::GetWorldNameByID(self::$dbID, self::$worldID);
            actionLog( "Edit artefact price in world '$worldName'", $log);
        }

    }
    
    static private function _editColumn()
    {
        assert(count(self::$columnCourse) == 1);

        foreach (self::$columnCourse as $id => $course)
        {
            $tempArr = explode("_", $id);
            $id_tradecategory = $tempArr[0];
            $id_article = $tempArr[1];

            settype($id_article, 'integer');
            settype($id_tradecategory, 'integer');
            
            assert($id_article > 0);
            assert($id_tradecategory > 0);
            
            $arts = ArtefactPriceSearch::getRithTitle(self::$dbID, self::$worldID, self::$scope, self::$size);
    
            foreach ($arts as $key=> $artData)
            {
                $id_artefact = (int)$artData[id_artefact];
                
                if($course == self::_getOldCourse($id_artefact, $id_tradecategory, $id_article))
                {
                    continue;
                }

                if ($course == NULL)
                {
                    continue;
                }
                $artName = ArtefactPriceSearch::GetArtefactNameByID(self::$dbID, $id_artefact);
                $categoryBy = ArtefactPriceSearch::GetNameByArticleAndTradecategory(self::$dbID, self::$worldID, $id_tradecategory, $id_article);

                $log .= "Set $artName/$categoryBy course=".$course."<br>";

                ArtefactPriceSearch::updateArtefactCourse($id_artefact, $id_tradecategory, $id_article, (float)$course, self::$dbID, self::$worldID);
            }
                //Логируем действие
        if ($log != '')
        {
            $worldName = ArtefactPriceSearch::GetWorldNameByID(self::$dbID, self::$worldID);
            actionLog( "Edit artefact price in world '$worldName'", $log);
        }
            
        }       
    }

    static private function _getOldCourse($id_artefact, $id_tradecategory, $id_article)
    {
        assert($id_artefact > 0);
        assert(is_int($id_article) && $id_article > 0);
        assert(is_int($id_tradecategory) && $id_tradecategory > 0);

        if(count(self::$oldData) == 0)
        {
            self::$oldData = ArtefactPriceSearch::getArtefactsByScopeAndSize(self::$scope, self::$size, self::$dbID, self::$worldID);
        }

        foreach(self::$oldData as $k => $course)
        {
            if($course['id_artefact'] == $id_artefact AND $course['id_article'] == $id_article AND $course['id_tradecategory'] == $id_tradecategory)
            {
                return $course['course'];
            }
        }

        return 0;
    }

    static private function _importCSV()
    {
        $artefacts = ArtefactPriceSearch::getArtefactsByScopeAndSize(self::$scope, self::$size, self::$dbID, self::$worldID);
        $title = ArtefactPriceSearch::getTitle(self::$dbID, self::$worldID);
        $rithTitle = ArtefactPriceSearch::getRithTitle(self::$dbID, self::$worldID, self::$scope, self::$size);

        $file = $_FILES['csv_file']['tmp_name'];

        if (!$fp = fopen($file, 'r'))
        {
            echo "<h2><font color=red>Не могу открыть файл ($file)</font></h2>";
            self::$out .= ArtefactPriceOut::outArtefactList($title, $rithTitle, $artefacts, self::$dbID.'_'.self::$worldID, self::$scope, self::$size);
            return;
        }
        
        while (($data = fgetcsv($fp, 1000, ";")) !== FALSE) 
        {       
            $newArtefactPrice[] = $data;
        }

        fclose($fp);
        
        $keyValue = 1;
        $key = 0;
        
        foreach ($rithTitle as $keyUnit => $artList)
        {
            $resKey = 1;
            
            foreach($title as $k1=>$v1)
            {
                
                foreach($v1['TYPES'] as $k2=>$v2)
                {                   
                    $value= (float)str_replace(',', '.', $newArtefactPrice[$keyValue][$resKey]);
                    if ($artList['name'] ==  $artefacts[$key]['name'] AND $artefacts[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
                    {
                        $artefacts[$key]['course'] = $value;
                        $key ++;
                        $resKey ++;
                    } else {
                        $artefacts[$key]['id'] = $artList['id_artefact'];
                        $artefacts[$key]['name'] = $newArtefactPrice[$keyValue][0];
                        $artefacts[$key]['course'] = $value;
                        $artefacts[$key]['id_tradecategory'] = $v1['CATEGORY_ID'];
                        $artefacts[$key]['id_article'] = $v1['TYPE_ID'];
                        $artefacts[$key]['trade_artyicle_name'] = $v2['TYPE_NAME'];
                        $artefacts[$key]['add_date'] = date('Y-m-d H:i:s');
                        $resKey ++;
                    }
                }
            }           
            $keyValue ++;
        }
        
        self::$out .= ArtefactPriceOut::outArtefactList($title, $rithTitle, $artefacts, self::$dbID.'_'.self::$worldID, self::$scope, self::$size);
    }
    
    static private function _exportCSV()
    {
        $artefacts = ArtefactPriceSearch::getArtefactsByScopeAndSize(self::$scope, self::$size, self::$dbID, self::$worldID);
        $title = ArtefactPriceSearch::getTitle(self::$dbID, self::$worldID);
        $rithTitle = ArtefactPriceSearch::getRithTitle(self::$dbID, self::$worldID, self::$scope, self::$size);

        $file = $_SERVER['DOCUMENT_ROOT']."/ds/utils/files/ArtefactPrace.csv";

        if (!$fp = fopen($file, 'w'))
        {
            echo "<h2><font color=red>Не могу открыть файл ($file)</font></h2>";
            self::$out .= ArtefactPriceOut::outArtefactList($title, $rithTitle, $artefacts, self::$dbID.'_'.self::$worldID, self::$scope, self::$size);
            return;

        }
        
        //Записываем в файл имена полей
        $fildName = array('Artefact Name', 'PRODIUM', 'NANO', 'KADERIUM', 'FOOD ', 'STASIS', 'SILVER', 'GOLD');
        fputcsv($fp, $fildName, ";");
        
        $key = 0;
        $currace = '';
        
        foreach ($rithTitle as $keyUnit => $artList)
        {
            //Записываем имя арта
            $line = '';
            $line[] = $artList['name'];

            foreach($title as $k1=>$v1)
            {
                foreach($v1['TYPES'] as $k2=>$v2)
                {
                    if ($artList['name'] ==  $artefacts[$key]['name'] AND $artefacts[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
                    {
                        $line[] =  number_format($artefacts[$key]['course'], 5, ',', " ");

                        //$line[] = str_replace('.', ',', $artefacts[$key]['course']);
                        $key ++;
                    }
                    else
                    {
                        $out .= '<td bgcolor="#EEE9E9" align="center"><input style="text-align: right;" type=text name="art['.$artList['id_artefact'].'/'.$v1['CATEGORY_ID'].'/'.$v2['TYPE_ID'].'][course]" size=11 ></td>';
                    }
                }
            }

            //Записываем строчку в файл
            fputcsv($fp, $line, ";");
        }

        fclose($fp);
        
        self::$out .= ArtefactPriceOut::outArtefactList($title, $rithTitle, $artefacts, self::$dbID.'_'.self::$worldID, self::$scope, self::$size);
    }
}

?>