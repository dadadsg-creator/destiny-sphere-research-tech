<?php

require_once "../Main.php";
require_once "./classes/CoursesControl.class.php";
require_once "./classes/CoursesOut.class.php";
require_once "./classes/CoursesSearch.class.php";
require_once "./classes/TypeSearch.class.php";

echo CoursesControl::Exec($_REQUEST);

?>