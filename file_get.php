#! /usr/local/php/bin/php 
<?php
set_time_limit(0);

require dirname(__FILE__).DIRECTORY_SEPARATOR."ssh2.php";
$infoxml = simplexml_load_file(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config/server.ini');

$remote_file = $_SERVER['argv'][1];

if(empty($remote_file))	die("Usage:file_get.php <remote_file>\n");

$cmd = "/sbin/ifconfig eth0 | grep Bcast  | awk -F\" \" '{print $2}' | awk -F\":\" '{print $2}'";		
exec($cmd, $out, $retval);
$serverIp = $out[0];
$total = count($infoxml->server)-1;
$i = 1;

$local_base_dir = "/tmp/fileget/".date('Ymd').DIRECTORY_SEPARATOR.date("His");

if (!mkdir($local_base_dir, 0777, true)) {
    die("Failed to create folders...".$local_base_dir);
}

foreach($infoxml->server as $element){
	
	$ip = $element->ip;
	$user = $element->root;
	$passwd = $element->passwd;
	if($ip == $serverIp){
		continue;
	}
	
	$SFTP = new SFTPConnection($ip, 22);
	$SFTP->login($user, $passwd);
	$local_dir = $local_base_dir.DIRECTORY_SEPARATOR.$ip;
	if (!mkdir($local_dir, 0777, true)) {
		die("Failed to create folders...".$local_dir);
	}
	$local_file = $local_dir.DIRECTORY_SEPARATOR.basename($remote_file);
	$ret = $SFTP->receiveFile($remote_file,$local_file);
	if($ret){
		$md5 = md5($local_file);
		echo "Process($ip) ......($i/$total)\t[$local_file]\tOK\n";
	}
	else{
		echo "Process($ip) ......($i/$total)\tNOTOK\n";
	}
	$i++;

}

