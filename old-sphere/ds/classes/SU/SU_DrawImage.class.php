<?php
//require_once 'Main.php';
require_once '../../Main.php';

SU_DrawImage::Draw($_GET);

class SU_DrawImage
{
	// Данные
	static private $data = array();
	
	// Рисунок
	static private $im;
	
	
	// Ширина изображения
	static private $width = 800;
	
	// Высота изображения
	static private $height = 600;
	
	// Отступы
	static private $MB = 20;  // Нижний
	static private $ML = 8;   // Левый
	static private $M = 5;    // Верхний и правый отступы.
	// Они меньше, так как там нет текста
	
	// Ширина одного символа
	static private $LW;
	
	// Максимальное значение
	static private $maxValue;

	// Количество рисок по игрику
	static private $countY;
	
	// Количество рисок по иксу
	static private $countX;
	
	// Цвет фона
	static private $colors = array();
	
	// Ширина текста
	static private $textWidth;
	
	// Реальные размеры графика
	static private $realWidth;
	static private $realHeight;
	
	// Координаты нуля
	static private $X0;
	static private $Y0;
	
	// Шаг
	static private $step;
	
	// Детализация
	static private $detalization;
	
	// Максимальное значение по второй оси
	static private $maxValue2;
	static private $countY2;
	static private $textWidth2;
	static private $step2;

	function Draw($inputData)
	{		
		$dbID = (int)$inputData['d'];
		assert($dbID >= 0);
		
		$worldID = (int)$inputData['w'];
		assert($worldID > 0);
		
		$idArticle = (int)$inputData['Ar'];
		assert($idArticle > 0);
		
		$idCategory = (int)$inputData['Ca'];
		assert($idCategory > 0);
		
		$idArticleFor = (int)$inputData['ArF'];
		assert($idArticleFor > 0);
		
		$idCategoryFor = (int)$inputData['CaF'];
		assert($idCategoryFor > 0);
		
		$dateFrom = (int)$inputData['dF'];
		
		$dateTo = (int)$inputData['dT'];
		
		$flag = (int)$inputData['f'];
		
		$detalization = $inputData['det'];
		
		$sershData = SU_TradeGraphicsSearch::GetData($dbID, $worldID, $idArticle, $idCategory, $idArticleFor, $idCategoryFor, $dateFrom, $dateTo, $flag, $detalization);
/*echo "<pre>";
echo "<pre>";
print_r($data);
exit;**/
		if (empty($sershData)) {
			echo "No Data";
			return false;
		}

		foreach ($sershData as $k => $trade) {
			if (!key_exists('minute', $trade)) {
				$trade['minute'] = 0;
			}
			
			if (!key_exists('hour', $trade)) {
				$trade['hour'] = 0;
			}
			
			if (!key_exists('month', $trade)) {
				$trade['month'] = 1;
			}
			
			if (!key_exists('day', $trade)) {
				$trade['day'] = 1;
			}
		    $data['x'][] = mktime ($trade['hour'], $trade['minute'], 0, $trade['month'], $trade['day'], $trade['year']) - $dateFrom;
		    $data['date'][] =  mktime ($trade['hour'], $trade['minute'], 0, $trade['month'], $trade['day'], $trade['year']);

		    /*$data['year'][] =  $trade['year'];
		    $data['month'][] =  $trade['month'];
		    $data['day'][] =  $trade['day'];
		    $data['hour'][] =  $trade['hour'];
		    $data['minute'][] =  $trade['minute'];*/
		    
			$data['y']['course'][] = $trade['course'];
			$data['y']['count'][] = $trade['count'];
			
			//$DATA['y']['max_price'][] = ceil($trade['current_cource'] + ($trade['max_price']/100) * $trade['current_cource']);
			//$DATA['y']['min_price'][] = floor($trade['current_cource'] + ($trade['min_price']/100) * $trade['current_cource']);		
			//$DATA['y']['current_cource'][] = $trade['current_cource'];
		}
		
		assert(is_array($data));
		self::$data = $data;
		self::$detalization = $detalization;
		
		// Устанавливаем все параметры
		self::_setParams();
		
		// Создадим изображение
		self::$im = imagecreate(self::$width, self::$height);
		
		// Задаем цвета
		self::_SetColors();

		// Рисуем рамку графика
		self::_DrawFrame();
		
		// Рисуем сетку
		//self::_DrawGride();

		// Рисуем гистограмму количества сделок
		self::_DrawCountTrades();
		
		// Рисуем график среднего курса
		self::_DrawCource();
		
		// Рисуем подписи
		self::_DrawCaption();

		// Генерация изображения
		header("Content-Type: image/png");
		ImagePNG(self::$im);
		imagedestroy(self::$im);
		
//echo self::$maxValue ;
//exit;	
	}
	
