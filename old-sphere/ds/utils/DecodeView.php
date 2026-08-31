<html>
<HEAD>
<title>DecodeView</title>
</head>
<body>
<?php
require_once '../Main.php';

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
?>
<h1>Decode of input protocol:</h1>
<form action="" METHOD="POST">
<textarea name="code" rows="20" cols="80"></textarea><br>
<input type="submit">
</form>

<xmp>
<?
echo "Coded string:\n";
$str = urldecode($_POST['code']);
echo $str;
echo "\n\nDecoded string:\n";
echo decode ($str);
?>
</xmp>
</body>
</html>