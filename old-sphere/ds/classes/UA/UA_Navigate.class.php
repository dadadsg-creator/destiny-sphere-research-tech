<?php $CVS_Version = '$Revision$ ; $Date$';

// навигация по частям соты или на колонию



class UA_Navigate extends UA_Action {

    public $destination;
    public $ctype;
    public $id_ring_to;
    public $id_combcomplex_to;
    public $id_comb_to;
    public $combnumber_to;
    public $cell;
    public $gooncolony = 1;

    function UA_Navigate () {
        UA_Action::UA_Action ();
    }

    // проверяем параметры
    function checkLocalParams ()
    {

        global $params;
        global $common;

        // передавать ли при переходе ячейки или нет
        $this->cell = $this->root->get_attribute ( "cell" );

        // место назначения
        $this->destination = $this->root->get_attribute ( "dest" );

        // часть соты
        if ( $this->destination == 'CENTRAL' || $this->destination == 'NEUTRAL' || $this->destination == 'HOME' ) {

            $sql = sprintf ( "select id_celltype
                    from ds_celltype
                    where celltypename = '%s'",
                    $this->destination );

            $res = $common->getResultArray ( "id_celltype_celltypename_".$this->destination, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR UA_Navigate::checkLocalParams1";

                return false;
            }

            $this->ctype = $res[0]['id_celltype'];

        // колония
        } else {

            if ( !preg_match ( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $this->destination ) ) {
                $this->errormsg .= " SYSTEM ERROR UA_Navigate::checkLocalParams2";
                return false;
            }

            if ( strlen ( $this->destination ) > 50 ) {
                $this->errormsg .= " SYSTEM ERROR UA_Navigate::checkLocalParams3";
                return false;
            }

            $dest = explode ( ".", $this->destination );

            $dest_ring = $dest[0];
            $dest_combcomplex = $dest[1];
            $dest_comb = $dest[2];

            $sql = sprintf ( "select co.id_comb,
                    co.id_combcomplex,
                    co.combnumber,
                    ri.id_ring
                    from ds_comb co,
                    ds_combcomplex cc,
                    ds_ring ri
                    where co.id_combcomplex = cc.id_combcomplex
                    and cc.id_ring = ri.id_ring
                    and ri.id_world = %d
                    and co.combnumber = %d
                    and cc.complexnumber = %d
                    and ri.ringnumber = %d",
                    $this->world,
                    $dest_comb,
                    $dest_combcomplex,
                    $dest_ring );

            $res = $common->getResultArray ( "coord", $sql, "", "", "" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR UA_Navigate::checkLocalParams4";

                return false;
            }

            $this->id_ring_to = $res[0]['id_ring'];
            $this->id_combcomplex_to = $res[0]['id_combcomplex'];
            $this->id_comb_to = $res[0]['id_comb'];
            $this->combnumber_to = $res[0]['combnumber'];
        }

        return true;
    }

    // проверяем допустимость действия
    function checkAction () {

        global $common;
        global $params;
        // если переходим на центральную или нейтральную часть соты - проверям открыты ли ЦВ
        if ( $this->destination == 'CENTRAL' || $this->destination == 'NEUTRAL') {

            $sql = sprintf ( "select id_attachedbuilding
                    from ds_attachedbuilding ab,
                    ds_building b
                    where ab.id_building = b.id_building
                    and ab.id_comb = %d
                    and ab.active = true
                    and b.buildingname = 'CENTRAL_GATE'",
                    $this->comb );

            $res = $common->getResultArray ( "open_central_gates_id_comb".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR - UA_Navigate::checkAction1";

                return false;
            }
        }
        // если на колонию проверяем его ли колония
        if ( preg_match ( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $this->destination ) ) { // to colony

            $sql = sprintf ( "select co.id_userinstance
                    from ds_comb co
                    where co.id_comb = %d
                    and co.id_userinstance = %d",
                    $this->id_comb_to,
                    $params['uiid'] );

            $res = $common->getResultArray ( "is_colony_comb", $sql, "", "", "" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR - UA_Navigate::checkAction2";

                return false;
            }
        }

        if(!$_SESSION['profi_game'] && ($this->destination == 'CENTRAL' || $this->destination == 'NEUTRAL'))
        {

            //Вычисляем количество не премиумных колоний
            $max_col = (int)$this->worldSettings->get('MAX_COLONIES');


            $sql = sprintf ( "
                        SELECT
                            c.id_comb
                        FROM
                            ds_comb c
                            FULL OUTER JOIN ds_colony cc ON (c.id_comb = cc.id_comb_from) AND
                                (c.id_comb = cc.id_comb_to)
                        WHERE
                            c.id_userinstance = %d",
            $params['uiid'] );

            $res = $common->getResultArray ( "colony_list_from_userinstance_".$params['uiid'], $sql, "", "", "GLOBAL_CACHE" );
            if ( !$res )
            {
                $this->errormsg .= " SYSTEM ERROR - UA_Navigate::checkAction4";
                return false;
            }

            foreach ($res as $key => $value)
            {
                if (($value['id_comb'] == $this->id_comb_to) && ($key > $max_col))
                {
                    //Выходим с ошибкой что эта колония не может быть заюзана так как она премиумная
                    $this->errormsg .= " This is PREMIUM colony. Please pay for PREMIUM account!";
                    $this->gooncolony = 0;


                    $sql = sprintf ( "
                            SELECT
                                c.id_comb,
                                cc.id_ring,
                                cc.id_combcomplex,
                                cc.id_complextype
                            FROM
                                ds_comb c
                                INNER JOIN ds_combcomplex cc ON (c.id_combcomplex = cc.id_combcomplex)
                                INNER JOIN ds_ring r ON (cc.id_ring = r.id_ring)
                            WHERE
                                r.id_world = %d
                                AND c.id_comb = %d",
                    $this->world,
                    $res[0]['id_comb'] );

                    $res = $common->getResultArray ( "coord", $sql, "", "", "" );
                    if ( !$res ) {
                        $this->errormsg .= " SYSTEM ERROR UA_Navigate::checkLocalParams5";
                        return false;
                    }

                    $this->celltype = 1;
                    $this->combcomplex = $res[0]['id_combcomplex'];
                    $this->ring = $res[0]['id_ring'];
                    $this->comb = $res[0]['id_comb'];
                    $this->combnumber = $res[0]['combnumber'];
                    return true;

                }

            }
        }
        return true;
    }

