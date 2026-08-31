<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_UpdateUnitVsUnit
{

	private $common;
	
	private $fileName;
	
	private $transMode = 'ROLLBACK';
	
	public function __construct($data, $files)
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		$this->_getParams($data, $files);
	}
	
	private function _getParams($data, $files)
	{			
		if(key_exists('filename', $files) && key_exists('tmp_name', $files['filename']))
		{
			$this->fileName = $files['filename']['tmp_name'];
			assert(file_exists($this->fileName));
			
			if(key_exists('rollback', $data) && $data['rollback'] == 'on')
			{
				$this->transMode = "ROLLBACK";
				echo "<h1>TEST MODE! NO DATA UPDATED!</h1>";
			} else {
				$this->transMode = "COMMIT";
			}
			
			$this->Update();
		} else {
			$this->_outFirstScreen();
		}
	}
	
	private function _outFirstScreen()
	{
		echo '
			<h1>Select file with UNIT VS UNIT data</h1>
			<form enctype="multipart/form-data" action="" method="post">
				<b>FILE NAME:</b> <input type="file" name="filename">&nbsp;&nbsp;&nbsp;&nbsp;				
			';	
		echo '<input type="submit" value="UPDATE"><br><br>
			<input type="checkbox" name="rollback" checked=1>&nbsp;<b>TEST UPDATE</b><br>
			* no data will be changed
			</form>
			';
	}
	
	public function Update()
	{
		$units = file($this->fileName);

		$this->common->startTrans();
		
		$lineCount = count($units) - 1;
		
		echo "<xmp>\n";
		
		// проходим по всем строкам
		foreach($units as $k => $unit)
		{
			// нулевой ряд - ID юнитов, которых атакуем
			//$unit = trim($unit);
			if($k == 0)
			{
				$toUnitName = explode("\t", $unit);
				$rowCount = count($toUnitName) - 1;
				echo "LINES : $lineCount\t\t\tROWS : $rowCount\n";
				assert($lineCount == $rowCount);
				continue;
			}
			$UnitVsUnit = explode("\t", $unit);	
			
			// проходим по всем столбцам		
			foreach($UnitVsUnit as $l => $toUnit)
			{
				if($l == 0)
				{
					$fromUnitName = $toUnit;
					$fromUnitId = $this->getUnitIdByUnitName($fromUnitName);
					continue;
				}
				
				// получаем ID юнитов по их именам
				echo $fromUnitName."\t=>\t".trim($toUnitName[$l])."";
				
				$toUnitId[$l] = $this->getUnitIdByUnitName(trim($toUnitName[$l]));
				
				// $fromUnitId -> $toUnitId[$l] = $toUnit
				// смотрим есть ли уже запись
				$sql = "
					SELECT
						value
					FROM
						ds_unitvsunit
					WHERE
						id_unitfrom = $fromUnitId
						AND
						id_unitto = ".$toUnitId[$l]."
					";
				
				$res = $this->common->getResultArray("", $sql, "", "", "");
				assert(empty($this->common->errormsg));
				
				$toUnit = str_replace(",", ".", $toUnit);
				
				// если запись есть
				if(count($res))
				{
					// если значение совпадает, то ничего не делаем
					if((float)$res[0]['value'] == $toUnit)
					{
						$update = "=";
						echo "\n$fromUnitId\t\t\t".$toUnitId[$l]."\t\t\t\t$update\t$toUnit\n";
						continue;
					// если не совпадает, то апдейтим	
					} else {
						$sql = "
							UPDATE
								ds_unitvsunit
							SET
								value = $toUnit
							WHERE
								id_unitfrom = $fromUnitId
								AND
								id_unitto = ".$toUnitId[$l]."
							";
						$update = "UP";
					}
				// создаем новую запись	
				} else {
					$sql = "
						INSERT INTO ds_unitvsunit
							(id_unitfrom, id_unitto, value)
						VALUES
							($fromUnitId, ".$toUnitId[$l].", '$toUnit')							
						";	
					$update = "NEW";				
				}
				
				$this->common->executeQuery($sql);
				echo "\n$fromUnitId\t\t\t".$toUnitId[$l]."\t\t\t\t$update\t$toUnit\n";
				assert(empty($this->common->errormsg));
			}
		}	
		echo "</xmp><h1>DATA UPDATED!</h1>";
		$this->common->closeTrans($this->transMode);
	}
	
	public function getUnitIdByUnitName($unitname)
	{
		$sql = "
			SELECT 
				id_unit
			FROM
				ds_unit
			WHERE
				unitname = '".$unitname."'
			";
//echo $sql;		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));
//print_r($res);		
//echo count($res);		
		assert(count($res)); 
		//assert((int)$res[0]['id_unit'] > 0);
		
		return (int)$res[0]['id_unit'];
	}
}

?>