	static private function _DrawFrame()
	{
		// Вывод главной рамки графика
		imagefilledrectangle(self::$im, self::$X0, self::$Y0 - self::$realHeight, self::$X0 + self::$realWidth, self::$Y0, self::$colors['white']);
		imagerectangle(self::$im, self::$X0, self::$Y0, self::$X0 + self::$realWidth, self::$Y0 - self::$realHeight, self::$colors['grey']);
	}
	
	static private function _DrawGride()
	{
		// Вывод сетки по оси Y
		for ($i = 1; $i <= self::$countY; $i ++) 
		{
			$y = self::$Y0 - self::$step * $i;
			imageline(self::$im, self::$X0, $y, self::$X0 + self::$realWidth, $y, self::$colors['grey']);
			imageline(self::$im, self::$X0, $y,  self::$X0 - (self::$ML - self::$textWidth)/4, $y, self::$colors['light_grey']);
		}
		
		// Вывод сетки по оси X
		for ($i = 0; $i < self::$countX; $i ++) {
			imageline(self::$im, self::$X0 + $i*(self::$realWidth/self::$countX), self::$Y0, self::$X0 + $i*(self::$realWidth/self::$countX), self::$Y0, self::$colors['grey']);
			imageline(self::$im, self::$X0+$i*(self::$realWidth/self::$countX), self::$Y0, self::$X0+$i*(self::$realWidth/self::$countX), self::$Y0-self::$realHeight, self::$colors['light_grey']);
		}
	}
	
	static private function _DrawCource()
	{
		// Величина, равная половине шага
		$dx = (self::$realWidth/self::$countX)/2;
		$px = intval(self::$X0 + $dx);
		$pi = self::$Y0 - (self::$realHeight / self::$maxValue * self::$data['y']['course'][0]);
		
		for ($i = 1; $i < self::$countX; $i ++) 
		{
			$x = intval(self::$X0 + $i * (self::$realWidth/self::$countX) + $dx);
			$y = self::$Y0 - (self::$realHeight/self::$maxValue * self::$data['y']['course'][$i]);

			imageline(self::$im, $px, $pi, $x, $y, self::$colors['blue']);

			$px = $x;
			$pi = $y;
		}
	}
	
	static private function _DrawCountTrades()
	{
		// Смещение задней грани куба по оси X
		$DX = (self::$realWidth/self::$countX);
		
		$dx = (self::$realWidth/self::$countX)/2;
		$px = self::$X0;
		$pi = self::$Y0;
		
		// Вывод кубов для всех трех рядов
		for ($i=0; $i < self::$countX; $i ++)
		{
			$x = $px + $DX;
			$px += $dx/2;
			$x -= $dx/2;
			
			
			$y = self::$Y0 - (self::$realHeight/self::$maxValue2 * (self::$data['y']['count'][$i]));
			imagefilledrectangle(self::$im, $px, $pi, $x, $y, self::$colors['red']);
			
			$px = $x + $dx/2;
		}
	}
	
	static private function _DrawCaption()
	{
		// Уменьшение и пересчет координат
		self::$ML -= self::$textWidth;
		
		// Вывод подписей по оси Y (курсы)
		for ($i=1; $i <= self::$countY; $i ++) 
		{
			$str = (self::$maxValue/self::$countY) * $i;
			$x = self::$X0-strlen($str)*self::$LW-self::$ML/4-2;
			$y = self::$Y0-self::$step*$i-imagefontheight(2)/2;
			
			$y1 = self::$Y0 - self::$step*$i;
			
			imageline(self::$im, self::$X0 - 2, $y1, self::$X0 + 2, $y1, self::$colors['dark_grey']);
			imagestring(self::$im, 2, $x, $y, $str, self::$colors['dark_grey']);
		}
		
		// Вывод подписей по оси Y (количество продаж)
		for ($i=1; $i <= self::$countY2; $i ++) 
		{
			$str = (self::$maxValue2/self::$countY2) * $i;
			$x = self::$X0 + self::$realWidth+2*strlen($str)*self::$LW-self::$ML/4-2;
			$y = self::$Y0-self::$step2*$i-imagefontheight(2)/2;
			$y1 = self::$Y0 - self::$step*$i;
			imageline(self::$im, self::$X0 + self::$realWidth - 2, $y1, self::$X0 + self::$realWidth + 2, $y1, self::$colors['dark_grey']);
			imagestring(self::$im, 2, $x, $y, $str, self::$colors['dark_grey']);
		}
		
		// Вывод подписей по оси X
		$prev = 100000;
		$twidth = self::$LW*strlen(self::$data["x"][0])+6;
		$i = self::$X0 + self::$realWidth;

		while ($i > self::$X0) {
			if ($prev - $twidth > $i) {
				$drawx = $i - (self::$realWidth/self::$countX)/2;
				if ($drawx > self::$X0) {
					if (self::$detalization == 'year') {
						$format = 'Y';
					} elseif (self::$detalization == 'month') {
						$format = 'F Y';
					} elseif (self::$detalization == 'day') {
						$format = 'Y-m-d';
					} elseif (self::$detalization == 'hour') {
						$format = 'Y-m-d H';
					} elseif (self::$detalization == 'minute') {
						$format = 'Y-m-d H:i';
					} else {
						$format = 'Y-m-d H:i';
					}
						
					$str =  date($format, self::$data["date"][round(($i - self::$X0)/(self::$realWidth/self::$countX))-1]);
					imageline(self::$im, $drawx, self::$Y0, $i-(self::$realWidth/self::$countX)/2, self::$Y0+5, self::$colors['dark_grey']);
					imagestring(self::$im, 2, $drawx-(strlen($str)*self::$LW)/2, self::$Y0+7, $str, self::$colors['dark_grey']);
				}
				$prev = $i;
			}
			$i -= self::$realWidth/self::$countX;
		}		
	}
	
