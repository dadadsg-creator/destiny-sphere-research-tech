<?php $CVS_Version = '$Revision$ ; $Date$';


class GL_BattleBonuses
{
    static private $userInstance;

    static private $forLog = '';

    static public function GetStaticBonus($countForObject, $userInstanceInput = null, $countWithArts = 1)
    {
        assert($countForObject instanceof DE_ArmyInterface || $countForObject instanceof DE_Comb);

        assert(is_null($userInstanceInput) || $userInstanceInput instanceof DE_UserInstance);

        /*
        * Здесь помни что должны учитываться сл. бонусы.
        OK!      * 1. Рассовые. (Для этого надо узнать какой рассы владелец армии)
        OK!      * 2. Религиозные. (Для этого узнаешь религию пользователя)
        OK       * 3. Артов в военно базе соты(помни что значение этього
        * для атакующей армии задаеться модификатором ATTACKER_ARTEFACT_EFFECT )
        * Для этогго в зависимости от режима армии ... если в атаке, то действуют арты домашей соты, если в
        * обороне то той соты на которой стоит.
        */

        if($countForObject instanceof DE_ArmyInterface)
        {
            $baseComb = GL_CombArmyArtefactBonus::BaseComb($countForObject);
            $userInstance = $countForObject->getUserInstance();
        }else{
            $baseComb = $countForObject;

            if(!is_null($userInstanceInput))
            {
                $userInstance = $userInstanceInput;
            }else{
                $userInstance = $countForObject->getUserInstance();
            }
        }

        //Возвращать должно экземпляр класса DE_BattleBonusesStatic
        $bonuses = DE_BattleBonuses::CreateFromCombID($baseComb->getID()."_".$userInstance->getID()."_".$countWithArts);

        self::$forLog .= $userInstance->getUser()->getName()."\n";

        if(!$bonuses->getIsComplit())
        {
            self::$forLog .= "NEED COUNT\n";
            self::_countBonuses($bonuses, $baseComb, $userInstance, $countWithArts);
            $bonuses->setIsComplit();
        }else{
            self::$forLog .= "HAVE COMPLIT\n";
        }


        //DebugTools::LogUser('40Min', self::$forLog, __CLASS__);
        self::$forLog = '';
        return $bonuses;
    }

