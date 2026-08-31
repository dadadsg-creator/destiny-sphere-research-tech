<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 *
 * Класс гейта для получения статистики премиумов в мирах
 *
 */
class BG_PremiumStat extends BG_Common
{
	public function __construct($data)
	{
	  parent::__construct($data);
	}

	public function proceed()
	{

		if($this->error_status != 0)
		{
			return;
		}

		if(!array_key_exists('date1',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
    else
    {
      $date1 = $this->incoming_info['date1'];
    }

		if(!array_key_exists('date2',$this->incoming_info))
		{
			$this->error_status = 1;
			$this->outputAnswer();
			return;
		}
    else
    {
      $date2 = $this->incoming_info['date2'];
    }

     $_SESSION['dbId'] = 0;
    $common = DS_Common::CreateForCurrentDB(0);

    //получим юзеров, у которых есть купленные премиумы
    $sql =  "SELECT id_user FROM  ds_userprofidata WHERE  enddate > now() and trial=false";

	    $res = $common->getResultArray("", $sql, "", "", "");

    //SU_DebugTools::LogAll('BG_PremiumStat error:'.$common->errormsg,'prem.log');
    //Теперь получим миры, в которых играет юзер
    assert(empty($common->errormsg));
    
    $multiWorldsAccounts = 0;
    $totalAccounts = count($res);

    $worlds = array();
    foreach ($res as $r)
    {
      $sql = "select   wc.worldname as wrl from   ds_userinstancecollection AS uic
            INNER JOIN ds_worldcollection AS wc USING (id_worldcollection)
          where   uic.id_user = {$r['id_user']}";
  		$worlds_acc = $common->getResultArray("",$sql, "", "", "NO_CACHE");
      $wcnt = count($worlds_acc);
      if ($wcnt > 1)
        $multiWorldsAccounts++;
      else
      {
         $worlds[$worlds_acc[0]['wrl']]['only']++;
      }

      //Если юзер зареган в n мирах, то считаем, что на каждый мир приходится 1/n премиума
      foreach ($worlds_acc as $r)
      {
         $worlds[$r['wrl']]['prem'] += 1/$wcnt;
      }

    }

    //SU_DebugTools::LogAll('BG_PremiumStat worlds'.var_export($worlds, true),'prem.log');

    $worldsstat  = urlencode(serialize($worlds));
    $this->addToResult('worldsstat', $worldsstat);
    $this->addToResult('totalAccounts', $totalAccounts);
    $this->addToResult('multiWorldsAccounts', $multiWorldsAccounts);

		$this->error_status = 0;
		$this->outputAnswer();
	}
}
?>