<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/class/Forms.class.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
require_once $_SERVER['DOCUMENT_ROOT']."/ds/include/Smarty/Smarty.class.php";

class Victorina
{
	private $common;
	
	private $error;
	
	private $id_victor = 1;
	
	//private $victorins = array(
	//							0 => 'PromoDTF',
	//							1 => 'Test');
	
	function Victorina()
	{
		$this->common = DS_Common::CreateForCurentDB(0);
		
		$this->proccessPostData();
	}
	
	function proccessPostData()
	{
		$tpl = new Smarty;
		$tpl->template_dir = "./";
		$tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';	$tpl->compile_check = true;
		$tpl->debugging = false;

		if(isset($_REQUEST['add']))
		{
			if ($this->validateData())
			{			
				$this->addQuestion($_REQUEST['question'], $_REQUEST['answer'], $_REQUEST['is_right']);
				
				$tpl->assign("SUCCESFUL", "SUCCESFUL");
			}
			
			
		} elseif (isset($_REQUEST['show_questions']))
		{
			$questions = $this->_getQuestions();
			$tpl->assign("QUESTIONS", $questions);
			$tpl->assign("ACTION", 'show_question');
			
			$tpl->assign("SUCCESFUL", "");
		}elseif (isset($_REQUEST['change_active']))
		{
			$questions = $this->_changeActive($_POST);
			
			$questions = $this->_getQuestions();
			$tpl->assign("QUESTIONS", $questions);
			$tpl->assign("ACTION", 'show_question');
			
			$tpl->assign("SUCCESFUL", "");
		} else {
			$tpl->assign("SUCCESFUL", "");
		}
		
		if (!empty($this->error))
		{
			if (isset($_REQUEST['question']))
			{
				$tpl->assign("question", $_REQUEST['question']);
			} 
			if (isset($_REQUEST['answer']))
			{			
				$tpl->assign("answer", $_REQUEST['answer']);
			} 
			if (isset($_REQUEST['is_right']))
			{
				$tpl->assign("is_right", $_REQUEST['is_right']);
			} 
			
			$tpl->assign("ERROR", $this->error);
		}
		
		$tpl->display('Victorina.tpl');	
	}
	
	function validateData()
	{
		if (strlen($_REQUEST['question']) == 0)
		{
			$this->error = 'Введите вопрос';
			return false;
		}
		
		if (strlen($_REQUEST['question']) < 3)
		{
			$this->error = 'Слишком короткий вопрос';
			return false;
		}
		
		$countAnswer = 0;
		
		foreach ($_REQUEST['answer'] as $key => $answer)
		{
			if (strlen($answer) != 0)
			{
				$countAnswer ++;
			}
		}
		
		if ($countAnswer == 0)
		{
			$this->error = 'Введите варианты ответа';
			return false;
		}
		
		if ($countAnswer == 1)
		{
			$this->error = 'Вариантов ответа должно быть как минимум 2';
			return false;
		}	
		if (!isset($_REQUEST['is_right']))
		{
			$this->error = 'Выберите правильный ответ';
			return false;
		}
		
		if (empty($_REQUEST['answer'][$_REQUEST['is_right']]))
		{
			$this->error = 'Некорректный правильный ответ';
			return false;
		}
					
		return true;
	}
	
	function addQuestion($question, $answerArr, $rightAnswer)
	{
		assert(!empty($question));
		assert(is_array($answerArr));
		
		$nextVal = $this->GetNextValIdQuestion();
		
		$sql = "
			INSERT INTO
				ds_victor_question
				(id_question, question, id_quiz)
			VALUES
				($nextVal, E'".pg_escape_string(urldecode($question))."', $this->id_victor)
			";
		
		$this->common->executeQuery($sql);	
		if(!empty($this->common->errormsg))
		{
			echo "Error:<br>".$this->common->errormsg."<br>";
			exit;
		}
		
		
		foreach ($answerArr as $key => $answer)
		{
			if (!empty($answer))
			{
				if ($key == $rightAnswer)
				{
					$is_right = 'true';
				} else {
					$is_right = 'false';
				}
				
				
				$sql = "
					INSERT INTO
						ds_victor_answer
						(id_question, answer, is_right)
					VALUES
						($nextVal, E'".pg_escape_string(urldecode($answer))."', $is_right)
					";
				
				$this->common->executeQuery($sql);	
				if(!empty($this->common->errormsg))
				{
					echo "Error:<br>".$this->common->errormsg."<br>";
					exit;	
				}
			}
		}
		
		return;
	}
	
	private function _getQuestions()
	{
		
		$sql = "
			SELECT 
			        vq.id_question,
			        vq.question,
			        va.answer,
			        va.is_right,
			        is_active
				FROM
			    	ds_victor_question vq
			    LEFT JOIN ds_victor_answer va ON (vq.id_question = va.id_question)
			    
			    ORDER BY 
			    	vq.id_question, 
			    	va.id_answer
			";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");
		if(!empty($this->common->errormsg))
		{
			echo "Error:<br>".$this->common->errormsg."<br>";
			exit;
		}
		
		foreach ($res as $key => $data)
		{
			$resArr[$data['id_question']]['question'] = $data['question'];
			$resArr[$data['id_question']]['is_active'] = $data['is_active'];
			
			//Если ответ правильный, помечаем его
			if ($data['is_right'] == 't')
			{
				$resArr[$data['id_question']]['answers'][] = "<b>".$data['answer']."</b>";
			} else {
				$resArr[$data['id_question']]['answers'][] = $data['answer'];
			}
		}
		return $resArr;
	}
	
	private function _changeActive($data)
	{		
		$sql = "
			UPDATE 
				ds_victor_question
			SET 
				is_active = '".$data['change_active']."'
			WHERE
				id_question = ".$data['id_question']."
			";
		
		$this->common->executeQuery($sql);	
		
		if(!empty($this->common->errormsg))
		{
			echo "Error:<br>".$this->common->errormsg."<br>";
			exit;
		}
		return;
	}
	
	function GetNextValIdQuestion()
	{
		
		$sql = "select nextval('ds_victor_question_id_question_seq') as id";
		
		$res = $this->common->getResultArray("", $sql, "", "", "");;
		
		echo $this->common->errormsg;	
		assert(empty($this->common->errormsg) && count($res) == 1);		
			
		return (int)$res[0]['id'];
	}
}

$vic = new Victorina();

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>