<?php 

//Высота	
$height = 11;

//Процент закрашенной области от всей ширины	
//$width = 80;
$width = $_GET['width'];

//Ширина
if(isset($_GET['ws']))
{
	$widthScale = $_GET['ws'];
} else {
	$widthScale = 1.0;
}

$scale = imagecreatetruecolor(100*$widthScale + 2, $height);

function blendColors($col1, $col2, $weight)
{
	$res['r'] = round(($col2['r'] - $col1['r']) * $weight + $col1['r'] );
	$res['g'] = round(($col2['g'] - $col1['g']) * $weight + $col1['g'] );
	$res['b'] = round(($col2['b'] - $col1['b']) * $weight + $col1['b'] );

	return $res;
}

$color2 = array(
	'r' => 0,
	'g' => 0,
	'b' => 200
);

$color1 = array(
	'r' => 64,
	'g' => 0,
	'b' => 128
);


//Цвет границы большого прямоугольника
$brd = imagecolorallocate($scale,  90, 90, 90);

//Цвет фона
$bg = imagecolorallocate($scale,230, 235, 255);

//Цвет границы маленького прямоугольника
$wht = imagecolorallocate($scale, 255, 255, 255);
$blk = imagecolorallocate($scale, 50, 50, 50);

imagefill($scale, 0, 0, $bg);

$width = round($width*$widthScale);

for($i=0; $i<$width+1; $i++)
{
	$w = $i/(100*$widthScale);
	$c = blendColors($color2, $color1, $w);
	$col = imagecolorallocate($scale, $c['r'], $c['g'], $c['b']);
	imageline($scale, $i, 0, $i, $height, $col);
}
imagerectangle($scale, 0,  -1, $width, $height-2, $blk);
imagerectangle($scale,0, 1, $width + 1,  $height-1, $wht);
imagerectangle($scale,  $width + 1, $height-1, $width - $width,  1,   $wht);
imagerectangle($scale, 0, 0, 100*$widthScale + 1, $height-1,  $brd);

// flush image
header('Content-type: image/png');
imagepng($scale);
imagedestroy($scale);

?> 


