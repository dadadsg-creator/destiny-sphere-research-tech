<?
class UA_GetRemoteWarBase extends UA_Action
{
    function ActivateAll() {
        UA_Action::UA_Action();
    }

    function PerformAction() {
        $ua = new UserAction;
        $coord = $this->root->get_attribute("coord");
        $ua->getRequest('<navigate dest="'.$coord.'" />');
        $ua->getRequest('<navigate dest="HOME" />');
        $tokens = explode(".", $coord);
        $id_comb = DS_Comb::GetIDByCordinates( $this->world, $tokens[0], $tokens[1], $tokens[2]);
        $sql = "select c.cellnumber from ds_cell c, ds_attachedbuilding ab
            where c.id_cell=ab.id_cell and c.id_comb=$id_comb
            and ab.id_building=26";

        $res = $this->common->getResultArray("", $sql);
        $ua->getRequest('<getbuildmenu c="'.$res[0]['cellnumber'].'" />');
        return TRUE;
    }
}
?>