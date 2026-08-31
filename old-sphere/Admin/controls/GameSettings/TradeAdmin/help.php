<?php

$action = $_GET['action'];
//echo $action;
$nbsp = "<br>";
                
switch ($action)
{
        case 'ArtefactPrice':
                //$nbsp = "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                $title = 'Artefact Price';
                $help = "Изменение цен на артефакты.$nbsp$nbsp
                				Дата - время последнего редактирования цены.$nbsp
                                History - история цен для данного артефакта.$nbsp";
                break;
                
        case 'TradeType':
                //$nbsp = "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                $title = 'Trade Type';
                $help = "Изменение налога и трейда на ресурсы, юниты или артефакты<br><br>
                 			Можно менять любой из параметров сразу для всего столбца,
                            для этого ввести новое значение(значения) в строке 'EDIT ALL COLUMN' и нажать кнопку Edit.$nbsp 
                       		Светло-зеленым цветом выделены те сочетания ресурсов, где отмечен флажек 'IsAllowed',
                            то есть данный продукт можно продавать за данный ресурс или деньги.$nbsp 
                            Розовым цветом выделены те сочетания ресурсов, где флажек 'IsAllowed' не отмечен,
                            то есть данный продукт нельзя продавать за данный ресурс или деньги.$nbsp$nbsp
                            	Дата - время последнего редактирования$nbsp
                                Trade - допустимое отклонение от установленного курса в процентах$nbsp
                                Tax - величина налога$nbsp
                                History - история курсов 
                                ";
                break;  
                
        case 'UnitPrice':
                //$nbsp = "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                $title = 'Unit Price';
                $help = "Изменение цен на юниты.<br><br>
                		Дата - время последнего редактирования цены<br>
                        History - история цен для данного юнита<br>";
                break;

        case 'ResourcePrice':
                //$nbsp = "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                $title = 'Resource Price';
                $help = "Изменение цен на ресурсы.<br><br>
                		<b>Дата</b> - время последнего редактирования цены<br>
               			 <b>History</b> - история цен для данного ресурса<br>";
                break;
                
        case 'TradeLog':
                //$nbsp = "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                $title = 'Trade Log';
                $help = "Просмотр всех торговых операций,$nbsp
                        с возможностью экспорта в файл .csv$nbsp
                        <br>
								<b>Поля:</b><br>
               					 <b>Start date</b> - время выставления на продажу$nbsp
                                <b>End date</b> - время покупки$nbsp
                                <b>Customer</b> - продавец$nbsp
                                <b>Customer comb</b> - сота продавца$nbsp
                                <b>Seller</b> - покупатель$nbsp
                                <b>Seller comb</b> - сота покупателя$nbsp
                                <b>Sell</b> - что и сколько продали$nbsp
                                <b>Sell for</b> - за что и за сколько продали$nbsp
                                <b>Tax</b> - налог$nbsp
                                <b>Scope</b> - скоуп$nbsp
                                <b>Counted course</b> - курс продажи$nbsp
                                <b> course</b> - курс продажи$nbsp
                                <b>Course difference</b> - разница между установленным и фактическим курсами в процентах$nbsp
                                <b>Course recount</b> - включение/исключение операции из расчета курсов$nbsp";
                                
                break;
                
        case 'TradeStatistic':
                $title = 'Trade Statistic';
                 $help = "Просмотр количества проданных артефактов, юнитов или ресурсов за определенный промежуток времени<br>";
                break;

        case 'MoneyLog':
                //$nbsp = "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                $title = 'Money Log';
                $help = "Просмотр всех операций с серебряными и золотыми кредитами$nbsp
                 		<br>
								<b>Поля:</b><br>
								<b>ID list</b> - номер операции$nbsp
                                <b>User from</b> - пользовотель, который перевел деньги$nbsp
                                <b>User to</b> - пользовотель, которому были переведены деньги$nbsp
                                <b>Date</b> - дата совершения операции$nbsp
                                <b>Action</b> - операция$nbsp
                                <b>GC</b> - количество золотых кредитов$nbsp
                                <b>SC</b> - количество серебряных кредитов$nbsp
                                ";
                break;
        
        default:
                $help = "Неверный запрос";
                
                
}
echo "<center><h2><font>$title</font></h2></center>";
echo $help;


?>
