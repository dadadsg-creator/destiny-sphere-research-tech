<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit.php";
require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/simpleedit_without_cvs.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

require_once "./classes/Search.class.php";

if (!key_exists('action', $_REQUEST)) {
	$_REQUEST['action'] = 'currentpoll';
}
?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<?php 
		if ($_REQUEST['action'] == 'currentpoll') {
			echo '
			<td>
		   		 &nbsp;&nbsp;<font color="Yellow">Tекущие вопросы</font>&nbsp;&nbsp;
		    </td>';
		} else {
			echo '
			<td>
		   		 <a href="?action=currentpoll">&nbsp;&nbsp;Tекущие вопросы&nbsp;&nbsp;</a>
		    </td>';
		}

		echo '
			 <td>
		    	<img src="http://unlim/Admin/img/menu-arrow.gif">
		    </td>';

		if ($_REQUEST['action'] == 'createpoll') {
			echo '
			<td>
		   		 &nbsp;&nbsp;<font color="Yellow">Добавить вопрос</font>&nbsp;&nbsp;
		    </td>';
		} else {
			echo '
			<td>
		   		<a href="?action=createpoll">&nbsp;&nbsp;Добавить вопрос&nbsp;&nbsp;</a>
		    </td>';
		}

		echo '
			 <td>
		    	<img src="http://unlim/Admin/img/menu-arrow.gif">
		    </td>';

		if ($_REQUEST['action'] == 'showAllpoll') {
			echo '
			<td>
		   		 &nbsp;&nbsp;<font color="Yellow">Список вопросов</font>&nbsp;&nbsp;
		    </td>';
		} else {
			echo '
			<td>
		   		<a href="?action=showAllpoll">&nbsp;&nbsp;Список вопросов&nbsp;&nbsp;</a>
		    </td>';
		}

		echo '
			 <td>
		    	<img src="http://unlim/Admin/img/menu-arrow.gif">
		    </td>';

		if ($_REQUEST['action'] == 'showStat') {
			echo '
			<td>
		   		 &nbsp;&nbsp;<font color="Yellow">Статистика</font>&nbsp;&nbsp;
		    </td>';
		} else {
			echo '
			<td>
		   		<a href="?action=showStat">&nbsp;&nbsp;Статистика&nbsp;&nbsp;</a>
		    </td>';
		}
		?>
	 </tr> 
	 <tr> 
	    <td>
	   	 	&nbsp;
	    </td>
    </tr> 
</table>

<?php

class Poll
{
	private $action = 'currentpoll';
	private $question = '';
	private $worlds = array();
	private $id_question;
	private $answerArr = array();
	private $questionName = "";
	private $common = "";
	private $countOfAnswers = 10;

	function __construct($data)
	{
		if (key_exists('action', $data)) {
			$this->action = $data['action'];
		}

		if (key_exists('question', $data)) {
			$this->question = $data['question'];
		}

		if (key_exists('worlds', $data)) {
			if (empty($data['worlds'])) {
				echo "<font color=red><h2>Enter worlds!</h2></font><br>";
				return;
			}
			$this->worlds = $data['worlds'];
		}

		if (key_exists('questionName', $data)) {
			$this->questionName = $data['questionName'];
		}

		if (key_exists('answer', $data)) {
			$this->answerArr = $data['answer'];
		}

		if (key_exists('id_question', $data)) {
			$this->id_question = (int)$data['id_question'];
			assert(!empty($this->id_question));
		}

	}

	function Exec()
	{
		switch ($this->action)
		{
			case 'currentpoll':
				$this->showCurrentPoll();
				break;
			case 'createpoll':
				$this->createPoll();
				break;
			case 'showAllpoll':
				$this->showAllPolls();
				break;
			case 'addpoll':
				if (!$this->addPoll()) {
					$this->createPoll();
				};
				break;
			case 'madeActive':
				$this->madeActive();
				$this->showAllPolls();
				break;
			case 'madeUnActive':
				$this->madeUnActive();
				$this->showAllPolls();
				break;
			case 'showStat':
				$this->showStat();
				break;
			case 'showStatQuestion':
				$this->showStatQuestion();
				break;
			case 'editQuestionMenu':
				$this->editQuestionMenu();
				break;
			case 'editPoll':
				$this->editQuestion();
				$this->editQuestionMenu();
				break;
			default:
				echo 'не опознано действие: '.$this->action;
				break;
		}
	}

