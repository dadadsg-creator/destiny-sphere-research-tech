<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_RequestStatOutForm
{
	static public function OutNewForm(SU_RequestStatEntityInputParams $params)
	{
		self::_out('<h1>Request Log</h1>');
		

		self::OutForm4 ();

		self::_out('<br>');
		self::_out('<br>');
		self::_out('<hr>');
		
		self::OutForm1 ();

		self::_out('<br>');
		self::_out('<br>');
		self::_out('<hr>');

		self::OutForm2 ();

		self::_out('<br>');
		self::_out('<br>');
		self::_out('<hr>');

		self::OutForm5 ();
		
		self::_out('<br>');
		self::_out('<br>');
		self::_out('<hr>');

		self::OutForm4 ();
	}

	static public function OutForm1 ()
	{
		self::_out('<h3>Choose world and name of the player:</h3>');

		self::_out('<form action="'.$_SERVER['PHP_SELF'].'" method="POST">');

		self::_out('<select name="WORLD">');

		$worldsList = SU_RequestStatSearch::GetWorldList();
		
		foreach ($worldsList as $k=>$worldData)
		{
			self::_out("<option value='".$worldData['worldname']."-".$worldData['dbId']."-".$worldData['id_world']."'>");
			self::_out($worldData['worldname']);
			self::_out('</option>');
		}
		self::_out('</select>');

		self::_out("<input type=text name='USER_NAME' value='User name'>");

		//Выводим меню выбора времени
		self::OutMenuDate ();

		self::_out('<input type="hidden" name="ACTION" value="worldandname">');
		self::_out('<input type="submit" name="SUBMIT" value="Show">');

		self::_out('</form>');
	}

	static public function OutForm2 ()
	{
		self::_out('<h3>Choose world:</h3>');

		self::_out('<form action="'.$_SERVER['PHP_SELF'].'" method="POST">');

		self::_out('<select name="WORLD">');
		$worldsList = SU_RequestStatSearch::GetWorldList();
		foreach ($worldsList as $k=>$worldData)
		{

			self::_out("<option value='".$worldData['worldname']."-".$worldData['dbId']."-".$worldData['id_world']."'>");
			self::_out($worldData['worldname']);
			self::_out('</option>');
		}
		self::_out('</select>');

		//Выводим меню выбора времени
		self::OutMenuDate ();

		self::_out('<input type="hidden" name="ACTION" value="world">');
		self::_out('<input type="submit" name="SUBMIT" value="Show">');
		self::_out('</form>');
	}

	static public function OutForm3 ()
	{
		self::_out('<h3>Choose database:</h3>');

		self::_out('<form action="'.$_SERVER['PHP_SELF'].'" method="POST">');

		self::_out('<select name="DB_ID">');

		foreach ($GLOBALS['dbWorldAccess'] as $k=>$worldAccess)
		{
			self::_out('<option value="'.$k."_".$worldAccess['server'].'">');
			self::_out($worldAccess['server']);
			self::_out('</option>');
		}

		self::_out('</select>');

		//Выводим меню выбора времени
		self::OutMenuDate ();

		self::_out('<input type="hidden" name="ACTION" value="database">');
		self::_out('<input type="submit" name="SUBMIT" value="Show">');
		self::_out('</form>');
	}

	static public function OutForm4 ()
	{
		self::_out('<h3>Log on worlds</h3>');

		self::_out('<form action="'.$_SERVER['PHP_SELF'].'" method="POST">');

		//Выводим меню выбора времени
		self::OutMenuDate ();

		self::_out('<input type="hidden" name="ACTION" value="allworld">');
		self::_out('<input type="submit" name="SUBMIT" value="Show">');
		self::_out('</form>');
	}
	
	static public function OutForm5 ()
	{
		self::_out('<h3>Show all query by user</h3>');

		self::_out('<form action="'.$_SERVER['PHP_SELF'].'" method="POST">');
		self::_out('<select name="WORLD">');

		$worldsList = SU_RequestStatSearch::GetWorldList();
		
		foreach ($worldsList as $k=>$worldData)
		{
			self::_out("<option value='".$worldData['worldname']."-".$worldData['dbId']."-".$worldData['id_world']."'>");
			self::_out($worldData['worldname']);
			self::_out('</option>');
		}
		self::_out('</select>');

		self::_out("<input type=text name='USER_NAME' value='User name'>");
		
		//Выводим меню выбора времени
		self::OutMenuDate ();

		self::_out('<input type="hidden" name="ACTION" value="queryUser">');
		self::_out('<input type="submit" name="SUBMIT" value="Show">');
		self::_out('</form>');
	}
	

	function _out($par)
	{
		echo $par;
	}

	//Функция возвращает ассоциативный массив, содержащий информацию о дате,
	//с которой начинаются записи в базе данных
	/*function GetBeginDate()
	{
		$minDate = SU_RequestStatSearch::GetMinDate();
		//print_r($minDate);
		$beginDate = getdate ($minDate);
		return $beginDate;
	}*/

	//Функция выводит меню выбора времени
	function OutMenuDate()
	{
		//$beginDate = self::GetBeginDate();

		$Date = time() - 604800;
		$beginDate = getdate($Date);

		self::_out("<br>");
		self::_out("<br>");
		self::_out("From ");
		self::_out("<input type=text name='DAY' value=".$beginDate['mday']." size=1>");
		self::_out("<input type=text name='MONTH' value=".$beginDate['mon']." size=1>");
		self::_out("<input type=text name='YEAR' value=".$beginDate['year']." size=4>");

		self::_out(" to ");
		self::_out("<input type=text name='DAY2' value=".date(d)." size=1>");
		self::_out("<input type=text name='MONTH2' value=".date(m)." size=1>");
		self::_out("<input type=text name='YEAR2' value=".date(Y)."  size=4>");
	}
}

?>

