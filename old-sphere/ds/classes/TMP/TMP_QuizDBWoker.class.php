<?php

class TMP_QuizDBWoker
{
	public static function GetWorldCollectionByIdWorld($dbId, $idWorld)
	{
		assert($dbId >= 0);
		assert($idWorld > 0);
		
		$common = DS_Common::CreateForCurentDB($dbId);
		
		$sql = "SELECT wc.id_worldcollection 
					FROM
						ds_worldcollection wc
					WHERE 
						wc.world_identifier = (
                            		SELECT 
                            			w.world_identifier
                            		FROM
                               			 ds_world w
                                	WHERE
                                		w.id_world = $idWorld)";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res[0]['id_worldcollection'];
	}
	
	public static function StartQuiz($idUser, $IdWorldCollection, $art, $idResult)
	{
		assert(is_int($idUser) && $idUser > 0);
		assert(is_int($IdWorldCollection) && $IdWorldCollection > 0);
		assert(!empty($art));
		assert(is_int($idResult) && $idResult > 0);
		
		$common = self::getCommon();
		
		$sql = "INSERT INTO ds_victor_result 
					(id_result, id_user, id_worldcollection, start_date, id_quest) 
				VALUES
					($idResult, $idUser, $IdWorldCollection, now(), '$art')";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		
		return ;
	}
	