	function showCurrentPoll()
	{
		//Получаем список активных вопросов
		$questions = Search::GetActiveQuestions();

		//если не было выбрано ни одного активного вопроса
		if (empty($questions)) {
			echo '<br><h3><font color="Aqua">Нет активных вопросов</font></h3><br>';
			return;
		}

		foreach ($questions as $keyQuestion => $question)
		{
			$answers = Search::GetAnswersByIdQuestion((int)$question['id_question']);
			self::_OutJavaScript();

			echo '
			<table border=0>
				<tr>
					<td class=maintxt_med>
			      	<table width=100% border=0>    
			        <tr>
			            <td class=maintxt_med>
			            	<a href="#" onclick="return display(\''.$question['id_question'].'\')">
			            		<b>'.$question['question'].'</b>
			            	</a>
			            </td>
			        </tr>
			        <tr id="'.$question['id_question'].'" style="display:none;">
				        <td>
				        	<table border=0 width="100%">';
			foreach ($answers as $answerKey => $answer)
			{
				echo "
					<tr>
			    		<td class=maintxt_med><p>".($answerKey+1)." ".$answer['answer']."</td>
			        </tr>";
			}
				echo "
						</table>
				     </td>
				</tr>
			</table>";

			echo "	</td>
					</tr>
					";

			echo "</table>";
		}

	}

	function createPoll()
	{
		//Выбираем все миры
		GE_SearchBase::SetCommon(DS_Common::CreateForCurentDB(0));
		$worlds = GE_SearchStart::GetWorldCollection(3);

		?>
		<form action="" method=post>
		<input type="hidden" name="action" value="addpoll">
		<TABLE border="0">
			<tr>
				<th>
					<font color=#FFFFFF>Название вопроса</font>
				</th>
			</tr>
			<tr>
				<th>
					<input type="text" name="questionName" size="150" value="<?php echo $this->questionName ?>">
				</th>
			</tr>
			<tr>
				<th>
					&nbsp;<br>
				</tr>
			<tr>
			<tr>
				<th>
					<font color=#FFFFFF>Вопрос</font>
				</th>
			</tr>
			<tr>
				<th>
					<textarea name="question" rows="10" cols="150"><?php echo $this->question ?></textarea>
				</th>
			</tr>
			<tr>
				<th>
					&nbsp;<br>
				</tr>
			<tr>
		</TABLE>
		
		<TABLE border="0" cellpadding="5" >	
			<tr>
				<th>
					<font color=#FFFFFF>Текст ответа</font>
				</th>
			</tr>
			
			<?php 
			for ($i = 0; $i < $this->countOfAnswers; $i++)
			{
				if (key_exists($i, $this->answerArr)) {
					$value = 'value="'.$this->answerArr[$i].'"';
				} else {
					$value = '';
				}
				echo '<tr>
					<th>
						<input type="text" size="100" name="answer[]" '.$value.'>
					</th>
				</tr>';
			}
			?>
			
			</TABLE>
			
		<TABLE border="0" cellpadding="5" >	
			<tr>
				<th>
					<font color=#FFFFFF>Миры</font>
				</th>
			</tr>
			<tr>
				<th>
					<select name="worlds[]" multiple size="<?Php echo count($worlds) ?>">
					<?php 
					foreach ($worlds as $key => $data)
					{
						echo '<option value="'.$data['dbname'].'.'.$data['id_world'].'">'.$data['worldname'].'</option>
						';
					}
					?>
					</select>
				</th>
			</tr>
			
			</TABLE>
		<input type="submit" class="Submit" value="Добавить">
		</form>
			   
		<?php
	}

