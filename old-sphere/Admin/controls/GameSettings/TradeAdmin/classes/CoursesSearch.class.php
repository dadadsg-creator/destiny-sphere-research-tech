<?php

class CoursesSearch
{
    static public function GetCourses($DBID, $worldID)
    {
        $common = DS_Common::CreateForCurentDB($DBID);
        
        $sql = "SELECT
                    r.id_attributename,
                    CASE
                     WHEN
                      r.id_attributename = 5
                        THEN
                          (
                           SELECT
                            1
                            )
                       WHEN
                     r.id_attributename = 2
                        THEN
                          (
                           SELECT
                            2
                            )
                              WHEN
                      r.id_attributename = 1
                        THEN
                          (
                           SELECT
                            3
                            )
                
                                  WHEN
                      r.id_attributename = 4
                        THEN
                          (
                           SELECT
                            4
                            )
                
                                  WHEN
                      r.id_attributename = 3
                        THEN
                          (
                           SELECT
                            5
                            )
                            END AS goods,
                    an.attributename,
                    tc.id_article,
                    tc.id_tradecategory,
                    trcd.add_date,
                    trcd.value,
                     CASE
                     WHEN
                      tc.id_article = 5
                      AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            1
                            )
                       WHEN
                      tc.id_article = 2
                       AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            2
                            )
                              WHEN
                      tc.id_article = 1
                       AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            3
                            )
                
                                  WHEN
                      tc.id_article = 4
                       AND
                      tc.id_tradecategory = 1
                        THEN
                          (
                           SELECT
                            4
                            )
                
                                  WHEN
                      tc.id_article = 3
                       AND
                      tc.id_tradecategory = 1
                
                        THEN
                          (
                           SELECT
                            5
                            )
                 WHEN
                      tc.id_article = 1
                       AND
                      tc.id_tradecategory = 4
                        THEN
                          (
                           SELECT
                            6
                            )
                
                                  WHEN
                      tc.id_article = 2
                       AND
                      tc.id_tradecategory = 4
                        THEN
                          (
                           SELECT
                            7
                            )
                    END AS number
                
                FROM
                    ds_resource AS r
                    INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
                    INNER JOIN ds_trade_course AS tc ON (1 = 1)
                    LEFT JOIN ds_trade_resorce_course_data AS trcd ON
                    (
                        trcd.id_resource = r.id_attributename
                        AND
                        trcd.id_tradecourse = tc.id_tradecourse
                        AND
                        trcd.is_current = true
                        AND
                        trcd.id_world = $worldID
                    )
                WHERE
                    r.trade_resources = true
                     /*AND
                     (trcd.id_tradecourse = 2
                        OR
                        trcd.id_tradecourse = 6
                        OR
                        trcd.id_tradecourse = 7
                        )*/
                ORDER BY goods, number";    
        $res = $common->getResultArray ( "", $sql, "", "", "");     
/*
echo "<xmp>";
print_r($res);      
echo "</xmp>";
exit ;
*/
        assert(empty($common->errormsg) && count($res) > 0);

        return $res;
    }
    
    static public function GetCoursesForNano($DBID, $worldID)
    {
        $common = DS_Common::CreateForCurentDB($DBID);
        
        $sql = "SELECT 


                        
                                       trcd.id_resource,
                                       trcd.value
                                FROM
                                    ds_trade_resorce_course_data AS trcd
                                WHERE
                               
                                    trcd.is_current = true
                                    AND
                                    trcd.id_world = ".$worldID."
                                    AND
                                    trcd.id_tradecourse = 2";   
/*echo "<xmp>";
echo $sql;      
echo "</xmp>";

exit ;  */      
        $res = $common->getResultArray ( "", $sql, "", "", "");     
        assert(empty($common->errormsg) && count($res) > 0);

        return $res;
    }
    
    static public function GetTitle($DBID, $worldID)
    {
        assert(is_int($DBID) && $DBID>=0);      
        assert(is_int($worldID) && $worldID>=0);        
                
        //--------------- Формирование верхнего заголовка таблицы -------------------------
        $upTitleCategoryes = TypeSearch::GetTableUpRowsCategorys($DBID);
        foreach ($upTitleCategoryes as $k1=>$v1)
        {
            $categoryName = $v1['categoryname'];
            assert(!empty($categoryName));
            
            $categoryID = (int)$v1['id_tradecategory'];
            assert($categoryID > 0);
            
            $tmp = array();
            $typeData = self::GetTypesByCategoryName($DBID, $categoryName);
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
            
            $table[] = array
            (
                'CATEGORY_NAME' => $categoryName,
                'CATEGORY_ID' => $categoryID,
                'TYPES' => $tmp
            );
        }
        return $table;
    }
    
    static public function SetCourse($DBID, $worldID, $resourceID, $id_article, $id_tradecategory, $tradeCourse)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert($resourceID>0);
        assert($id_article>0);
        assert($id_tradecategory>0);
        assert(is_float($tradeCourse) && $tradeCourse >= 0);    
        
        $common = DS_Common::CreateForCurentDB($DBID);
        
        $sql = "SELECT insert_resource_trade_course(
                                            $worldID,
                                           $resourceID,
                                           $id_tradecategory,
                                           $id_article,
                                           $tradeCourse)";

        $common->executeQuery($sql);    
        assert(empty($common->errormsg));       
    }
    static public function GetNameByID($DBID, $worldID, $resourceID)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(is_int($worldID) && $worldID>0);
        assert($resourceID>0);
        
        $common = DS_Common::CreateForCurentDB($DBID);
        
        $sql = "SELECT
                     attributename
                FROM ds_attributename
                WHERE
                     id_attributename = $resourceID";

        $res = $common->getResultArray ( "", $sql, "", "", "");     
        assert(empty($common->errormsg) && count($res) > 0);
