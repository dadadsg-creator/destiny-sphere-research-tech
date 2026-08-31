<?
set_time_limit(0);
require_once '../Main.php';

$emails = array("maxim@destinysphere.com", "dmitri@destinysphere.com",
    "vladislav@destinysphere.com");

$queries = array(
    "totalLogins" => "select count(*) as num from ds_loginhistory where lastvisit",
    "uniqueLogins" => "select count(distinct id_user) as num from ds_loginhistory where lastvisit",
    "totalRegs" => "select count(*) as num from ds_user where registrationdate",
    "activeRegs" => "select count(*) as num from ds_user where activate_mail=1 and registrationdate"
);

$multiWorld = array(
    "shopNum" => "select count(*) as num from ds_paymentserviceslog where paid_date",
    "shopAmount" => "select sum(gc_price)*1000+sum(sc_price) as num from ds_paymentserviceslog where paid_date",
    "battles" => "select count(*) as num from ds_battle where battlestartdate"
);

$periods = array(
    "Day2" => " between now() - interval '2 days' and now() - interval '1 day'",
    "Day" => " > now() - interval '1 day'",
    "Week2" => " between now() - interval '2 weeks' and now() - interval '1 week'",
    "Week" => " > now() - interval '1 week'",
    "Month2" => " between now() - interval '2 months' and now() - interval '1 month'",
    "Month" => " > now() - interval '1 month'"
);

    
$out = [];
$perc = [];
$common = DS_Common::CreateForCurentDB();

foreach ($queries as $qkey=>$q) {
    reset ($periods);
    foreach($periods as $pkey=>$p) {
        $res = $common->GetResultArray("", $q.$p);
        $out[$qkey.$pkey] = $res[0]['num'];
    }
}

foreach ($GLOBALS['dbWorldAccess'] as $dbId=>$db) {
    $common->setDbId($dbId);
    reset ($multiWorld);
    foreach ($multiWorld as $qkey=>$q) {
        reset ($periods);
        foreach($periods as $pkey=>$p) {
            $res = $common->GetResultArray("", $q.$p);
            $out[$qkey.$pkey] += $res[0]['num'];
        }
    }
}

foreach (array_merge ($queries, $multiWorld) as $qkey=>$q) {
    reset ($periods);
    foreach($periods as $pkey=>$p) {
        if (strpos($pkey, "2") === FALSE) {
            $perc[$qkey.$pkey] = ($out[$qkey.$pkey] / $out[$qkey.$pkey."2"] - 1) * 100;
            if ($perc[$qkey.$pkey] >= 0) 
                $perc[$qkey.$pkey] = sprintf('<font color="green">+%.2f%%</font>', $perc[$qkey.$pkey]);
            else
                $perc[$qkey.$pkey] = sprintf('<font color="red">%.2f%%</font>', $perc[$qkey.$pkey]);
        }
    }
}

$billingDay = explode(";", file_get_contents("https://billing.destinysphere.ru/gate/paymentNum.php?p=day&v=".VERSION_NAME));
$billingWeek = explode(";", file_get_contents("https://billing.destinysphere.ru/gate/paymentNum.php?p=week&v=".VERSION_NAME));
$billingMonth = explode(";", file_get_contents("https://billing.destinysphere.ru/gate/paymentNum.php?p=month&v=".VERSION_NAME));

$arpuDayPercent = (((1+$billingDay[3]/100) / ($out['uniqueLoginsDay'] / $out['uniqueLoginsDay2'])) - 1) * 100;
$arpuCoreDayPercent = (((1+$billingDay[3]/100) / (($out['uniqueLoginsDay'] - $out['totalRegsDay']) / ($out['uniqueLoginsDay2'] - $out['totalRegsDay2']))) - 1) * 100;
$arpuWeekPercent = (((1+$billingWeek[3]/100) / ($out['uniqueLoginsWeek'] / $out['uniqueLoginsWeek2'])) - 1) * 100;
$arpuCoreWeekPercent = (((1+$billingWeek[3]/100) / (($out['uniqueLoginsWeek'] - $out['totalRegsWeek']) / ($out['uniqueLoginsWeek2'] - $out['totalRegsWeek2']))) - 1) * 100;
$arpuMonthPercent = (((1+$billingMonth[3]/100) / ($out['uniqueLoginsMonth'] / $out['uniqueLoginsMonth2'])) - 1) * 100;
$arpuCoreMonthPercent = (((1+$billingMonth[3]/100) / (($out['uniqueLoginsMonth'] - $out['totalRegsMonth']) / ($out['uniqueLoginsMonth2'] - $out['totalRegsMonth2']))) - 1) * 100;