	static private function _SetColors()
	{
		// Цвет фона (белый)
		self::$colors['white'] = imagecolorallocate(self::$im, 255, 255, 255);
		
		// Цвет задней грани графика (светло-серый)
		self::$colors['light_grey'] = imagecolorallocate(self::$im, 231, 231, 231);
		
		// Цвет левой грани графика (серый)
		//self::$colors['grey'] = imagecolorallocate(self::$im, 212, 212, 212);
		
		// Цвет сетки (серый, темнее)
		self::$colors['grey'] = imagecolorallocate(self::$im, 184, 184, 184);
		
		// Цвет текста (темно-серый)
		self::$colors['dark_grey'] = imagecolorallocate(self::$im, 136, 136, 136);
		
		// Цвета для линий графиков
		self::$colors['blue'] = imagecolorallocate(self::$im, 0, 0, 255); // Синий
		self::$colors['red'] = imagecolorallocate(self::$im, 255, 0, 0); // Красный
	}
	
	static private function _getMaxValue($arrays)
	{
		$max = 0;
		
		foreach ($arrays as $key => $data)
		{
			$maxTmp = max($data);
			if ($maxTmp > $max) {
				$max = $maxTmp;
			}
		}
		
		// Увеличим максимальное значение на 10% (для того, чтобы столбик
		// соответствующий максимальному значение не упирался в в границу
		//  графика 
		$max += $max/10;
		
		return $max;
	}
	
	static private function _setParams()
	{
		// Устанавливаем ширину одного символа
		self::$LW = imagefontwidth(2);
		
		// Получаем максимальное значение по игрику
		self::$maxValue = self::_getMaxValue(self::$data['y']);
		
		// Количество рисок по иксу
		self::$countX = count(self::$data['y']['course']);
		
		// Количество рисок по игрику
		self::$countY = 10;
		
		// Расчитываем ширину текста
		self::$textWidth = 0;
		for ($i = 1; $i <= self::$countY; $i ++) {
			$strl = strlen(( self::$maxValue / self::$countY) * $i) * self::$LW;
			if ( $strl > self::$textWidth) self::$textWidth = $strl;
		}
		
		// Подравняем левую границу с учетом ширины подписей по оси Y
		self::$ML += self::$textWidth;
		
		// Посчитаем реальные размеры графика (за вычетом подписей и отступов)
		self::$realWidth = self::$width - self::$ML*2 - self::$M;
		self::$realHeight = self::$height - self::$MB - self::$M;
		
		// Посчитаем координаты нуля
		self::$X0 = self::$ML;
		self::$Y0 = self::$height - self::$MB;
		
		self::$step = self::$realHeight/self::$countY;
		
		// Максимальное значение количества подаж
		self::$maxValue2 = max(self::$data['y']['count']);
		
		// Количество рисок по игрику
		self::$countY2 = max(self::$data['y']['count']);
		
		// Расчитываем ширину текста
		self::$textWidth2 = 0;
		for ($i = 1; $i <= self::$countY2; $i ++) {
			$strl = strlen(( self::$maxValue2 / self::$countY2) * $i) * self::$LW;
			if ( $strl > self::$textWidth2) self::$textWidth2 = $strl;
		}
		
		self::$step2 = self::$realHeight/self::$countY2;
	}
}
?>