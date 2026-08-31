<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CaprureColony
{
    static public function Leave(DE_Comb $comb)
    {
        assert($comb->getIsCapture());
        
        // Выставляем временную защиту на модифаер
        $maxEndDate = DE_WorldSettings::CreateForCurentWorld()->get('TEMPORARY_DEFEND_AFTER_LEAVE_COLONY');
        $endDate = rand(1, $maxEndDate);
        $endDate = GB_TypeFactory::CreateConstant('GB_Date', time() + $endDate*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME')); //
        $protectedField = DE_CombProtectiveField::CreateFromCombID($comb->getCombID());
        //$protectedField->setProtectiveField(100);
        $protectedField->setTemporaryDefend($endDate);

        // Удялаем квестовых юнитов, если они есть
        DS_Army::DeleteAllQuestUnitOnComb($comb->getCombID());

        // Выполняем все действия
        $idCombTo = self::_Exec($comb, 0, GB_TypeFactory::CreateConstant('GB_Date', time()));

        // Вы покинули колонию %
        $messparams[0] = $comb->getFullName();
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "CAPTURE_COLONY_LEAVE", $messparams);

        assert($idCombTo > 0);

        // Если мы находимся на захваченной колонии, выходим из нее
        if ($_SESSION['comb'] == $comb->getCombID()) {
            $comb = DE_Comb::CreateFromID($idCombTo);

            // меняем координаты в сессии
            $_SESSION["celltype"] = 1;
            $_SESSION["combcomplex"] = $comb->getComplexNumber();
            $_SESSION["ring"] = $comb->getCombRing()->getID();
            $_SESSION['comb'] = $idCombTo;
            $_SESSION["combnumber"] = $comb->getNumber();


            $refr = new UA_Refresh;

            $refr->langId = 1;
            $refr->uiid = $comb->getUserInstanceID();
            $refr->comb = $comb->getID();
            $refr->celltype = 1;
            $refr->combnumber = $comb->getNumber();
            $refr->combcomplex = $comb->getComplexNumber();
            $refr->ring = $comb->getCombRing()->getID();
            $refr->world = 1;
            $refr->noscan = 1;
            $refr->sysmsg = "";

            $notCellInfo = false;

            $_SESSION["complexview"] = "";
            $_SESSION["complex_ring"] = "";
            $_SESSION["complex_complex"] = "";

            $userName = $_SESSION['s_login'];
            $combData = DS_Comb::GetByID($_SESSION['comb']);
            $combCoords = $combData['combname'].'('.$combData['ringnumber'].'.'.$combData['complexnumber'].'.'.$combData['combnumber'].')';
            $worldData = DS_World::GetByID($_SESSION['world']);
            $worldName = $worldData['worldname'];


            $textD = "nick=Имя: $userName&complex=Сота: $combCoords&world=Мир: $worldName";

            SU_OutputHandler::Singleton()->addReadyString($textD);

            $refr->performAction($notCellInfo, 1 , 1, 1);

            return true;
        }
    }

    static public function Capture(DE_Comb $comb, DE_UserInstance $userInctance, GB_Date $endBattleDate)
    {
        assert($comb->getIsCapture());

        // Включаем защиту новичка
        $endDate = GB_TypeFactory::CreateConstant('GB_Date', $endBattleDate->value + DE_WorldSettings::CreateForCurentWorld()->get('TEMPORARY_DEFEND')*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME')); //  <-> 21
        $defend = DE_CombProtectiveField::CreateFromCombID($comb->getCombID());
        $defend->setTemporaryDefend($endDate);

        // Выставляем защиту 0%
        $defend->setProtectiveField(0);

        // Отправляем системные сообщения
        $messparams = array();
        $messparams[0] = $comb->getFullName();
        $messparams[1] = $endDate->getSystemMessageFormat();

        //Поздравляем! Вы захватили колонию %. Сейчас колония находится под временной защитой, которая продлится до %. Перейти на колонию Вы можете по кнопке "Колонии"
        DE_SystemMessages::AddMessageFromMessageCompiler($endBattleDate, $userInctance->getID(), 'CAPTURE_COLONY_GRATULATION', $messparams, 1, 'RED');

        // Если бывший владелец не DestinySphere
        if ($comb->getUserInstanceID() != 0) {
            $messparams = array();
            $messparams[0] = $userInctance->getUser()->getName();
            $messparams[1] = $comb->getFullName();
            // К сожалению игрок % захватил колонию %, которая принадлежала Вам.
            DE_SystemMessages::AddMessageFromMessageCompiler($endBattleDate, $comb->getUserInstanceID(), 'CAPTURE_COLONY_REGRET', $messparams, 1, 'RED');

        }

        // Выполняем все действия
        $idCombTo = self::_Exec($comb, $userInctance->getID(), $endBattleDate);
        
        // Патчим соту, для пересчета абсолютного и относительного бонуса на добычу ресурсов с учетом религии и тп..
        // Патчим соту, для пересчета тотребления и производства после удаления зданий на соте
        $patch = new MP_PatchComb(array('id_comb' => $comb->getCombID(), 'combname' => $comb->getName()));
        // Чтоб не писалось в лог
        //$patch->setDisableLog(false);
        $patch->setDisableLog(true);
        MP_PatchComb::$common = DS_Common::CreateForCurentDB();

        $patch->patch();
        
        // Если мы находимся на захваченной колонии, выходим из нее
        // ПРОВЕРКА ПЕРЕНЕСЕНА В UA_Action
        /*if ($_SESSION['comb'] == $comb->getCombID()) {
        $_SESSION['comb'] = $idCombTo;
        }*/

    }

    static private function _Exec(DE_Comb $comb, $userInctanceTo, GB_Date $date)
    {
        assert($userInctanceTo >= 0);
        $idCombTo = 0;
        // Уничтожаем здание силового поля
        self::_DeleteGeneratorField($comb->getCombID());
        
        // Возвращаем армии, летящие на эту соту
        DS_Army::ReturnAllArmiesToComb($comb->getCombID(), $userInctanceTo, $date);

        // Переделываем здания чужой рассы
        DS_Building::ConvertTrainBuildings($comb->getCombID(), DE_UserInstance::CreateFromID($userInctanceTo)->getRaceID());

        // Если бывший НЕ владелец DestinySphere
        if ($comb->getUserInstanceID() != 0) {
            // Получаем id домашней соты
            $combsCollection = $comb->getUserInstance()->getCombsCollection();
            $combsCollection->resetNext();
            while($combH = $combsCollection->next())
            {
                if ($combH->getRingNumber() != 4) {

                    $idCombTo = (int)$combH->getCombID();
                }
            }
            if ($idCombTo > 0) {
                // Возвращаем бои на соте
                DS_Army::ReturnAllBattles($comb->getCombID(), $idCombTo);
                
                // Перезаписываем лоты на домашку
                DS_Trade::RewriteAllTradeToHome($comb->getCombID(), $idCombTo);
                
                // Отменяем все дейчтвия на соте
                DS_Comb::CancelAllActionsOnCombs($comb->getCombID(), $idCombTo);

                // Возвращаем армии на основную соту
                DS_Army::RewriteAllArmiesToHome($comb->getCombID(), $idCombTo, $userInctanceTo, $date);

                // Разлагаем зонды
                // DS_Army::DecomposeZonds($comb->getCombID(), $idCombTo);
                
                // Перенаправляем ученых и архов
                DS_Comb::RewriteHumansToHome($comb->getCombID(), $idCombTo);
            }

            // Сбрасываем коллекцию армий, стоящих на защите
            /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            DC_CombDefendArmys::CreateFromCombID($combTo->getID());
            !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/

        } else {
            // Удаляем все армии (на случай мира расса против рассы) и перезаписываем домашку на нового владельца
            DS_Army::DeleteAllArmies($userInctanceTo, $comb->getCombID());
        }

        // Разлагаем все артефакты на соте
        DS_Artefact::DropAllArtFromComb($comb->getCombID());

        // Снимаем процент
        $tax = DE_WorldSettings::CreateForCurentWorld()->get('CAPTURE_COLONY_RESOURSE_TAX');
        if ($tax > 0) {
            $combAttributes = DE_CombAttributes::CreateFromCombID($comb->getCombID());
            foreach(GB_ResourcesList::$allResources as $k => $resourceName)
            {
                $value = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true);

                if($value == 0)
                {
                    continue;
                }

                $combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, (float)-$value*$tax/100);
            }
        }

        //Рестартим вид домашки и нейтралки
        CR_CombView::CreateFromCombID($comb->getCombID())->resetBuildings(1);
        CR_CombView::CreateFromCombID($comb->getCombID())->resetBuildings(2);
        
        // На сколько надо изменить строительный рейтинг
        $deltaBuildRanking = DS_Ranking::GetDeltaBuildRanking($comb->getID());
        assert($deltaBuildRanking >= 0);
        
        // Если старый владелец не DestinySphere, уменьшаем его строительный рейтинг
        if ($comb->getUserInstanceID() != 0) { 
            DE_Ranking::CreateFromID($comb->getUserInstanceID())->deltaBuildingRanking(-$deltaBuildRanking);
        }
        
        // Если новый владелец не DestinySphere, пересчитываем его строительный рейтинг
        if ($userInctanceTo != 0) {
            DE_Ranking::CreateFromID($userInctanceTo)->deltaBuildingRanking($deltaBuildRanking);
        }

        
        // Логируем захват
        DS_Comb::LogCaptureColony($comb->getCombID(), $comb->getUserInstanceID(), $userInctanceTo, $date);

        // Переписываем соты на нового владельца
        $comb->setUserInstanceID($userInctanceTo);
        
        // Возвращаем ID домашки
        return $idCombTo;
    }

    static private function _DeleteGeneratorField($combId)
    {
        assert(is_int($combId) && $combId > 0);

        $attachedBuilding = DS_Building::GetAttachedBuildingByCombIDAndBuildingID($combId, 91);
        assert(count($attachedBuilding) == 0 || count($attachedBuilding) == 1);
        // Если такое здание на соте есть
        if (count($attachedBuilding) > 0) {
            $attachedBuildingId = (int)$attachedBuilding[0]['id_attachedbuilding'];
            assert($attachedBuildingId > 0);
            DS_Building::DeleteBuilding($attachedBuildingId);
        }
    }
}

?>