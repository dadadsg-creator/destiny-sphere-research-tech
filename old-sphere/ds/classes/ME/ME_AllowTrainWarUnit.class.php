<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_AllowTrainWarUnit
{
    static public function Exec(DE_UserInstance $userInstance, DE_Comb $comb, $buildingID)
    {
        $common = DS_Common::CreateForCurentDB();

            // не производятся
            // есть ли этот список в сессии
            if(key_exists("allow_units_array", $_SESSION) && false)
            {
                $au = $_SESSION["allow_units_array"];
            }else{
                $au = array();
            }


            if ( isset ( $au[$comb->getID()][$buildingID]))
            {
                // если есть, берем из сессии
                //$this->logResponse ( "From session\n".$au[$this->comb][$this->bid] );
                //print $au[$this->comb][$this->bid];
                return $au;
            } else {
                // если нет, берем из базы
                $sql = sprintf ( "
                        SELECT
                            t.id_unit,
                            u.*,
                            (
                                SELECT
                                    SUM(
                                        uha.value
                                        *
                                        (
                                            SELECT exchangerate
                                            FROM ds_resource
                                            WHERE id_attributename = atn.id_attributename
                                        )
                                    )
                                FROM
                                    ds_unithasattribute uha,
                                    ds_attribute a,
                                    ds_attributename atn,
                                    ds_attributetype att
                                WHERE
                                    uha.id_attribute = a.id_attribute
                                    AND
                                    a.id_attributename = atn.id_attributename
                                    AND
                                    a.id_attributetype = att.id_attributetype
                                    AND
                                    att.typename = 'PRODUCTION_COSTS'
                                    AND
                                    atn.attributename != 'CONSTRUCTION_TIME'
                                    AND
                                    uha.id_unit = u.id_unit
                            ) AS ttt
                        FROM
                            (
                                SELECT
                                    pu.id_building,
                                    pu.id_unit,
                                    ucbbr.id_race,
                                    (
                                        SELECT
                                            (
                                                SELECT count(id_unit)
                                                FROM ds_unitneedsscience
                                                WHERE id_unit = pu.id_unit
                                            ) = (
                                                SELECT count(uns.id_unit)
                                                FROM
                                                    ds_unitneedsscience uns,
                                                    ds_userhasscience uhs
                                                WHERE
                                                    uns.id_science = uhs.id_science
                                                    AND
                                                    uhs.inresearch = false
                                                    AND
                                                    uhs.id_userinstance = %d
                                                    AND
                                                    uns.id_unit = pu.id_unit
                                            )
                                    ) AS senough,
                                    (
                                        SELECT
                                            (
                                                select count(id_unit)
                                                from ds_unitneedsbuilding unb
                                                where id_unit = pu.id_unit
                                            ) = (
                                                select count(unb.id_unit)
                                                from
                                                    ds_unitneedsbuilding unb,
                                                    ds_attachedbuilding ab
                                                where
                                                    unb.id_building = ab.id_building
                                                    and ab.active = true
                                                    AND ab.underconstruction = 'f'
                                                    AND ab.inactivation = 'f'
                                                    and ab.id_comb = %d
                                                    and id_unit = pu.id_unit
                                            )
                                    ) AS benough
                                FROM
                                    ds_produceableunit pu,
                                    ds_unitcanbebuildbyrace ucbbr
                                WHERE
                                    pu.id_unit = ucbbr.id_unit
                                    and
                                    pu.id_building = %d
                                    and
                                    ucbbr.id_race = %d
                            ) t,
                            ds_unit u
                        where t.id_unit = u.id_unit
                        and t.senough = true
                        and t.benough = true
                        order by ttt",
                $userInstance->getID(),
                $comb->getID(),
                $buildingID,
                $userInstance->getRace()->getID() );

                $res = $common->getResultArray ( "all_allowed_units_id_language_id_userinstace_id_comb_id_building_id_race_".$_SESSION["lang"]."_".$userInstance->getID()."_".$comb->getID()."_".$buildingID."_".$userInstance->getRace()->getID(), $sql, "", $userInstance->getID(), "" );
                if ( $res === false )
                {
                    // $this->errormsg .= " SYSTEM ERROR ME_AllowTrainWarUnit::get1";
                    return false;
                }

                $_SESSION["allow_units_array"][$comb->getID()][$buildingID] = $res; // список в сессию
                return $res;
            }
    }
}

?>