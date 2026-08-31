<?php

class TMP_DtfPromoChangeBoxToPresent
{
	static private $boxArt = 'WHITE_QUEST_BOXIM';
	
	static private $baseProb = array(
				'GOOD' 		=> 2,
				'NEUTRAL' 	=> 25,
				'BAD'		=> 73,
			);
			
	static private $probAdd = array(
				'GOOD' 		=> 0.5,
				'NEUTRAL' 	=> 1,
				'BAD'		=> 1.5,
			);
	
	static private $prizeArts = array(
				'GOOD' 		=> array(1091), // joystick
				'NEUTRAL' 	=> array(1089), // phone
				'BAD'		=> array(1090), // mouse
			);
	
	static private $prob = array(
			'GOOD' 		=> 0,
			'NEUTRAL' 	=> 0,
			'BAD' 		=> 0
		);
			
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $art;
	
	static private $dbID;
	
	static private $worldID;
			
	static public function Exec($dbID, $worldID, $art, $questionCount)
	{
		assert(is_int($dbID) && $dbID >= 0);
		assert(is_int($worldID) && $worldID > 0);
		assert(is_int($questionCount) && $questionCount > 0);
		assert(strlen($art) == 10/* && ereg('[a-zA-Z0-9]{10}')*/);
		
		self::$dbID = $dbID;
		
		self::$worldID = $worldID;
		
		self::$art = $art;
		
		self::$common = DS_Common::CreateForCurentDB(0);
		
		$quizResult = self::_getQuizResults();
		
		// точно ли закончил викторину
		assert($quizResult['question_amount'] == $questionCount);
		
		$rightAnswers = $quizResult['right_answer_amount'];
		$wrongAnswers = $questionCount - $rightAnswers;
		$diff = $rightAnswers - $wrongAnswers;
		
		self::$prob['GOOD'] 	= self::$baseProb['GOOD'] 	 + $diff * self::$probAdd['GOOD'];
		self::$prob['NEUTRAL'] 	= self::$baseProb['NEUTRAL'] + $diff * self::$probAdd['NEUTRAL'];
		self::$prob['BAD'] 		= self::$baseProb['BAD']	 - $diff * self::$probAdd['BAD'];	
		
		$prizeArt = self::_getPrizeArt();
		$prizeArtId = self::$prizeArts[$prizeArt][array_rand(self::$prizeArts[$prizeArt])];
		$prizeArtName = self::_getArtefactName($prizeArtId);
		$prizeArtBonus = self::_getArtefactBonus($prizeArtId);
		self::_replaceBoxToPrize($prizeArtId);
		
		return $prizeArtName.' ('.$prizeArtBonus.')';
	}
	
	static private function _getQuizResults()
	{
		self::$common->setDbId(0);
		$sql = "
			SELECT
				id_user,
				id_worldcollection,
				question_amount,
				right_answer_amount,
				id_quest
			FROM
				ds_victor_result
			WHERE
				id_quest = '".self::$art."'
			";
		$res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		return $res[0];
	}
	
	static private function _getPrizeArt()
	{
		assert((self::$prob['BAD'] + self::$prob['NEUTRAL'] + self::$prob['GOOD']) == 100);
		
		$rand = rand(1, 100);

		if($rand <= self::$prob['BAD'])
		{
			return 'BAD';
		}
		
		if($rand <= (self::$prob['BAD'] + self::$prob['NEUTRAL']))
		{
			return 'NEUTRAL';
		}
		
		return 'GOOD';
	}
	
	static private function _replaceBoxToPrize($prizeArtId)
	{
		self::$common->setDbId(self::$dbID);
		assert(is_int($prizeArtId) && $prizeArtId > 0);
		
		self::$common->startTrans();
		
		$sql = "
			UPDATE 
				ds_artefactinstance
			SET
				id_artefact = $prizeArtId
			WHERE
				id_artefactinstance = (
					SELECT
						id_artefactinstance
					FROM
						ds_promo_dtf_artefactbox
					WHERE
						public_id = '".self::$art."'
				)
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		$sql = "
			UPDATE
				ds_promo_dtf_artefactbox
			SET
				prize_artefact = $prizeArtId
			WHERE
				public_id = '".self::$art."'
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
				
		self::$common->closeTrans('COMMIT');
	}
	
	static private function _getArtefactName($artID)
	{
		assert(is_int($artID) && $artID > 0);		
		$sql = "
			SELECT
				ltl.text
			FROM
				ds_artefact AS a
				INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = a.id_localizedname)
			WHERE
				ltl.id_language = 3
				AND
				a.id_artefact = $artID
			";
		$res = $res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		return $res[0]['text'];
	}
	
	static private function _getArtefactBonus($artID)
	{
		assert(is_int($artID) && $artID > 0);	
		
		$sql = "
			SELECT 
					attt.typename,
					attn.attributename,
					ahb.value,
					ltl.text
				FROM
					ds_artefacthasbonus AS ahb
					INNER JOIN ds_attribute AS att ON (ahb.id_attribute = att.id_attribute)
					INNER JOIN ds_attributetype AS attt ON (att.id_attributetype = attt.id_attributetype)
					INNER JOIN ds_attributename AS attn ON (att.id_attributename = attn.id_attributename)
					INNER JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = attn.id_localizedname)
				WHERE
					ahb.id_artefact = ".$artID."
					AND
					ltl.id_language = ".constant('DEFAULT_LANGUAGE')."
			";
		$res = $res = self::$common->getResultArray("", $sql);
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		$bonus = '';
		
		if($res[0]['typename'] == 'BONUS_RELATIVE')
		{
			$bonus = ($res[0]['value']*100)."%";
		} else {
			$bonus = $res[0]['value'];
		}
		
		return $res[0]['text']." +".$bonus;
	}
}


?>