<?php

function is_day($value){
	return  preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])$/", $value);
}

function is_month($value){
	return  preg_match("/^(0[1-9]|1[0-2])$/", $value);
}

function is_year($value){
	return preg_match('/(20[0-9][0-9])$/', $value);
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


function diffForHumans(Carbon\Carbon $time) {

  $x = Carbon\Carbon::now()->diffForHumans($time);
                    
  return str_replace("after", "ago",  $x);
}



function logAction($action, $log, $logfile=NULL) {
	$logfile = !is_null($logfile) 
		? $logfile
		: base_path().DS.'logs'.DS.now().'-log.txt';

	$dir = pathinfo($logfile, PATHINFO_DIRNAME);

	if(!is_dir($dir))
		mkdir($dir, 0775, true);

	$new = file_exists($logfile) ? false : true;
	if($new){
		$handle = fopen($logfile, 'w+');
		chmod($logfile, 0775);
	} else
		$handle = fopen($logfile, 'a');

	$ip = clientIP();
	$brw = $_SERVER['HTTP_USER_AGENT'];
	$content = date('r')." | {$ip} | {$action} | {$log} \t {$brw}\n";
  fwrite($handle, $content);
  fclose($handle);
}	

function test_log($log, $logfile=NULL) {
  $logfile = !is_null($logfile) 
    ? $logfile
    : base_path().DS.'logs'.DS.now().'-test-log.txt';

  $dir = pathinfo($logfile, PATHINFO_DIRNAME);

  if(!is_dir($dir))
    mkdir($dir, 0775, true);

  $new = file_exists($logfile) ? false : true;
  if($new){
    $handle = fopen($logfile, 'w+');
    chmod($logfile, 0775);
  } else
    $handle = fopen($logfile, 'a');

  $ip = clientIP();
  $brw = $_SERVER['HTTP_USER_AGENT'];
  //$content = date('r')." | {$ip} | {$action} | {$log} \t {$brw}\n";
  $content = "{$log}\n";
  fwrite($handle, $content);
  fclose($handle);
} 



function getBrowserInfo() 
{ 
    $u_agent = $_SERVER['HTTP_USER_AGENT']; 
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'Linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'Mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'Windows';
    }
    
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Internet Explorer'; 
        $ub = "MSIE"; 
    } 
    elseif(preg_match('/Firefox/i',$u_agent)) 
    { 
        $bname = 'Mozilla Firefox'; 
        $ub = "Firefox"; 
    } 
    elseif(preg_match('/Chrome/i',$u_agent)) 
    { 
        $bname = 'Google Chrome'; 
        $ub = "Chrome"; 
    } 
    elseif(preg_match('/Safari/i',$u_agent)) 
    { 
        $bname = 'Apple Safari'; 
        $ub = "Safari"; 
    } 
    elseif(preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Opera'; 
        $ub = "Opera"; 
    } 
    elseif(preg_match('/Netscape/i',$u_agent)) 
    { 
        $bname = 'Netscape'; 
        $ub = "Netscape"; 
    } else {
        $bname = 'unkown'; 
        $ub = "unkown"; 
    }
    
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }
    
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }
    
    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
    
    return array(
        'user-agent' => $_SERVER['HTTP_USER_AGENT'],
        'browser'    => getBrowser($_SERVER['HTTP_USER_AGENT']),
        'version'   => $version,
        'platform'  => getOS($_SERVER['HTTP_USER_AGENT']),
        'pattern'    => $pattern
    );
} 


