<?php

class TMP_ClearEmptyComplex
{
    /**
     * Enter description here...
     *
     * @var DS_Common
     */
    static private $common;
    
    static private $worldID;
    
    static private $ringNumber;
    
    static private $offset;
    
    static private $complexOnRing;
    
    static private $complexList;
    
    static private $changeList;
    
    static private $cutEdge;
    
    static public function Clear($dbID, $worldID, $ringNumber, $offset = 0)
    {
        assert(is_int($dbID) && $dbID >= 0);
        assert(is_int($worldID) && $worldID > 0);
        assert(is_int($ringNumber) && $ringNumber > 0 && $ringNumber < 5);
        assert(is_int($offset) && $offset >= 0 && $offset < 5000);
        
        self::$worldID = $worldID;
        
        self::$ringNumber = $ringNumber;
        
        self::$offset = $offset;
        
        self::$common = DS_Common::CreateForCurentDB($dbID);
        
        self::_initComplexCountOnRing();
        
        self::_initComplexData();
        
        self::_getComplexForChange();
        
        self::$common->startTrans();
        
        self::$common->executeQuery('SET STATEMENT_TIMEOUT TO 240000;');
        
        self::_changeComplexes();
        
        self::_cutTail();
        
        self::$common->closeTrans('COMMIT');
        
        $countCleared = self::$complexOnRing - self::$cutEdge;
        
        return $countCleared;
    }
    
    static private function _cutTail()
    {
        $sql = "
            DELETE FROM
                ds_combcomplex
            WHERE
                complexnumber > ".self::$cutEdge."
                AND
                id_ring = (
                    SELECT
                        id_ring
                    FROM
                        ds_ring
                    WHERE
                        id_world = ".self::$worldID."
                        AND
                        ringnumber = ".self::$ringNumber."
                )
            ";
        // echo $sql;
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static private function _changeComplexes()
    {
        foreach(self::$changeList as $k => $change)
        {
            self::_change($change['FROM'], $change['TO']);
        }
    }
    
    static private function _change($fromComplexNumber, $toComplexNumber)
    {
        assert(is_int($fromComplexNumber) && $fromComplexNumber > 0);
        assert(is_int($toComplexNumber) && $toComplexNumber > 0);
        
        $sql = "
            DELETE FROM         
                ds_combcomplex
            WHERE
                id_ring = (
                    SELECT
                        id_ring
                    FROM
                        ds_ring
                    WHERE
                        id_world = ".self::$worldID."
                        AND
                        ringnumber = ".self::$ringNumber."
                )
                AND
                complexnumber = $toComplexNumber
            ";
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
        
        $sql = "
            UPDATE
                ds_combcomplex
            SET
                complexnumber = $toComplexNumber
            WHERE
                id_ring = (
                    SELECT
                        id_ring
                    FROM
                        ds_ring
                    WHERE
                        id_world = ".self::$worldID."
                        AND
                        ringnumber = ".self::$ringNumber."
                )
                AND
                complexnumber = $fromComplexNumber
            ";
//echo $sql;
        self::$common->executeQuery($sql);
        assert(empty(self::$common->errormsg));
    }
    
    static private function _getComplexForChange()
    {
        $firstFree = 0;
        
        $changePairs = array();
        
        $usedComplex = array();
        
        $maxUsed = self::$offset;

        while (true)
        {
            foreach (self::$complexList as $complexNumber => $liveCombsCount)
            {
                if($firstFree == 0)
                {
                    if($liveCombsCount == 0 && !in_array($complexNumber, $usedComplex))
                    {
                        $firstFree = $complexNumber;
                    }
                } else {
                    if($liveCombsCount != 0 && !in_array($complexNumber, $usedComplex))
                    {
                        $changePairs[] = array('FROM' => (int)$complexNumber, 'TO' => (int)$firstFree);
                        if(($firstFree > $maxUsed) || ($maxUsed == self::$complexOnRing))
                        {
                            $maxUsed = $firstFree;
                        }
                        self::$complexList[$complexNumber] = 0;
                        $usedComplex[] = $firstFree;
                        $firstFree = 0;
                        continue 2;
                    }
                }
            }
            
            break;
        }
        
        self::$cutEdge = $maxUsed;
        
        self::$changeList = $changePairs;
//print_r($changePairs);
    }
    
    static private function _initComplexCountOnRing()
    {
        $sql = "
            SELECT
                count(*)
            FROM
                ds_combcomplex AS cc
                INNER JOIN ds_ring AS r USING (id_ring)
            WHERE
                r.ringnumber = ".self::$ringNumber."
                AND
                r.id_world = ".self::$worldID."
            ";
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        
        self::$complexOnRing = (int)$res[0]['count'];
    }
    
    static private function _initComplexData()
    {
        $sql = "
            SELECT
                cc.complexnumber,
                (
                    SELECT
                        count(id_comb)
                    FROM
                        ds_comb AS c
                    WHERE
                        NOT c.id_userinstance IS NULL
                        AND
                        c.id_combcomplex = cc.id_combcomplex
                ) as live_count
            FROM
                ds_combcomplex AS cc 
                INNER JOIN ds_ring AS r USING (id_ring)
            WHERE
                r.ringnumber = ".self::$ringNumber."
                AND
                r.id_world = ".self::$worldID."
                AND
                complexnumber > ".self::$offset."
            ORDER BY cc.complexnumber
            ";
        $res = self::$common->getResultArray("", $sql);
        assert(empty(self::$common->errormsg));
        
        $arr = array();
        
        foreach ($res as $k => $cc)
        {
            $arr[(int)$cc['complexnumber']] = (int)$cc['live_count'];
        }
        
        self::$complexList = $arr;
    }
}

?>