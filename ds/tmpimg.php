<?

/*if ( !session_start() ) {
	exit;
}

if ( !isset ( $_SESSION['uid'] ) ) {
	exit;
}

include_once ( 'classfactory.php' );

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	if ( !$common->connect() ) {
		echo "DB-connection failed";
		exit;
	}
}*/



//$im = imagecreate(100, 23);

//$bg = imagecolorallocate($im, 255, 255, 155);
//$textcolor = imagecolorallocate($im, 0, 0, 255);

$str = rand ( 10000, 99999 );

/*$sql = sprintf ( "update ds_usercheckcode
		set code = '%s'
		where id_user = %d",
		$str,
		$_SESSION['uid'] );


$res = $common->executeQuery ( $sql );
if ( $res === false ) {
	die ( "a_rem sql-query error1" );
}*/

/*imagestring ( $im, 2, 25, 4, $str, $textcolor );

//$imname = "./tmp/".time().".png";
//$image = imagepng ( $im, $imname );
		
Header("Content-type: image/png");
		
imagepng ( $im );
  
imagedestroy ( $im );*/

$circles=10;
$width=100;
$height=40;
$font=5;

$string = $str;

$fontwidth = ImageFontWidth($font) * strlen($string);
$fontheight = ImageFontHeight($font);

$im = @imagecreate ($width,$height);
$background_color = imagecolorallocate ($im, 255, 255, 255);
$text_color = imagecolorallocate ($im, rand(0,140), rand(0,140), rand(0,140));

for ($i=1;$i<=$circles;$i++) {
$randomcolor = imagecolorallocate ($im , rand(100,255), rand(100,255),rand(100,255));
imagefilledellipse($im,rand(0,$width-10),rand(0,$height-3),rand(20,70),rand(20,70),$randomcolor);
}

imagerectangle($im,0,0,$width-1,$height-1,$text_color);
imagestring ($im, $font, rand(3, $width-$fontwidth-3), rand(2,$height-$fontheight-3),  $string, $text_color);
header ("Content-type: image/png");
imagepng ($im);

?>