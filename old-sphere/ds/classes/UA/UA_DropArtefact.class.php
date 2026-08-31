<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2008/07/01 08:56:38 $';

// команда уничтожить артефакт в хранилище

class UA_DropArtefact extends UA_Action {

    public $id_artefactinstance = array();

    private $deleted_arts = array();

    function UA_DropArtefact ()
    {
        UA_Action::UA_Action ();
    }

    // проверяем параметры
    function checkLocalParams ()
    {
        // id_artefactinstance
        $this->id_artefactinstance = explode(',',$this->root->get_attribute ( "id" ));

        if(empty($this->id_artefactinstance))
        {
            $this->errormsg .= " SYSTEM ERROR UA_DropArtefact::checkLocalParams1";
            return false;
        }

        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {
        global $common;
        global $params;

        // есть ли такой артефакт в хранилище
        foreach ($this->id_artefactinstance as $k=>$v)
        {
            $sql = "select id_artefact
                from ds_combhasartefact
                where id_comb = ".$this->comb."
                and id_artefact = ".$v;
            $res = $common->getResultArray("id_artefact_id_comb_id_artefactinstance_".$this->comb."_".$v, $sql, "", $params["uiid"], "LOCAL_CACHE");
            if(!$res)
            {
                $this->errormsg .= " SYSTEM ERROR - UA_DropArtefact::checkAction1";
                return false;
            }
        }

        return true;
    }

    // выполняем действие
    function performAction ()
    {
        global $params;
        global $common;

        if(!DE_WorldSettings::CreateForCurentWorld()->get('CAN_DROP_ARTS'))
        {
            ds_error("Sorry. This action disabled.");
        }

        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();
            return true;
        }

        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();
            return true;
        }

        // название соты
        $combName = DE_Comb::CreateFromID((int)$this->comb)->getName();

        foreach($this->id_artefactinstance as $k=>$v)
        {
            if(!$this->drop($v, $combName, $common, $params))
            {
                return false;
            }
        }

        //if($_SESSION['profi_game'])
        //{
        $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}

        // минимальный рефреш
        if(!$this->refresh(2, "", "", "", $noCellData))
        {
            return false;
        }

        if($_SESSION['profi_game'] == true)
        {
            $this->_sendHelper();
        }

        return true;
    }

    function drop($id, $combName, DS_Common $common, array $params)
    {
        $artefact = DE_Artefact::CreateFromArtefactInstanceID((int)$id);
        if(!$artefact->getIsDestroyable())
        {
            return true;
        }

        // Получаем время смерти артефакта
        $sql = "
                SELECT  
                    *
                FROM 
                    ds_artefactinstance
                WHERE 
                    id_artefactinstance = ".(int)$id;
        $res = $common->getResultArray("", $sql, "", "", "");
        if(!$res || count($res) != 1)
        {
            $this->errormsg .= " SYSTEM ERROR - UA_DropArtefact::performAction1";
            return false;
        }

        if ($res[0]['immortal'] == 'f') {
            $art_death_time = $res[0]['death_time'];
        } else {
            $art_death_time = '';
        }

        //Получаем компенсацию за арт
        $compensation = DS_Artefact::GetDropCompensation($this->world, $id);
        if(!empty($compensation) && $compensation['value'] > 0) {
            $extended_message = true;
            $messparams[2] = DE_Resource::CreateFromName($compensation['resource'])->getLocolizedName();
            $messparams[3] = $compensation['value'];
            $comb = DE_Comb::CreateFromID((int)$this->comb);
            $comb->getAttributes()->deltaAttribute('CURRENT_AMOUNT', $compensation['resource'], (float)$compensation['value']);
            assert($this->refresh(2, "", "", 1, true));
        }

        SU_LogDeleteCombObjects::LogForCurrentUserAndComb('ARTEFACT', $artefact->getArtefactID(), 0, $art_death_time);

        $messparams[0] = "{b} ".$combName."{/b}";
        $messparams[1] = "{b} ".$artefact->getLocalizedName()."{/b}";

        // удаляем артефакт из хранилища
        $sql = "delete from
                    ds_combhasartefact
                where
                    id_comb = ".$this->comb."
                    and id_artefact = ".$id;
        $res = $common->executeQuery($sql);
        if($res === false)
        {
            $this->errormsg = "SYSTEM ERROR - UA_DropArtefact::performAction3";
            return false;
        }

        // стираем artefactinstance
        $sql = "delete from ds_trade_lot_artefactinstances
                where id_artefactinstance = ".$id;
        $res = $common->executeQuery ( $sql );
        if($res === false)
        {
            $this->errormsg = "SYSTEM ERROR - UA_DropArtefact::performAction5";
            return false;
        }

        $sql = "delete from ds_artefactinstance
                where id_artefactinstance = ".$id;
        $res = $common->executeQuery ( $sql );
        if($res === false)
        {
            $this->errormsg = "SYSTEM ERROR - UA_DropArtefact::performAction4";
            return false;
        }

        $this->deleted_arts[] = $id;

        // системное сообщение
        if(!isset ($extended_message)) {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "DROP_ARTEFACT_MESS", $messparams );
        }
        else {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "DROP_ARTEFACT_COMPENSATION_MESS", $messparams );
        }


        return true;
    }

    private function _sendHelper()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"'."?".">\n<artdepotdelete>";
        $xml .= implode(",", $this->deleted_arts);
        $xml .= "</artdepotdelete>";
        SU_OutputHandler::Singleton()->addString($xml, 'helper');
    }
}

?>