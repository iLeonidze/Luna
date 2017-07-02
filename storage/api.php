<?php
session_start();
header('Content-Type: application/json');

class DataModel{
    private $datamodelArray = null;
    private $datamodel = null;
    function __construct(){
        global $console;
        global $paths;
        $console->trace("Loading DataModel...");
        try{
            $this->datamodelArray = json_decode(file_get_contents($paths["workspace"]."datamodel.json"),true);
            $console->trace("Pacing DataModel...");
            $this->datamodel = array();
            foreach ($this->datamodelArray as $pageType){
                if(
                    !is_null($pageType) &&
                    is_array($pageType)
                ){
                    array_push($this->datamodel, new PageType($pageType));
                }
            }
        }catch (Error $error){
            $console->error("DataModel is corrupted or not accessible");
            $this->datamodel = null;
        }
    }
    public function getArray(){
        return $this->datamodelArray;
    }
}
class PageType{
    private $pageType = null;
    function __construct($pageType){
        $this->pageType = $pageType;
        if(
            !is_null($this->pageType) &&
            is_array($this->pageType) &&
            isset($this->pageType->sections) &&
            !is_null($this->pageType->sections) &&
            is_array($this->pageType->sections)
        ){
            $newSections = array();
            foreach ($this->pageType->sections as $section) {
                array_push($newSections,new SectionObject($section));
            }
            $this->pageType->sections = $newSections;
        }
    }
    public function getID(){
        if(
            !is_null($this->pageType) &&
            is_array($this->pageType) &&
            isset($this->pageType->id) &&
            !is_null($this->pageType->id) &&
            is_string($this->pageType->id)
        ){
            return $this->pageType->id;
        }
        return null;
    }
    public function getName(){
        if(
            !is_null($this->pageType) &&
            is_array($this->pageType) &&
            isset($this->pageType->name) &&
            !is_null($this->pageType->name) &&
            is_string($this->pageType->name)
        ){
            return $this->pageType->name;
        }
        return null;
    }
    public function getIcon(){
        if(
            !is_null($this->pageType) &&
            is_array($this->pageType) &&
            isset($this->pageType->icon) &&
            !is_null($this->pageType->icon) &&
            is_string($this->pageType->icon)
        ){
            return $this->pageType->icon;
        }
        return null;
    }
    public function getAmount(){
        if(
            !is_null($this->pageType) &&
            is_array($this->pageType) &&
            isset($this->pageType->amount) &&
            !is_null($this->pageType->amount) &&
            is_integer($this->pageType->amount) // TODO: add to wiki
        ){
            return $this->pageType->amount;
        }
        return null;
    }
    public function getSections(){
        if(
            !is_null($this->pageType) &&
            is_array($this->pageType) &&
            isset($this->pageType->sections) &&
            !is_null($this->pageType->sections) &&
            is_array($this->pageType->sections)
        ){
            return $this->pageType->sections;
        }
        return null;
    }
    public function getSectionsIDs(){
        $sections = $this->getSections();
        if(
            !is_null($sections) &&
            is_array($sections)
        ){
            $sectionsIDs = array();
            foreach ($sections as $section){
                if(
                    !is_null($section) &&
                    !is_null($section->getID())
                ){
                    array_push($sectionsIDs,$section->getID());
                }
            }
            return array_unique($sectionsIDs);
        }
        return null;
    }
    public function getSection($sectionID){
        $sections = $this->getSections();
        if(
            !is_null($sections) &&
            is_array($sections)
        ){
            foreach ($sections as $section){
                if(
                    !is_null($section) &&
                    !is_null($section->getID()) &&
                    $section->getID() == $sectionID
                ){
                    return $section;
                }
            }
        }
        return null;
    }
}
class PageTypeSection{
    private $pageTypeSection = null;
    function __construct($pageTypeSection){
        $this->pageTypeSection = $pageTypeSection;
        if(
            !is_null($this->pageTypeSection) &&
            is_array($this->pageTypeSection) &&
            isset($this->pageTypeSection->objects) &&
            !is_null($this->pageTypeSection->objects) &&
            is_array($this->pageTypeSection->objects)
        ){
            $newObjects = array();
            foreach ($this->pageTypeSection->objects as $object) {
                array_push($newObjects,new SectionObject($object));
            }
            $pageTypeSection->pageTypeSection->objects = $newObjects;
        }
    }
    public function getID(){
        if(
            !is_null($this->pageTypeSection) &&
            is_array($this->pageTypeSection) &&
            isset($this->pageTypeSection->id) &&
            !is_null($this->pageTypeSection->id) &&
            is_string($this->pageTypeSection->id)
        ){
            return $this->pageTypeSection->id;
        }
        return null;
    }
    public function getName(){
        if(
            !is_null($this->pageTypeSection) &&
            is_array($this->pageTypeSection) &&
            isset($this->pageTypeSection->name) &&
            !is_null($this->pageTypeSection->name) &&
            is_string($this->pageTypeSection->name)
        ){
            return $this->pageTypeSection->name;
        }
        return null;
    }
    public function getIcon(){
        if(
            !is_null($this->pageTypeSection) &&
            is_array($this->pageTypeSection) &&
            isset($this->pageTypeSection->icon) &&
            !is_null($this->pageTypeSection->icon) &&
            is_string($this->pageTypeSection->icon)
        ){
            return $this->pageTypeSection->icon;
        }
        return null;
    }
    public function getObjects(){
        if(
            !is_null($this->pageTypeSection) &&
            is_array($this->pageTypeSection) &&
            isset($this->pageTypeSection->objects) &&
            !is_null($this->pageTypeSection->objects) &&
            is_array($this->pageTypeSection->objects)
        ){
            return $this->pageTypeSection->objects;
        }
        return null;
    }
    public function getObjectsIDs(){
        $objects = $this->getObjects();
        if(
            !is_null($objects) &&
            is_array($objects)
        ){
            $objectsIDs = array();
            foreach ($objects as $object){
                if(
                    !is_null($object) &&
                    !is_null($object->getID())
                ){
                    array_push($objectsIDs,$object->getID());
                }
            }
            return array_unique($objectsIDs);
        }
        return null;
    }
    public function getField($fieldID){
        $objects = $this->getObjects();
        if(
            !is_null($objects) &&
            is_array($objects)
        ){
            foreach ($objects as $object){
                if(
                    !is_null($object) &&
                    $object->isField() &&
                    !is_null($object->getID()) &&
                    $object->id == $fieldID
                ){
                    return $object;
                }
            }
        }
        return null;
    }
}
class SectionObject{
    private $object = null;
    function __construct($object){
        $this->object = $object;
    }

