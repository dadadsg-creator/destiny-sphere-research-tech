<?php

class SU_TradeGraphics
{
	static private $action = 'imdex';
	
	static private $worldID;
	
	static private $dbID;
	
	static private $idArticle;
	
	static private $idArticleFor;
	
	static private $idCategory;
	
	static private $idCategoryFor;
	
	static private $start = array();
	
	static private $end = array();
	
	static private $unitName = '';
	
	static private $resourse;
	
	static private $artScope;
	static private $artSize;
					
	static private $detalizationArr = array(
										'year',
										'month',
										'day',
										'hour',
										'minute'
										);
	
	// Уровень детализации (месяц, день час)
	static private $detalization;

	static function Exec($data)
	{
		self::_getParams($data);
		switch (self::$action) {
			case 'show':
				self::_ShowIndex();
				self::_Show();
				break;
			case 'showIndex':
				self::_ShowIndex();
				break;
			default:
				self::_ShowWorldList();
				break;
		}
	}
	
	static private function _Show()
	{
		if (empty(self::$idArticle)) {
			echo '<font color="Red">Выберите товар</font>';
			return false;
		}
		
		if (empty(self::$idArticleFor)) {
			echo '<font color="Red">Выберите за что шли продажи</font>';
			return false;
		}
		
		assert(is_int(self::$idArticle) && self::$idArticle > 0);
		
		switch (self::$idArticle)
		{
			// ресурс
			case 1:
				self::$idCategory = (int)self::$resourse;
				if (empty(self::$idCategory)) {
					echo '<font color="Red">Выберите ресурс</font>';
					return false;
				}
				
				assert(self::$idCategory) > 0;
				$flag = false;
				break;
			// юнит
			case 2:
				if (empty(self::$unitName)) {
					echo '<font color="Red">Выберите имя юнита</font>';
					return false;
				}
				self::$idCategory = (int)SU_TradeGraphicsSearch::GetUnitIdByName(self::$dbID, self::$unitName);
				if (empty(self::$idCategory)) {
					echo '<font color="Red">Такого юнита не существует</font>';
					return false;
				}
				assert(self::$idCategory) > 0;
				$flag = false;
				break;
			// арт
			case 3:
				if (empty(self::$artScope)) {
					echo '<font color="Red">Выберите скоуп артефакта</font>';
					return false;
				}
				if (empty(self::$artSize)) {
					echo '<font color="Red">Выберите размер артефакта</font>';
					return false;
				}
				$idCategory = SU_TradeGraphicsSearch::getArtefactIdBySizeAndScope(self::$dbID, self::$artScope, self::$artSize);
				$flag = true;			
				self::$idCategory = '';
				$comma = '';
				foreach ($idCategory as $key => $data)
				{
					self::$idCategory .= $comma.$data['id_artefact'];
					$comma = ', ';
				}				
				break;
		}
		
				
		$dateFrom = gmmktime(self::$start[3], 0, 0, self::$start[1], self::$start[2], self::$start[0]);
		$dateTo = gmmktime(self::$end[3], 0, 0, self::$end[1], self::$end[2], self::$end[0]);
		   
		echo '<img src="tradegraphic.php?d='.self::$dbID.'&w='.self::$worldID.'&Ar='.self::$idArticle.'&Ca='.self::$idCategory.'&ArF='.self::$idArticleFor.'&CaF='.self::$idCategoryFor.'&dF='.$dateFrom.'&dT='.$dateTo.'&f='.(int)$flag.'&det='.self::$detalization.'" border="0" alt="">';
	}
	
