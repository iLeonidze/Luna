<?php
$execution_start = time();
session_start();
$paths = array(
    'storage'=>'storage/',
    'users'=>'storage/users/',
    'pages'=>'storage/pages/',
    'workspace'=>'storage/workspace/'
);
// TODO: fix file read error & check consistent
$configuration = json_decode(file_get_contents($paths['storage']."configuration.json"),true);
$request_url = urldecode($_SERVER['REQUEST_URI']);
$version = 10000; // #.##.##
header('X-Powered-By: Luna '.getLunaVersionAsString());

if($configuration['log']['enabled']){
    require_once('storage/libraries/Console.php');
}else{
    class Console{
        public function save(){
            return $this;
        }
        public function trace(){
            return $this;
        }
        public function log(){
            return $this;
        }
        public function info(){
            return $this;
        }
        public function warn(){
            return $this;
        }
        public function error(){
            return $this;
        }
    }
}

$console = new Console($configuration['log']['location'],$configuration['log']['level'], $configuration['log']['size']);
$console->info("URI: ".$request_url);
$console->info("IP:  ".$_SERVER['REMOTE_ADDR']);

function terminate($code=null,$code_header='HTTP/1.0 200 OK',$reason=null){
    global $path;
    global $console;
    global $configuration;
    if(!is_null($code)){
        $console->trace("Server wants to send custom response code ".$code);
        if(!headers_sent()){
            $console->trace("Sending custom header...");
            header($code_header);
            $console->trace("Headers done, searching for code file...");
            if(file_exists($path['workspace'].$code.".php")){
                $console->trace("Code PHP-file found, loading...");
                require_once($path['workspace']."page_".$code.".php");
            }elseif(file_exists($path['workspace'].$code.".html")){
                $console->trace("Code HTML-file found, loading...");
                require_once($path['workspace']."page_".$code.".html");
            }else{
                $console->trace("Code file is not found, sending plain text");
                echo $reason;
            }
        }else{
            $console->trace("Headers already sent");
        }
    }
    $console->trace("Terminating");
    if($configuration['log']['statistics']) $console->
        log("Execution time: ".getExecutionTime()."ms")->
        log("Memory usage: ".(floor(memory_get_usage()/10485.76)/100)."MB");
    $console->save();
    exit();
}

function getLunaVersionAsString($value=-1){
    global $version;
    if($value<1) $value = $version;
    return floor($value/10000).".".(($value-floor($value/10000)*10000)/100);
}

function generateID(){
    return (time()+microtime())*10000;
}
function getExecutionTime(){
    global $execution_start;
    return time()-$execution_start;
}

function getSiteURI(){
    global $configuration;
    return "http".($configuration['site']['https_supported'] ? "s" : "")."://".$configuration['site']['host']."/";
}

function getCurrentPage(){
    global $configuration;
    global $request_url;
    global $paths;
    global $console;
    $console->trace("Determining current page...");
    if(!is_array($configuration['index'])){
        $console->trace("Seems to be configuration index is not array");
        return array('id'=>-1);
    }
    foreach($configuration['index'] as $page){
        if(isset($page['urls']) && is_array($page['urls']) && isset($page['id'])){
            foreach($page['urls'] as $url){
                $console->trace("Proceeding #".$page['id'].", comparing \"".$url."\"");
                if("/".$url==$request_url && ($page['id']==1||isset($page['redirect'])||file_exists($paths["pages"].$page['id'].".json"))){
                    if($page['id']==1){
                        $console->trace("This is API page");
                        return array('id'=>1);
                    }
                    if(isset($page['redirect'])){
                        $console->trace("This is page with redirect");
                        return $page;
                    }
                    $console->trace("Page determined, loading...");
                    $page_contents = json_decode(file_get_contents($paths['pages'].$page['id'].".json"),true);
                    if(is_array($page_contents)){
                        $console->trace("Page loaded");
                        // TODO: check page consistent
                        return array_merge($page,$page_contents);
                    }else{
                        $console->error("Page #".$page['id']." is corrupted");
                        return array('id'=>-1);
                    }
                }
            }
        }
    }
    $console->trace("Seems to be there is no requested page");
    return array('id'=>0);
}

$page = getCurrentPage();
switch($page["id"]){ // TODO: show multilanguage errors
    case -1:
        $console->trace("Server can not show page");
        terminate(500,"HTTP/1.0 500 Internal Server Error","Something went wrong, server is no longer to work.");
        break;
    case 0:
        $console->warn("Requested page not found");
        terminate(404, "HTTP/1.0 404 Not Found", "File not found.");
        break;
    case 1:
        $console->trace("Loading API...");
        require_once($paths['storage']."api.php");
        terminate();
}


if(isset($page["redirect"])&&is_string($page["redirect"])){
    header('Location: '.getSiteURI().$page["redirect"]);
    $console->trace("Page redirects to ".$page["redirect"]);
    terminate();
}

function getTitle(){ // TODO: skip empty fields
    global $page;
    global $configuration;
    return $page['main']['title'].
            $configuration['site']['delimiter'].
            $configuration['site']['name'].
            $configuration['site']['delimiter'].
            $configuration['site']['description'];
}

