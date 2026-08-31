<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * –асчет стоимости часа заморозки в стазисе
 *
 */

class GL_PowerShildTurnPrice
{
    static public function Count(DE_UserInstance $userInstance)
    {
        assert(key_exists('world', $_SESSION)); 
        $worldID = (int)$_SESSION['world'];
        assert($worldID > 0);
        
        $topUIIDs = DS_Freez::GetCurrentScoreTop($worldID, 20);
        
        $userAverRating = DS_Freez::GetAverRatingForUIIDs(array($userInstance->getID()));
        // «атыка на тот случай, если игрок еще не по€вилс€ в рейтинге
        if ($userAverRating == 0 || empty($topUIIDs)) {
            $userAverRating = 1;
        }
        assert($userAverRating > 0);
        
        $topAverRating = DS_Freez::GetAverRatingForUIIDs($topUIIDs);
        // «атыка на тот случай, если игрок еще не по€вилс€ в рейтинге
        if ($topAverRating == 0 || empty($topUIIDs)) {
            $topAverRating = 1;
        }
        
        $price = ceil($userAverRating / $topAverRating * DE_WorldSettings::CreateForCurentWorld()->get('PS_COST_MULTIPLAER'));
        assert($price > 0);
        
        return (int)$price;
    }
}




?>