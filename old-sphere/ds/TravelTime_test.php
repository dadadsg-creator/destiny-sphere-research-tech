<?php
ini_set('memory_limit', '16M');
set_time_limit(0);

require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler')

?>

<style>
body {
  font-family: Verdana;
  font-size: 14px;
}

input{
  font-family: verdana;
  font-size: 12px;
}
</style>

<?php
include "TravelTime.php";

$timeStartRotation = 1127419200;
$GraphFlag = 0;



if (isset($_POST['command'])) $command=$_POST['command'];
else $command="";

if(isset($_POST['GraphFlag']))
{
    $GraphFlag = 1;
}

if ( isset($_POST['RingFrom']) ) 
{
	$testRingFrom = (int)$_POST['RingFrom'];
}else{
	$testRingFrom = 1;
}
assert($testRingFrom >= 1 && $testRingFrom <= 4);

if(isset($_POST['RingTo']))
{
	$testRingTo = (int)$_POST['RingTo'];
}else{
	$testRingTo = 1;
}
assert($testRingFrom >= 1 && $testRingFrom <= 4);

if(isset($_POST['SrcComplex'])) 
{ 
	$testComplexStart = (int)$_POST['SrcComplex'];
}else{
	$testComplexStart = 1;
}
assert($testComplexStart >= 1);

$ComplCountFrom =  new DE_Ring($testRingFrom);

if ($testComplexStart > $ComplCountFrom->getComplexCount())
{ 
	echo "Количество комплексов на кольце старта не выше: ".$ComplCountFrom->getComplexCount()."<br />";
    $testComplexStart = $ComplCountFrom->getComplexCount();
}

if ( isset($_POST['DstComplex']) ) 
{ 
	$testComplexFinish=(int)$_POST['DstComplex'];
}else{
	$testComplexFinish = 1;
}

$ComplCountTo =  new DE_Ring($testRingTo);
if ($testComplexFinish > $ComplCountTo->getComplexCount())
    { echo "Количество комплексов на кольце старта не выше: ".$ComplCountFrom->getComplexCount()."<br />";
      $testComplexFinish = $ComplCountTo->getComplexCount(); }

if ( isset($_POST['StartTime']) ) { $testStartTime=$_POST['StartTime'];} else $testStartTime = 0;
if ( isset($_POST['FinishTime']) ) { $testFinishTime=$_POST['FinishTime'];} else $testFinishTime = 12;

if ($testStartTime > $testFinishTime ){ echo "Время финиша не может быть меньше времени начала. Меняем.<br />";
        $temp = $testFinishTime; $testFinishTime = $testStartTime; $testStartTime = $temp; }

if ( isset($_POST['TimeStep']) ) { $testTimeStep = $_POST['TimeStep'];} else $testTimeStep = 600;
if ( $testTimeStep == 0 ) { echo "Некорректный период шага. Установлено значение по умолчанию.<br />";
     $testTimeStep = 600; }

if ( isset($_POST['TurnTime']) ) { $testTurnTime = $_POST['TurnTime'];} else $testTurnTime = 3600;
if ( $testTurnTime <= 0 ) { echo "Некорректное время турна. Установлено значение по умолчанию.<br />";
    $testTurnTime = 3600;}

if ( isset($_POST['TimeLanding']) ) { $testTimeLanding = $_POST['TimeLanding'];} else $testTimeLanding = 1;
if ( $testTimeLanding <= 0 ) { echo "Некорректное время \"площадки\". Установлено значение по умолчанию.<br />";
    $testTimeLanding = 1;}

?>
<style>
body {
  font-family: Verdana;
  font-size: 11px;  
}

input{
  font-family: verdana;
  font-size: 12px;
}
td{
  font-family: verdana;
  font-size: 12px;
}

</style>
<form  method="POST">
C : <select name="RingFrom" class="input">
    <option value="1" <?php if($testRingFrom == 1) echo "selected"; ?>>Ворания</option>
    <option value="2" <?php if($testRingFrom == 2) echo "selected"; ?>>Лиенсорд</option>
    <option value="3" <?php if($testRingFrom == 3) echo "selected"; ?>>Псолеон</option>
    <option value="4" <?php if($testRingFrom == 4) echo "selected"; ?>>Мельсион</option>
    </select>
Комплекс: <input type="text" name="SrcComplex" size="4" class="input" value="<?php echo $testComplexStart;?>"><br />