    // выполняем действие
    function performAction () {

        global $params;
        global $common;

        $_SESSION['old_comb'] = $_SESSION["comb"];
        if ($this->gooncolony == 0)
        {

            $messparams[0] = '';
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PREMIUM_COLONY', $messparams);

            if ( !$this->refresh ( 2, 1, 1 ) )
            {
                return false;
            }
            $_SESSION["comb"] = $this->comb;
            return true;

        }

        // если на колонию
        if ( preg_match ( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $this->destination ) )
        {

            $common->clearLocalCache ( $params["uiid"] );

            // меняем координаты в сессии
            $_SESSION["celltype"] = $this->celltype;
            $_SESSION["combcomplex"] = $this->id_combcomplex_to;
            $this->combcomplex = $this->id_combcomplex_to;

            $_SESSION["ring"] = $this->id_ring_to;
            $this->ring = $this->id_ring_to;

            $_SESSION["comb"] = $this->id_comb_to;
            $this->comb = $this->id_comb_to;

            $_SESSION["combnumber"] = $this->combnumber_to;
            $this->combnumber = $this->combnumber_to;

            // отктыты ли ЦВ
            $sql = sprintf ( "select id_attachedbuilding
                    from ds_attachedbuilding ab,
                    ds_building b
                    where ab.id_building = b.id_building
                    and ab.id_comb = %d
                    and ab.active = true
                    and b.buildingname = 'CENTRAL_GATE'",
                    $this->comb );

            $res = $common->getResultArray ( "open_central_gates_id_comb".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR - UA_Navigate::checkAction1";

                return false;
            }

            if ( sizeof ( $res ) == 0 ) {

                $this->celltype = 1;
                $_SESSION["celltype"] = $this->celltype;
            }
            
            // Обновляем инфу о том, где мы находмся
            $userName = $_SESSION['s_login'];
            $combData = DS_Comb::GetByID($_SESSION['comb']);
            $combCoords = $combData['combname'].'('.$combData['ringnumber'].'.'.$combData['complexnumber'].'.'.$combData['combnumber'].')';
            $worldData = DS_World::GetByID($_SESSION['world']);
            $worldName = $worldData['worldname'];
            $textD = "nick=Имя: $userName&complex=Сота: $combCoords&world=Мир: $worldName";

            SU_OutputHandler::Singleton()->addReadyString($textD);

        } else { // если на часть соты

            // меняем координаты в сессии

            $sql = sprintf ( "select co.id_comb,
                    co.combnumber,
                    cc.id_combcomplex,
                    r.id_ring,
                    r.id_world
                    from ds_comb co,
                    ds_combcomplex cc,
                    ds_ring r,
                    ds_world w
                    where co.id_combcomplex = cc.id_combcomplex
                    and cc.id_ring = r.id_ring
                    and co.id_comb = %d",
                    $this->comb );

            $res = $common->getResultArray ( "id_comb_combnumber_id_combcomplex_id_ring_id_world_id_comb_".$this->comb, $sql, "", "", "GLOBAL_CACHE" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR UA_Navigate::PerformAction1";

                return false;
            }

            $_SESSION["celltype"] = $this->ctype;
            $this->celltype = $this->ctype;

            $_SESSION["combcomplex"] = $res[0]['id_combcomplex'];
            $this->combcomplex = $res[0]['id_combcomplex'];

            $_SESSION["ring"] = $res[0]['id_ring'];
            $this->ring = $res[0]['id_ring'];
        }

        $_SESSION["complexview"] = "";
        $_SESSION["complex_ring"] = "";
        $_SESSION["complex_complex"] = "";

/*
        if ($_SESSION['old_comb'] == $_SESSION["comb"])
        {
            $str = "
            this->cell = ".$this->cell."
            _SESSION['old_comb'] = ".$_SESSION['old_comb']."
            _SESSION['comb'] = ".$_SESSION["comb"]."
            ";
            DebugTools::logAll($str, 'UA_Navigate.log');
        }
*/

        if ( $this->cell ) { // рефреш с передачей ячеек

            if ( !$this->refresh ( 2, 1 , 1, 1) )
            {
                return false;
            }

        } else { // минимальный рефреш

            if ( !$this->refresh ( 2 ) ) {
                return false;
            }
        }

        $res = "";

        return true;
    }
}
?>