function buildHeader(){ // TODO: minify function
    global $configuration;
    global $console;
    global $page;
    global $request_url;
    $b = $configuration["beatify"]["code"];
    $header = "<!DOCTYPE html>";
    if($b){
        $console->trace("Beatification is on");
        $header .= "\r\n";
    }else{
        $console->trace("Beatification is off");
    }
    $header .= "<html prefix=\"og: http://ogp.me/ns#\">";
    if($b) $header .= "\r\n\t";
    $header .= "<head>";
    if($b) $header .= "\r\n\t\t";
    $header .= "<meta charset=\"utf-8\" />";
    if(isset($configuration["additional"]["xuacompatible"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta http-equiv=\"X-UA-Compatible\" content=\"".$configuration["additional"]["xuacompatible"]."\" />";
        $console->trace("meta.XUACompatible added");
    }else{
        $console->trace("meta.XUACompatible disabled");
    }
    if(isset($configuration["additional"]["viewport"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta name=\"viewport\" content=\"";
        if(isset($configuration["additional"]["viewport"]["width"])) {
            $header .= "width=" . $configuration["additional"]["viewport"]["width"] . ", ";
        }
        if(isset($configuration["additional"]["viewport"]["initial_scale"])) {
            $header .= "initial-scale=" . $configuration["additional"]["viewport"]["initial_scale"] . ", ";
        }
        if(isset($configuration["additional"]["viewport"]["maximum_scale"])) {
            $header .= "maximum-scale=" . $configuration["additional"]["viewport"]["maximum_scale"] . ", ";
        }
        if(isset($configuration["additional"]["viewport"]["user_scalable"])) {
            $header .= "user-scalable=" . $configuration["additional"]["viewport"]["user_scalable"];
        }
        $header .= "\" />";
        $console->trace("meta.viewport added");
    }else{
        $console->trace("meta.viewport disabled");
    }
    if($b) $header .= "\r\n\t\t";
    $header .= "<title>".getTitle()."</title>";
    $console->trace("title added");
    if(isset($configuration["additional"]["favicon_path"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<link rel=\"shortcut icon\" href=\"".getSiteURI().$configuration["additional"]["favicon_path"]."\" type=\"image/x-icon\">";
        $console->trace("link.icon added");
    }else{
        $console->trace("link.icon disabled");
    }
    if(isset($page["optimization"]["description"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta name=\"description\" content=\"".$page["optimization"]["description"]."\" />"; // TODO: add 150 words maximum check in UI!
        $console->trace("meta.description added");
    }else{
        $console->trace("meta.description disabled");
    }
    if(isset($page["optimization"]["keywords"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta name=\"keywords\" content=\"".implode(",",$page["optimization"]["keywords"])."\" />"; // TODO: add 10 keywords maximum check in UI!
        $console->trace("meta.keywords added");
    }else{
        $console->trace("meta.keywords disabled");
    }
    if(isset($configuration["additional"]["index"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<link rel=\"index\" title=\"".$configuration['site']['name']."\" href=\"".getSiteURI()."\">"; // TODO: hide title if no site name
        $console->trace("link.index added");
    }else{
        $console->trace("link.index disabled");
    }
    if(isset($page["robots"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta name=\"robots\" content=\"";
        if(isset($page["robots"]["index"])&&$page["robots"]["index"]==false) $header .= "no";
        $header .= "index,";
        if(isset($page["robots"]["follow"])&&$page["robots"]["follow"]==false) $header .= "no";
        $header .= "follow";
        if(isset($page["robots"]["archive"])){
            $header .= ",";
            if ($page["robots"]["archive"] == false) $header .= "no";
            $header .= "archive";
        }
        if(isset($page["robots"]["odp"])) {
            if ($page["robots"]["odp"] == false) {
                $header .= ",noodp,noydir";
            } else {
                $header .= ",odp,ydir";
            }
        }
        $header .= "\">";
        $console->trace("meta.robots added");
    }else{
        $console->trace("meta.robots disabled");
    }
    if(isset($configuration["additional"]["copyright"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta name=\"copyright\" content=\"".$configuration["additional"]["copyright"]."\" />";
        $console->trace("meta.copyright added");
    }else{
        $console->trace("meta.copyright disabled");
    }
    if(isset($page["main"]["author"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta name=\"author\" content=\"".$page["main"]["author"]."\" />";
        $console->trace("meta.author added");
    }else{
        $console->trace("meta.author disabled");
    }
    if(isset($page["main"]["language"])){
        if($b) $header .= "\r\n\t\t";
        $header .= "<meta name=\"language\" content=\"".$page["main"]["language"]."\" />";
        $console->trace("meta.language added");
    }else{
        $console->trace("meta.language disabled");
    }


    if(sizeof($page["urls"])>1){
        $console->trace("Current page have many URLs, canonical required");
        foreach($page['urls'] as $url){
            if("/".$url!==$request_url){
                if($b) $header .= "\r\n\t\t";
                $header .= "<link rel=\"canonical\" href=\"".getSiteURI().$url."\">";
                $console->trace("Canonical added: ".$url);
            }
        }
    }else{
        $console->trace("Current page do no have other URLs");
    }

    return $header;
}


$console->trace("Connecting template...");
require_once($paths['workspace'].$configuration['workspace']['template']);

terminate();