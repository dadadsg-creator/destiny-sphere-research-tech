<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

$common = DS_Common::CreateForCurentDB(0);
$sql = "select extract(epoch from date)-86400 as stamp, * from ds_daily_stat order by date";
$res = $common->getResultArray("", $sql);
$arr = array(
    'totallogins' => array('title'=>'Всего логинов', 'data'=>array(), 'tipDigits'=>0),
    'uniquelogins' => array('title'=>'Уникальные логины', 'data'=>array(), 'tipDigits'=>0),
    'totalregs' => array('title'=>'Всего регистраций', 'data'=>array(), 'tipDigits'=>0),
    'activeregs' => array('title'=>'Активные регистрации', 'data'=>array(), 'tipDigits'=>0),
    'conversion' => array('title'=>'Конверсия', 'data'=>array(), 'tipDigits'=>2),
    'battles' => array('title'=>'Количество битв', 'data'=>array(), 'tipDigits'=>0),
    //'shopnum' => array('title'=>'Количество покупок в кабинете', 'data'=>array(), 'tipDigits'=>0),
    'shopamount' => array('title'=>'Сумма покупок в кабинете (в серебре)', 'data'=>array(), 'tipDigits'=>0),
    //'billingnum' => array('title'=>'Количество покупок в биллинге', 'data'=>array(), 'tipDigits'=>0),
    'billingamount' => array('title'=>'Сумма покупок в биллинге (в рублях)', 'data'=>array(), 'tipDigits'=>0),
    'arpu' => array('title'=>'ARPU (в рублях)', 'data'=>array(), 'tipDigits'=>2)
);
foreach ($res as $row) {
    foreach ($row as $key=>$val) {
        if ($key=='stamp' || $key=='date' || $key=='shopnum' || $key=='billingnum') continue;
        $arr[$key]['data'][] = '['.($row['stamp']*1000).','.$val.']';
    }
    $arr['arpu']['data'][] = '['.($row['stamp']*1000).','.($row['uniquelogins']?$row['billingamount']/$row['uniquelogins']:0).']';
}
foreach ($arr as $key=>$row)
    $arr[$key]['data'] = '['.implode(',', $row['data']).']';

?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Destiny Sphere Charts</title>

        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <script type="text/javascript">
<? foreach ($arr as $key=>$data) { ?>
$(function() {
    $('#<?=$key?>').highcharts('StockChart', {
        title : {
            text : '<?=$data['title']?>'
        },
        rangeSelector : {
            selected : 0
        },
        series : [{
            name : 'Значение',
            data : <?=$data['data']?>,
            type : 'areaspline',
            tooltip : {
                valueDecimals : <?=$data['tipDigits']?>
            },
            fillColor : {
                linearGradient : {
                    x1: 0, 
                    y1: 0, 
                    x2: 0, 
                    y2: 1
                },
                stops : [[0, Highcharts.getOptions().colors[0]], [1, 'rgba(0,0,0,0)']]
            }
        }]
    });
});
<? } ?>

        </script>
    </head>
    <body>
<script src="highstock.js"></script>
<table><tr>
<?  $i = 0;
    foreach ($arr as $key=>$data) {
        $i++;
        if ($i%2) echo '</tr><tr>';
?>
<td id="<?=$key?>" style="height: 400px;"></td>
<? } ?>
</tr></table>
    </body>
</html>
