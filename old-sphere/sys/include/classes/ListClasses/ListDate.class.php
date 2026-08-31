<?
	class ListDate extends ListClass {
		
		var $monthes = array(
			"01" =>"Январь",
			"02" => "Февраль",
			"03" => "Март",
			"04" => "Апрель",
			"05" => "Май",
			"06" => "Июнь",
			"07" => "Июль",
			"08" => "Август",
			"09" => "Сентябрь",
			"10" => "Октябрь",
			"11" => "Ноябрь",
			"12" => "Декабрь");
		
		var $lstDay;
		var $lstMonth;
		var $lstYear;
		
		var $postfix;
		var $tplName = "default_date_list.tpl.html";
		
		function ListDate ($tpl, $tplName, $postfix, $withSubmit = false, $yearFilter = false, $monthFilter = false, $dayFilter = false){
			$this->Object("ListDate");
			$this->ListClass($tpl);
			if($tplName){
				$this->tplName = $tplName;
			}
			$this->postfix = $postfix;
			$this->lstDay = new ListNum ($this->tpl, 1, 31, date('d'), 'day'.$this->postfix, $dayFilter, $withSubmit);
			$this->lstMonth = new ListArray ($this->tpl, $this->monthes, date('m'), 'month'.$this->postfix, $monthFilter, $withSubmit);
			$this->lstYear = new ListNum ($this->tpl, 1900, 2100, date('Y'), 'year'.$this->postfix, $yearFilter, $withSubmit);
		}
		
		function outList($date = 0){
			if($date){
				$date = explode("-", $date);
			}
			
			$this->tpl->define(array("date_".$this->postfix => $this->getTplName($this->tplName)));
			$tplData = array(
				"YEAR_LIST" => $this->lstYear->outSelect($date[0]),
				"MONTH_LIST" => $this->lstMonth->outSelect($date[1]),
				"DAY_LIST" => $this->lstDay->outSelect($date[2]));
			$this->tpl->assign($tplData);
			$this->tpl->parse ( "DATE_".$this->postfix, "date_".$this->postfix );
			return $this->tpl->fetch( "DATE_".$this->postfix );
		}
		
		function getValue($data){
			$year = 'year_'.$this->postfix;
			$month = 'month_'.$this->postfix;
			$day = 'day_'.$this->postfix;
			return $data[$year]."-".$this->monthes[$data[$month]]."-".$data[$day];
		}
	}//END OF CLASS lst_date


?>