    public function getObject(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->object) &&
            !is_null($this->object->object) &&
            is_string($this->object->object)
        ){
            return $this->object->object;
        }
        return null;
    }

    public function getID(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->id) &&
            !is_null($this->object->id) &&
            is_string($this->object->id)
        ){
            return $this->object->id;
        }
        return null;
    }
    public function getName(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->name) &&
            !is_null($this->object->name) &&
            is_string($this->object->name)
        ){
            return $this->object->name;
        }
        return null;
    }
    public function getDescription(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->description) &&
            !is_null($this->object->description) &&
            is_string($this->object->description)
        ){
            return $this->object->description;
        }
        return null;
    }
    public function getTest(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->test) &&
            !is_null($this->object->test) &&
            is_string($this->object->test)
        ){
            return $this->object->test;
        }
        return null;
    }

    public function isRequired(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->required) &&
            is_bool($this->object->trequired)
        ){
            return $this->object->required;
        }
        return false;
    }
    public function isField(){
        return $this->getObject() == "field";
    }
    public function isTitle(){
        return $this->getObject() == "title";
    }
    public function isDelimiter(){
        return $this->getObject() == "delimiter";
    }

    public function getType(){
        if(
            $this->isField() &&
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->type) &&
            !is_null($this->object->type) &&
            is_string($this->object->type)
        ){
            return $this->object->type;
        }
        return null;
    }

    public function isText(){
        return $this->getType() == "text";
    }
    public function isNumber(){
        return $this->getType() == "number";
    }
    // TODO: range!
    public function isContent(){
        return $this->getType() == "content";
    }
    public function isToggle(){
        return $this->getType() == "toggle";
    }
    public function isSelector(){
        return $this->getType() == "selector";
    }
    public function isFile(){
        return $this->getType() == "file";
    }
    public function isFiles(){
        return $this->getType() == "files";
    }

    public function getKind(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->kind) &&
            !is_null($this->object->kind) &&
            is_string($this->object->kind) &&
            (
                $this->isContent() ||
                $this->isSelector() ||
                $this->isFile() ||
                $this->isFiles()
            )
        ){
            return $this->object->kind;
        }
        return null;
    }

    public function getValue(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->value) &&
            !is_null($this->object->value)
        ){
            switch ($this->getType()){
                case "text":
                    if(is_string($this->object->value)) return $this->object->value;
                case "number":
                    if(is_numeric($this->object->value)) return $this->object->value;
                case "content":
                    if(is_string($this->object->value)){
                        // TODO: if markdown
                        return $this->object->value;
                    }
                case "toggle":
                    if(is_bool($this->object->value)) return $this->object->value;
                case "selector":
                    if(is_array($this->object->value)) return $this->object->value;
                case "file":
                    if(is_string($this->object->value)) return $this->object->value;
                case "files":
                    if(is_array($this->object->value)) return $this->object->value;
            }
        }
        return null;
    }

    public function getRange(){
        if(
            !is_null($this->object) &&
            is_array($this->object) &&
            isset($this->object->range) &&
            !is_null($this->object->range) &&
            is_array($this->object->range)
        ){
            return $this->object->range;
        }
        return null;
    }
    public function getRangeMax(){
        $range = $this->getRange();
        if(
            !is_null($range) &&
            is_array($range) &&
            isset($range->max) &&
            !is_null($range->max) &&
            is_numeric($range->max)
        ){
            return $range->max;
        }
        return null;
    }
    public function getRangeMin(){
        $range = $this->getRange();
        if(
            !is_null($range) &&
            is_array($range) &&
            isset($range->min) &&
            !is_null($range->min) &&
            is_numeric($range->min)
        ){
            return $range->min;
        }
        return null;
    }
    public function getRangeStep(){
        $range = $this->getRange();
        if(
            !is_null($range) &&
            is_array($range) &&
            isset($range->step) &&
            !is_null($range->step) &&
            is_numeric($range->step)
        ){
            return $range->step;
        }
        return null;
    }
    public function isRangeBeautified(){
        $range = $this->getRange();
        if(
            !is_null($range) &&
            is_array($range) &&
            isset($range->beatify) &&
            !is_null($range->beatify) &&
            is_bool($range->beatify)
        ){
            return $range->beatify;
        }
        return false;
    }
}

