<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CountBattleHit
{
    static public function CountDamage($hitAttack, $hitDamage, $reciveDefend)
    {
        assert(is_float($hitAttack) && $hitAttack >= 0);
        assert(is_float($hitDamage) && $hitDamage >= 0);
        assert(is_float($reciveDefend) && $reciveDefend >= 0 && $reciveDefend < 1);
        
        return $hitAttack * (1 - $reciveDefend) * $hitDamage;
    }
    
    static public function CountLoses($recivedDamage, $oneHitPoint, $defenceField)
    {
        assert(is_float($recivedDamage) && $recivedDamage >= 0);
        assert(is_float($oneHitPoint) && $oneHitPoint >= 0);
        assert(is_float($defenceField) && $defenceField >= 0);
        
        return $recivedDamage / ($oneHitPoint * (1 + $defenceField));
    }
}

?>