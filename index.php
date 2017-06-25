<?php
$execution_start = time();
session_start();
$paths = array(
    'storage'=>'storage/',
    'pages'=>'storage/pages/',
    'workspace'=>'storage/workspace/'
);
// TODO: fix file read error & check consistent
$configuration = json_decode(file_get_contents($paths['storage']."configuration.json"),true);
$request_url = urldecode($_SERVER['REQUEST_URI']);
$version = 12345; // #.##.##
header('X-Powered-By: Luna '.getLunaVersionAsString());

if($configuration['log']['enabled']){
    require_once('storage/Console.php');
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

function getLunaVersionAsString(){
    global $version;
    return floor($version/10000).".".(($version-floor($version/10000)*10000)/100);
}

function generateID(){
    return (time()+microtime())*10000;
}
function getExecutionTime(){
    global $execution_start;
    return time()-$execution_start;
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
                if("/".$url==$request_url && ($page['id']==1||file_exists($paths["pages"].$page['id'].".json"))){
                    if($page['id']==1){
                        $console->trace("This is admin page");
                        return array('id'=>1);
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
        $console->info("Requested page not found");
        terminate(404, "HTTP/1.0 404 Not Found", "File not found.");
        break;
    case 1:
        $console->trace("Loading admin page...");
        require_once($paths['workspace'].$configuration['template']['admin']);
        terminate();
}

if(isset($page["redirect"])&&is_string($page["redirect"])){
    header('Location: '.$page["redirect"]); // TODO: prevent URI hack
    $console->trace("Page redirects to ".$page["redirect"]);
    terminate();
}


function getTitle(){
    global $page;
    global $configuration;
    return $page['main']['title'].
            $configuration['site']['delimiter'].
            $configuration['site']['name'].
            $configuration['site']['delimiter'].
            $configuration['site']['description'];
}


$console->trace("Appending header...");
require_once($paths['workspace'].$configuration['template']['header']);

$console->trace("Appending content...");
echo $page['content'];

$console->trace("Appending footer...");
require_once($paths['workspace'].$configuration['template']['footer']);

terminate();