$sql = "insert into ds_daily_stat
    (date, totalLogins, uniqueLogins, totalRegs, activeRegs, conversion, shopNum, shopAmount, battles, billingNum, billingAmount)
    values (
        now(),
        {$out['totalLoginsDay']},
        {$out['uniqueLoginsDay']},
        {$out['totalRegsDay']},
        {$out['activeRegsDay']},
        ".($out['activeRegsDay'] / $out['totalRegsDay'] * 100).",
        {$out['shopNumDay']},
        ".(int)$out['shopAmountDay'].",
        {$out['battlesDay']},
        {$billingDay[0]},
        ".(int)$billingDay[2]."
     )";
$common->setDbId(0);
$common->executeQuery($sql);

ob_start();

// ===========================================================================
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
</head>
<body>
<table border="1" cellspacing="0" cellpadding="4">
    <th></th>
    <th>За вчера</th>
    <th>За неделю</th>
    <th>За месяц</th>
<tr>
    <td>Всего логинов</td>
    <td><?printf ("%d (%s)", $out['totalLoginsDay'], $perc['totalLoginsDay'])?></td>
    <td><?printf ("%d (%s)", $out['totalLoginsWeek'], $perc['totalLoginsWeek'])?></td>
    <td><?printf ("%d (%s)", $out['totalLoginsMonth'], $perc['totalLoginsMonth'])?></td>
</tr>
<tr>
    <td>Уникальные логины</td>
    <td><?printf ("%d (%s)", $out['uniqueLoginsDay'], $perc['uniqueLoginsDay'])?></td>
    <td><?printf ("%d (%s)", $out['uniqueLoginsWeek'], $perc['uniqueLoginsWeek'])?></td>
    <td><?printf ("%d (%s)", $out['uniqueLoginsMonth'], $perc['uniqueLoginsMonth'])?></td>
</tr>
<tr>
    <td>Регистрации всего</td>
    <td><?printf ("%d (%s)", $out['totalRegsDay'], $perc['totalRegsDay'])?></td>
    <td><?printf ("%d (%s)", $out['totalRegsWeek'], $perc['totalRegsWeek'])?></td>
    <td><?printf ("%d (%s)", $out['totalRegsMonth'], $perc['totalRegsMonth'])?></td>
</tr>
<tr>
    <td>Регистрации активные</td>
    <td><?printf ("%d (%s)", $out['activeRegsDay'], $perc['activeRegsDay'])?></td>
    <td><?printf ("%d (%s)", $out['activeRegsWeek'], $perc['activeRegsWeek'])?></td>
    <td><?printf ("%d (%s)", $out['activeRegsMonth'], $perc['activeRegsMonth'])?></td>
</tr>
<tr>
    <td>Конверсия (активные реги/всего)</td>
    <td><?printf ("%.2f%% (%.2f%%)", $out['activeRegsDay'] / $out['totalRegsDay'] * 100, $out['activeRegsDay'] / $out['totalRegsDay'] * 100 - $out['activeRegsDay2'] / $out['totalRegsDay2'] * 100)?></td>
    <td><?printf ("%.2f%% (%.2f%%)", $out['activeRegsWeek'] / $out['totalRegsWeek'] * 100, $out['activeRegsWeek'] / $out['totalRegsWeek'] * 100 - $out['activeRegsWeek2'] / $out['totalRegsWeek2'] * 100)?></td>
    <td><?printf ("%.2f%% (%.2f%%)", $out['activeRegsMonth'] / $out['totalRegsMonth'] * 100, $out['activeRegsMonth'] / $out['totalRegsMonth'] * 100 - $out['activeRegsMonth2'] / $out['totalRegsMonth2'] * 100)?></td>
</tr>
<tr>
    <td>Покупки в магазине (количество)</td>
    <td><?printf ("%d (%s)", $out['shopNumDay'], $perc['shopNumDay'])?></td>
    <td><?printf ("%d (%s)", $out['shopNumWeek'], $perc['shopNumWeek'])?></td>
    <td><?printf ("%d (%s)", $out['shopNumMonth'], $perc['shopNumMonth'])?></td>
</tr>
<tr>
    <td>Покупки в магазине (сумма в серебре)</td>
    <td><?printf ("%d (%s)", $out['shopAmountDay'], $perc['shopAmountDay'])?></td>
    <td><?printf ("%d (%s)", $out['shopAmountWeek'], $perc['shopAmountWeek'])?></td>
    <td><?printf ("%d (%s)", $out['shopAmountMonth'], $perc['shopAmountMonth'])?></td>
