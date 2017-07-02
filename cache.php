<?php
$CF = "cache/".md5($_SERVER['REQUEST_URI']).".html";
header('X-Powered-By: Luna 1.0.0');
if(false&&file_exists($CF)&&(filemtime($CF)+86400)>time()){
    header('Content-Length: ' . filesize($CF));
    readfile($CF);
    exit();
}
include_once("index.php");