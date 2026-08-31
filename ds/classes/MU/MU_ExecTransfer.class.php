<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_ExecTransfer
{
    static public function Exec($listID, MU_EntityOperation $operation)
    {           
        assert(is_int($listID) && $listID > 0);

        $memberFrom = $operation->getFromUser();
        
        $memberTo = $operation->getToUser();
        
        $summ = $operation->getSumm();
        assert($summ instanceof MU_EntityMoneySumm);
        
        $accountDataFrom = MU_SearchOperations::GetAccountData($memberFrom);
        assert(is_array($accountDataFrom) && count($accountDataFrom) > 0);
        
        $accountDataTo = MU_SearchOperations::GetAccountData($memberTo);
        assert(is_array($accountDataTo) && count($accountDataTo) > 0);
        
        //Оба счета должны быть в одном мире
        assert($memberFrom->getWorldShortName() == $memberTo->getWorldShortName());

        //Нельзя делать перевод самому себе
        assert($memberFrom->getUserID() != $memberTo->getUserID());
        
        //Счета должны быть не заблокированны
        assert($accountDataFrom['IS_ACTIVE']);
        assert($accountDataTo['IS_ACTIVE']);
        
        //Сумма на счете с которого перводим должна быть болеше требуемой
        assert(key_exists($summ->getCreditName(), $accountDataFrom));
        $amountFrom = $accountDataFrom[$summ->getCreditName()];
                
        assert($amountFrom >= $summ->getAmount());
        
        //Снимаем деньги
        MU_SearchOperations::DeltaAccount
        (
            $memberFrom, 
            $summ, 
            'MINUS'
        );
        
        //Записываем платеж
        MU_SearchOperations::InsertPayment($memberFrom, $summ, 'MINUS', $listID);   
        
        //Начиcляем деньги
        MU_SearchOperations::DeltaAccount
        (
            $memberTo, 
            $summ, 
            'PLUS'
        );
        
        //Записываем платеж
        MU_SearchOperations::InsertPayment($memberTo, $summ, 'PLUS', $listID);  
                
    }
}

?>