<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

function print_rr ($array) {
	echo "<xmp>";
	print_r ( $array );
	echo "</xmp>";
}


class PlaceBuilding
{
	var $buildings = array();
	var $cellsNumbers = array();
	var $cellsTypes = array();
	var $errorMsg = '';
	var $common;
	var $worldList;

	var $cellnumber;
	var $celltype;
	var $bid;
	var $world;



	function PlaceBuilding()
	{
		if ( !isset ( $GLOBALS["common"] ) )
		{
			$this->common = &ClassFactory::getInstance ( "DS_Common" );
			$this->common->connect();
		}
		$this->getBuildingsList();
		$this->getCellsNumbers();
		$this->getCellsTypes();
		$this->getWorldsList();

		if (!$this->postData($_POST))
		{
			$this->showForm();
		}
	}

	function postData($data)
	{

		if (sizeof($data) <= 0)
		{
			return false;
		}

		print_rr($data);
		$dataTmp = explode('_', $data['world']);
		$data['world'] = $dataTmp[0];

		$dbId = $dataTmp[1];
		$this->common->setDbId($dbId);

		if (!key_exists('cellnumber',$data))
		{
			$this->errorMsg .= "Enter Cell";
			return false;
		}
		if (!key_exists('celltype',$data))
		{
			$this->errorMsg .= "Enter celltype";
			return false;
		}
		if (!key_exists('bid',$data))
		{
			$this->errorMsg .= "Select Build";
			return false;
		}
		if (!key_exists('world',$data))
		{
			$this->errorMsg .= "Select World";
			return false;
		}

		$this->cellnumber = $data['cellnumber'];
		$this->celltype = $data['celltype'];
		$this->bid = $data['bid'];
		$this->world = $data['world'];

		if ($this->checkAction())
		{
			//Если тест прошел нормально, и нет ни одного построенного здания на этой ячейке
			//тогда инициируем постройка здания.

			if ( $this->perfomAction() )
			{
				print ShowCMSMessage('Операция выполнена успешно',1);
			}


		} else {
			$this->errorMsg .= "На одной из этих ячеек уже что-то построено!";
			return false;
		}


		return false;
	}


	function perfomAction()
	{
		$this->common->executeQuery ( 'begin;' );

		$sql = "

SELECT 
  cell.id_cell,
  cell.id_comb
FROM
  public.ds_ring r
  INNER JOIN public.ds_combcomplex cc ON (r.id_ring = cc.id_ring)
  INNER JOIN public.ds_comb c ON (cc.id_combcomplex = c.id_combcomplex)
  INNER JOIN public.ds_cell cell ON (c.id_comb = cell.id_comb)
WHERE
  (r.id_world = ".$this->world.") AND 
  (cell.id_celltype = ".$this->celltype.") AND 
  (cell.cellnumber = ".$this->cellnumber.")
		";
		$res = $this->common->getResultArray ( "", $sql,"", "", "" );
//print_rr($res);

		if (sizeof($res)<=0)
		{
			$this->errorMsg = "PlaceBuilding::perfomAction1";
			$this->errorMsg .= " !!! ".$sql;
			return false;
		}

		foreach ($res as $key => $value)
		{
			$out = "
		INSERT INTO ds_attachedbuilding (id_building, id_cell, active, underconstruction, starttime, endtime, inactivation, busy, inupgrade, id_comb)
		VALUES
		('".$this->bid."','".$value['id_cell']."','t','f',now(),now(),'f','f','f','".$value['id_comb']."');
";
			$res = $this->common->executeQuery ( $out );
//			print $out."<br>\n\n";
			if ( $res === false ){
				$this->common->executeQuery ( 'rollback;' );
				$this->errorMsg .= " SYSTEM ERROR - PlaceBuilding::perfomAction2";

				return false;
			}

		}
		if ($this->common->executeQuery ( 'rollback;' ) )
		{
			return true;
		}
		



		return false;
	}