На: <select name="RingTo" class="input">
    <option value="1" <?php if($testRingTo == 1) echo "selected"; ?> >Ворания</option>
    <option value="2" <?php if($testRingTo == 2) echo "selected"; ?> >Лиенсорд</option>
    <option value="3" <?php if($testRingTo == 3) echo "selected"; ?> >Псолеон</option>
    <option value="4" <?php if($testRingTo == 4) echo "selected"; ?> >Мельсион</option>
    </select>
Комплекс: <input type="text" name="DstComplex" size="4" class="input" value="<?php echo $testComplexFinish; ?>"><br />
</select>
Времена указываются в количестве турнов, прошедших с начала вращения (турн - <input type="text" name="StartTime" size="4" class="input" value="<?php echo $testTurnTime; ?>"> сек)<br />
Выборка с турна  <input type="text" name="StartTime" size="4" class="input" value="<?php echo $testStartTime; ?>"> по турн <input type="text" name="FinishTime" size="4" class="input" value="<?php echo $testFinishTime; ?>"><br />
Шаг выборки (в секундах): <input type="text" name="TimeStep" size="4" class="input" value="<?php echo $testTimeStep; ?>"><br />
Размер "полётной площадки" (в секундах): <input type="text" name="TimeLanding" size="4" class="input" value="<?php echo $testTimeLanding; ?>"><br />
<input type="checkbox" name="GraphFlag" <?php if ($GraphFlag == 1) echo "checked"; ?> /> - Выводить в виде графика<br />
<input type="hidden" name="command" value="calculation" />
<br /><input type=submit  value="Рассчитать"/>
</form><hr>

<?php
if ($command == "calculation")
{
GL_TravelTime::$turnTime = $testTurnTime;
$combFrom = new DE_Comb($testRingFrom, $testComplexStart, 1);
$combTo = new DE_Comb($testRingTo, $testComplexFinish, 1);
GL_TravelTime::$timeLanding = $testTimeLanding;

$timeStart = $testStartTime * $testTurnTime + $timeStartRotation;
$timeFinish= $testFinishTime * $testTurnTime  + $timeStartRotation;
echo "Начало мониторинга: ".($timeStart-$timeStartRotation)." секунд со старта вращения<br />";
echo "Конец мониторинга: ".($timeFinish-$timeStartRotation)." секунд со старта вращения<br />";

if ( $GraphFlag == 0)
{
    echo "<table border=1>";
    echo "<tr><td>Отладочная информация из класса</td><td>Tурн</td><td>Время от начала мониторинга</td><td>Время полёта</td><tr>\n";
    for ($i=$timeStart ; $i <= $timeFinish ; $i=$i+$testTimeStep )
    {
        $startDate = new GB_Date($i);
        echo "<tr><td>";
        $timefly = GL_TravelTime::_count($combFrom, $combTo, $startDate);
        echo "</td>";
        $turnNum = ($i - $timeStartRotation)/$testTurnTime;
        echo "<td>".$turnNum."</td><td>".($i-$timeStartRotation)."</td><td align=right>".PrintFlyTime($timefly)."</td><tr>\n";
    }
    echo "</table>";
}
else
{
    echo "1 пиксел - 1 секунда.<br />";
    echo "Калибровочная шкала - 6 часов.<br />";
    echo "<img src=\"1x1red.gif\" height=2 width=\"360\"><br><br />\n";
    for ($i=$timeStart ; $i <= $timeFinish ; $i=$i+$testTimeStep )
    {
        $startDate = new GB_Date($i);
        $timefly = GL_TravelTime::_count($combFrom, $combTo, $startDate);
        $turnNum = ($i - $timeStartRotation)/$testTurnTime;
        echo "<img src=\"1x1red.gif\" height=1 width=\"".(floor($timefly/60))."\"><br>\n";
    }
}


}

function PrintFlyTime($timefly)
{
 $ts_hour=floor($timefly/3600);
 $ts_min=floor(($timefly-($ts_hour*3600))/60);
 $ts_sec=round(($timefly-($ts_hour*3600))-$ts_min*60);
if ($ts_min < 10 ) $ts_min="0$ts_min";
if ($ts_sec < 10 ) $ts_sec="0$ts_sec";
 return " ".$ts_hour."ч.".$ts_min."м.".$ts_sec."с.";
}
?>