	function editQuestionMenu()
	{
		assert($this->id_question > 0);
		
		//Получаем данные о вопросе
		$questionInfo = Search::GetQuestionInfoById($this->id_question);		

		//Получаем ответы на вопросы
		$questionAnswers = Search::GetAnswersByIdQuestion($this->id_question);
		
		//Получаем миры вопроса
		$questionWorlds = Search::GetQuestionWorlds($this->id_question);
		
		//Выбираем все миры
		GE_SearchBase::SetCommon(DS_Common::CreateForCurentDB(0));
		$worlds = GE_SearchStart::GetWorldCollection(3);
		
		foreach ($questionWorlds as $key => $world)
		{
			$questionWorlds[$key] = $world['worldname'];
		}

		?>
		<form action="" method=post>
		<input type="hidden" name="action" value="editPoll">
		<input type="hidden" name="id_question" value="<?php echo $this->id_question; ?>">
		<TABLE border="0">
			<tr>
				<th>
					<font color=#FFFFFF>Название вопроса</font>
				</th>
			</tr>
			<tr>
				<th>
					<input type="text" name="questionName" size="150" value="<?php echo $questionInfo[0]['question_name']; ?>">
				</th>
			</tr>
			<tr>
				<th>
					&nbsp;<br>
				</tr>
			<tr>
			<tr>
				<th>
					<font color=#FFFFFF>Вопрос</font>
				</th>
			</tr>
			<tr>
				<th>
					<textarea name="question" rows="10" cols="150"><?php echo $questionInfo[0]['question']; ?></textarea>
				</th>
			</tr>
			<tr>
				<th>
					&nbsp;<br>
				</tr>
			<tr>
		</TABLE>
		
		<TABLE border="0" cellpadding="5" >	
			<tr>
				<th>
					<font color=#FFFFFF>Текст ответа</font>
				</th>
			</tr>
			
			<?php 

			foreach ($questionAnswers as $key => $questionAnswer)
			{
				echo '<tr>
					<th>
						<input type="text" size="100" name="answer['.$questionAnswer['id_answer'].']" value="'.$questionAnswer['answer'].'">
					</th>
				</tr>';
			}
			?>
			
			</TABLE>
			
		<TABLE border="0" cellpadding="5" >	
			<tr>
				<th>
					<font color=#FFFFFF>Миры</font>
				</th>
			</tr>
			<tr>
				<th>
				
				<select name="worlds[]" multiple>
					<?php 					
					
					foreach ($worlds as $key => $data)
					{
						if (in_array($data['worldname'], $questionWorlds)) {
							$selected = 'selected';
						} else {
							$selected = '';
						}
						
						echo '<option value="'.$data['dbname'].'.'.$data['id_world'].'" '.$selected.'>'.$data['worldname'].'</option>
						';
					}
					?>
					</select>
				</th>
			</tr>
			
			</TABLE>
			<input type="submit" class="Submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Edit&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;">
		</form>
			   
		<?php
	}

	function editQuestion()
	{
		assert($this->id_question > 0);
		
		if (empty($this->questionName)) {
			echo "<font color=red><h2>Error: Enter question name!</h2></font><br>";
			return false;
		}
		if (empty($this->question)) {
			echo "<font color=red><h2>Error: Enter question!</h2></font><br>";
			return false;
		}
		$countAnswer = 0;

		foreach ($this->answerArr as $key => $answer)
		{
			if (empty($answer)) {
				echo "<font color=red><h2>Error: Enter answers!</h2></font><br>";
				return false;
			}
			$countAnswer ++;
		}

		if ($countAnswer == 0)
		{
			echo "<font color=red><h2>Error: Enter answers!</h2></font><br>";
			return false;
		}

		if ($countAnswer == 1)
		{
			echo "<font color=red><h2>Error: Count of answers could be minimum 2!</h2></font><br>";
			return false;
		}
		if (empty($this->worlds) || !is_array($this->worlds)) {
			echo "<font color=red><h2>Error: Enter worlds!</h2></font><br>";
			return false;
		}

		assert(is_array($this->worlds));

		//Редактируем вопрос
		Search::EditQuestion($this->id_question, $this->question, $this->questionName, $this->answerArr, $this->worlds);

		//Логируем
		actionLog( "Edit Poll", 'Question Name: '.$this->questionName);
		
		ShowCMSMessage('OK', 1);
		return true;
	}