function getOS($user_agent) { 



    $os_platform    =   "Unknown OS Platform";

    $os_array       =   array(
                            '/windows nt 10/i'     =>  'Windows 10',
                            '/windows nt 6.3/i'     =>  'Windows 8.1',
                            '/windows nt 6.2/i'     =>  'Windows 8',
                            '/windows nt 6.1/i'     =>  'Windows 7',
                            '/windows nt 6.0/i'     =>  'Windows Vista',
                            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                            '/windows nt 5.1/i'     =>  'Windows XP',
                            '/windows xp/i'         =>  'Windows XP',
                            '/windows nt 5.0/i'     =>  'Windows 2000',
                            '/windows me/i'         =>  'Windows ME',
                            '/win98/i'              =>  'Windows 98',
                            '/win95/i'              =>  'Windows 95',
                            '/win16/i'              =>  'Windows 3.11',
                            '/macintosh|mac os x/i' =>  'Mac OS X',
                            '/mac_powerpc/i'        =>  'Mac OS 9',
                            '/linux/i'              =>  'Linux',
                            '/ubuntu/i'             =>  'Ubuntu',
                            '/iphone/i'             =>  'iPhone',
                            '/ipod/i'               =>  'iPod',
                            '/ipad/i'               =>  'iPad',
                            '/android/i'            =>  'Android',
                            '/blackberry/i'         =>  'BlackBerry',
                            '/webos/i'              =>  'Mobile'
                        );

    foreach ($os_array as $regex => $value) { 

        if (preg_match($regex, $user_agent)) {
            $os_platform    =   $value;
        }

    }   

    return $os_platform;

}

function getBrowser($user_agent) {


    $browser        =   "Unknown Browser";

    $browser_array  =   array(
                            '/msie/i'       =>  'Internet Explorer',
                            '/firefox/i'    =>  'Firefox',
                            '/safari/i'     =>  'Safari',
                            '/chrome/i'     =>  'Chrome',
                            '/opera/i'      =>  'Opera',
                            '/netscape/i'   =>  'Netscape',
                            '/maxthon/i'    =>  'Maxthon',
                            '/konqueror/i'  =>  'Konqueror',
                            //'/mobile/i'     =>  'Handheld Browser'
                        );

    foreach ($browser_array as $regex => $value) { 

        if (preg_match($regex, $user_agent)) {
            $browser    =   $value;
        }

    }

    return $browser;

}

if (!function_exists('c')) {
    function c($datetime=null) {
        return is_null($datetime) 
        ? Carbon\Carbon::now()
        : Carbon\Carbon::parse($datetime);
    }
}

if (!function_exists('brcode')) {
    function brcode() {
        return strtolower(session('user.branchcode'));
    }
}


if (!function_exists('is_payroll_backup')) {
    function is_payroll_backup($name) {
        $re = '/\b(PR)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(\d\d)(\.ZIP)/';
        return preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0);
    }
}



if (!function_exists('is_pos_backup')) {
    function is_pos_backup($name) {
        $re = '/\b(GC)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(\d\d)(\.ZIP)/';
        return preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0);
    }
}

if (!function_exists('backup_to_carbon_date')) {
    function backup_to_carbon_date($name) {
        if (!is_pos_backup($name))
            return false;

        $m = substr($name, 2, 2);
        $d = substr($name, 4, 2);
        $y = '20'.substr($name, 6, 2);
        
        if(is_iso_date($y.'-'.$m.'-'.$d))
            return c($y.'-'.$m.'-'.$d);
        else 
            return false;
    }
}


if (!function_exists('dayDesc')) {
    function dayDesc($x=1, $short=false) {
        
      switch ($x) {
        case '0':
            if ($short)
                echo 'O';
            else    
            echo 'Day Off';
          break;
         case '1':
            if ($short)
                echo 'D';
            else    
            echo 'With Duty';
          break;
        case '2':
            if ($short)
                echo 'L';
            else    
            echo 'On Leave';
          break;
        case '3':
            if ($short)
                echo 'S';
            else    
            echo 'Suspended';
          break;
        case '4':
            if ($short)
                echo 'B';
            else    
            echo 'Backup';
          break;
        case '5':
            if ($short)
                echo 'R';
            else    
            echo 'Resigned';
          break;
        case '6':
            if ($short)
                echo 'X';
            else    
            echo 'Others';
          break;
        default:
          echo '-';
          break;
      }
                        
    }
}

if (!function_exists('stl')) {
    function stl($str) {
        return strtolower($str);
    }
}







