<?php $CVS_Version = '$Revision$ ; $Date$';

class DU_DeleteEvents extends DU_DeleteBase
{
	public function __construct(DS_Common $common, $UIID)
	{
		assert(is_int($UIID) && $UIID > 0);
		
		parent::__construct($common, $UIID);
	}
	
	public function Exec()
	{
		//Находим все чужие армии связанные с убдаляемым инстиансом
		$sql = "
				SELECT
      				uca.id_unitcontainer,
      				ucbeq.id_eventqueue
				FROM
    				ds_unitcontaineraction AS uca
    				LEFT JOIN ds_unitcontainerbindeventqueue AS ucbeq ON (ucbeq.id_unitcontainer = uca.id_unitcontainer)
				WHERE
     				uca.id_targetcomb IN
     				(
       					SELECT id_comb
       					FROM ds_comb
       					WHERE id_userinstance = ".$this->UIID."
     				)
     				OR uca.id_startcomb IN
     				(
       					SELECT id_comb
       					FROM ds_comb
       					WHERE id_userinstance = ".$this->UIID."
     				)";     								
		$res = $this->common->getQueryArray($sql);
		assert(empty($this->common->errormsg));

		//Елси нашли что-то то возвращаем это на базу
		foreach($res as $k=>$v)
		{
			$eventID = (int)$v['id_eventqueue'];
			
			if($eventID > 0)
			{
				$sql = "UPDATE ds_eventqueue SET istrash = true WHERE id_eventqueue = ".$eventID;
				$this->common->executeQuery($sql);
				assert(empty($this->common->errormsg));	
			}			
			
			$sql = "UPDATE ds_unitcontainer SET inaction = false WHERE id_unitcontainer = ".$v['id_unitcontainer'];
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			$sql = "DELETE FROM  ds_unitcontaineraction WHERE id_unitcontainer = ".$v['id_unitcontainer'];
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			$sql = "DELETE FROM  ds_unitcontainerbindeventqueue WHERE id_unitcontainer = ".$v['id_unitcontainer'];
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));			
		}
		
		//Сомтрим были ли трейды у этого человка то их удаляем
		
		$sql = "
			DELETE FROM ds_tradebindeventqueue 
			WHERE 
				id_trade IN
				(
	 				SELECT t.id_trade
 					FROM ds_trade AS t
 					WHERE
     					t.id_seller = ".$this->UIID."
     					OR
     					t.id_currentcustomer = ".$this->UIID."
				)";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));				
		
		$sql = "
			DELETE FROM ds_trade 
			WHERE 
				id_trade IN
				(
	 				SELECT t.id_trade
 					FROM ds_trade AS t
 					WHERE
     					t.id_seller = ".$this->UIID."
     					OR
     					t.id_currentcustomer = ".$this->UIID."
				)";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));
		
		//ВЫрезаем все хвосты ивентов
		$sql = "	
					SELECT DISTINCT eq.id_eventqueue
      				FROM
          				ds_eventqueue AS eq
              			INNER JOIN ds_eventparticipant AS ep ON (ep.id_eventqueue = eq.id_eventqueue)
              		WHERE
                   		ep.id_userinstance = ".$this->UIID."";
		$res = $this->common->getQueryArray($sql);
		assert(empty($this->common->errormsg));

		foreach($res as $k=>$v)
		{
			$sql = "delete from ds_attachedbuildingbindeventqueue where id_eventqueue = ".$v['id_eventqueue'];     
			$res = $this->common->executeQuery($sql);
			assert(empty($common->errormsg));
			
     		$sql = "delete from DS_UserHasScienceBindEventQueue where ID_EventQueue = ".$v['id_eventqueue'];
     		$res = $this->common->executeQuery($sql);
			assert(empty($common->errormsg));
		
	     	$sql = "delete from DS_CombBindEventQueue where ID_EventQueue = ".$v['id_eventqueue'];
     		$res = $this->common->executeQuery($sql);
			assert(empty($common->errormsg));
			
    	 	$sql = "delete from DS_TradeBindEventQueue where ID_EventQueue = ".$v['id_eventqueue'];
	     	$res = $this->common->executeQuery($sql);
			assert(empty($common->errormsg));
		
     		$sql = "delete from DS_UnitContainerBindEventQueue where ID_EventQueue = ".$v['id_eventqueue'];
     		$res = $this->common->executeQuery($sql);
			assert(empty($common->errormsg));
		}
		
		//Тперь заносим в терш все ивенты в которых данный челоывек являеться участником
		$sql = "
			UPDATE ds_eventqueue
			SET istrash = true
			WHERE 
				id_eventqueue IN 
				(
      				SELECT DISTINCT eq.id_eventqueue
      				FROM
          				ds_eventqueue AS eq
              			INNER JOIN ds_eventparticipant AS ep ON (ep.id_eventqueue = eq.id_eventqueue)
              		WHERE
                   		ep.id_userinstance = ".$this->UIID."
                   		AND 
                   		eq.istrash = false
      			)";
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));		
		
		//Удаляем участников ивентов с этим юзверем что ыб не мешало удалять из ds_userinstance
		$sql = "DELETE FROM ds_eventparticipant WHERE id_userinstance =  ".$this->UIID."";			
		$this->common->executeQuery($sql);
		assert(empty($this->common->errormsg));		
	}		
}
?>