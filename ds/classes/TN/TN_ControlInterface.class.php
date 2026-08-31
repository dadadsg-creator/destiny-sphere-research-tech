<?php

/**

Реализовано
        - хранение и использование заранее заданных наборов натроек
                в шаблон входит - список запрещенных зданий, список значений модифаеров, шаблон соты
        - старт, пауза, остановка турнира и пересчет рейтинга

Подготовка турниров, нужно сделать
        - оплата участия через кабинет и перенос регистрационных данных в базу турнира 2 ч.
        - входа в турнир (небольшое изменение механизма логина) 1 ч.
        - военный рейтинг для боевого турнира 2 ч.
        - военный рейтинг квестового турнира 3 ч.
        - интерфейсы админские (старт, стоп, выбор шаблона ..) 3 ч.

        - турнирный рейтинг (очки за последний турнир, очки за все турниры, просмотр, архив) 4 ч.
        - награждение по результатам турнира 2 ч.
        
        - тестирование (общая работоспособность всех механизмов, без проведения тестовых турниров с игроками) 4 ч.

 */

class TN_ControlInterface
{
	static private $action = 'index';
	
	static private $cpAction;
	
	static private $template;
	
	static private $comments;
	
	static private $turnirID;
	
	static private $data;
	
	static private $templateID;
	
	static public function Exec(array $data)
	{
		self::_getParams($data);
		
		switch (self::$action)
		{
			case 'index':
				self::_outIndex();
				break;
		
			case 'create':
				self::_outCreateMenu();
				break;
				
			case 'control':
				self::_outControlPanel();
				break;
				
			case 'cp':
				self::_turnirControl();
				break;
				
			case 'up_ranking':
				self::_updateRanking();
				break;
				
			case 'list':
				self::_outTurnirList();
				break;
				
			case 'create_new':
				self::_createNewTurnir();
				break;
				
			case 'delete':
				self::_deleteTurnir();
				break;
				
			case 'clear':
				self::_clearWorld();
				break;
				
			case 'template':
				self::_templateControl();
				break;
				
			case 'modifier':
				break;
				
			case 'building':
				break;
				
			case 'comb_tpl':
				break;
				
			default:
				break;
		}
	}
	
	static private function _getParams(array $data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}
		
		if(key_exists('template', $data))
		{
			self::$template = (int)$data['template'];
			assert(self::$template > 0);
		}
		
		if(key_exists('comment', $data))
		{
			self::$comments = $data['comment'];
		}
		
		if(key_exists('cp', $data))
		{
			self::$cpAction = $data['cp'];
		}
		
		if(key_exists('id', $data))
		{
			self::$turnirID = (int)$data['id'];
			assert(self::$turnirID > 0);
		}
		
		if(key_exists('id_t', $data))
		{
			self::$templateID = (int)$data['id_t'];
			assert(self::$templateID > 0);
		}
		