</tr>
<tr>
    <td>Количество боёв</td>
    <td><?printf ("%d (%s)", $out['battlesDay'], $perc['battlesDay'])?></td>
    <td><?printf ("%d (%s)", $out['battlesWeek'], $perc['battlesWeek'])?></td>
    <td> - </td>
</tr>
<tr>
    <td>Покупки в биллинге (количество)</td>
    <td><?printf ("%d (%s)", $billingDay[0], $billingDay[1])?></td>
    <td><?printf ("%d (%s)", $billingWeek[0], $billingWeek[1])?></td>
    <td><?printf ("%d (%s)", $billingMonth[0], $billingMonth[1])?></td>
</tr>
<tr>
    <td>Покупки в биллинге (сумма в рублях)</td>
    <td><?printf ("%d (%s)", $billingDay[2], $billingDay[3] >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $billingDay[3]) : sprintf('<font color="red">%.2f%%</font>', $billingDay[3]))?></td>
    <td><?printf ("%d (%s)", $billingWeek[2], $billingWeek[3] >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $billingWeek[3]) : sprintf('<font color="red">%.2f%%</font>', $billingWeek[3]))?></td>
    <td><?printf ("%d (%s)", $billingMonth[2], $billingMonth[3] >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $billingMonth[3]) : sprintf('<font color="red">%.2f%%</font>', $billingMonth[3]))?></td>
</tr>
<tr>
    <td>ARPU (в рублях)</td>
    <td><?printf ("%.2f (%s)", $billingDay[2]/$out['uniqueLoginsDay'], $arpuDayPercent >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $arpuDayPercent) : sprintf('<font color="red">%.2f%%</font>', $arpuDayPercent))?></td>
    <td><?printf ("%.2f (%s)", $billingWeek[2]/$out['uniqueLoginsWeek'], $arpuWeekPercent >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $arpuWeekPercent) : sprintf('<font color="red">%.2f%%</font>', $arpuWeekPercent))?></td>
    <td><?printf ("%.2f (%s)", $billingMonth[2]/$out['uniqueLoginsMonth'], $arpuMonthPercent >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $arpuMonthPercent) : sprintf('<font color="red">%.2f%%</font>', $arpuMonthPercent))?></td>
</tr>
<tr>
    <td>ARPU ядро (в рублях)</td>
    <td><?printf ("%.2f (%s)", $billingDay[2]/($out['uniqueLoginsDay'] - $out['totalRegsDay']), $arpuCoreDayPercent >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $arpuCoreDayPercent) : sprintf('<font color="red">%.2f%%</font>', $arpuCoreDayPercent))?></td>
    <td><?printf ("%.2f (%s)", $billingWeek[2]/($out['uniqueLoginsWeek'] - $out['totalRegsWeek']), $arpuCoreWeekPercent >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $arpuCoreWeekPercent) : sprintf('<font color="red">%.2f%%</font>', $arpuCoreWeekPercent))?></td>
    <td><?printf ("%.2f (%s)", $billingMonth[2]/($out['uniqueLoginsMonth'] - $out['totalRegsMonth']), $arpuCoreMonthPercent >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $arpuCoreMonthPercent) : sprintf('<font color="red">%.2f%%</font>', $arpuCoreMonthPercent))?></td>
</tr>
<tr>
    <td>Оплативших игроков за период</td>
    <td><?printf ("%d (%s)", $billingDay[4], $billingDay[5] >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $billingDay[5]) : sprintf('<font color="red">%.2f%%</font>', $billingDay[5]))?></td>
    <td><?printf ("%d (%s)", $billingWeek[4], $billingWeek[5] >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $billingWeek[5]) : sprintf('<font color="red">%.2f%%</font>', $billingWeek[5]))?></td>
    <td><?printf ("%d (%s)", $billingMonth[4], $billingMonth[5] >= 0 ? sprintf('<font color="green">+%.2f%%</font>', $billingMonth[5]) : sprintf('<font color="red">%.2f%%</font>', $billingMonth[5]))?></td>
</tr>
<tr>
    <td>ARPPU (в рублях)</td>
    <td><?printf ("%.2f", $billingDay[2]/$billingDay[4])?></td>
    <td><?printf ("%.2f", $billingWeek[2]/$billingWeek[4])?></td>
    <td><?printf ("%.2f", $billingMonth[2]/$billingMonth[4])?></td>
</tr>
</table>
</body>
</html>

<?
//=======================================================================
$body = ob_get_clean();

foreach ($emails as $addr)
    mail($addr, 'Daily stat for '.VERSION_NAME, $body, "Content-Type: text/html");

?>