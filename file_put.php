#! /usr/local/php/bin/php 
<?php
set_time_limit(0);

require dirname(__FILE__).DIRECTORY_SEPARATOR."ssh2.php";
$infoxml = simplexml_load_file(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config/server.ini');

$fileToDeal = $_SERVER['argv'][1];
$remoteFile = $_SERVER['argv'][2];

if(empty($fileToDeal))	die("Usage:file_sync.php <localfile> [remotefile]\n");
$remoteFile = (empty($remoteFile)) ? $fileToDeal : $remoteFile;

$cmd = "/sbin/ifconfig eth0 | grep Bcast  | awk -F\" \" '{print $2}' | awk -F\":\" '{print $2}'";		
exec($cmd, $out, $retval);
$serverIp = $out[0];
$total = count($infoxml->server)-1;
$i = 1;
foreach($infoxml->server as $element)
{
	
	$ip = $element->ip;
	$user = $element->root;
	$passwd = $element->passwd;
	if($ip == $serverIp)
	{
		continue;
	}
	
	$SFTP = new SFTPConnection($ip, 22);
	$SFTP->login($user, $passwd);
	$SFTP->uploadFile($fileToDeal, $remoteFile);
	$ret = $SFTP->remoteCommand("md5sum ".$remoteFile);
	$arr = explode("  ",$ret);
	$md5 = $arr[0];
	echo "Process($ip) ......($i/$total)\t[$md5]\tOK\n";
	$i++;
}

