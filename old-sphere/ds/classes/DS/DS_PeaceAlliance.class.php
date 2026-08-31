<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_PeaceAlliance extends DS_Base
{
  
    public static function getAlliancePeaceList($id_alliance='')
    {
        $where = '';
        if ($id_alliance)
         $where = "WHERE id_alliance_1={$id_alliance} or id_alliance_2={$id_alliance}";
        
      $common = self::getCommon();      
    $sql = "select id_peace, id_alliance_1, id_alliance_2, status, peace_date, who_offer,  peace_end_date, date_part('epoch', peace_end_date) as psec, 
    peace_duration, id_comb_1, id_comb_2,
    price_prodium_1,price_kad_1, price_nano_1,price_food_1, price_energy_1, price_silver_1, 
        price_prodium_2,price_kad_2, price_nano_2,price_food_2, price_energy_2, price_silver_2, a1.alliancetag as alliancename1, a2.alliancename as 
alliancename2,  date_part('epoch', pa.peace_end_date) as psec  from ds_peace_alliance  pa
INNER JOIN ds_alliance  a1 ON  a1.id_alliance = id_alliance_1
INNER JOIN ds_alliance  a2 ON  a2.id_alliance = id_alliance_2
    $where";
    
        //SU_DebugTools::LogAll("getPrivatePeace sql=$sql",'peace.log');
    $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
    echo $common->errormsg;
        assert(empty($common->errormsg));

        if(!$res){
            return array();
        }else{
            return $res;
        }
    }   

    function delAlliancePeace($id_peace)
    {
        $common = self::getCommon();        

    $common->executeQuery("DELETE FROM ds_peace_alliance WHERE id_peace={$id_peace}");
        assert(empty($common->errormsg));
    }
        
    function offerAlliancePeace($pD)
    {
        $common = self::getCommon();        

        $sql = "INSERT INTO ds_peace_alliance(id_alliance_1, id_alliance_2, id_comb_1, id_comb_2,  
        status, peace_date, who_offer, peace_duration, 
        price_prodium_1,price_kad_1, price_nano_1,price_food_1, price_energy_1, price_silver_1, 
        price_prodium_2,price_kad_2, price_nano_2,price_food_2, price_energy_2, price_silver_2
        ) 
        VALUES({$pD['id_alliance_1']}, {$pD['id_alliance_2']}, {$pD['id_comb_1']}, {$pD['id_comb_2']},
        {$pD['status']}, '{$pD['peace_date']}', {$pD['who_offer']}, {$pD['peace_duration']},
        {$pD['price_prodium_1']}, {$pD['price_kad_1']}, {$pD['price_nano_1']}, {$pD['price_food_1']},   {$pD['price_energy_1']}, {$pD['price_silver_1']}, 
        {$pD['price_prodium_2']}, {$pD['price_kad_2']}, {$pD['price_nano_2']}, {$pD['price_food_2']},   {$pD['price_energy_2']}, {$pD['price_silver_2']} 
        )";
        //SU_DebugTools::LogAll($sql, 'alliance.log');
    $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }


    function setPeaceStatus($id_peace, $status)
    {
        $common = self::getCommon();
        $sql = "UPDATE ds_peace_alliance SET  status={$status} where id_peace={$id_peace}";
    $common->executeQuery($sql);
    
        assert(empty($common->errormsg));               
    }
    
    function confirmPeace($peace, $my_n)
    {
        $idc = $peace['id_comb_'.$my_n];
        $common = self::getCommon();
        $sql = "UPDATE ds_peace_alliance SET  status=1, id_comb_{$my_n}={$idc}, peace_end_date=NOW()+'{$peace['peace_duration']} days'::INTERVAL WHERE id_peace={$peace['id_peace']}";
    $common->executeQuery($sql);
    
        assert(empty($common->errormsg));               
    }
        
    function deletePeace($id_peace)
    {
        $common = self::getCommon();
        $sql = "DELETE FROM ds_peace_alliance WHERE id_peace={$id_peace}";
    $common->executeQuery($sql);
        assert(empty($common->errormsg));               
    }
        
    public static function getAlliancePeace($my_al, $offer_al)
    {
        $common = self::getCommon();
        
        if ($my_al < $offer_al)
        {
           $plr_n = 1;
           $with_whom_n = 2;
        }
        else 
        {
           $plr_n = 2;
           $with_whom_n = 1;
        }
        
    $sql = "SELECT id_peace, status, peace_date, who_offer,  peace_end_date, date_part('epoch', peace_end_date) as psec, 
    peace_duration, id_comb_1, id_comb_2,
    price_prodium_1,price_kad_1, price_nano_1,price_food_1, price_energy_1, price_silver_1, 
        price_prodium_2,price_kad_2, price_nano_2,price_food_2, price_energy_2, price_silver_2
    FROM ds_peace_alliance WHERE id_alliance_{$plr_n} = {$my_al} AND
        id_alliance_{$with_whom_n} = {$offer_al}";
    
    $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
    assert(empty($common->errormsg));
    

        if ($res)
        return $res[0];
      else 
        return 0;  
    }

}

?>