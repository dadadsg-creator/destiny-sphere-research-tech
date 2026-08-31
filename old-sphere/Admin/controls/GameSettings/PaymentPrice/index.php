<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

class PaymentPrice
{
    static private $common;

    static private $dbId = 0;

    static private $price = array();

    static private $action = 'index';

    static public function Exec(array $data)
    {
        /*echo "<font color=white><pre>";
        print_r($data);*/
        self::_getParams($data);

        self::$common = DS_Common::CreateForCurentDB(0);

        echo "<h1>Edit payment servise prise</h2>";

        switch (self::$action)
        {
            case 'index':
                self::_showIndex();
                break;

            case 'editPrise':
                self::_editPrice();
                self::_showIndex();
                break;
        }
    }

    static private function _getParams(array $data)
    {
        if(key_exists('action', $data))
        {
            self::$action = $data['action'];
        }

        if(key_exists('price', $data))
        {
            self::$price = $data['price'];
        }
    }

    static private function _showIndex()
    {
        //Получаем все цены
        $price = self::_getPaymentPrice();

        $str = "
        <form action='' method='POST'>
        <input type='hidden' name='action' value='editPrise'>
        
        <table cellSpacing='0' cellPadding='0'>
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>
                    &nbsp;&nbsp;
                </td>
                <td align=center class='FormText'>
                    Silver
                </td>
                <td>
                    &nbsp;&nbsp;
                </td>
                <td align=center class='FormText'>
                    Gold
                </td>
                <td>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                </td>
                <td align=center class='FormText'>
                    Anabled
                </td>
            </tr>
            ";

        foreach ($price as $k => $data)
        {
            $str .= "
            <tr>
                <td class='FormText'>
                    ".$data['text']."
                </td>
                <td>
                    &nbsp;&nbsp;
                </td>
                <td  class='FormText'>
                    <input type='hidden' name='price[".$data['id_money_action']."][action_name]' value='".$data['action_name']."'>
                    <input type='text' name='price[".$data['id_money_action']."][sc]' value='".$data['sc_price']."' size='10'>  
                </td>
                <td>
                    &nbsp;&nbsp;
                </td>
                <td  class='FormText'>
                    <input type='hidden' name='price[".$data['id_money_action']."][action_name]' value='".$data['action_name']."'>
                    <input type='text' name='price[".$data['id_money_action']."][gc]' value='".$data['gc_price']."' size='10'>  
                </td>
                <td>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                </td>
                <td class='FormText' align=center>";

            if ($data['is_active'] == 't') {
                $checked = 'checked';
            } else {
                $checked = '';
            }

            $str .= "
                    <input type='checkbox' name='price[".$data['id_money_action']."][is_active]' $checked>  
                </td>
            </tr>
            ";

            $_SESSION['ADMIN_PAYMENT_PRICE'][$data['id_money_action']]['is_active'] = $data['is_active'];
            $_SESSION['ADMIN_PAYMENT_PRICE'][$data['id_money_action']]['gc'] = $data['gc_price'];
            $_SESSION['ADMIN_PAYMENT_PRICE'][$data['id_money_action']]['sc'] = $data['sc_price'];
        }
        $str .= "
            <tr>
                <td>
                    &nbsp;&nbsp;
                </td>   
            </tr>   
        </table>
        <input type='submit' value='&nbsp;&nbsp;&nbsp;&nbsp;Edit&nbsp;&nbsp;&nbsp;&nbsp;' class='submit'>";
        $str .= "</form>";

        echo $str;
    }

