<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс логики, которая определяет был ли найден артефакт
 *
 */
class GL_ArtefactSearch
{
	static public function isFound($countArceologist, $searchTime, $scopeProbability, $archBonus)
	{
		assert(is_int($countArceologist) && $countArceologist > 0);
		assert(is_int($searchTime) && $searchTime > 0);
		assert(is_numeric($scopeProbability) && $scopeProbability >= 0);
		assert(is_numeric($archBonus));
		
		// шанс найти артефакт
		$endprobability = (float)$scopeProbability * ( $countArceologist + $countArceologist * $archBonus ) * $searchTime;
		$endprobability *= 1000;

		// можно модификатором изменять шансы
		// в любом случае на выше 90%
		$endprobability *= (float)DE_WorldSettings::CreateForCurentWorld()->get('ARTEFACTS_PROBABILITY_BOOST');
		if($endprobability > 900)
		{
			$endprobability = 900;
		}

		$rand = rand(1, 1000); // случайное число

		// не нашли артефакт
		if($rand > $endprobability)
		{
			return false;
		}
		
		return true;
	}
}

?>