<?
	/**
	 * Класс для вывода цифрового списка
	 */
	class ListNum extends ListClass {

		/** С какого значения начинаеться писок */
		var $from;

		/** По какое значение список */
		var $to;

		/** Значени по умолчанию */
		var $default;
		
		function ListNum ($tpl, $from, $to, $default, $name, $outFilter = false, $onChangeSubmit = false){
			$this->Object("ListNum");
			$this->ListClass($tpl);
			$this->from = $from;
			$this->to = $to;
			$this->default = $default;
			$this->listName = $name;
			$this->outFilter = $outFilter;
			$this->onChangeSubmit = $onChangeSubmit;
		}

		function getList(){
			$k = 0;
			if($this->from < $this->to){
				for($i = $this->from; $i <= $this->to; $i++){

					if($i == $this->default){
						$selected = 1;
					}else{
						$selected = 0;
					}
	
					if($i < 10){
						$val = "0".$i;
					}else{
						$val = $i;
					}
	
					$rs[$k] = array ("id"=>$i, "name"=>$val, "selected" => $selected);
					$k++;
				}
			}else{
				for($i = $this->from; $i >= $this->to; $i--){
					if($i == $this->default){
						$selected = 1;
					}else{
						$selected = 0;
					}
	
					if($i < 10){
						$val = "0".$i;
					}else{
						$val = $i;
					}
	
					$rs[$k] = array ("id"=>$i, "name"=>$val, "selected" => $selected);
					$k++;
				}
			}
			

			return $rs;
		}

	}//END OF CLASS ListNum

?>