    static private function _editPrice()
    {
        $cache = DS_Cache::Singleton(); 
        
        $log = "";  
        
        foreach (self::$price as $id_money_action => $priceArr)
        {
            // Смотрим, поменялась ли цена
            if($_SESSION['ADMIN_PAYMENT_PRICE'][$id_money_action]['sc'] != self::$price[$id_money_action]['sc'] || $_SESSION['ADMIN_PAYMENT_PRICE'][$id_money_action]['gc'] != self::$price[$id_money_action]['gc'])
            {
                if (!is_numeric(self::$price[$id_money_action]['sc'])) {
                    ShowCMSMessage('WARNING: Wrong SC price: '.self::$price[$id_money_action]['sc'], 2);
                    continue;
                } elseif (!is_numeric(self::$price[$id_money_action]['gc'])){
                    ShowCMSMessage('WARNING: Wrong GC price: '.self::$price[$id_money_action]['gc'], 2);
                    continue;
                }

                if (self::$price[$id_money_action]['sc'] == 0 && self::$price[$id_money_action]['gc'] == 0) {
                    ShowCMSMessage('WARNING: Wrong price: 0', 2);
                    continue;
                }
                
                if (!self::_UdatePrice((int)$id_money_action, (int)self::$price[$id_money_action]['sc'], (int)self::$price[$id_money_action]['gc']))
                {
                    ShowCMSMessage('Error3!', 2);
                    Exit;
                }
                
                
                $log .= 'Set price for '.self::$price[$id_money_action]['action_name'].' '.self::$price[$id_money_action]['sc'].' SC, '.self::$price[$id_money_action]['gc'].' GC (Old Price '.$_SESSION['ADMIN_PAYMENT_PRICE'][$id_money_action]['sc'].' SC, '.$_SESSION['ADMIN_PAYMENT_PRICE'][$id_money_action]['gc'].' GC)<br>';
                
                // Сбрасываем цену услуги
                $cache->delete("PAYMENT_SERVICES_PRICE_".$id_money_action);
            }
            // Смотрм, поменялась ли активность
            if (key_exists('is_active',  self::$price[$id_money_action])) {
                if ($_SESSION['ADMIN_PAYMENT_PRICE'][$id_money_action]['is_active'] == 'f') {
                    // Включаем
                    if (!self::_Udateactive((int)$id_money_action, 't'))
                    {
                        ShowCMSMessage('Error1!', 2);
                        Exit;
                    }
                    
                    $log .= 'Anabled service '.self::$price[$id_money_action]['action_name'].' <br>';

                    // Сбрасываем кеши по услуге
                    $cache->delete("PAYMENT_SERVICES_".self::$price[$id_money_action]['action_name']);
                }
            } elseif ($_SESSION['ADMIN_PAYMENT_PRICE'][$id_money_action]['is_active'] == 't') {
                // Выключаем
                if (!self::_Udateactive((int)$id_money_action, 'f'))
                {
                    ShowCMSMessage('Error2!', 2);
                    Exit;
                }

                $log .= 'Dasabled service '.self::$price[$id_money_action]['action_name'].' <br>';
                
                // Сбрасываем кеши по услуге
                $cache->delete("PAYMENT_SERVICES_".self::$price[$id_money_action]['action_name']);
            }
            
        }
        
        //Сбрасывем активность
        $cache->delete("PAYMENT_SERVICES");
        
        if (!empty($log)) {
            //Логируем действие
            actionLog( "Edit payment price", $log); 
        }
        
        ShowCMSMessage('OK', 1);
        return;
    }

    static private function _getPaymentPrice()
    {
        $sql = "
            SELECT
                mpp.id_money_action,
                mpp.is_current,
                mat.action_name,
                COALESCE(l.text, mat.action_name) as text,
                mpp.gc_price,
                mpp.sc_price,
                mat.is_active
            FROM
                ds_money_action_types mat
            LEFT JOIN ds_money_payments_price mpp ON (mpp.id_money_action = mat.id_money_action)
            LEFT JOIN ds_localizedtextlanguage l ON (l.id_localizedtext = mat.id_localizedname AND l.id_language = 3)
            WHERE
                mat.have_price = 't'
            AND
                mpp.is_current = 't'
            ORDER BY mat.action_name
            ";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        assert(empty(self::$common->errormsg));

        return $res;
    }

    static private function _UdatePrice($id_money_action, $sc_price, $gc_price)
    {
        assert(is_int($id_money_action) && $id_money_action > 0);
        assert(is_int($sc_price));
        assert(is_int($gc_price));
        assert($sc_price != 0 || $gc_price != 0);

        self::$common->startTrans();

        $sql = "
                UPDATE
                    ds_money_payments_price
                SET
                    is_current = 'f'
                WHERE
                    id_money_action =
                        (SELECT
                            id_money_action
                        FROM
                            ds_money_action_types a
                        WHERE
                            id_money_action = $id_money_action
                        )
                    AND
                    is_current = 't';
                    
                INSERT INTO 
                    ds_money_payments_price 
                        (sc_price, gc_price, id_money_action) 
                    VALUES 
                        ($sc_price, $gc_price, $id_money_action) 
                    
                ";

        $res = self::$common->getResultArray("", $sql, "", "", "");
        if (!empty(self::$common->errormsg)) {
            echo self::$common->errormsg;
            self::$common->closeTrans('ROLLBACK');
            return false;
        }

        //self::$common->closeTrans('ROLLBACK');
        self::$common->closeTrans('COMMIT');
        return true;
    }

    static private function _Udateactive($id_money_action, $active)
    {
        assert(is_int($id_money_action) && $id_money_action > 0);
        assert($active == 't' || $active == 'f');

        self::$common->startTrans();

        $sql = "
                UPDATE ds_money_action_types
                SET is_active = '$active'
                WHERE id_money_action = $id_money_action";
        $res = self::$common->getResultArray("", $sql, "", "", "");
        if (!empty(self::$common->errormsg)) {
            self::$common->closeTrans('ROLLBACK');
            return false;
        }

        self::$common->closeTrans('COMMIT');
        return true;
    }
}

PaymentPrice::Exec($_REQUEST);

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>