function generateID(){
    return (time()+microtime())*10000;
}

if(!isset($_SESSION["login"])||!file_exists($paths["users"].md5($_SESSION["login"]).".json")){
    $console->log("Unknown user came to API");
    if(!isset($_POST["action"])||$_POST["action"]!=="login"){
        $console->log("Unknown user tries to bypass auth");
        terminate(405,"HTTP/1.1 405 Method Not Allowed","{\"error\":\"Authorize first\"}");
    }else{
        $console->trace("Unknown user wants to auth");
        if(!isset($_POST["login"])||$_POST["login"]==""||!isset($_POST["password"])||$_POST["password"]==""){
            $console->log("Unknown user did not provided login or password to auth");
            terminate(400,"HTTP/1.0 400 Bad Request","{\"error\":\"Login or password fields are missing\"}");
        }else{
            $user = @json_decode(@file_get_contents($paths["users"].md5($_POST["login"]).".json"),true);
            if(!is_null($user)&&isset($user["password"])&&$user["password"]==md5($_POST["password"])){
                $_SESSION["login"] = basename($_POST["login"]);
                $console->info("User logged in as ".$_SESSION["login"]);
                terminate(200,"HTTP/1.0 200 OK","{\"error\":false}");
            }else{
                $console->log("Unknown user provided incorrect login credentials");
                terminate(403,"HTTP/1.0 403 Forbidden","{\"error\":\"Invalid login or password\"}");
            }
        }
    }
}else{
    $console->trace("Working with authed user @".$_SESSION["login"]);
    try {
        $user = @json_decode(@file_get_contents($paths["users"] . md5($_POST["login"]) . ".json"), true);
    }catch (Error $error){
        session_destroy();
        terminate(500,"HTTP/1.0 500 Internal Server Error","{\"error\":\"User profile file is corrupted or in use\"}");
    }
    switch ($_POST["action"]){
        case "logout":
            $console->trace("@".$_SESSION["login"]." logged out");
            session_destroy();
            terminate(200,"HTTP/1.0 200 OK","{\"error\":false}");
            break;
        case "whoami":
            terminate(200,"HTTP/1.0 200 OK",json_encode(array(
                "error"=>false,
                "user"=>array(
                    "profile"=>$user["profile"],
                    "id"=>$_SESSION["login"],
                    "grants"=>$user["grants"],
                )
            )));
            break;




        case "getPages":
            // TODO: add filter by page type
            // TODO: add filter by search
            // TODO: add pages list size managing
            break;
        case "getPage":
            if(!isset($_POST['id'])&&isset($_POST['uri'])){
                terminate(400,"HTTP/1.0 400 Bad Request",json_encode(array(
                    "error"=>true
                )));
            }
            if(isset($_POST['uri'])){
                $page = new Page($_POST["uri"]);
            }else{
                $page = new Page((float)$_POST["id"]);
            }
            if(!$page->isValid()){
                terminate(222,"HTTP/1.0 422 Unprocessable Entity",json_encode(array(
                    "error"=>true
                )));
            }
            terminate(200,"HTTP/1.0 200 OK",json_encode(array(
                "error"=>false,
                "data"=>$page->getArray()
            )));
            break;




        case "getDataModel":
            $dataModel = new DataModel();
            terminate(200,"HTTP/1.0 200 OK",json_encode(array(
                "error"=>false,
                "data"=>$dataModel->getArray()
            )));
            break;
        case "savePage":
            $dataModel = new DataModel();
            // TODO
            break;




        default:
            $console->warn("@".$_SESSION["login"]." requires unknown action");
            terminate(501,"HTTP/1.0 501 Not Implemented","{\"error\":\"Action is not supported\"}");
    }
}