	function showForm()
	{
		if ($this->errorMsg != '')
		{
			print ShowCMSMessage($this->errorMsg, 2);
		}

		print Forms::openForm('PlaceBuilding','','post')."\n";
		print "<table>";
		print "<tr><td>Выберите ячейку:</td><td>".Forms::selectBox($this->cellsNumbers, $_POST['cellnumber'],'cellnumber')."</td></tr>";
		print "<tr><td>Выберите соту:</td><td>".Forms::selectBox($this->cellsTypes, $_POST['celltype'],'celltype')."</td></tr>";
		print "<tr><td>Выберите здание:</td><td>".Forms::selectBox($this->buildings, $_POST['bid'],'bid')."</td></tr>";
		print "<tr><td>Выберите мир:</td><td>".Forms::selectBox($this->worldList, $_POST['world'],'world')."</td></tr>";
		print "<tr><td></td><td>".Forms::submitButton('POST')."</td></tr>";
		print "</table>";
		print Forms::closeForm();

	}


	function checkAction()
	{
		$sql = "
SELECT 
  cell.id_cell,
  cell.id_comb
FROM
  public.ds_ring r
  INNER JOIN public.ds_combcomplex cc ON (r.id_ring = cc.id_ring)
  INNER JOIN public.ds_comb c ON (cc.id_combcomplex = c.id_combcomplex)
  INNER JOIN public.ds_cell cell ON (c.id_comb = cell.id_comb)
  INNER JOIN public.ds_attachedbuilding ab ON (cell.id_cell = ab.id_cell) AND 
    (cell.id_comb = ab.id_comb)
WHERE
  (r.id_world = ".$this->world.") AND 
  (cell.id_celltype = ".$this->celltype.") AND 
  (cell.cellnumber = ".$this->cellnumber.")
			";

		$res = $this->common->getResultArray ( "", $sql,"", "", "" );

		if (sizeof($res)>0)
		{
			return false;
		}

		return true;
	}


	function getBuildingsList()
	{
		//тут вносим здания которые доступны для установки на все соты зарегистрированных игроков.
		$array = array('RING_GATE');

		$sql = "SELECT id_building, buildingname FROM ds_building WHERE buildingname in ('".implode("', '",$array)."')";

		$res = $this->common->getResultArray ( "", $sql,"", "", "" );
		if (!$res)
		{
			$this->errorMsg = "PlaceBuilding::getBuildingsList";
			$this->errorMsg .= " !!! ".$sql;
			return false;
		}

		foreach ($res as $key => $value)
		{
			$this->buildings[$value['id_building']] = $value['buildingname'];
		}
	}


	function getWorldsList()
	{
		$sql = "select worldname, id_world from ds_world";

		$res = $this->common->getMultySql ($sql);
		if (!$res)
		{
			$this->errorMsg = "PlaceBuilding::getWorldsList";
			$this->errorMsg .= " !!! ".$sql;
			return false;
		}

		foreach ($res as $key => $value)
		{
			$this->worldList[$value['id_world']."_".$value['dbId']] = $value['worldname'];
		}
	}

	function getCellsNumbers()
	{
		$cells = 217;
		for ($i=1;$i<=$cells;$i++)
		{
			$this->cellsNumbers[$i] = $i;
		}
	}

	function getCellsTypes()
	{
		$sql = "
				SELECT 
					ct.id_celltype,
					ltl.text
				FROM
					ds_celltype ct
					INNER JOIN ds_localizedtext lt ON (ct.id_localizedname = lt.id_localizedtext)
					INNER JOIN ds_localizedtextlanguage ltl ON (lt.id_localizedtext = ltl.id_localizedtext)
				WHERE
					ltl.id_language = ".constant('DEFAULT_LANGUAGE')."
		";

		$res = $this->common->getResultArray ( "", $sql,"", "", "" );
		if (!$res)
		{
			$this->errorMsg = "PlaceBuilding::getBuildingsList";
			$this->errorMsg .= " !!! ".$sql;
			return false;
		}

		foreach ($res as $key => $value)
		{
			$this->cellsTypes[$value['id_celltype']] = $value['text'];
		}

	}

}

$z = new PlaceBuilding();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>