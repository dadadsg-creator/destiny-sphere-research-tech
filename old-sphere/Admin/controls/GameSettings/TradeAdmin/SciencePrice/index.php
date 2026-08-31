<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

$_SESSION['dbId'] = 0;

class SciencePrice
{
    static private $action = 'index';

    static public function Exec(array $data)
    {
        if (key_exists('action', $data)) {
            self::_chengePrice($data['science']);
        }

        self::_showIndex();
    }

    static private function _showIndex()
    {
        $common = DS_Common::CreateForCurentDB(0);

        // Получаем науки
        $sql = "
            SELECT 
                l.text,
                sb.branchname 
            FROM  
                ds_sciencebranch sb
            LEFT JOIN ds_localizedtextlanguage l ON (sb.id_localizedname = l.id_localizedtext AND l.id_language = 3)
            ";
        $science = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        // Получаем цены
        $sql = "
            SELECT 
                s.sciencename,
                sap.price
            FROM 
                ds_science s
            LEFT JOIN ds_science_acceleration_price sap ON (sap.id_science = s.id_science)
            WHERE 
                s.level > 1
            ";
        $sciencePriceArr = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));

        // Преобразуем массивчик
        foreach ($sciencePriceArr as $key => $data)
        {
            $sciencePrice[$data['sciencename']] = $data['price'];
        }

        echo "<h1>Science Price</h1>
        <form action='' method='POST'>
        <input type='hidden' name='action' value='chenge_price'>
        <input type='submit' class='Submit' value='Change price'><br><br>
            <table border=0 cellspacing=0 cellpadding=0 bgcolor=222233>
                <tr>
                    <td>
                        <table border=0 width=100%  bordercolor=black cellspacing=1 cellpadding=3>
                            <tr>
                                <th class=th_3>
                                    Уровень
                                </th>";
        // Проходимся по каждой науке
        foreach ($science as $scienceKey => $scienceVal) {
            echo "
                                <th class=th_3>
                                    ".$scienceVal['text']."
                                </th>";
        }

        echo "
                            </tr>";
        // Проходимся по каждому уровню науки
        foreach (range(2, 200) as $number) {
            echo "
                            <tr>
                                <td bgcolor='#2A3E66' align='center'>
                                    <font color=#F8F8FF><b>$number</b></font>
                                </td>";
            // Проходимся по каждой науке
            foreach ($science as $scienceKey => $scienceVal) {
                if (key_exists($scienceVal['branchname']."_".$number, $sciencePrice)) {
                    echo "
                                <td bgcolor='#F8F8FF' align='center'>
                                    <input style='text-align: right;' type=text name='science[".$scienceVal['branchname']."_".$number."][price]' size=11 value='".$sciencePrice[$scienceVal['branchname']."_".$number]."'>
                                </td>";
                } else {
                    echo "
                                <td bgcolor='#2A3E66' align='center'>
                                    <font color=#F8F8FF><b>&nbsp</b></font>
                                </td>";
                }

            }
            echo "
                            </tr>";
        }

        echo"
                            </tr>
                        </table>
                    </td>
                <tr>
            </table>
        <br><br><input type='submit' class='Submit' value='Change price'><br>
        </form>";
    }

    static private function _chengePrice($newPrices)
    {
        $common = DS_Common::CreateForCurentDB(0);

        $log = '';
        
        foreach ($newPrices as $science => $price)
        {
            $newPrice = (int)$price['price'];

            // Если в поле цены ничего не введено, пропускаем
            if ($newPrice == 0) {
                continue;
            }
            assert($newPrice > 0);

            // Смотрим, есть ли курс на эту науку
            $sql = "
                SELECT 
                    s.id_science, 
                    sap.price, 
                    sap.id_science_acceleration_price,
                    s.sciencename
                FROM ds_science s
                LEFT JOIN ds_science_acceleration_price sap ON (sap.id_science = s.id_science)
                WHERE 
                    s.sciencename = '".$science."'
                ";
            $priceData = $common->getResultArray("", $sql, "", "", "");
            assert(empty($common->errormsg));
            
            assert(count($priceData) == 1);

            // Если цена уже есть
            if (!empty($priceData[0]['id_science_acceleration_price'])) {
                // Если курс не поменялся
                if ($newPrice == (int)$priceData[0]['price']) {
                    continue;
                }
                
                $oldCource = $priceData[0]['price'];

                // Обновляем курс
                $sql = "
                UPDATE 
                    ds_science_acceleration_price
                SET
                    price = ".$newPrice."
                WHERE
                    id_science = ".$priceData[0]['id_science']."
                ";

                $res = $common->getResultArray("", $sql, "", "", "");
                assert(empty($common->errormsg));

            } else {    
                $oldCource = 'NULL';
                        
                // Вставляем новый курс
                $sql = "
                INSERT INTO 
                    ds_science_acceleration_price
                    (id_science, price)
                VALUES
                    (".$priceData[0]['id_science'].", ".$newPrice.")
                ";
                
                $res = $common->getResultArray("", $sql, "", "", "");
                assert(empty($common->errormsg));
            }
            
            $log .= "Science: ".$priceData[0]['sciencename']."; Old price: ".$oldCource."; New price: ".$newPrice."<br>";
        }
        
        if (!empty($log)) {
            actionLog('Change Scince price', $log);
        }
    }

}

SciencePrice::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>