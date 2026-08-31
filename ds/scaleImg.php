<?php
if(key_exists('s', $_GET))
{
    $height = $_GET['s'];
    
    if($height > 100)
    {
        $height = 100;
    }
    
    if($height < 0)
    {
        $height = 0;
    }
} else {
    exit;
}

if(key_exists('w', $_GET))
{
    $width = $_GET['w'];
} else {
    $width = 9;
}

$heightScale = 1.0;

$scale = @imagecreatetruecolor($width, 100*$heightScale + 2);

function blendColors($col1, $col2, $weight)
{
    $res['r'] = round(($col2['r'] - $col1['r']) * $weight + $col1['r']);
    $res['g'] = round(($col2['g'] - $col1['g']) * $weight + $col1['g']);
    $res['b'] = round(($col2['b'] - $col1['b']) * $weight + $col1['b']);

    return $res;
}

$color1 = array(
    'r' => 0,
    'g' => 255,
    'b' => 0
);

$color2 = array(
    'r' => 255,
    'g' => 0,
    'b' => 0
);

$brd = imagecolorallocate($scale, 90, 90, 90);
$bg = imagecolorallocate($scale, 155, 155, 155);
$wht = imagecolorallocate($scale, 255, 255, 255);
$blk = imagecolorallocate($scale, 50, 50, 50);

imagefill($scale, 0, 0, $bg);

$height = round($height*$heightScale);

for($i=0; $i<$height+1; $i++)
{
    $w = $i/(100*$heightScale);
    $c = blendColors($color2, $color1, $w);
    $col = imagecolorallocate($scale, $c['r'], $c['g'], $c['b']);
    imageline($scale, 0, 100*$heightScale - $i, $width, 100*$heightScale - $i, $col);
}

imagerectangle($scale, -1, 100*$heightScale - $height, $width-2, 100*$heightScale,     $blk);
imagerectangle($scale,  1, 100*$heightScale - $height, $width-1, 100*$heightScale + 1, $wht);

imagerectangle($scale, 0, 0, $width-1, 100*$heightScale + 1, $brd);

// flush image
header('Content-type: image/png');
imagepng($scale);
imagedestroy($scale);
?>