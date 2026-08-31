<?
class FineUserSearch
{
    static public function GetUserArmy(DS_Common $common, $username, $worldId)
    {
        assert(!empty($username));
        assert(is_int($worldId) && $worldId >0);
                
        $sql = "SELECT
                u.username,
                ui.id_userinstance,
                ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                co.id_comb,
                cn.combname,
                uc.id_unitcontainer,
                uc.unitcontainername,
                ltl.text as unitname,
                uu.amount::real,
                uu.id_army,
                uu.id_unitcontainerhasunit
            
            FROM
                ds_user u
                LEFT JOIN ds_userinstance ui ON ( u.id_user = ui.id_user )
                LEFT JOIN ds_comb co ON ( ui.id_userinstance = co.id_userinstance )
                LEFT JOIN ds_combname cn ON ( co.id_comb = cn.id_comb )
                LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                LEFT JOIN ds_unitcontainer uc ON ( co.id_comb = uc.id_basecomb )
                LEFT JOIN ds_unitorcontainertype uct ON ( uc.id_unitcontainertype = uct.id_unitcontainertype )
                LEFT JOIN ds_unitcontainerhasunit uu ON ( uc.id_unitcontainer = uu.id_army )
                LEFT JOIN ds_unit un ON ( uu.id_unit = un.id_unit )
                LEFT JOIN ds_localizedtext lot ON ( un.id_localizedname = lot.id_localizedtext )
                LEFT JOIN ds_localizedtextlanguage ltl ON ( lot.id_localizedtext = ltl.id_localizedtext AND ltl.id_language = '".constant( 'DEFAULT_LANG_ID')."')
            
            WHERE
                uct.containertypename = 'ARMY'
                AND u.username = '".$username."'
                AND ui.id_world = '".$worldId."'
            
            ORDER BY
                ri.ringnumber,
                cc.complexnumber,
                co.combnumber,
                uc.unitcontainername,
                unitname";

        $res = $common->getResultArray("", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }

    static public function GetUserResources(DS_Common $common, $username, $worldId)
    {
        assert(!empty($username));
        assert(is_int($worldId) && $worldId >0);
                
        $sql = "SELECT
                  u.username,
                  ui.id_userinstance,
                  ( ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber ) as coords,
                  co.id_comb,
                  cn.combname,
                  chre.id_combhasresource,
                  attrname.attributename,
                  chre.value
                FROM
                     ds_user u
                     LEFT JOIN ds_userinstance ui ON ( u.id_user = ui.id_user )
                     LEFT JOIN ds_comb co ON ( ui.id_userinstance = co.id_userinstance )
                     LEFT JOIN ds_combname cn ON ( co.id_comb = cn.id_comb )
                     LEFT JOIN ds_combcomplex cc ON ( co.id_combcomplex = cc.id_combcomplex )
                     LEFT JOIN ds_ring ri ON ( cc.id_ring = ri.id_ring )
                     LEFT JOIN ds_combhasresource chre ON ( co.id_comb = chre.id_comb )
                     LEFT JOIN ds_attribute attr ON ( chre.id_attribute = attr.id_attribute )
                     LEFT JOIN ds_attributetype attrtype ON ( attr.id_attributetype = attrtype.id_attributetype)
                     LEFT JOIN ds_attributename attrname ON ( attr.id_attributename = attrname.id_attributename)
                WHERE
                      attrtype.typename = 'CURRENT_AMOUNT'
                      AND attrname.attributename IN ('FOOD', 'KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'STASIS_ENERGY')
                      AND u.username = '".$username."'
                      AND ui.id_world = ".$worldId."
                ORDER BY
                    ri.ringnumber,
                    cc.complexnumber,
                    co.combnumber,
                    attrname.id_attributename";

        $res = $common->getResultArray("", $sql, "", "", "" );
        assert(empty($common->errormsg));

        return $res;
    }
    
    static public function getIdUserByName(DS_Common $common, $userName)
    {
        assert(!empty($userName));
        
        $sql = "SELECT
                                u.id_user
                            FROM
                                ds_user u
                            WHERE
                                u.username = '".$userName."'";
        
        $res = $common->getResultArray("", $sql, "", "", "" );
        assert(empty($common->errormsg));
        if(!$res || (sizeof($res) === 0))
        {
            return false;
        } 
        
        return (int)$res[0]['id_user'];
    }
    
    static public function getLocalizedUnitName(DS_Common $common, $id_unitcontainerhasunit, $id_unit)
    {   
        if($id_unitcontainerhasunit > 0)
        {
            $sql = sprintf("SELECT
                                ltl.text
                            FROM
                                ds_unitcontainerhasunit uchu,
                                ds_unit u,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl
                            WHERE
                                uchu.id_unitcontainerhasunit = %d
                                AND uchu.id_unit = u.id_unit
                                AND u.id_localizedname = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d",
                            $id_unitcontainerhasunit,
                            constant( 'DEFAULT_LANG_ID' ));
        }
        else 
        {
            assert($id_unit > 0);           
            $sql = sprintf("SELECT
                                ltl.text
                            FROM                                
                                ds_unit u,
                                ds_localizedtext lt,
                                ds_localizedtextlanguage ltl
                            WHERE                               
                                u.id_unit = %d
                                AND u.id_localizedname = lt.id_localizedtext
                                AND lt.id_localizedtext = ltl.id_localizedtext
                                AND ltl.id_language = %d",
                            $id_unit,
                            constant( 'DEFAULT_LANG_ID' ));
        }
                            
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));

        if (!$res || sizeof($res) === 0)
        {
            echo $sql;
            return false;
        } else {
            return $res[0]['text'];
        }
    }
    