	public static function IsVictirActive($idQuiz)
	{
		assert($idQuiz >= 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
				  
				   is_active
				   FROM ds_victor
				   where id_quiz = $idQuiz";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		assert(count($res) == 1);

		if ($res[0]['is_active'] == 't')
		{
			return true;
		} else {
			return false;
		}
	}
	
	public static function WriteActivationDate($art, $dbId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		assert(!empty($art));
		
		$common = DS_Common::CreateForCurentDB($dbId);
		
		
		$sql = "UPDATE 
					ds_promo_dtf_artefactbox
				SET 
					activation_date = now()
				WHERE
					public_id = '$art'";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		
		return ;
	}
	
	public static function IsQuizEnded($idResult)
	{
		assert(is_int($idResult) && $idResult > 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					end_date
				FROM
					ds_victor_result
				WHERE
				  id_result = $idResult";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		assert(count($res) == 1);

		if (empty($res[0]['end_date']))
		{
			return  true;
		} else {
			return false;
		}
	}
	
	public static function EndQuiz($id_result)
	{
		assert(is_int($id_result) && $id_result >= 0);
		
		$common = self::getCommon();
		
		$sql = "UPDATE
					ds_victor_result
				SET
					end_date = now()
				WHERE
					id_result = $id_result";

		

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
	
		return ;
	}
		
	public static function IsExistNotEndedQuiz($art)
	{
		assert(!empty($art));
		
		$common = self::getCommon();
		
		$sql = "SELECT *
				FROM
					ds_victor_result v
				WHERE	
					v.id_quest = '$art'";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		assert(count($res) == 1 || count($res) == 0);
	
		return $res[0];
	}
	
	public static function IsExistArtefactID($public_id, $db_id)
	{
		assert(!empty($public_id));
		assert(is_int($db_id) && $db_id >= 0);
		
		$common = DS_Common::CreateForCurentDB($db_id);
		
		$sql = "SELECT *
				FROM 
					ds_promo_dtf_artefactbox pda
				WHERE	
					pda.public_id='$public_id'";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		if (count($res) > 0)
		{
			return $res;
		} else {
			return false;	
		}
	}
		
	public static function GetCountRightAnswer($id_result)
	{
		assert(is_int($id_result) && $id_result >= 0);
		
		$common = self::getCommon();
		
		$sql = " SELECT 
					COALESCE(right_answer_amount, 0) as count
				FROM 
					ds_victor_result 
				WHERE 
					id_result = $id_result";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		assert(count($res) == 1);
	
		return $res[0]['count'];
	}
	
	public static function GetAllQuestionsId()
	{
		$common = self::getCommon();
		
		$sql = "SELECT
					id_question
				FROM
					ds_victor_question
				WHERE
				    is_active = true";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	public static function GetUserQuestion($idUser)
	{
		$common = self::getCommon();
		
		$sql = "SELECT 
					vrd.id_question
				FROM
					ds_victor_result vr
				INNER JOIN
					ds_victor_result_detailed vrd ON (vrd.id_result = vr.id_result)
				WHERE 
					vr.id_user = $idUser";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		foreach ($res as $k => $v)
		{
			$resArr[] = $v['id_question'];
		}
		
		return $resArr;
	}
	
	public static function GetUserQuestionInResult($idResult)
	{
		$common = self::getCommon();
		
		$sql = "SELECT
					id_question
				FROM
					
					ds_victor_result_detailed
				WHERE
					
					id_result = $idResult";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		foreach ($res as $k => $v)
		{
			$resArr[] = $v['id_question'];
		}
		
		return $resArr;
	}
	
	public static function GetQuestionByIdQuestion($id_question)
	{
		assert(is_int($id_question) && $id_question >= 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT 
					id_question,
					question
				FROM 
					ds_victor_question
				WHERE
					id_question = $id_question";

		$res = $common->getResultArray('', $sql);		
		
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		return $res;
	}
	
	public static function IsQuestionWasSubmited($id_result, $id_question)
	{
		assert(is_int($id_question) && $id_question >= 0);
		assert(is_int($id_result) && $id_result >= 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT 
					id_answer 
				FROM 
					ds_victor_result_detailed
				WHERE
					id_result = $id_result
				   AND
					id_question = $id_question";

		$res = $common->getResultArray('', $sql);		
		
		assert(empty($common->errormsg));
		
		if (!empty($res[0]['id_answer']))
		{
			return true;
		} else {
			return false;
		}
	}
		
	public static function GetAnswerByIdQuestion($idQuestion)
	{
		$common = self::getCommon();
		
		$sql = "SELECT 
					id_answer, 
					answer
				FROM 
					ds_victor_answer
				WHERE
					id_question = $idQuestion";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	public static function IsAnswerTrue($idAnswer)
	{
		$common = self::getCommon();
		
		$sql = "SELECT 
					is_right
				FROM 
					ds_victor_answer
				WHERE
					id_answer = $idAnswer";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		assert(count($res) == 1);		
		
		return $res[0]['is_right'];
	}
	
	public static function SubmitAnswer($id_question, $id_answer, $id_result, $isTrue)
	{
		assert(is_int($id_question) && $id_question >= 0);
		assert(is_int($id_answer) && $id_answer >= 0);
		assert(is_int($id_result) && $id_result >= 0);
		assert($isTrue == 'f' || $isTrue == 't');
		
		$common = self::getCommon();
		
		$sql = "UPDATE  
					ds_victor_result_detailed 
				SET
					id_answer=$id_answer,
					is_right='$isTrue'
				WHERE
					id_result=$id_result
				   AND
					id_question=$id_question";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	public static function AddRightAnswer($id_result)
	{
		assert(is_int($id_result) && $id_result >= 0);
		
		$common = self::getCommon();
		
		$sql = "UPDATE 
					ds_victor_result
				SET
					right_answer_amount = COALESCE(right_answer_amount, 0)+1
				WHERE
					id_result = $id_result";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		return;
	}
	
	public static function GetNotAnsweredQuestion($id_result)
	{
		assert(is_int($id_result) && $id_result >= 0);	
		
		$common = self::getCommon();
		
		$sql = "SELECT
					id_question
				FROM
					ds_victor_result_detailed
                WHERE
					id_result = $id_result
				AND
					id_answer is Null";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		if (!empty($res))
		{
			return $res[0]['id_question'];
		}
	}
		
	public static function SetQuestion($id_result, $id_question)
	{
		assert(is_int($id_question) && $id_question > 0);
		assert(is_int($id_result) && $id_result >= 0);
		
		$common = self::getCommon();
		
		$common->startTrans();
		
		$sql = "UPDATE 
					ds_victor_result
				SET
					question_amount = COALESCE(question_amount, 0)+1
				WHERE
					id_result = $id_result
				";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		$sql = "INSERT INTO 
					ds_victor_result_detailed
					(id_question, id_result)
				VALUES
					($id_question, $id_result)
				";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		
		$common->closeTrans("commit");
	}
		
	public static function GetCountAnsweredQuestion($id_result)
	{
		assert(is_int($id_result) && $id_result >= 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					question_amount
				FROM
					ds_victor_result
                WHERE
					id_result = $id_result
				";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		assert(count($res) == 1 || count($res) == 0);
		
		return $res[0]['question_amount'];
	}
	
	public static function GetQuestionCountSettings($id_quiz)
	{
		assert(is_int($id_quiz) && $id_quiz >= 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					count_question
				FROM
					ds_victor
				WHERE
					id_quiz = $id_quiz
				";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		return $res[0]['count_question'];
	}
	
	public static function GetTimeStart($id_result)
	{
		assert(is_int($id_result) && $id_result >= 0);
		
		$common = self::getCommon();
		
		$sql = "SELECT
					start_date
				FROM
					ds_victor_result
                WHERE
					id_result = $id_result
				";

		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));
		assert(count($res) == 1);
		
		return $res[0]['start_date'];
	}
	
	private static function getCommon()
	{				
		return DS_Common::CreateForCurentDB(0);
	}
	
	public static function GetNextValIdResult()
	{
		$common = self::getCommon();
		
		$sql = "select nextval('ds_victor_result_id_result_seq') as id";
		
		$res = $common->getResultArray("", $sql, "", "", "");;
			
		assert(empty($common->errormsg) && count($res) == 1);		
			
		return (int)$res[0]['id'];
	}
}


?>