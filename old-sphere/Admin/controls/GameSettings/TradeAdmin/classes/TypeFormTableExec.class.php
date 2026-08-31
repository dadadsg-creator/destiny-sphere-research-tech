<?php


class TypeFormTableExec
{   
    static public function Exec($DBID, $worldID)
    {   
        assert(is_int($worldID) && $worldID > 0);
        assert(is_int($DBID) && $DBID >= 0);

        $table = array
        (
            'UP_TITLE' => array(),
            'LEFT_TITLE' => array(),
            'DATA' => array(),
        );
                
        //--------------- Формирование верхнего заголовка таблицы -------------------------
        $upTitleCategoryes = TypeSearch::GetTableUpRowsCategorys($DBID);
        foreach ($upTitleCategoryes as $k1=>$v1)
        {
            $categoryName = $v1['categoryname'];
            assert(!empty($categoryName));
            
            $categoryID = (int)$v1['id_tradecategory'];
            assert($categoryID > 0);
            
            $tmp = array();
            $typeData = TypeSearch::GetTypesByCategoryName($DBID, $categoryName);
            foreach($typeData as $k2=>$v2)
            {
                $typeName = $v2['name'];
                assert(!empty($typeName));
                
                $typeID = (int)$v2['id'];
                assert($typeID > 0);
                
                $tmp[] = array
                (                   
                    'TYPE_NAME' => $typeName,
                    'TYPE_ID' => $typeID
                );
            }
            
            $table['UP_TITLE'][] = array
            (
                'CATEGORY_NAME' => $categoryName,
                'CATEGORY_ID' => $categoryID,
                'TYPES' => $tmp
            );
        }
        
        //-------------------- Формирование левого заголовка ----------------------------------------
        $leftTitleCategoryes = TypeSearch::GetTableLeftColumnCategorys($DBID);
        foreach ($leftTitleCategoryes as $k1=>$v1)
        {
            $categoryName = $v1['categoryname'];
            assert(!empty($categoryName));
            
            $categoryID = (int)$v1['id_tradecategory'];
            assert($categoryID > 0);
            
            $tmp = array();
            $typeData = TypeSearch::GetTypesByCategoryName($DBID, $categoryName);
            foreach($typeData as $k2=>$v2)
            {
                $typeName = $v2['name'];
                assert(!empty($typeName));
                
                $typeID = (int)$v2['id'];
                assert($typeID > 0);
                
                $tmp[] = array
                (                   
                    'TYPE_NAME' => $typeName,
                    'TYPE_ID' => $typeID
                );
            }
            
            $table['LEFT_TITLE'][] = array
            (
                'CATEGORY_NAME' => $categoryName,
                'CATEGORY_ID' => $categoryID,
                'TYPES' => $tmp
            );
        }
        
        //---------------------------------Формирование данных для таблицы----------------------------------
        foreach ($table['LEFT_TITLE'] as $leftCategoryID=>$leftDataCategory)
        {
            foreach ($leftDataCategory['TYPES'] as $leftTypeID=>$leftTypeData)
            {
                foreach($table['UP_TITLE'] as $upCategoryID=>$upDataCategory)
                {
                    foreach($upDataCategory['TYPES'] as $upTypeID=>$upTypeData)
                    {               
                        $categoryIDLeft = (int)$leftDataCategory['CATEGORY_ID'];
                        assert($categoryIDLeft > 0);
                        
                        $typeIDLeft = (int)$leftTypeData['TYPE_ID'];
                        assert($typeIDLeft > 0);
                        
                        $categoryIDUp = (int)$upDataCategory['CATEGORY_ID'];
                        assert($categoryIDUp > 0);
                        
                        $typeIDUp = (int)$upTypeData['TYPE_ID'];
                        assert($typeIDUp > 0);
                        
                        $table['DATA'][$categoryIDLeft."_".$typeIDLeft][$categoryIDUp."_".$typeIDUp] = 
                        TypeSearch::GetTradeTypeDataByAllID($DBID, $worldID, $categoryIDLeft, $typeIDLeft, $categoryIDUp, $typeIDUp);
                    }                   
                }
            }
        }
        
        return $table;
    }       
}


?>