	function addPoll()
	{
		if (empty($this->questionName)) {
			echo "<font color=red><h2>Error: Enter question name!</h2></font><br>";
			return false;
		}
		if (empty($this->question)) {
			echo "<font color=red><h2>Error: Enter question!</h2></font><br>";
			return false;
		}
		$countAnswer = 0;

		foreach ($this->answerArr as $key => $answer)
		{
			if (strlen($answer) != 0)
			{
				$countAnswer ++;
			}
		}

		if ($countAnswer == 0)
		{
			echo "<font color=red><h2>Error: Enter answers!</h2></font><br>";
			return false;
		}

		if ($countAnswer == 1)
		{
			echo "<font color=red><h2>Error: Count of answers could be minimum 2!</h2></font><br>";
			return false;
		}
		if (empty($this->worlds) || !is_array($this->worlds)) {
			echo "<font color=red><h2>Error: Enter worlds!</h2></font><br>";
			return false;
		}

		assert(is_array($this->worlds));

		//Получаем id следующего вопроса
		$nextVal = Search::GetNextValIdQuestion();

		//Записываем в базу сам вопрос
		Search::AddQuestion($nextVal, $this->question, $this->questionName);

		//Проходимся по каждому из миров, для игроков которого будет задан вопрос
		foreach ($this->worlds as $key => $data)
		{
			$temp = explode('.', $data);
			$dbname = $temp[0];
			$id_world = (int)$temp[1];

			assert(!empty($dbname));
			assert($id_world > 0);

			//Записываем в базу в каких мирах будет доступен вопрос
			Search::AddQuestionWorlds($nextVal, $dbname, $id_world);
		}

		foreach ($this->answerArr as $key => $answer)
		{
			if (!empty($answer))
			{
				//Записываем в базу ответы на вопрос
				Search::AddQuestionAnswers($nextVal, $answer);
			}
		}
			
		//Логируем
		actionLog( "Create Poll", 'Question Name: '.$this->questionName);
		
		ShowCMSMessage('Вопрос добавлен', 1);
		return true;
	}

	function madeActive()
	{
		Search::SetQuestionIsActive($this->id_question);
		
		//Получаем название по его id (для логирования)
		$question = Search::GetQuestionInfoById($this->id_question);
		//Логируем
		actionLog( "Activate Poll", 'Question Name: '.$question[0]['question_name']);
		
		ShowCMSMessage('Вопрос активирован', 1);
	}

	function madeUnActive()
	{
		Search::SetQuestionNotActive($this->id_question);
		
		//Получаем название по его id (для логирования)
		$question = Search::GetQuestionInfoById($this->id_question);
		//Логируем
		actionLog( "Deactivate Poll", 'Question Name: '.$question[0]['question_name']);
		
		ShowCMSMessage('Вопрос деактивирован', 1);
	}

	function showStat()
	{
		$questions = Search::GetQuestions();
		
		//Получаем время последнего ответа (дату окончания вопроса)
		//$endDate = Search::GetQuestions();
/*echo "<br>
<br><pre>
";
print_r($questions);
exit;*/
		//если нет ни одного вопроса
		if (empty($questions)) {
			echo '<br><h3><font color="Aqua">Нет вопросов</font></h3><br>';
			return;
		}

		echo '
				<table border=0>
					<tr>';

		foreach ($questions as $key => $data)
		{
			if (empty($data['start_date'])) {
				$date = 'Нет ни одного ответа на вопрос';
			} else {
				$date = 'с '.date("d-m-Y", strtotime($data['start_date'])).' по '.date("d-m-Y", strtotime($data['end_date'])).'';
			}
			
			
			echo '
					<tr>
						<td class=maintxt_med>
							<a tbarget="_blank" href="?action=showStatQuestion&id_question='.$data['id_question'].'"><b>'.$data['question_name'].'</b></a>
						</td>
						<td class=maintxt_med>
							('.$date.')
						</td>
						
					</tr>';
		}

		echo '</table>';

		return;
	}

	function showStatQuestion()
	{
		assert(is_int($this->id_question) && $this->id_question > 0);

		$stat = Search::getQuestionStat($this->id_question);
		$countOfAbstainPlayers = Search::GetCountOfAbstainPlayers($this->id_question);

		echo '
				<table border=0>
					<tr>
						<td class=maintxt_med>
				      		<table width=100% border=0>    
				        <tr>
				            <td class=maintxt_med colspan="2">
				            	<b>'.$stat[0]['question'].'</b> (Дата проведения с '.date("H:i d-m-Y", strtotime($stat[0]['start_date'])).' по '.date("H:i d-m-Y", strtotime($stat[0]['end_date'])).')
				            </td>
				        </tr>
				         <tr>
				            <td class=maintxt_med colspan="2">
				            	&nbsp
				            </td>
				        </tr>';
		foreach ($stat as $key => $data)
		{
			echo '
					<tr>
				    	<td class=maintxt_med>
				    		'.$data['answer'].'
				    	</td>
				    	<td class=maintxt_med>';
			//Рисуем график
			if ($data['total_count'] != 0)
			{
				$persum = $data['count']/$data['total_count']*100;
			}
			else {
				$persum = 0;
			}
			echo "<img src='http://defo.destinysphere.ru/ds/drowimage.php?width=$persum'>";
			echo " ".round($persum, 1)."% (".$data['count']." чел.)";
			echo "		</td>
				   </tr>";
		}
		
		echo '
				<tr>
				    <td class=maintxt_med>
				    	----------
				    </td>
				</tr>';
		
		
		echo '
					<tr>
				    	<td class=maintxt_med>
				    		Воздержались
				    	</td>
				    	<td class=maintxt_med>';
		//Рисуем график
		if ($data['total_count'] != 0)
		{
			$persum = $countOfAbstainPlayers/$stat[0]['total_count']*100;
		}
		else {
			$persum = 0;
		}
		echo "<img src='http://defo.destinysphere.ru/ds/drowimage.php?width=$persum'>";
		echo " ".round($persum, 1)."% (".$countOfAbstainPlayers." чел.)";
		echo "		</td>
				   </tr>";
			
		echo "
				<tr>
				    <td class=maintxt_med>
				    	&nbsp;
				    </td>
				</tr>
				<tr>
				    <td class=maintxt_med>
				    	<i>Всего приняло в опросе <b>".($stat[0]['total_count'])."</b> игроков.</i><br>
				    </td>
				</tr>
		</table>";

		echo "	</td>
			</tr>
			";

		echo "
				<tr>
					<td>&nbsp;</td>
				</tr>
				";
		echo "</table>";

		return;
	}