		self::$data = $data;
	}
	
	static private function _outIndex($msg = '')
	{
		TN_ControlInterfaceOut::OutIndex($msg);
	}
	
	static private function _outCreateMenu()
	{
		$templates = TN_TurnirController::GetTemplates();
		
		$msg = '';
		
		if(count(TN_Search::GetNotStartedTurnirs()) > 0)
		{
			$msg = 'NOT_STARTED_TURNIR_FOUND';
		}
		
		if(count(TN_Search::GetNotFinishedTurnirs()) > 0)
		{
			$msg = 'NOT_ENDED_TURNIR_FOUND';
		}
		
		if(TN_Search::GetCountInstances() > 0)
		{
			$msg = 'NOT_EMPTY_WORLD';
		}
		
		TN_ControlInterfaceOut::OutSettings($templates, $msg);
	}
	
	static private function _outControlPanel($msg)
	{
		TN_ControlInterfaceOut::OutControlPanel($msg);
	}
	
	static private function _updateRanking()
	{
		TN_TurnirController::UpdateRanking();
		$msg = 'RANKING UPDATED';
		
		self::_outIndex($msg);
	}
	
	static private function _outTurnirList()
	{
		$list = TN_Search::GetAllTurnirs();
		
		TN_ControlInterfaceOut::OutTurnirList($list);
	}
	
	static private function _createNewTurnir()
	{
		TN_TurnirController::CreateNewTurnir(self::$template, self::$comments);
		
		self::_outIndex('TURNIR CREATED');
	}
	
	static private function _deleteTurnir()
	{
		TN_Search::DeleteTurnir(self::$turnirID);
		
		self::_outTurnirList();
	}
	
	static private function _clearWorld()
	{
		TN_ControlInterfaceOut::OutClearWorld();
		
		if(self::$cpAction == 'clear')
		{
			DU_ClearWorld::Clear(TN_TurnirController::GetDBID(), TN_TurnirController::GetWorldID());
		} 
	}
	
	static private function _turnirControl()
	{
		switch (self::$cpAction)
		{
			case 'stop':
				TN_TurnirController::StopTurnir();
				$msg = 'TURNIR STOPED';
				break;
				
			case 'start':
				TN_TurnirController::StartTurnir();
				$msg = 'TURNIR STARTED';
				break;
				
			case 'pause':
				TN_TurnirController::PauseTurnir();
				$msg = 'TURNIR PAUSED';
				break;
		}
		
		self::_outControlPanel($msg);
	}
	
	
	static private function _templateControl()
	{
		switch (self::$cpAction)
		{
			case 'edit_m':
				self::_outTemplateModifierEdit();
				break;
				
			case 'do_edit_m':
				self::_editTemplateModifiers();
				break;
				
			case 'edit_b':
				self::_outTemplateBuildingsEdit();
				break;
				
			case 'do_edit_b':
				self::_editTemplateBuildings();
				break;
				
			case 'add_new':
				self::_addNewTemplate();
				break;
				
			case 'del_template':
				self::_delTemplate();
				break;
						
			default:
				self::_outTemplateEditIndex();
		}
	}
	
	static private function _outTemplateEditIndex($error = false)
	{
		$templates = TN_TurnirController::GetTemplates();
		TN_ControlInterfaceOut::OutTemplateEdit($templates, $error);
	}
	
	static private function _outTemplateBuildingsEdit()
	{	
		$allowed = TN_Search::GetNotDisabledBuildings(self::$templateID);
		$disabled = TN_Search::GetDisabledBuildings(self::$templateID);
		
		TN_ControlInterfaceOut::OutTemplateBuildingsEditForm(self::$templateID, $allowed, $disabled);
	}
	
	static private function _editTemplateBuildings()
	{
		$disable = false;
		
		if(key_exists('disable', self::$data))
		{
			$id = (int)self::$data['disable'];
			assert($id > 0);
			$disable = true;
		}
		
		if(key_exists('enable', self::$data))
		{
			$id = (int)self::$data['enable'];
			assert($id > 0);
		}
		
		if($id > 0)
		{
			if($disable)
			{
				TN_Search::AddDisabledBuildingToTemplate(self::$templateID, $id);
			} else {
				TN_Search::RemoveDisabledBuildingFromTemplate(self::$templateID, $id);
			}
		}
		
		self::_outTemplateBuildingsEdit();
	}
	
	static private function _outTemplateModifierEdit()
	{		
		$modifiers = TN_Search::GetAllModifiers(self::$templateID);
		TN_ControlInterfaceOut::OutTemplateModifierEdit($modifiers);
	}
	
	static private function _editTemplateModifiers()
	{
		if(!key_exists('m', self::$data) || !is_array(self::$data['m']))
		{
			return;
		}
		
		foreach (self::$data['m'] as $modifier => $value)
		{
			if(TN_Search::CheckTemplateModifier($modifier, $value))
			{
				TN_Search::InsertOrUpdateTemplateModifier($modifier, self::$templateID, $value);
			}
		}
		
		self::_outTemplateEditIndex();
	}
	
	
	static private function _delTemplate()
	{
		TN_Search::DeleteTemplate(self::$templateID);
		
		self::_outTemplateEditIndex();
	}
	
	static private function _addNewTemplate()
	{
		$ok = true;
		
		if(key_exists('name', self::$data))
		{
			$name = self::$data['name'];
		} else {
			$ok = false;
		}
		
		if(key_exists('descr', self::$data))
		{
			$descr = self::$data['descr'];
		} else {
			$ok = false;
		}
		
		$templates = TN_TurnirController::GetTemplates();
		foreach ($templates as $k => $data)
		{
			if($name == $data['template_name'])
			{
				$ok = false;
				break;
			}
		}
		
		if($ok)
		{
			TN_Search::AddTurnirTemplate($name, $descr);			
		}
		
		self::_outTemplateEditIndex(!$ok);
	}
}

?>