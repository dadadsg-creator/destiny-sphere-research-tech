<?php

class UC_ControlPaymentServices implements UC_ControlInterface
{
    public static function Exec(DE_User $user, array $data)
    {
        // Проверка на гейм даун
        if(GameDown())
        {
            header('Location: index_down.html');
            exit;
        }

        // Запоминаем бд и мир в сессии
        $dbIdSession = $_SESSION['dbId'];
        $worldIdSession = $_SESSION['world'];

        // Получаем список платных сервисов
        $services = UC_PaymentServiceBase::GetPaymentServices();
        assert(is_array($services));

        if (key_exists('ps_action', $data) && key_exists($data['ps_action'], $services)) {
            // Выводим конкретную услугу
            $serviceExec = new $services[$data['ps_action']]['header']($user, $data);
            $outData = $serviceExec->Exec();
        } else {
            // Выводим список платных услуг
            foreach ($services as $serviceName => $serviceOptions)
            {
                if ($serviceName == 'PS_BUY_TRAINING_COMPLETION') {
                    continue;
                }
                $activeservices[$serviceOptions['section']][] = $serviceName;
            }

            $outData['PAYMENT_SERVICES'] = $activeservices;
            $outData['MODE'] = 'LIST_PAYMENT_SERVICES';
        }

        // Если еще не послали заголовок
        if (!headers_sent()) {
            // Выводим страничку
            UC_OutUserCabinet::Out('PaymantServices', $outData);
        }

        // Возвращаем на место бд и мир в сессию
        $_SESSION['dbId'] = $dbIdSession;
        $_SESSION['world'] = $worldIdSession;
    }
}

?>