<?
class UA_ActivateAll extends UA_Action
{
    function ActivateAll() {
        UA_Action::UA_Action();
    }

    function PerformAction() {
        $this->resource = (int)$this->root->get_attribute("res");
        $this->switch = $this->root->get_attribute("switch");
        $ua = new UserAction;
        $cells = $this->getCellNumbers(1);
        foreach ($cells as $c) {
            $ua->getRequest('<'.($this->switch=='on'?'':'de').'activatebuild c="'.$c.'"/>');
        }
        $ua->getRequest('<navigate cell="1" dest="CENTRAL" />');
        $serverCells = $this->getCellNumbers(2);
        $mask = GB_CombCentralViewMask::GetServerBaseMask();
        $cells = array();
        foreach ($serverCells as $c)
            $cells[] = $mask[$c];
        foreach ($cells as $c) {
            $ua->getRequest('<'.($this->switch=='on'?'':'de').'activatebuild c="'.$c.'"/>');
        }
        $ua->getRequest('<navigate cell="1" dest="NEUTRAL" />');
        $cells = $this->getCellNumbers(3);
        foreach ($cells as $c) {
            $ua->getRequest('<'.($this->switch=='on'?'':'de').'activatebuild c="'.$c.'"/>');
        }
        $ua->getRequest('<navigate cell="1" dest="HOME" />');

        return TRUE;
    }


    private function getCellNumbers($zone)
    {
        $sql = "select c.cellnumber
            from ds_cell c, ds_attachedbuilding ab
            where c.id_cell=ab.id_cell
            and c.id_celltype=$zone
            and ab.id_comb=".$this->comb."
            and ab.active=".($this->switch=='on'?'false':'true')."
            and ab.underconstruction=false
            and ab.inactivation=false
            and ab.busy=false
            and ab.inupgrade=false
            and ab.id_building in
                (select id_building from ds_buildinghasattribute
                where id_building in (SELECT id_building FROM ds_building where id_buildingtype=1)
                and id_attribute in (select id_attribute from ds_attribute where id_attributetype=2 and id_attributename=".$this->resource."))";
        $res = $this->common->getResultArray("", $sql);
        $out = array();
        foreach ($res as $row)
            $out[] = $row['cellnumber'];
        return $out;
    }
}
?>