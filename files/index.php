<?php
ob_start();

define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');
define('MYSQL_DB', 'itrust');


$cid = mysql_connect (MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
if (!mysql_select_db (MYSQL_DB, $cid)) die("couldn't open the database");

session_start();

if (!isset($_SESSION['user'])) {
	die("You should be logged in order to download files");
}

$url =  $_SERVER['REQUEST_URI'];
$filename = str_replace('/files/', '', $url);


list($eng_id, $file_id) = explode('/', $filename);
$eng_id = intval($eng_id); 
$file_id = intval($file_id);

$qid = mysql_query($sql="SELECT * FROM files WHERE (eng_id={$eng_id}) AND (id={$file_id})");
if ($qid && (mysql_num_rows($qid)>0)) {
	$row = mysql_fetch_assoc($qid);
	$filename = "{$eng_id}/{$row['wp_id']}.{$row['fileext']}";
	
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment;filename=\"{$row['name']}\"");
	header("Content-Length: ".filesize($filename));
	readfile($filename);
} else {
	echo "There is no such file";
}
?>