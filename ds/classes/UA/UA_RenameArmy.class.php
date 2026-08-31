<?php $CVS_Version = '$Revision$ ; $Date$';

// команда создать армию
class UA_RenameArmy extends UA_Action {

    public $armyname;
    public $errorname;
    public $id_army;

    function UA_RenameArmy () {
        UA_Action::UA_Action ();
        $this->errorname = array ();
    }

    // проверяем параметры
    function checkLocalParams () {

        global $common;
        global $params;

        $noCellData = true;
        
        $this->id_army = (int) $this->root->get_attribute("id");
        $this->armyname = trim(utf8_decode($this->root->get_attribute("newName")));

        if ( strlen ( $this->armyname ) < 3 ) {
            $this->errorname[] = 'ARMY_NAME_TOO_SHORT';
        }

        if(!preg_match('/^[ .a-zA-Z0-9_-]+$/', $this->armyname)) {
            $this->errorname[] = 'ARMY_NAME_HAS_WRONG_SYMBOLS';
        }

        if ( strlen ( $this->armyname ) > 20 ) {
            $this->errorname[] = 'ARMY_NAME_TOO_LONG';
        }

        // уникальность названия
        if ( sizeof ( $this->errorname ) == 0 ) {

            $sql = sprintf("select id_unitcontainer
                from ds_unitcontainer
                where upper(unitcontainername) = upper('%s')",
            $this->armyname );

            $res = $common->getResultArray ( "", $sql);
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR UA_RenameArmy::checkLocalParams1";

                return false;
            }

            if ( sizeof ( $res ) > 0 ) {
                $this->errorname[] = 'ARMY_NAME_NOT_UNIQUE';
            }
        }

        return true;
    }

    // проверяем допустимость действия
    function checkAction () {

        global $common;
        global $params;

        $common->clearLocalCache ( $params["uiid"] );

        $sql = "select 1 from ds_unitcontainer where not isdefault
            and id_unitcontainer=".$this->id_army."
            and id_userinstance=".$this->uiid;
        $res = $common->getResultArray("", $sql);
        if (!count($res)) {
            $this->errorname[] = 'WRONG_ARMY';
        }

        // проверяем не идет ли на соте бой
        $sql = sprintf ( "select id_battle
                from ds_battle
                where id_battlecomb = %d
                and battleenddate is null",
        $this->comb );

        $res = $common->getResultArray ( "id_battle_id_comb_".$this->comb, $sql, "", "", "" );
        if ( $res === false ) {
            $this->errormsg .= " SYSTEM ERROR UA_RenameArmy::checkAction1";
            return false;
        }
        if ( sizeof ( $res ) > 0 ) {
            $this->errorname[] = 'BATTLE_IN_PROGRESS';
        }

        return true;
    }

    // совершаем действие
    function performAction () {

        global $params;
        global $common;

        if ( $_SESSION['profi_game'] ){
            $noCellData = true;
        } else {
            $noCellData = false;
        }

        if ( sizeof ( $this->errorname ) == 0 ) {


            // переименовываем армию
            $sql = "update ds_unitcontainer
                set unitcontainername='".pg_escape_string($this->armyname)."'
                where id_unitcontainer=".$this->id_army;
            $res = $common->executeQuery ($sql);
            if ( $res === false ) {
                $this->errorname[] = 'ARMY_NAME_NOT_UNIQUE';
            }

            $menu = new PN_ArmyOverview();
            $bid = 26; // военная база
            $tab = 'PN_ArmyOverview';
            $menu->setBID($bid);
            $menu->setTAB($tab);
            assert($menu->getPanel());

        } else { // если какая-нибудь ошибка - отправляем сообщение и выходим
            $messparams[0] = "";
            for ( $i=0; $i < sizeof ( $this->errorname ); $i++ ) {
                DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errorname[$i], $messparams );
            }
        }
        return true;
    }
}

?>