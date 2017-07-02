<?php
$execution_start = time();
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

class Page{
    private $page = null;
    private $found = false;
    function __construct($pageID){
        global $paths;
        global $console;
        global $configuration;

        if(is_string($pageID)) {
            $console->trace("Loading page via URI...");
            if (!is_array($configuration['index'])) {
                $console->trace("Seems to be configuration index is not array");
                $this->page = null;
            }
            foreach ($configuration['index'] as $prePage) {
                if (isset($prePage['urls']) && is_array($prePage['urls']) && isset($prePage['id'])) {
                    foreach ($prePage['urls'] as $url) {
                        $console->trace("Proceeding #" . $prePage['id'] . ", comparing \"" . $url . "\"");
                        if ("/" . $url == $pageID && isset($prePage['redirect']) || file_exists($paths["pages"] . $prePage['id'] . ".json")) {
                            if (isset($prePage['redirect'])) {
                                $console->trace("This is page with redirect");
                                $this->page = $prePage;
                                $this->found = true;
                                return;
                            }
                            $console->trace("Page determined, loading...");
                            $page_contents = json_decode(file_get_contents($paths['pages'] . $prePage['id'] . ".json"), true);
                            if (is_array($page_contents)) {
                                $console->trace("Page loaded");
                                $this->page = array_merge($prePage, $page_contents);
                                $this->validate();
                                $this->found = true;
                                return;
                            } else {
                                $console->error("Page #" . $prePage['id'] . " is corrupted");
                                $this->page = null;
                                return;
                            }
                        }
                    }
                }
            }
            $this->found = false;
            return;
        }
        /**
         * @deprecated Do not use new Page(1234567890)
         */
        if(is_numeric($pageID)){
            $console->trace("Loading page via ID...");
            foreach ($configuration['index'] as $prePage) {
                if (
                    isset($prePage['urls']) &&
                    is_array($prePage['urls']) &&
                    isset($prePage['id']) &&
                    $prePage['id']==$pageID &&
                    isset($prePage['urls'][0]) &&
                    is_string($prePage['urls'][0])
                ) {
                    $console->trace("Reconstructing using URI ".$prePage['urls'][0]."...");
                    $this->__construct("/".$prePage['urls'][0]);
                    return;
                }
            }
        }
    }
    private function validate(){
        if(
            !isset($this->page["id"]) ||
            is_null($this->page["id"]) ||
            !is_numeric($this->page["id"]) ||
            (
                (
                    !isset($this->page["type"]) ||
                    is_null($this->page["type"]) ||
                    !is_string($this->page["type"])
                ) &&
                $this->isRedirect()
            )
        ) $page = null;
    }
    public function isValid(){
        return
            !is_null($this->page) &&
            is_array($this->page) &&
            isset($this->page["id"]);
    }
    public function isFound(){
        return $this->found;
    }
    public function getArray(){
        return $this->page;
    }
    public function getID(){
        return $this->page["id"];
    }
    public function getType(){
        return $this->page["type"];
    }
    public function isRedirect(){
        return
            isset($this->page["redirect"]) &&
            !is_null($this->page["redirect"]) &&
            is_string($this->page["redirect"]);
    }
    public function getRedirect(){
        return
            isset($this->page["redirect"]) &&
            is_string($this->page["redirect"])
                ? $this->page["redirect"]
                : null;
    }
    public function getCreated(){
        return
            isset($this->page["created"]) &&
            is_numeric($this->page["created"])
                ? date($this->page["created"])
                : null;
    }
    public function getModified(){
        return
            isset($this->page["modified"]) &&
            is_numeric($this->page["modified"])
                ? date($this->page["modified"])
                : null;
    }
    public function getRevisions(){
        return
            isset($this->page["revisions"]) &&
            is_numeric($this->page["revisions"])
                ? date($this->page["revisions"])
                : null;
    }
    public function getTitle(){
        return
            isset($this->page["main"]) &&
            !is_null($this->page["main"]) &&
            is_array($this->page["main"]) &&
            isset($this->page["main"]["title"]) &&
            !is_null($this->page["main"]["title"]) &&
            is_string($this->page["main"]["title"])
                ? $this->page["main"]["title"]
                : null;
    }
    public function getFullTitle(){
        global $configuration;
        $title = $this->getTitle();
        return
            (
                !is_null($title)
                    ? $title
                    : ""
            ).
            (
                !is_null($title) &&
                isset($configuration['site']) &&
                is_array($configuration['site']) &&
                (
                    (
                        isset($configuration['site']['name']) &&
                        !is_null($configuration['site']['name'])
                    ) ||
                    (
                        isset($configuration['site']['description']) &&
                        !is_null($configuration['site']['description'])
                    )
                )
                    ? (
                        isset($configuration['site']['delimiter']) &&
                        !is_null($configuration['site']['delimiter'])
                    )
                        ? $configuration['site']['delimiter']
                        : " - "
                    : ""
            ).
            (
                isset($configuration['site']) &&
                is_array($configuration['site']) &&
                isset($configuration['site']['name']) &&
                !is_null($configuration['site']['name'])
                    ? $configuration['site']['name']
                    : ""
            ).
            (
                isset($configuration['site']) &&
                is_array($configuration['site']) &&
                isset($configuration['site']['name']) &&
                !is_null($configuration['site']['name']) &&
                isset($configuration['site']['description']) &&
                !is_null($configuration['site']['description'])
                    ? (
                        isset($configuration['site']['delimiter']) &&
                        !is_null($configuration['site']['delimiter'])
                    )
                        ? $configuration['site']['delimiter']
                        : " - "
                    : ""
            ).
            (
                isset($configuration['site']) &&
                is_array($configuration['site']) &&
                isset($configuration['site']['description']) &&
                !is_null($configuration['site']['description'])
                    ? $configuration['site']['description']
                    : ""
            );
    }
    public function getDescription(){ // TODO: add 150 words maximum check in UI!
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["description"]) &&
            !is_null($this->page["optimization"]["description"]) &&
            is_string($this->page["optimization"]["description"])
                ? $this->page["optimization"]["description"]
                : null;
    }
    public function getKeywords(){ // TODO: add 10 keywords maximum check in UI!
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["keywords"]) &&
            !is_null($this->page["optimization"]["keywords"]) &&
            is_array($this->page["optimization"]["keywords"])
                ? $this->page["optimization"]["keywords"]
                : null;
    }
    public function getAuthor(){
        return
            isset($this->page["main"]) &&
            !is_null($this->page["main"]) &&
            is_array($this->page["main"]) &&
            isset($this->page["main"]["author"]) &&
            !is_null($this->page["main"]["author"]) &&
            is_string($this->page["main"]["author"])
                ? $this->page["main"]["author"]
                : null;
    }
    public function getLanguage(){
        return
            isset($this->page["main"]) &&
            !is_null($this->page["main"]) &&
            is_array($this->page["main"]) &&
            isset($this->page["main"]["language"]) &&
            !is_null($this->page["main"]["language"]) &&
            is_string($this->page["main"]["language"])
                ? $this->page["main"]["language"]
                : null;
    }
    public function getCanonicalURLS($url){
        $urls = array();
        foreach ($this->page["urls"] as $uri){
            if($url !== "/".$uri)
                array_push($urls,$uri);
        }
        return $urls;
    }
    public function isRobotsIndex(){
        return
            isset($this->page["robots"]["index"]) &&
            is_bool($this->page["robots"]["index"])
            ? $this->page["robots"]["index"]
            : true;
    }
    public function isRobotsFollow(){
        return
            isset($this->page["robots"]["follow"]) &&
            is_bool($this->page["robots"]["follow"])
            ? $this->page["robots"]["follow"]
            : true;
    }
    public function isRobotsArchive(){
        return
            isset($this->page["robots"]["archive"]) &&
            is_bool($this->page["robots"]["archive"])
            ? $this->page["robots"]["archive"]
            : true;
    }
    public function isRobotsODP(){
        return
            isset($this->page["robots"]["odp"]) &&
            is_bool($this->page["robots"]["odp"])
            ? $this->page["robots"]["odp"]
            : true;
    }
    public function getParentPageID(){
        $parentsHierarchy = $this->getParentPageHierarchy();
        return
            !is_null($parentsHierarchy) &&
            is_array($parentsHierarchy) &&
            isset($parentsHierarchy[0]) &&
            !is_null($parentsHierarchy[0]) &&
            is_numeric($parentsHierarchy[0])
                ? $parentsHierarchy[0]
                : null;
    }
    public function getParentPage(){
        $parentID = $this->getParentPageID();
        return
            !is_null($parentID)
                ? new Page($parentID)
                : null;
    }
    public function getParentPageHierarchy(){
        return
            isset($this->page["parents"]) &&
            is_array($this->page["parents"])
                ? $this->page["parents"]
                : null;
    }
    public function getOGTitle(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["og"]) &&
            !is_null($this->page["optimization"]["og"]) &&
            is_array($this->page["optimization"]["og"]) &&
            isset($this->page["optimization"]["og"]["title"]) &&
            !is_null($this->page["optimization"]["og"]["title"]) &&
            is_string($this->page["optimization"]["og"]["title"])
                ? $this->page["optimization"]["og"]["title"]
                : null;
    }
    public function getOGDescription(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["og"]) &&
            !is_null($this->page["optimization"]["og"]) &&
            is_array($this->page["optimization"]["og"]) &&
            isset($this->page["optimization"]["og"]["description"]) &&
            !is_null($this->page["optimization"]["og"]["description"]) &&
            is_string($this->page["optimization"]["og"]["description"])
                ? $this->page["optimization"]["og"]["description"]
                : null;
    }
    public function getOGType(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["og"]) &&
            !is_null($this->page["optimization"]["og"]) &&
            is_array($this->page["optimization"]["og"]) &&
            isset($this->page["optimization"]["og"]["type"]) &&
            !is_null($this->page["optimization"]["og"]["type"]) &&
            is_string($this->page["optimization"]["og"]["type"])
                ? $this->page["optimization"]["og"]["type"]
                : null;
    }
    public function getOGLocale(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["og"]) &&
            !is_null($this->page["optimization"]["og"]) &&
            is_array($this->page["optimization"]["og"]) &&
            isset($this->page["optimization"]["og"]["locale"]) &&
            !is_null($this->page["optimization"]["og"]["locale"]) &&
            is_string($this->page["optimization"]["og"]["locale"])
                ? $this->page["optimization"]["og"]["locale"]
                : null;
    }
    public function getOGImages(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["og"]) &&
            !is_null($this->page["optimization"]["og"]) &&
            is_array($this->page["optimization"]["og"]) &&
            isset($this->page["optimization"]["og"]["images"]) &&
            !is_null($this->page["optimization"]["og"]["images"]) &&
            is_array($this->page["optimization"]["og"]["images"])
                ? $this->page["optimization"]["og"]["images"]
                : array();
    }
    public function getTwitterSiteUsername(){
        global $configuration;
        return
            !is_null($configuration["additional"]["twitterid"]) &&
            is_string($configuration["additional"]["twitterid"])
                ? "@".$configuration["additional"]["twitterid"]
                : "";
    }
    public function getTwitterAuthorUsername(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["twitter"]) &&
            !is_null($this->page["optimization"]["twitter"]) &&
            is_array($this->page["optimization"]["twitter"]) &&
            isset($this->page["optimization"]["twitter"]["author"]) &&
            !is_null($this->page["optimization"]["twitter"]["author"]) &&
            is_string($this->page["optimization"]["twitter"]["author"])
                ? "@".$this->page["optimization"]["twitter"]["author"]
                : null;
    }
    public function getTwitterCardType(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["twitter"]) &&
            !is_null($this->page["optimization"]["twitter"]) &&
            is_array($this->page["optimization"]["twitter"]) &&
            isset($this->page["optimization"]["twitter"]["card"]) &&
            !is_null($this->page["optimization"]["twitter"]["card"]) &&
            is_string($this->page["optimization"]["twitter"]["card"])
                ? $this->page["optimization"]["twitter"]["card"]
                : null;
    }
    public function getTwitterImage(){
        return
            isset($this->page["optimization"]) &&
            !is_null($this->page["optimization"]) &&
            is_array($this->page["optimization"]) &&
            isset($this->page["optimization"]["twitter"]) &&
            !is_null($this->page["optimization"]["twitter"]) &&
            is_array($this->page["optimization"]["twitter"]) &&
            isset($this->page["optimization"]["twitter"]["image"]) &&
            !is_null($this->page["optimization"]["twitter"]["image"]) &&
            is_string($this->page["optimization"]["twitter"]["image"])
                ? $this->page["optimization"]["twitter"]["image"]
                : null;
    }

    public function getContent($sectionID,$fieldID){
        $id = $sectionID."_".$fieldID;
        return
            isset($this->page["contents"]) &&
            is_array($this->page["contents"]) &&
            isset($this->page["contents"][$id])
            ? $this->page["contents"][$id]
            : null;
    }

    public function getHTMLTitle($p=""){
        return $p."<title>".$this->getFullTitle()."</title>";
    }
    public function getHTMLXUACompatible($p=""){
        global $configuration;
        return
            isset($configuration["additional"]["xuacompatible"])
                ? $p."<meta http-equiv=\"X-UA-Compatible\" content=\"".$configuration["additional"]["xuacompatible"]."\" />"
                : "";
    }
    public function getHTMLViewPort($p=""){
        global $configuration;
        return
            isset($configuration["additional"]["viewport"])
            ? $p . "<meta name=\"viewport\" content=\"" . (
                (
                    isset($configuration["additional"]["viewport"]["width"])
                    ? "width=" . $configuration["additional"]["viewport"]["width"] . ", " : ""
                ).
                (
                    isset($configuration["additional"]["viewport"]["initial_scale"])
                    ? "initial-scale=" . $configuration["additional"]["viewport"]["initial_scale"] . ", " : ""
                ).
                (
                    isset($configuration["additional"]["viewport"]["maximum_scale"])
                    ? "maximum-scale=" . $configuration["additional"]["viewport"]["maximum_scale"] . ", " : ""
                ).
                (
                    isset($configuration["additional"]["viewport"]["user_scalable"])
                    ? "user-scalable=" . $configuration["additional"]["viewport"]["user_scalable"] : ""
                )
            ) . "\" />"
            : "";
    }
    public function getHTMLFavicon($p=""){
        // TODO: load another favicon from page
        global $configuration;
        return
            isset($configuration["additional"]["favicon_path"])
                ? $p."<link rel=\"shortcut icon\" href=\"".getSiteURI().$configuration["additional"]["favicon_path"]."\" type=\"image/x-icon\">"
                : "";
    }
    public function getHTMLDescription($p=""){
        $d = $this->getDescription();
        return
            !is_null($d)
                ? $p."<meta name=\"description\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLKeywords($p=""){
        $d = $this->getKeywords();
        return
            !is_null($d)
                ? $p."<meta name=\"keywords\" content=\"".implode(",",$d)."\" />"
                : "";
    }
    public function getHTMLIndex($p=""){ // TODO: hide title if no site name
        global $configuration;
        return
            !is_null($configuration['site']['name'])
                ? $p."<link rel=\"index\" title=\"".$configuration['site']['name']."\" href=\"".getSiteURI()."\">"
                : "";
    }
    public function getHTMLRobots($p=""){
        return
            isset($this->page["robots"])
            ? (
                $p .
                "<meta name=\"robots\" content=\"" .
                (!$this->isRobotsIndex() ? "no" : "") . "index," .
                (!$this->isRobotsFollow() ? "no" : "") . "follow," .
                (!$this->isRobotsArchive() ? "no" : "") . "archive," .
                (!$this->isRobotsODP() ? "noodp,noydir" : "odp,ydir").
                "\">"
            )
            : "";
    }
    public function getHTMLCopyright($p=""){ // TODO: hide title if no site name
        global $configuration;
        return
            !is_null($configuration["additional"]["copyright"])
                ? $p."<meta name=\"copyright\" content=\"".$configuration["additional"]["copyright"]."\" />"
                : "";
    }
    public function getHTMLAuthor($p=""){
        $d = $this->getAuthor();
        return
            !is_null($d)
                ? $p."<meta name=\"author\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLLanguage($p=""){
        $d = $this->getLanguage();
        return
            !is_null($d)
                ? $p."<meta name=\"language\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLCanonical($p="", $url){
        $d = $this->getCanonicalURLS($url);
        return
            count($d)>0
                ? $p.
                    "<link rel=\"canonical\" href=\"".
                    implode("\">".$p."<link rel=\"canonical\" href=\"",$d).
                    "\">"
                : "";
    }

    public function getHTMLOG($p=""){ // https://developers.facebook.com/docs/sharing/webmasters
        return
            $this->getHTMLOGURL($p).
            $this->getHTMLOGType($p).
            $this->getHTMLOGTitle($p).
            $this->getHTMLOGDescription($p).
            $this->getHTMLOGImages($p).
            $this->getHTMLOGLocale($p).
            $this->getHTMLOGFBID($p);
    }
    public function getHTMLOGURL($p=""){
        global $request_url;
        return $p."<meta property=\"og:url\" content=\"".getSiteURI().substr($request_url,1)."\" />";
    }
    public function getHTMLOGTitle($p=""){
        $d = $this->getOGTitle();
        if(is_null($d)) $d = $this->getFullTitle();
        return
            !is_null($d)
                ? $p."<meta property=\"og:title\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLOGType($p=""){
        $d = $this->getOGType();
        return
            !is_null($d)
                ? $p."<meta property=\"og:type\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLOGDescription($p=""){
        $d = $this->getOGDescription();
        if(is_null($d)) $d = $this->getDescription();
        return
            !is_null($d)
                ? $p."<meta property=\"og:description\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLOGLocale($p=""){
        $d = $this->getOGLocale();
        return
            !is_null($d)
                ? $p."<meta property=\"og:locale\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLOGImages($p=""){
        $d = $this->getOGImages();
        $r = "";
        foreach ($d as $i){
            if(
                is_array($i) &&
                isset($i["path"]) &&
                is_string($i["path"])
            ){
                $r .= $p. "<meta property=\"og:image\" content=\"".getSiteURI().$i["path"]."\" />" . (
                    isset($i["width"]) &&
                    is_numeric($i["width"]) &&
                    isset($i["height"]) &&
                    is_numeric($i["height"])
                    ? $p."<meta property=\"og:image:width\" content=\"".$i["width"]."\" />".$p."<meta property=\"og:image:height\" content=\"".$i["height"]."\" />"
                    : ""
                );
            }
        }
        return
            $r;
    }
    public function getHTMLOGFBID($p=""){
        global $configuration;
        return
            !is_null($configuration['additional']['fbid']) &&
            is_numeric($configuration['additional']['fbid'])
                ? $p."<meta property=\"fb:app_id\" content=\"".$configuration['additional']['fbid']."\" />"
                : "";
    }

    public function getHTMLTwitterMetaTags($p=""){ // https://dev.twitter.com/cards/markup
        return
            $this->getHTMLTwitterCardMetaTag($p).
            $this->getHTMLTwitterTitleMetaTag($p).
            $this->getHTMLTwitterDescriptionMetaTag($p).
            $this->getHTMLTwitterImageMetaTag($p).
            $this->getHTMLTwitterSiteMetaTag($p).
            $this->getHTMLTwitterAuthorMetaTag($p);
    }
    public function getHTMLTwitterCardMetaTag($p=""){
        $d = $this->getTwitterCardType();
        return
            !is_null($d)
                ? $p."<meta name=\"twitter:card\" content=\"".$d."\">"
                : "";
    }
    public function getHTMLTwitterSiteMetaTag($p=""){
        $d = $this->getTwitterSiteUsername();
        return
            !is_null($d)
                ? $p."<meta name=\"twitter:site\" content=\"".$d."\">"
                : "";
    }
    public function getHTMLTwitterAuthorMetaTag($p=""){
        $d = $this->getTwitterAuthorUsername();
        return
            !is_null($d)
                ? $p."<meta name=\"twitter:author\" content=\"".$d."\">"
                : "";
    }
    public function getHTMLTwitterTitleMetaTag($p=""){
        $d = $this->getOGTitle();
        if(is_null($d)) $d = $this->getFullTitle();
        return
            !is_null($d)
                ? $p."<meta name=\"twitter:title\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLTwitterDescriptionMetaTag($p=""){
        $d = $this->getOGDescription();
        if(is_null($d)) $d = $this->getDescription();
        return
            !is_null($d)
                ? $p."<meta name=\"twitter:description\" content=\"".$d."\" />"
                : "";
    }
    public function getHTMLTwitterImageMetaTag($p=""){
        $d = $this->getTwitterImage();
        return
            !is_null($d)
                ? $p."<meta name=\"twitter:image\" content=\"".getSiteURI().$d."\" />"
                : "";
    }

    public function getHTMLBaseHead(){
        global $configuration;
        global $console;
        global $request_url;

        $b = $configuration["beatify"]["code"];
        if($b){
            $console->trace("Beatification is on");
        }else{
            $console->trace("Beatification is off");
        }

        $p = $b ? "\r\n\t\t" : "";
        return "<!DOCTYPE html>".
            ($b ? "\r\n" : "").
            "<html prefix=\"og: http://ogp.me/ns#\">".
            ($b ? "\r\n\t" : "").
            "<head>".
            $p.
            "<meta charset=\"utf-8\" />".
            $this->getHTMLXUACompatible($p).
            $this->getHTMLViewPort($p).
            $this->getHTMLTitle($p).
            $this->getHTMLFavicon($p).
            $this->getHTMLDescription($p).
            $this->getHTMLKeywords($p).
            $this->getHTMLIndex($p).
            $this->getHTMLRobots($p).
            $this->getHTMLCopyright($p).
            $this->getHTMLAuthor($p).
            $this->getHTMLLanguage($p).
            $this->getHTMLCanonical($p,$request_url).
            $this->getHTMLOG($p).
            $this->getHTMLTwitterMetaTags($p);
    }
    public function getHTMLHead(){
        return $this->getHTMLBaseHead();
    }
}

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
    global $CF;
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
        info("Execution time: ".getExecutionTime()."ms")->
        info("Memory usage: ".(floor(memory_get_usage()/10485.76)/100)."MB");
    $console->save();
    if(ob_get_status()){
        $cached = fopen($CF, 'w');
        fwrite($cached, ob_get_contents());
        fclose($cached);
        ob_end_flush();
    }
    exit();
}
function getLunaVersionAsString($value=-1){
    global $version;
    if($value<1) $value = $version;
    return floor($value/10000).".".(($value-floor($value/10000)*10000)/100);
}
function getExecutionTime(){
    global $execution_start;
    return time()-$execution_start;
}
function getSiteURI(){
    global $configuration;
    return "http".($configuration['site']['https_supported'] ? "s" : "")."://".$configuration['site']['host']."/";
}

if($request_url=="/luna/api"){
    $console->trace("Loading API...");
    require_once($paths['storage']."api.php");
    terminate();
}

$page = new Page($request_url);
ob_start();
// TODO: show multilanguage errors
if(!$page->isFound()){
    $console->warn("Requested page not found");
    terminate(404, "HTTP/1.0 404 Not Found", "File not found.");
}
if(!$page->isValid()){
    $console->trace("Server can not show page");
    terminate(500,"HTTP/1.0 500 Internal Server Error","Something went wrong, server is no longer to work.");
}
if($page->isRedirect()){
    header('Location: '.getSiteURI().$page->getRedirect());
    $console->trace("Page redirects to ".$page->getRedirect());
    terminate();
}

$console->trace("Connecting template...");
require_once($paths['workspace'].$configuration['workspace']['template']);

terminate();
// More metatags: www.nytimes.com