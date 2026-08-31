<?php

class TMP_QuizController
{
	private $user;
	
	//private $worldCollection;
	private $world;
	private $dbId;
	
	private $repeatMode = 'result'; // 'result' - вопросы не могут повтор€тс€ в пределах одного арта,
									// 'quiz' - вопросы не могут повтор€тс€ в пределах овсей викторины;

	private $art;
	
	private $tpl;
	
	private $id_result;
	
	private $id_answer;
	
	private $id_question;
		
	private $id_quiz = 1;	
	
	private $action = 'index';
	
	
	public function __construct()
	{		
		assert(key_exists('uid', $_SESSION));

		$this->user = DE_User::CreateFromID((int) $_SESSION['uid']);
	}
	
	public function Exec()
	{
		$this->tpl = new Smarty();
		$this->tpl->template_dir = $_SERVER['DOCUMENT_ROOT']."/ds/templates/";
		$this->tpl->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/CacheFiles/';
		$this->tpl->compile_check = true;
		
		//—мотрим, открыта ли викторина
		if (!TMP_QuizDBWoker::IsVictirActive($this->id_quiz))
		{
			//¬ыводим сообщение, что викторина открыта
			$this->tpl->assign("ACTION", 'CLOSE');
		} else {

			self::_getPostData();

			switch ($this->action)
			{
				case 'index':
					self::_index();
					break;

				case 'startQuiz':
					self::_startQuiz();
					break;

				case 'quiz_comtiniu':
					self::_setAnswer();
					break;

				case 'sabmitAnswer':
					//ѕовер€ем, не пустой ли вопрос
					if (!empty($this->id_answer))
					{
						//ѕровер€м не был ли этот вопрос уже запощен(например, если мы нажали F5)
						if (!TMP_QuizDBWoker::IsQuestionWasSubmited($this->id_result, $this->id_question))
						{
							self::_sabmitAnswer();
						}
					}

					self::_setAnswer();
					break;

				default:
					$this->tpl->assign("ACTION", 'ERROR');
					break;
			}

			$this->tpl->assign("ART", $this->art);
		}

		$this->tpl->display('quiz.tpl');
	}

	private function _index()
	{
		if (empty($this->art))
		{
			$this->tpl->assign("ACTION", 'ERROR');
			return;
		}
		
		//«апоминаем значени€ базы и мира, из которой пришел игрок
		assert(key_exists('dbId', $_SESSION));
		assert(key_exists('world', $_SESSION));
		
		assert($_SESSION['dbId'] >= 0);
		assert($_SESSION['world'] > 0);
		
		
		$this->tpl->assign("dbId", $_SESSION['dbId']);
		$this->tpl->assign("world", $_SESSION['world']);

		//—мотрим, есть ли такой арт
		$arsExist = TMP_QuizDBWoker::IsExistArtefactID($this->art, $_SESSION['dbId']);
		
		if (empty($arsExist))
		{
			$this->tpl->assign("ACTION", 'ERROR');
			return;
		} else {			
			if (!is_null($arsExist['prize_artefact']))
			{
				$this->tpl->assign("ACTION", 'ERROR');
				return;	
			}
		}

		//—мортрим, есть ли у игрока викторина по этому арту
		$quiz = TMP_QuizDBWoker::IsExistNotEndedQuiz($this->art);

		if (!empty($quiz))
		{
			assert(isset($quiz['id_result']));
			assert(isset($quiz['id_user']));
			
			//ѕровер€ем игрока
			assert($quiz['id_user'] == $this->user->GetId());
			
			//—мотрим, не закончена ли уже викторина
			if (!empty($quiz['end_date']))
			{
				$this->tpl->assign("ACTION", 'QUIZ_ALREADY_ENDED');
				return;
			}
			
			//≈сли была
			$this->tpl->assign("ID_RESULT", $quiz['id_result']);
			$this->tpl->assign("ACTION", 'QUIZ_CONTINIU');
			
		} else {
			//ѕоказываем стартовую страничку
			$this->tpl->assign("ACTION", 'INDEX');
		}
	}
	
	private function _startQuiz()
	{	
		//ѕровер€ем, не активирована ли еще эта викторина
		$quiz = TMP_QuizDBWoker::IsExistNotEndedQuiz($this->art);
		
		if (!empty($quiz))
		{
			assert(isset($quiz['id_result']));
			assert(isset($quiz['id_user']));
			
			//ѕровер€ем игрока
			assert($quiz['id_user'] == $this->user->GetId());
			
			$this->id_result = (int)$quiz['id_result'];
		} else {
			
			$worldCollection = TMP_QuizDBWoker::GetWorldCollectionByIdWorld($this->dbId, $this->world);
			
			$this->id_result = (int)TMP_QuizDBWoker::GetNextValIdResult();
			TMP_QuizDBWoker::StartQuiz((int)$this->user->GetId(), (int)$worldCollection, $this->art, $this->id_result);

			//«аписываем дату активации арта
			TMP_QuizDBWoker::WriteActivationDate($this->art, (int)$this->dbId);
		}
		
		//«апоминаем это значение
		$this->tpl->assign("ID_RESULT", $this->id_result);

		self::_setAnswer();
		
		$this->tpl->assign("ACTION", 'SHOW_QUESTION');
		
		return ;
	}
		