	static private function _ShowIndex()
	{
		// Получаем список ресурсов
		$recources = SU_TradeGraphicsSearch::GetAllResources(self::$dbID);
		$artScopes = SU_TradeGraphicsSearch::getArtefactScopes(self::$dbID);
		$artSizes = SU_TradeGraphicsSearch::getArtefactSizes(self::$dbID);

		if (empty(self::$start)) {
			$week = strtotime("-1 week");
			
			self::$start[0] = date('Y', $week);
			self::$start[1] = date('m', $week);
			self::$start[2] = date('d', $week);
			self::$start[3] = date('H', $week);
		}
		
		if (empty(self::$end)) {
			self::$end[0] = date('Y');
			self::$end[1] = date('m');
			self::$end[2] = date('d');
			self::$end[3] = date('H') + 1;
		}
		
		
		
		echo '
		<script language="JavaScript">
		<!-- Скрыть 
		function displayRes() {		    
			document.getElementById("RES").style.display = "";
			document.getElementById("UNT").style.display = "none";
			document.getElementById("ART").style.display = "none";
			return true;
		}
		function displayUnt() {		    
			document.getElementById("UNT").style.display = "";
			document.getElementById("RES").style.display = "none";
			document.getElementById("ART").style.display = "none";
			return true;
		}
		function displayArt() {		    
			document.getElementById("ART").style.display = "";
			document.getElementById("RES").style.display = "none";
			document.getElementById("UNT").style.display = "none";
			return true;
		}
		window.onload = function() 
		{ 
			document.getElementById("RES").style.display = "none";
			document.getElementById("UNT").style.display = "none";
			document.getElementById("ART").style.display = "none";
		}
		// -->
		</script>



		<h2>Визуаизация торговых курсов</h2>
		<form action="" method="POST">
		<input type="hidden" name="action" value="show">
		<input type="hidden" name="world" value="'.self::$dbID.'_'.self::$worldID.'" value="show">
		<table border="0">
			<tr>
			<td>
				<font color=#F8F8FF>Start date:</font> 
				<table border=1>
					<tr class=th_2>
						<td>Year</td><td>month</td><td>day</td><td>hour</td>
					</tr>
					<tr>
						<td><input type="text" size=4 name="start[0]" value="'.self::$start[0].'">&nbsp;-&nbsp;</td>
						<td><input type="text" size=2 name="start[1]" value="'.self::$start[1].'">&nbsp;-&nbsp;</td>
						<td><input type="text" size=2 name="start[2]" value="'.self::$start[2].'">&nbsp; &nbsp;</td>
						<td><input type="text" size=2 name="start[3]" value="'.self::$start[3].'"></td>
					</tr>
				</table>
			</td>
			<td width=250px>
				<font color=#F8F8FF>End date:</font> 
				<table border=1>
					<tr class=th_2>
						<td>Year</td><td>month</td><td>day</td><td>hour</td>
					</tr>
					<tr>
						<td><input type="text" size=4 name="end[0]" value="'.self::$end[0].'">&nbsp;-&nbsp;</td>
						<td><input type="text" size=2 name="end[1]" value="'.self::$end[1].'">&nbsp;-&nbsp;</td>
						<td><input type="text" size=2 name="end[2]" value="'.self::$end[2].'">&nbsp; &nbsp;</td>
						<td><input type="text" size=2 name="end[3]" value="'.self::$end[3].'"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
		</tr>
		<tr>
			<td>
				Детализация:
					<select name="detalization">';
		foreach (self::$detalizationArr as $key => $det)
		{
			if ($det == self::$detalization) {
				$selected = 'selected';
			} else {
				$selected = '';
			}
			echo '
					<option value="'.$det.'" '.$selected.'>'.$det;
		}
					
		echo '	
				</select>				
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
		</tr>
		<tr>
				<td>
					Ресурс
				</td>
			</tr>
			<tr>
				<td>';
		if (self::$idArticle == 1) {
			$selected = 'checked';	
		} else {
			$selected = '';	
		}
		
		echo '
					<input type="radio" name="goods" value="1" onClick="return displayRes();" '.$selected.'>Ресурс<br>
				</td>
			</tr>
			<tr id="RES">
				<td>';
					foreach ($recources as $key => $val)
					{
						if($val['id'] == self::$resourse)
						{
							$selected = 'checked';
						} else {
							$selected = '';
						}
						echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="resourse" value="'.$val['id'].'" '.$selected.'>'.$val['name']."<br>
						";
					}
		if (self::$idArticle == 2) {
			$selected = 'checked';	
		} else {
			$selected = '';	
		}
		
		echo '					
				</td>
			</tr>
			<tr>
				<td>
					<input type="radio" name="goods" value="2" onClick="return displayUnt();" '.$selected.'>Юнит<br>
			</td>
			</tr>
			<tr id="UNT">
				<td>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Имя юнита <br>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="unitName" value="'.self::$unitName.'">
				</td>
			</tr>';
		if (self::$idArticle == 3) {
			$selected = 'checked';	
		} else {
			$selected = '';	
		}
		
		echo '
			<tr>
				<td>
					<input type="radio" name="goods" value="3" onClick="return displayArt();" '.$selected.'>Артефакт<br>
				</td>
			</tr>
			<tr id="ART">
				<td>
					<table>
					<tr>
						<td>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						</td>
						<td valign="top">
							Скоуп:<br>';
			foreach ($artScopes as $key => $data)
			{
				if($data['id'] == self::$artScope)
				{
					$selected = 'checked';
				} else {
					$selected = '';
				}
				echo '
				<input type="radio" name="artScope" value="'.$data['id'].'" '.$selected.'>'.$data['name'].'<br>
							';
			}
			echo'		
						</td>
						<td valign="top">
							Размер:<br>';
			foreach ($artSizes as $key => $data)
			{
				if($data['id'] == self::$artSize)
				{
					$selected = 'checked';
				} else {
					$selected = '';
				}
				echo '<input type="radio" name="artSize" value="'.$data['id'].'" '.$selected.'>'.$data['name'].'<br>
							';

			}
			echo'
						</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					&nbsp;
				</td>
			</tr>
			<tr>
				<td>
					Ресурс за который продаем
				</td>
			</tr>
			<tr>
				<td>';
		
		foreach ($recources as $key => $val)
		{
			if($val['id'] == self::$idCategoryFor)
			{
				$selected = 'checked';
			} else {
				$selected = '';
			}
			echo '<input type="radio" name="goodsfor" value="1_'.$val['id'].'" '.$selected.'>'.$val['name']."<br>
			";
		}	
		echo '
		</table>	
		<br>
		<input type="submit" value="Show">
		</form>
';
	}
	
	
	static private function _ShowWorldList()
	{
		// Получаем список миров
		$worlds = SU_TradeGraphicsSearch::GetAllWorldsList();

		echo "
		<form action='ShowTradeGraphics.php' method='POST'>
		<input type='hidden' name='action' value='showIndex'>
		<table border=0>
			<tr>
				<td valign=top>
					<!--<font color=#F8F8FF><b>Choose world:</b></font>-->
					<b>Choose world:</b>
				</td>
			</tr>
			<tr>
				<td valign=top>";
					
		foreach($worlds as $k => $world)
		{
			if($world['dbid'].'_'.$world['id_world'] == self::$dbID."_".self::$worldID)
			{
				$s = ' selected=1';
			} else {
				$s = '';
			}
			echo 
				'<input type="radio" name="world" value="'.$world['dbid'].'_'.$world['id_world'].'" '.$s.'> '.$world['worldname'].'<br>';

		}
		echo '
				</td>
			</tr>
		</table>
		<input type="submit" value=" OK ">
		</form>';
	}
	
