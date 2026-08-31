<? $CVS_Version = '$Revision: 1.1 $ ; $Date: 2007/10/24 09:18:44 $';
ini_set('memory_limit', '512M');
require_once('../Main.php');
require_once('files/compare_tables_data.conf');

session_start();

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
include_once('../classes/SU/SU_ExecSQLforComparition.class.php');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<HTML>
 <HEAD>
 <META http-equiv="Content-Type" content="text/html; charset=windows-1251">
  <TITLE> Макрос сравнения баз данных </TITLE>
 </HEAD>
 <BODY>  
<?php

echo "<h1>Compare Table Data</h1>";

if (isset($_REQUEST['MasterBaseChoise']))
{
    $_SESSION['MasterBaseChoise'] = $_REQUEST['MasterBaseChoise'];
}

if (isset($_REQUEST['SlaveBaseChoise']))
{
    $_SESSION['SlaveBaseChoise'] = $_REQUEST['SlaveBaseChoise'];
}

if (isset($_REQUEST['backToStart']))
{
    unset($_REQUEST['action']);
}

if (isset($_REQUEST['backOneStep']))
{
    $_REQUEST['action']='compare';
}



switch ($_REQUEST['action'])
{
    case 'compare':
        echo "<form action='' method=POST>";
        echo "<input type=hidden name=action value=index>";
        echo "<input type=submit value=' Back '>";
        echo "</form>";
        $comparator = new SU_CompareDataController;
        $comparator->exec();
        break;
        
    case 'showSQL': 
        echo 'show SQL';
        $comparator = new SU_ExecSQLforComparition;
        $comparator->showSQL($_SESSION['Script']);
        break;
        
    case 'showSQLsecond':
        $comparator = new SU_CompareDataSqlResult;
        $comparator->showSQLSecond();
        break;
        
    case 'executeSQL':
        sessionContinue();
        $comparator = new SU_ExecSQLforComparition;
        $comparator->executeSQL($_SESSION['Script']);
        break;

    default:

        if (isset($_SESSION['MasterBaseChoise']))
        {
            $selectedMaster = $_SESSION['MasterBaseChoise'];
        } else {
            $selectedMaster = "";
        }
        
        if (isset($_SESSION['SlaveBaseChoise']))
        {
            $selectedSlave = $_SESSION['SlaveBaseChoise'];
        } else {
            $selectedSlave = "";
        }
        
        echo " <SCRIPT LANGUAGE='JavaScript'>   
                  <!-- 
                   function select_all_script()
                   {
                        Count=document.forms[0].elements.length;
                        if (Count)
                        {
                            for (I=0;I<Count;I++)
                            {
                                if (document.forms[0].elements[I].type=='checkbox')
                                {
                                    document.forms[0].elements[I].checked=true;
                                }                   
                            }
                        } 
                   };
                   function unselect_all_script() 
                   {
                        Count=document.forms[0].elements.length;
                        if (Count)
                        {
                            for (I=0;I<Count;I++)
                            {
                                if (document.forms[0].elements[I].type=='checkbox')
                                {
                                    document.forms[0].elements[I].checked=false;
                                }                   
                            }
                        }
                   };
                  //--> 
                 </SCRIPT>";
        
        
        //Заполняем форму
        echo "<FORM ACTION='' METHOD='POST'>";
        echo '<input type="hidden" name="action" value="compare">';

        assert(array_key_exists('List_of_Bases', $GLOBALS));
        $baseList = $GLOBALS['List_of_Bases'];


        echo "</SELECT>";
        echo "<H3>Выберите первую базу (master):</H3>";
        echo "<SELECT NAME=MasterBaseChoise>";
        foreach ($baseList as $name => $data)
        {
            if ($name == $selectedMaster)
            {
                $selected = ' selected ';
            }
            else 
            {
                $selected = "";
            }
            echo "<OPTION VALUE='$name' $selected> $name";
        }
        echo "</SELECT>";
        echo "<H3>Выберите вторую базу (slave):</H3>";
        echo "<SELECT NAME=SlaveBaseChoise>";
        foreach ($baseList as $name => $data)
        {
            if ($name == $selectedSlave)
            {
                $selected = ' selected ';
            }
            else 
            {
                $selected = "";
            }
            echo "<OPTION VALUE='$name' $selected> $name";
        }
        echo "</SELECT> <br> <br> <br>";


        assert(array_key_exists('Tables_to_compare', $GLOBALS));
        $tables = $GLOBALS['Tables_to_compare'];

        //Проверяем, что бы имена полей не повторялись
        foreach ($tables as $tableName => $tableData)
            {
                $countVal = array_count_values($tableData['FeildKey']);
                
                foreach ($countVal as $value => $countValue)
                {
                    if ($countValue != 1)
                    {
                        echo "<font color='red'>В списке таблиц для сравнения в таблице <b>$tableName</b> поле <b>$value</b> повторяется $countValue раза!</font>";
                        exit;
                    }
                }
            }

        echo "Таблицы для сравнения:<br><br>";

        foreach ($tables as $tableName => $data)
        {
            echo '<input type="checkbox" name="tables[]" value="'.$tableName.'" checked>';
            echo $tableName;
            echo "<br>";
        }
        echo "<br>";
        echo "<INPUT TYPE='submit' name='compare' VALUE='Сравнить данные'>&nbsp;&nbsp;&nbsp;";
        echo "<INPUT NAME='OKButton1' TYPE='button' onClick='select_all_script()' VALUE='Выбрать все'> \n";
        echo "<INPUT NAME='OKButton1' TYPE='button' onClick='unselect_all_script()' VALUE='Отказ от всех'> <br>";
        echo "</FORM>";
        
}
?>
 </BODY>
</HTML>