	private function _setAnswer()
	{	
		//ѕровер€ем количество уже заданных вопросов
		$countAnsweredQuestion = TMP_QuizDBWoker::GetCountAnsweredQuestion($this->id_result);

		//ѕолучаем количество вопросов, которые должны задаватьс€ игроку
		$questionCountSettings = TMP_QuizDBWoker::GetQuestionCountSettings($this->id_quiz);

		if (!empty($countAnsweredQuestion))
		{
			if ($countAnsweredQuestion >= $questionCountSettings)
			{
				$this->_endQuiz($questionCountSettings);
				return;
			}
		}

		$idQuestion = (int)self::_generateQuestion();

		//¬ыбираем вопрос по его id
		$question = TMP_QuizDBWoker::GetQuestionByIdQuestion($idQuestion);

		//выбирает ответы на вопрос
		$answers = TMP_QuizDBWoker::GetAnswerByIdQuestion($idQuestion);

		$this->tpl->assign("ID_QUESTION", $question[0]['id_question']);
		$this->tpl->assign("QUESTION", $question[0]['question']);
		$this->tpl->assign("ANSWERS", $answers);

		$this->tpl->assign("ID_RESULT", $this->id_result);
		$this->tpl->assign("ACTION", 'SHOW_QUESTION');
		
		$this->tpl->assign("dbId", $this->dbId);
		$this->tpl->assign("world", $this->world);
	}
		
	private function _endQuiz($questionCountSettings)
	{	
		//—мотрим, не была ли викторина уже запощена
		if (!TMP_QuizDBWoker::IsQuizEnded($this->id_result))
		{
			$this->tpl->assign("ACTION", 'QUIZ_ALREADY_ENDED');
			return;
		}
		
		TMP_QuizDBWoker::EndQuiz($this->id_result);

		$prizeArtefactName = TMP_DtfPromoChangeBoxToPresent::Exec((int)$this->dbId, (int)$this->world, $this->art, (int)$questionCountSettings);
		
		//ѕолучаем количество правильных ответов
		$countRightAnswer = TMP_QuizDBWoker:: GetCountRightAnswer($this->id_result);

		$this->tpl->assign("PRIZE_ARTEFACT", $prizeArtefactName);
		$this->tpl->assign("COUNT_RIGHT_ANSWER", $countRightAnswer);
		$this->tpl->assign("ACTION", 'SHOW_RESULT');
	}
	
	private function _generateQuestion()
	{	
		// ѕровер€ем, есть ли у игрока неотвеченный вопрос
		$notAnsweredQuestion = TMP_QuizDBWoker::GetNotAnsweredQuestion($this->id_result);

		//≈сли есть, то задаем его
		if (!empty($notAnsweredQuestion))
		{
			$idQuestion = $notAnsweredQuestion;
		} else {
			
			switch ('result')
			{
				case 'result':
					//¬ыбираем все вопросы, которые уже были заданы игроку в пределах данного idResalt
					$oldQuestion = TMP_QuizDBWoker::GetUserQuestionInResult($this->id_result);
					break;
					
				case 'quiz':
					//¬ыбираем все вопросы, которые уже были заданы игроку
					$oldQuestion = TMP_QuizDBWoker::GetUserQuestion($this->user->GetId());
					break;
					
			}

			//¬ыбираем из базы случайный вопрос
			$idQuestion = self::_getRandomQuestion();

			if (!empty($oldQuestion))
			{
				while (in_array($idQuestion, $oldQuestion))
				{
					$idQuestion = self::_getRandomQuestion();
				}
			}
			//«аносим в базу сведени€ о заданном вопросе
			TMP_QuizDBWoker::SetQuestion($this->id_result, (int)$idQuestion);
		}
		
		return $idQuestion;		
	}
	
	private function _getRandomQuestion()
	{	
		$questions = TMP_QuizDBWoker::GetAllQuestionsId();
		
		foreach ($questions as $key => $question)
		{
			$questionsId[] = $question['id_question'];
		}
	
		$questoinRnd = array_rand($questionsId);
		
		return $questionsId[$questoinRnd];
	}
	
	private function _sabmitAnswer()
	{	
		$isTrue = TMP_QuizDBWoker::IsAnswerTrue($this->id_answer);
		
		TMP_QuizDBWoker::SubmitAnswer($this->id_question, $this->id_answer, $this->id_result, $isTrue);
		
		//≈сли ответ на вопрос был правильный, то увеличиваем в базе счетчик правильных ответов
		if ($isTrue == 't')
		{
			TMP_QuizDBWoker::AddRightAnswer($this->id_result);
		}
		
		return;
	}
	
	private function _getPostData()
	{				
		if (isset($_REQUEST['art']))
		{
			$this->art = $_REQUEST['art'];
		}

		if (isset($_POST['action']))
		{
			$this->action = $_POST['action'];
		}
		
		if (isset($_POST['id_result']))
		{
			$this->id_result = (int)$_POST['id_result'];
		}
		
		if (isset($_POST['id_question']))
		{
			$this->id_question = (int)$_POST['id_question'];
		}
		
		if (isset($_POST['id_answer']))
		{
			$this->id_answer = (int)$_POST['id_answer'];
		}
		
		if (isset($_POST['art']))
		{
			$this->art = $_POST['art'];
		}
		
		if (isset($_POST['dbId']))
		{
			$this->dbId = $_POST['dbId'];
		}

		if (isset($_POST['world']))
		{
			$this->world = $_POST['world'];
		}
	}
	
}


?>