	static private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}

		if(key_exists('world', $data))
		{
			$tmp = explode('_', $data['world']);
			self::$dbID = (int)$tmp[0];
			self::$worldID = (int)$tmp[1];

			assert(self::$dbID >= 0);
			assert(self::$worldID > 0);
		}
		
		if(key_exists('goods', $data))
		{
			self::$idArticle = (int)$data['goods'];
			assert(self::$idArticle > 0);
		}

		if(key_exists('goodsfor', $data))
		{
			$tmp = explode('_', $data['goodsfor']);
			self::$idArticleFor = (int)$tmp[0];
			self::$idCategoryFor = (int)$tmp[1];
			
			assert(self::$idArticleFor > 0);
			assert(self::$idCategoryFor > 0);
		}

		if(key_exists('start', $data))
		{
			self::$start = $data['start'];
		} 

		if(key_exists('end', $data))
		{
			self::$end = $data['end'];
		} 

		if(key_exists('unitName', $data))
		{
			self::$unitName = $data['unitName'];
		}

		if(key_exists('resourse', $data))
		{
			self::$resourse = $data['resourse'];
		}

		if(key_exists('artSize', $data))
		{
			self::$artSize = (int)$data['artSize'];
			assert(self::$artSize > 0);
		}

		if(key_exists('artScope', $data))
		{
			self::$artScope = (int)$data['artScope'];
			assert(self::$artScope > 0);
		}

		if(key_exists('detalization', $data))
		{
			self::$detalization = $data['detalization'];
			assert(!empty(self::$detalization));
		}
	}
}

?>