	function showAllPolls()
	{
		//Получаем все вопросы
		$questions = Search::GetQuestions();
		
		self::_OutJavaScript();

		//если не было выбрано ни одного вопроса
		if (empty($questions)) {
			echo '<br><h3><font color="Aqua">Нет вопросов</font></h3><br>';
			return;
		}

		echo "
		<table border=0>";

		foreach ($questions as $key => $question)
		{
			echo "
				<tr>
					<td class=maintxt_med>
				";

			// Выбираем ответы
			$answers = Search::GetQuestionAnswers((int)$question['id_question']);

			// Выбираем миры
			$worlds = Search::GetQuestionWorlds((int)$question['id_question']);

			$worldList = "";
			$comma = "";
			foreach ($worlds as $key => $world)
			{
				$worldList .= $comma.$world['worldname'];
				$comma = ", ";
			}

			//Смотрим, активный ли вопрос
			if ($question['is_active'] == 't') {
				$link = ' <a href="?action=madeUnActive&id_question='.$question['id_question'].'"><font style="font-size: 10px"><u>Сделать неактивным</u></font></a>';
				$class = ' class=maintxt';
				$class2 = ' class=maintxt_med';
			} else {
				$link = ' <a href="?action=madeActive&id_question='.$question['id_question'].'"><font style="font-size: 10px" color="Black"><u>Сделать активным</u></font></a>';
				$class = 'style = "font-size: 12px"';
				$class2 = 'style = "font-size: 11px"';
			}


			// Выводим результаты
			echo '
		      <table width=100% border=0 font="#696969" >    
		        <tr>
		            <td '.$class.'>
		            	<a href="#" onclick="return display(\''.$question['id_question'].'\')">
			            	<b>'.$question['question_name'].'</b>
			            </a>
		            	'.$link.'
		            	 <a href="?action=editQuestionMenu&id_question='.$question['id_question'].'"><img src="/Admin/img/knopka_edit.gif" border="0" alt="Edit"></a>
		            </td>
		        </tr>
		        <tr id="'.$question['id_question'].'" style="display:none;">
				        <td>
				        	<table border=0 width="100%">
						         <tr>
						            <td '.$class.'>'.$question['question'].'</td>
						        </tr>';

							foreach ($answers as $answerKey => $answer)
							{
								echo "
								<tr>
							    	<td $class><p>".($answerKey+1)." ".$answer['answer']."</td>
							     </tr>";
							}

							echo "
								<tr>
						    		<td $class>
						    			<font style='font-size: 10px'>Миры: $worldList</font>
						    		</td>
						    	</tr>
						    </table>
						 </td>
				</tr>
			</table>";
		}
		echo "</table>";
	}
	
	static private function _OutJavaScript()
	{
		echo '<script  type=text/javascript>
					function display(elem)
					{
				    	if (document.getElementById(elem).style.display != "") 
				    	{ 
				        	document.getElementById(elem).style.display = "";
				        } else {
				              document.getElementById(elem).style.display = "none";
				        }
				        return false;
				    }
				   </script>';
	}
}

$poll = new Poll($_REQUEST);

$poll -> Exec();


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>