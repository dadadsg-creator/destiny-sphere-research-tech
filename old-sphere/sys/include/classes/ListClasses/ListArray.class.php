<?
	/** 
	 * Класс для вывода списка из массива
	 */
	
	class ListArray extends ListClass{
		
		/** Массив с данными для списка */
		var $data;
		
		/** Индекс в массиве элемента выделенного по умолчанию*/
		var $default;
		
		function ListArray($tpl, $data, $default, $name, $outFilter = false, $onChangeSubmit = false){
			$this->Object("ListArray");
			$this->ListClass($tpl);
			$this->default = $default;
			$this->outFilter = $outFilter;
			$this->onChangeSubmit = $onChangeSubmit;
			$this->listName = $name;
			if(is_array($data)){
				$this->data = $data;
			}else{
				$msg = "Data for list is not array.";
				$this->error->processError($msg);
			}
		}
		
		function getList(){
			$k = 0;
			foreach($this->data as $k=>$v){
				
				if($k == $this->default){
					$selected = 1;
				}else{
					$selected = 0;
				}
				
				$rs[$k] = array ("id"=>$k, "name"=>$v, "selected" => $selected);
				$k++;
			}
			return $rs;
		}
	}//END OF CLASS ListArray


?>