//print_r($res);
//exit;     
        return $res[0]['attributename'];    
    }
    static public function GetWorldNameByID($dbID, $worldID)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID >= 0);
        
        $common = DS_Common::CreateForCurentDB($dbID);
        
        $sql = "
                SELECT worldname
                FROM ds_world
                WHERE id_world = $worldID
            ";
    
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) == 0)
        {
            return 0;
        }
        
        return $res[0]['worldname'];
    }
    static public function GetNameByArticleAndTradecategory($dbID, $worldID, $id_article, $id_tradecategory)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID >= 0);
        assert($id_article>0);
        assert($id_tradecategory>0);
        
        $common = DS_Common::CreateForCurentDB($dbID);
        switch ($id_tradecategory)
        {
            case '4':
            $sql = "SELECT
                       mon.money_name as name
                     FROM
                       ds_money mon
                     WHERE
                        mon.id_money = $id_article";
            break;
            case '1':
            $sql = "SELECT
                        an.attributename as name
                      FROM
                        ds_attributename an
                     WHERE
                        an.id_attributename = $id_article";
            break;
        }
//echo $sql."<br>";     
        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(count($res) == 0)
        {
            return 0;
        }
        
        return $res[0]['name'];
    }
            static public function GetTypesByCategoryName($DBID, $categoryName)
    {
        assert(is_int($DBID) && $DBID>=0);
        assert(!empty($categoryName));

        $common = DS_Common::CreateForCurentDB($DBID);

        switch($categoryName)
        {
            case 'RESOURCE':
                $sql = "SELECT
                            r.id_attributename AS id,
                            an.attributename AS name,
                             CASE
                         WHEN
                          r.id_attributename = 5
                            THEN
                              (
                               SELECT
                                1
                                )
                           WHEN
                          r.id_attributename = 2
                            THEN
                              (
                               SELECT
                                2
                                )
                                  WHEN
                         r.id_attributename = 1
                            THEN
                              (
                               SELECT
                                3
                                )
                    
                                      WHEN
                          r.id_attributename = 4
                            THEN
                              (
                               SELECT
                                4
                                )
                    
                                      WHEN
                          r.id_attributename = 3
                            THEN
                              (
                               SELECT
                                5
                                )
                     WHEN
                          r.id_attributename = 1
                            THEN
                              (
                               SELECT
                                6
                                )
                    
                                      WHEN
                          r.id_attributename = 2
                            THEN
                              (
                               SELECT
                                7
                                )
                        END AS number

                        FROM
                            ds_resource AS r
                            INNER JOIN ds_attributename AS an ON (an.id_attributename = r.id_attributename)
                        WHERE r.trade_resources = true
                        ORDER BY number";
                break;
            case 'UNIT':
                $sql = "SELECT
                            uc.id_unitclass AS id,
                            uc.classname AS name
                        FROM ds_unitclass AS uc
                        ORDER BY uc.id_unitclass";
                break;
            case 'ARTEFACT':
                $sql = "SELECT
                            e.id_artefactprobability AS id,
                            e.prefix AS name 
                        FROM ds_artefactprobability AS e
                        ORDER BY e.id_artefactprobability";
                break;
            case 'MONEY':
                $sql = "SELECT
                            m.id_money  AS id,
                            m.money_name AS name
                        FROM
                            ds_money AS m
                        ORDER BY m.id_money";
                break;
            default:
                ds_error("Unknown category name = ".$categoryName);
        }
        $res = $common->getResultArray ( "", $sql, "", "", "");
        assert(empty($common->errormsg));

        return $res;
    }
}
?>