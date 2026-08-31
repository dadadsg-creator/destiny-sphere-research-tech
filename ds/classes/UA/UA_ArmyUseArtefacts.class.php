<?
class UA_ArmyUseArtefacts extends UA_Action 
{
    private $id_army;

    function UA_ArmyUseArtefacts()
    {
        UA_Action::UA_Action();
    }

    // проверяем параметры
    function checkLocalParams()
    {
        $this->id_army = (int)$this->root->get_attribute("id_army");
        return true;
    }

    // выполняем действие
    function performAction ()
    {
        global $common;
        $sql = "select id_unitcontainer from ds_unitcontainer where id_basecomb=".$this->comb." and use_artefacts";
        $res = $common->getResultArray("", $sql);
        if (count($res)) {
            $artArmy = DE_Army::CreateFromID($res[0]['id_unitcontainer']);
            if ($artArmy->getInAction()) return false;
        }
        $army = DE_Army::CreateFromID($this->id_army);
        if ($army->getBaseCombID()!=$this->comb || $army->getInAction()) return false;

        $sql = "update ds_unitcontainer set use_artefacts=false where id_basecomb=".$this->comb." and id_unitcontainer!=".$this->id_army;
        $common->executeQuery($sql);
        $sql = "update ds_unitcontainer set use_artefacts=not use_artefacts where id_unitcontainer=".$this->id_army;
        $common->executeQuery($sql);
        if ($artArmy) $artArmy->useArtefacts = false;
        $army = DE_Army::CreateFromID($this->id_army);
        $army->useArtefacts = !$army->useArtefacts;

        SU_DebugTools::LogAll("Arts to army: ".$this->id_army, "AttArt.log");

        $this->_rebuildMenu();
        return true;
    }

    private function _rebuildMenu()
    {
        $menu = new PN_ArmyOverview();
        $bid = 26; // военная база
        $tab = 'PN_ArmyOverview';
        $menu->setBID($bid);
        $menu->setTAB($tab);
        assert($menu->getPanel());
    }
}
?>