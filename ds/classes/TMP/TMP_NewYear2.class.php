<?
class TMP_NewYear2
{
    private static $presents;

    static public function givePremiumPresent($id_user) {
        if (time() < 1388347200 || time() > 1388692800) return;
        $common = DS_Common::CreateForCurrentDB();
        $sql = "select 1 from ds_user_got_present where id_user=$id_user";
        $res = $common->getResultArray("", $sql);
        if (count($res)) return;

        $sql = "select * from ds_userprofidata where id_user=$id_user";
        $res = $common->getResultArray("", $sql);
        if (!count($res)) {
            $sql = "insert into ds_userprofidata (id_user, profi, creationdate, startdate, enddate, active)
              values ($id_user, true, now(), now(), now() + interval '5 days', true)";
            $common->executeQuery($sql);
        }
        else {
            $sql = "update ds_userprofidata set enddate=enddate + interval '5 days' where id_user=$id_user";
            $common->executeQuery($sql);
        }
        $sql = "insert into ds_user_got_present (id_user) values ($id_user)";
        $common->executeQuery($sql);
    }

    static public function givePresent(DE_UserInstance $userInstance, $present) {
        $common = DS_Common::CreateForCurrentDB();
        $combsCollection = $userInstance->getCombsCollection();
        $combsCollection->resetNext();
        $comb = $combsCollection->next();
        $text = "Получите подарок от Деда Мимикрия! ";
        $unit = DE_Unit::CreateFromName($present[2]);
        $text .= $unit->getUnitNameLocolized()." (".$present[3].")";
        $sql = sprintf("SELECT uc.id_unitcontainer, uchu.amount
            FROM ds_unitcontainer uc
            left join ds_unitcontainerhasunit uchu
            on uc.id_unitcontainer=uchu.id_army AND uchu.id_unit = %d
            WHERE uc.isdefault AND uc.id_basecomb = %d",
            $unit->getID(), $comb->getID());
        print $sql."<br>";
        $res = $common->getResultArray("", $sql);
        $id_army = $res[0]['id_unitcontainer'];
        if ($res[0]['amount']>0) {
            $sql = "update ds_unitcontainerhasunit set amount=amount+{$present[3]}
            where id_unit=".$unit->getID()." and id_army=$id_army";
            $common->executeQuery($sql);
        }
        else {
            $sql = sprintf("insert into ds_unitcontainerhasunit (id_army, id_unit, amount)
                values (%d, %d, %d)",
                $id_army, $unit->getID(), $present[3]);
            $common->executeQuery($sql);
        }
    }
}
?>