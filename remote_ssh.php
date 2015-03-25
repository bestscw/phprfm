#! /usr/local/php/bin/php 
<?php
set_time_limit(0);

require dirname(__FILE__).DIRECTORY_SEPARATOR."ssh2.php";
$infoxml = simplexml_load_file(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config/server.ini');

$command = $_SERVER['argv'][1];

if(empty($command))	die("Usage:remote_ssh.php <command>\n");

$cmd = "/sbin/ifconfig eth0 | grep Bcast  | awk -F\" \" '{print $2}' | awk -F\":\" '{print $2}'";		
exec($cmd, $out, $retval);
$serverIp = $out[0];
$total = count($infoxml->server);
$i = 1;
foreach($infoxml->server as $element)
{
	$ip = $element->ip;
	$user = $element->root;
	$passwd = $element->passwd;
	
	$SFTP = new SFTPConnection($ip, 22);
	$SFTP->login($user, $passwd);
	$ret = $SFTP->remoteCommand($command);
	echo "--------------------------------------------------\n";
	echo $ret;
	echo "--------------------------------------------------\n";
	echo "Process($ip) ......($i/$total) OK\n\n";
	$i++;
}

