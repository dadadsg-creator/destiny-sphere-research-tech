<html>
<head>
<title>Pattern Adder</title>

</head>
<body>

<?php
//require_once '../Main.php';

include("../classes/SU/SU_PatternAdder.class.php");

$pattern= new SU_patternAdder;
$pattern->show($_REQUEST);

?>
</body>
</html>