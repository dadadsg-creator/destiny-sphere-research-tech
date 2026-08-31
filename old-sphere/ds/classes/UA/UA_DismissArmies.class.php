<?php $CVS_Version = '$Revision$ ; $Date$';
// распускание всех армий в основную армию на соте
class UA_DismissArmies extends UA_Action
{
    private $ids="";

    function UA_TransferUnit()
    {
        UA_Action::UA_Action();
    }

    // проверяем параметры
    function checkLocalParams()
    {
        $this->ids = explode(",", $this->root->get_attribute("ids"));
        for ($i=0; $i<count($this->ids); $i++)
            $this->ids[$i] = (int)$this->ids[$i];
        $this->ids = implode(",", $this->ids);
        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {
        return true;
    }

    // выполняем действие
    function performAction ()
    {
        global $common;

        $sql = "select id_unitcontainer from ds_unitcontainer
            where id_basecomb=".$this->comb." and isdefault";
        $res = $common->getResultArray ( "", $sql, "", "", "" );
        assert(empty($common->errormsg));
        $defaultArmy = $res[0]['id_unitcontainer'];

        $sql = "select hu.id_unit, sum(hu.amount) as total
            from ds_unitcontainerhasunit hu, ds_unitcontainer c
            where c.id_basecomb=".$this->comb." and c.id_unitcontainer=hu.id_army
            and id_unitcontainertype=1
            and not c.inaction and not c.isdefault";
        if ($this->ids!='0') {
            $sql .= " and c.id_unitcontainer in (".$this->ids.")";
        }
        $sql .=  " group by hu.id_unit";

        $res = $common->getResultArray ( "", $sql, "", "", "" );        
        assert(empty($common->errormsg));

        foreach ($res as $row) {
            $sql = "select amount from ds_unitcontainerhasunit
                    where id_army=$defaultArmy and id_unit={$row['id_unit']}";
            $haveUnit = $common->getResultArray ( "", $sql, "", "", "" );        
            assert(empty($common->errormsg));
            if (count($haveUnit)) {
                $sql = "update ds_unitcontainerhasunit set amount=amount+{$row['total']}
                    where id_army=$defaultArmy and id_unit={$row['id_unit']}";
                $common->executeQuery ($sql);
                assert(empty($common->errormsg));
            }
            else {
                $sql = "insert into ds_unitcontainerhasunit (id_army, id_unit, amount)
                    values ($defaultArmy, {$row['id_unit']}, {$row['total']})";
                $common->executeQuery ($sql);
                assert(empty($common->errormsg));
            }
        }

        $sql = "delete from ds_unitcontainer
            where id_basecomb=".$this->comb." and id_unitcontainertype=1
            and not isdefault and not inaction";
        if ($this->ids!='0') {
            $sql .= " and id_unitcontainer in (".$this->ids.")";
        }
        $common->executeQuery ($sql);
        assert(empty($common->errormsg));

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