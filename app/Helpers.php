<?php

function is_day($value){
	return  preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])$/", $value);
}

function is_month($value){
	return  preg_match("/^(0[1-9]|1[0-2])$/", $value);
}

function is_year($value){
	return preg_match('/(20[0-9][0-9])/', $value);
}

function is_iso_date($date){
	return preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date);
}

function now($val=null){
	switch ($val) {
		case 'year':
			return date('Y', strtotime('now'));
			break;
		case 'month':
			return date('m', strtotime('now'));
			break;
		case 'day':
			return date('d', strtotime('now'));
			break;
		case 'Y':
			return date('Y', strtotime('now'));
			break;
		case 'M':
			return date('m', strtotime('now'));
			break;
		case 'D':
			return date('d', strtotime('now'));
			break;
		default:
			return date('Y-m-d', strtotime('now'));
			break;
	}
	
}

function pad($val, $len=2, $char='0', $direction=STR_PAD_LEFT){
	return str_pad($val, $len, $char, $direction);
}


function is_uuid($uuid=0) {
	return preg_match('/^[A-Fa-f0-9]{32}+$/', $uuid);
}


/**
 * Return sizes readable by humans
 */
function human_filesize($bytes, $decimals = 2)
{
  $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
  $factor = floor((strlen($bytes) - 1) / 3);

  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) .
      @$size[$factor];
}

/**
 * Is the mime type an image
 */
function is_image($mimeType)
{
    return starts_with($mimeType, 'image/');
}


function endKey($array){
	end($array);
	return key($array);
}

function clientIP(){
	$ipAddress = $_SERVER['REMOTE_ADDR'];
	if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
    $ipAddress =  $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	return $ipAddress;
}




function lastWeekOfYear($year='') {
	$year = empty($year) ? date('Y', strtotime('now')):$year;
  $date = new \DateTime;
  $date->setISODate($year, 53);
  return ($date->format("W") === "53" ? 53 : 52);
}

function firstDayOfWeek($weekno='', $year=''){
	$weekno = empty($weekno) ? date('W', strtotime('now')) : $weekno;
	$year = empty($year) ? date('Y', strtotime('now')) : $year;
	$dt = new DateTime();
	$dt->setISODate($year, $weekno);
	return $dt;
}

function filename_to_date($filename, $type='l'){
	$f = pathinfo($filename, PATHINFO_FILENAME);

	$m = substr($f, 2, 2);
	$d = substr($f, 4, 2);
	$y = '20'.substr($f, 6, 2);

	if($type==='l')
		return $y.'-'.$m.'-'.$d;
	if($type==='s')
		return $m.'/'.$d.'/'.$y;
	return $y.'-'.$m.'-'.$d;
}

function filename_to_date2($filename){
	$f = pathinfo($filename, PATHINFO_FILENAME);

	$m = substr($f, 2, 2);
	$d = substr($f, 4, 2);
	$y = '20'.substr($f, 6, 2);

	return Carbon\Carbon::parse($y.'-'.$m.'-'.$d);
}


function vfpdate_to_carbon($f){
	

	$m = substr($f, 4, 2);
	$d = substr($f, 6, 2);
	$y = substr($f, 0, 4);

	return Carbon\Carbon::parse($y.'-'.$m.'-'.$d);
}


function carbonCheckorNow($date=NULL) {

	if(is_null($date))
		return Carbon\Carbon::now();

	try {
		$d = Carbon\Carbon::parse($date); 
	} catch(\Exception $e) {
		return Carbon\Carbon::now(); 
	}
	return $d;
}