    static private function _countBonuses(DE_BattleBonuses $bonuses, DE_Comb $baseComb, DE_UserInstance $userInstance, $countWithArts)
    {
        if($countWithArts > 0)
        {
            //Здесь тоже может получиться $militaryBaseBuildingID = 0;
            $militaryBaseBuildingID = (int)$baseComb->GetWarArtefactsByBuildType();
        }else{
            $militaryBaseBuildingID = 0;
        }


        if($militaryBaseBuildingID == 0)
        {//Здания военно БД нет, значит испольуем временную затыку для пустой оллекции
            $militaryBaseBuildingID = 111111111;
        }

        // Если владелец соты не DestinySphere
        if ($baseComb->getUserInstanceID() == 0) {
            $attackBonus = new DE_Bonus();
            $bonuses->setAttackBonus($attackBonus);
            $defendBonus = new DE_Bonus();
            $bonuses->setDefendBonus($defendBonus);
            $demageBonus = new DE_Bonus();
            $bonuses->setDemageBonus($demageBonus);
            $hitpointBonus = new DE_Bonus();
            $bonuses->setHitpointBonus($hitpointBonus);
        } else {
            // Если владелец соты DestinySphere
            $religionAttributes = $userInstance->getReligionStatus()->getReligion()->getAttributes();
            $raceAttributes = $userInstance->getRace()->getAttributes();
            $warArtefactsCollection = DC_MilitaryBaseArtefacts::CreateFromBuildID($militaryBaseBuildingID, $countWithArts);

            self::$userInstance = $userInstance;

            $attackBonus = new DE_Bonus();
            self::_addBonus($attackBonus, $raceAttributes, 'ATTACK');
            self::_addBonus($attackBonus, $religionAttributes, 'ATTACK');
            self::_addMilitaryBaseBonus($attackBonus, $warArtefactsCollection, 'ATTACK');
            // Если атака идет с захватываемой колонии
            if ($countWithArts !=1 && $baseComb->getIsCapture()) {
                // Добавляем бонусы с лолонии
                $attackBonusColony = (float)DE_WorldSettings::CreateForCurentWorld()->get('BONUS_COLONY_ATTACK')/100;
                $attackBonus->deltaRelativeBonus($attackBonusColony);
            }
            $attackBonus->deltaRelativeBonus(DS_Economical::GetUserBonus($userInstance, 'ATTACK'));
            $bonuses->setAttackBonus($attackBonus);

            $defendBonus = new DE_Bonus();
            self::_addBonus($defendBonus, $raceAttributes, 'DEFENCE');
            self::_addBonus($defendBonus, $religionAttributes, 'DEFENCE');
            self::_addMilitaryBaseBonus($defendBonus, $warArtefactsCollection, 'DEFENCE');
            $defendBonus->deltaRelativeBonus(DS_Economical::GetUserBonus($userInstance, 'DEFENCE'));
            $bonuses->setDefendBonus($defendBonus);

            $demageBonus = new DE_Bonus();
            self::_addBonus($demageBonus, $raceAttributes, 'DAMAGE');
            self::_addBonus($demageBonus, $religionAttributes, 'DAMAGE');
            self::_addMilitaryBaseBonus($demageBonus, $warArtefactsCollection, 'DAMAGE');
            // Если атака идет с захватываемой колонии
            if ($countWithArts !=1 && $baseComb->getIsCapture()) {
                // Добавляем бонусы с лолонии
                $damageBonusColony = (float)DE_WorldSettings::CreateForCurentWorld()->get('BONUS_COLONY_DAMAGE')/100;
                $demageBonus->deltaRelativeBonus($damageBonusColony);
            }
            $demageBonus->deltaRelativeBonus(DS_Economical::GetUserBonus($userInstance, 'DAMAGE'));
            $bonuses->setDemageBonus($demageBonus);
            
            $hitpointBonus = new DE_Bonus($userInstance->getUser()->getName().'_HITPOINTS');
            self::_addBonus($hitpointBonus, $raceAttributes, 'HITPOINTS');
            self::_addBonus($hitpointBonus, $religionAttributes, 'HITPOINTS');
            self::_addMilitaryBaseBonus($hitpointBonus, $warArtefactsCollection, 'HITPOINTS');
            $hitpointBonus->deltaRelativeBonus(DS_Economical::GetUserBonus($userInstance, 'HITPOINTS'));
            $bonuses->setHitpointBonus($hitpointBonus);
        }

        $bonuses->validate();
    }

    static private function _addBonus(DE_Bonus $bonus, DE_AttributesInterface $attributes, $attributeName)
    {
        assert(is_string($attributeName) && !empty($attributeName));

        $attributeType = 'BONUS_ABSOLUTE';
        if($attributes->haveAttribute($attributeType, $attributeName))
        {
            $bonusValue = (float)$attributes->getAttribute($attributeType, $attributeName);
            if($bonusValue != 0)
            {
                $bonus->deltaAbsoluteBonus($bonusValue);
            }
        }

        $attributeType = 'BONUS_RELATIVE';
        if($attributes->haveAttribute($attributeType, $attributeName))
        {

            $bonusValue = (float)$attributes->getAttribute($attributeType, $attributeName);
            if($bonusValue != 0)
            {
                $bonus->deltaRelativeBonus($bonusValue);
            }
        }
    }

    static private function _addMilitaryBaseBonus(DE_Bonus $bonus, DC_MilitaryBaseArtefacts $artefactsCollection, $attributeName)
    {
        assert(is_string($attributeName) && !empty($attributeName));

        if($artefactsCollection->countCollection() == 0)
        {
            self::$forLog .= "SKIP ADD ARTS $attributeName\n";
            return;
        }
        self::$forLog .= "COUNT ARTS $attributeName\n";
        $artefactsCollection->resetNext();
        while($artefact = $artefactsCollection->next())
        {
            self::_addBonus($bonus, $artefact->getAttributes(), $attributeName, true);
        }
    }
}


?>