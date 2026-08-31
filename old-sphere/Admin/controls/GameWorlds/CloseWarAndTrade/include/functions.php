<?php

function checkDateFormat($dateStr)
{
	$res = preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})$/', $dateStr, $matches);
	
	$year = intval($matches[1]);
	$month = intval($matches[2]);
	$day = intval($matches[3]);
	
	$hours   = intval($matches[4]);
	$minutes = intval($matches[5]);
	$seconds = intval($matches[6]);
	
	if (!checkdate($month, $day, $year) || $hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59 || $seconds < 0 || $seconds > 59) {
		return false;
	}
	//echo '<pre>'; print_r($matches); echo "</pre>\n";
	return $res === 1 ? 1 : false;
}

/**
 * This function is used to change both DB id in singleton and in session
 * To move to the DS_Common
 * */

function repareDbId(&$dbh, $dbId)
{
	$dbh->setDbId($dbId);
	
	if (key_exists('dbId', $_SESSION)) {
		$_SESSION['dbId'] = $dbId;
	}
}

?>