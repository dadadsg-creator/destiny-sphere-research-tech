<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2007/07/06 12:30:53 $';

require_once('../Main.php');

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

$a = new SU_CollectWorlds();

$sapi = php_sapi_name();

//Если работаем из браузера
if( $sapi == "apache" )
{
    if (isset($_GET) && sizeof($_GET)>0 && key_exists('servers', $_GET))
    {
        $servers = $_GET['servers'];

        if(!$servers)
        {
            $servers = false;
        }
        
        echo "<xmp>";
        
        $a->allowBases = $servers;
        $a->collect();
        
        echo "</xmp>";
        
    } else {
        
        //Показываем список миров, по которым будем делать коллекции
        $out = '<h1>CollectWorlds</h1>';
        $out .= '<form method=GET action="'.$_SERVER["PHP_SELF"].'">';
        $out .= "<table border=1>";
        $out .= "<tr><td>&nbsp;</td><td>DBNAME</td></tr>";

        foreach ($GLOBALS['dbWorldAccess'] as $key => $value)
        {
            $out .= "<tr>";
            $out .= "<td><input type=\"checkbox\" name=\"servers[]\" value=\"".$key."\"></td>";
            $out .= "<td>".$value['server']."</td>";
            $out .= "</tr>";
        }
        $out .= '<tr>
                    <td colspan=2><input type="submit" name="delete" value="Collect bases" /></td>
                </tr>';
        $out .= '</table>';
        $out .= '</form>';

        echo $out;
    }
} else { //Если рбоатем из консоли
    
    //Проверяем есть ли хотябы один элемент, и количество элементов не должно быть менее 1
    if(isset($_SERVER['argv'][1]) && sizeof($_SERVER['argv']) > 1)
    {
        foreach ($_SERVER['argv'] as $key => $value)
        {
            if ($key == 0) continue;
            $servers[] = $value;
        }
    } else {
        // иначе собираем все базы.
        foreach($GLOBALS['dbWorldAccess'] as $dbId => $db)
        {
            $servers[] = $dbId;
        }
    }
    
    $a->allowBases = $servers;
    $a->collect();
}


?>