    static public function cancelTransferUnit(DS_Common $common, $userInstanceId)
    {
        assert($userInstanceId > 0);
        
        // Получаем все ивенты трансфера юнитов
        $sql = "UPDATE 
                    ds_eventqueue
                SET 
                    eventstarttime = now()
                WHERE
                    id_eventqueue  IN (
                            SELECT 
                                eq.id_eventqueue from ds_eventqueue eq
                            LEFT JOIN ds_event e ON (e.id_event = eq.id_event)
                            LEFT JOIN ds_eventparticipant ep ON (eq.id_eventqueue = ep.id_eventqueue)
                            INNER JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                            LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
                            WHERE
                                ep.id_userinstance = ".$userInstanceId."
                                 AND
                                eventname = 'TRANSFER_RESOURCE'
                                 AND
                                eo.optionname = 'ID_Category'
                                 AND
                                eho.value = 2
                            )";

        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function cancelTransferResources(DS_Common $common, $userInstanceId)
    {
        assert($userInstanceId > 0);
        
        // Получаем все ивенты трансфера юнитов
        $sql = "UPDATE 
                    ds_eventqueue
                SET 
                    eventstarttime = now()
                WHERE
                    id_eventqueue  IN (
                            SELECT 
                                eq.id_eventqueue from ds_eventqueue eq
                            LEFT JOIN ds_event e ON (e.id_event = eq.id_event)
                            LEFT JOIN ds_eventparticipant ep ON (eq.id_eventqueue = ep.id_eventqueue)
                            INNER JOIN ds_eventhasoption eho ON (eho.id_eventqueue = eq.id_eventqueue)
                            LEFT JOIN ds_eventoption eo ON (eo.id_eventoption = eho.id_eventoption)
                            WHERE
                                ep.id_userinstance = ".$userInstanceId."
                                 AND
                                eventname = 'TRANSFER_RESOURCE'
                                 AND
                                eo.optionname = 'ID_Category'
                                 AND
                                eho.value = 1
                            )";

        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
    
    static public function getWorldNameById(DS_Common $common, $worldId)
    {
        assert(is_int($worldId) && $worldId >0);

        //Получаем имя мира по его id
        $sql = "SELECT
                        worldname
                        FROM ds_world 
                        WHERE id_world = ".$worldId;

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        return $res[0]['worldname'];
    }
    
    static public function FineResource(DS_Common $common, $id_attribute, $value)
    {
        assert(is_int($id_attribute) && $id_attribute > 0);
        assert($value >= 0);

        $sql = 
            "
            UPDATE
                ds_combhasresource
            SET
                value = ".$value."
            WHERE
                id_combhasresource = ".$id_attribute;

        $res = $common->executeQuery ( $sql );
        assert(empty($common->errormsg));
        
        return;
    }
    
    static public function GetResNameByIdCombHasRes(DS_Common $common, $id_attribute)
    {
        assert(is_int($id_attribute) && $id_attribute > 0);

        $sql = 
            "
            SELECT
                    text 
            FROM 
                ds_localizedtextlanguage ltl
            WHERE
                    id_localizedtext =
                        (
                         SELECT 
                            an.id_localizedname
                         FROM
                            ds_attribute as a
                         LEFT JOIN ds_attributename as an ON (an.id_attributename = a.id_attributename)
                         WHERE 
                            a.id_attribute = (
                                        SELECT
                                            c.id_attribute
                                         FROM
                                            ds_combhasresource c
                                         WHERE c.id_combhasresource = ".$id_attribute."
                                            )
                            )
                        AND
                        id_language = '".constant( 'DEFAULT_LANG_ID')."'";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        assert(count($res) == 1);
        
        return $res[0]['text'];
    }
        
    static public function GetCurrentAmountResource(DS_Common $common, $id_attribute)
    {
        assert(is_int($id_attribute) && $id_attribute > 0);

        $sql = 
            "SELECT
                                cohr.value
                            FROM
                                ds_combhasresource cohr,
                                ds_attribute a,
                                ds_attributename an,
                                ds_attributetype at,
                                ds_comb c
                            WHERE
                                c.id_comb = 
                                (
                                    SELECT
                                            id_comb
                                    FROM
                                        ds_combhasresource
                                    WHERE
                                        id_combhasresource = ".$id_attribute."
                                )
                                AND c.id_comb = cohr.id_comb
                                AND cohr.id_attribute = a.id_attribute
                                AND a.id_attributename = an.id_attributename
                                AND an.attributename = 
                                (
                                    SELECT
                                            an2.attributename
                                        FROM
                                            ds_combhasresource cohr2,
                                            ds_attribute a2,
                                            ds_attributename an2
                                        WHERE
                                            cohr2.id_combhasresource = ".$id_attribute."
                                            AND cohr2.id_attribute = a2.id_attribute
                                            AND a2.id_attributename = an2.id_attributename
                                )
                                AND a.id_attributetype = at.id_attributetype
                                AND at.typename = 'CURRENT_AMOUNT'";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        assert(count($res) == 1);
    
        return $res[0]['value'];
    }
            
    static public function getMaxAmmount(DS_Common $common, $id_attribute)
    {
        assert(is_int($id_attribute) && $id_attribute > 0);

        $sql = 
            "SELECT
                                cohr.value
                            FROM
                                ds_combhasresource cohr,
                                ds_attribute a,
                                ds_attributename an,
                                ds_attributetype at,
                                ds_comb c
                            WHERE
                                c.id_comb = 
                                (
                                    SELECT
                                            id_comb
                                    FROM
                                        ds_combhasresource
                                    WHERE
                                        id_combhasresource = ".$id_attribute."
                                )
                                AND c.id_comb = cohr.id_comb
                                AND cohr.id_attribute = a.id_attribute
                                AND a.id_attributename = an.id_attributename
                                AND an.attributename = 
                                (
                                    SELECT
                                            an2.attributename
                                        FROM
                                            ds_combhasresource cohr2,
                                            ds_attribute a2,
                                            ds_attributename an2
                                        WHERE
                                            cohr2.id_combhasresource = ".$id_attribute."
                                            AND cohr2.id_attribute = a2.id_attribute
                                            AND a2.id_attributename = an2.id_attributename
                                )
                                AND a.id_attributetype = at.id_attributetype
                                AND at.typename = 'MAX_AMOUNT'";

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        
        if(sizeof($res) === 0)
        {
            return 999999999999;
        } else {
            return $res[0]['value'];
        }
    }
}

?>