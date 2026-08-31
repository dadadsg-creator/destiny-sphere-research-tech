<?php
class Search
{
	private $common;
	
	static public function GetQuestions()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
     			pq.*,
     			(SELECT pr.date
     			FROM
     			    ds_poll_result pr
 			    WHERE
                     pr.id_question = pq.id_question
                ORDER BY
                     pr.date
                LIMIT 1
     			) as start_date,
     			(SELECT pr.date
     			FROM
     			    ds_poll_result pr
 			    WHERE
                     pr.id_question = pq.id_question
                ORDER BY
                     pr.date DESC
                LIMIT 1
     			) as end_date
			FROM
				ds_poll_question pq
			ORDER BY
				pq.add_date DESC";

		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function getQuestionStat($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
     			pq.question,
    			pa.answer,
    			pq.add_date,
			    (
			    	SELECT 
			    		count(*)
			        FROM
			    		ds_poll_result pr
			    	WHERE 
			    		pr.id_answer = pa.id_answer
			    ) as count,
			   	(
              		SELECT 
              			count(*)
             		FROM
              			ds_poll_result pr
             	 	WHERE 
             	 		pr.id_question = pa.id_question
             	 		--AND pr.id_answer != 0
    			) as total_count,
    			(SELECT pr.date
     			FROM
     			    ds_poll_result pr
 			    WHERE
                     pr.id_question = pq.id_question
                ORDER BY
                     pr.date
                LIMIT 1
     			) as start_date,
     			(SELECT pr.date
     			FROM
     			    ds_poll_result pr
 			    WHERE
                     pr.id_question = pq.id_question
                ORDER BY
                     pr.date DESC
                LIMIT 1
     			) as end_date
			FROM
				ds_poll_question pq
			INNER JOIN ds_poll_answer pa ON (pq.id_question = pa.id_question)
				WHERE pq.id_question = $id_question
			";


		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetActiveQuestions()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				*
			FROM
				ds_poll_question 
            WHERE
            	is_active = 't'
			";

		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetQuestionInfoById($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				*
			FROM
				ds_poll_question 
            WHERE
            	id_question = $id_question
			";

		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetQuestionAnswers($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT 
				* 
			FROM 
				ds_poll_answer
             WHERE 
              	id_question = $id_question
			";

		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetQuestionWorlds($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT 
				wc.worldname 
			FROM 
				ds_poll_question_worlds p
			LEFT JOIN ds_worldcollection  wc ON (wc.dbname = p.dbname AND wc.id_world = p.id_world)
              WHERE 
              	id_question = $id_question
			";

		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function GetAnswersByIdQuestion($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
				SELECT
					*
				FROM
					ds_poll_answer
	            WHERE
	            	id_question = $id_question
				";

		$res = $common->getResultArray("", $sql, "", "", "");		
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static public function AddQuestion($id_question, $Question, $QuestionName)
	{
		assert(is_int($id_question) && $id_question > 0);
		assert(!empty($Question));
		assert(!empty($QuestionName));
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			INSERT INTO
				ds_poll_question
				(id_question, question, question_name)
			VALUES
				($id_question, E'".pg_escape_string(urldecode($Question))."', '$QuestionName')
			";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function AddQuestionWorlds($id_question,  $dbname, $id_world)
	{
		assert(is_int($id_question) && $id_question > 0);
		assert(!empty($dbname));
		assert(is_int($id_world) && $id_world > 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			INSERT INTO
				ds_poll_question_worlds
				(id_question, dbname, id_world)
			VALUES
				($id_question, '$dbname', $id_world)
			";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function AddQuestionAnswers($id_question, $answer)
	{
		assert(is_int($id_question) && $id_question > 0);
		assert(!empty($answer));

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			INSERT INTO
				ds_poll_answer
				(id_question, answer)
			VALUES
				($id_question, E'".pg_escape_string(urldecode($answer))."')
					";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function SetQuestionIsActive($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			UPDATE
				ds_poll_question
			SET
				is_active = 't'
			WHERE
				id_question = $id_question
					";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}
	
	static public function SetQuestionNotActive($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			UPDATE
				ds_poll_question
			SET
				is_active = 'f'
			WHERE
				id_question = $id_question
					";

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
	}	
	
	static public function GetCountOfAbstainPlayers($id_question)
	{
		assert(is_int($id_question) && $id_question > 0);

		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "SELECT
		 			count(*) as count
		 		FROM
		 			ds_poll_result 
		 		WHERE
		 			id_question = $id_question
					 AND
		 			id_answer = 0";

		$res = $common->getResultArray("", $sql, "", "", "");;

		echo $common->errormsg;
		assert(empty($common->errormsg) && count($res) == 1);

		return (int)$res[0]['count'];
	}
	
	static public function GetNextValIdQuestion()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "select nextval('ds_poll_question_id_question_seq') as id";

		$res = $common->getResultArray("", $sql, "", "", "");;

		echo $common->errormsg;
		assert(empty($common->errormsg) && count($res) == 1);

		return (int)$res[0]['id'];
	}	
	
	static public function EditQuestion($id_question, $question, $questionName, $answerArr, $worlds)
	{
		assert(is_int($id_question) && $id_question >0);
		assert(!empty($question));
		assert(!empty($questionName));
		assert(is_array($answerArr));
		assert(is_array($worlds));
		
		
		$common = DS_Common::CreateForCurentDB(0);
		
		
		// Апдейтим название и суть вопроса
		$sql = "UPDATE
					ds_poll_question
				SET
					question = '".$question."',
					question_name = '".$questionName."'
				WHERE
					id_question = ".$id_question;

		$common->executeQuery($sql);
		assert(empty($common->errormsg));
		
		// Апдейтим ответы на вопрос
		foreach ($answerArr as $id_answer => $answer)
		{
			assert($id_answer > 0);
			assert(!empty($answer));
			
			$sql = "UPDATE
						ds_poll_answer
					SET
						answer = '".$answer."'
					WHERE
						id_answer = ".$id_answer;
			$common->executeQuery($sql);
			assert(empty($common->errormsg));
		}
		
		// Апдейтим миры
		$sql = "DELETE FROM
						ds_poll_question_worlds
					WHERE
						id_question = ".$id_question;
			$common->executeQuery($sql);
			assert(empty($common->errormsg));
			
		foreach ($worlds as $key => $data)
		{
			$temp = explode('.', $data);
			$dbname = $temp[0];
			$id_world = (int)$temp[1];

			assert(!empty($dbname));
			assert($id_world > 0);

			//Записываем в базу в каких мирах будет доступен вопрос
			self::AddQuestionWorlds($id_question, $dbname, $id_world);
		}
	}
}
?>