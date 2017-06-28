<?php
header('Content-Type: application/json');
global $paths;
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
            //$console->trace(file_get_contents($paths["users"].md5($_POST["login"]).".json"));
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
        $user = json_decode(file_get_contents($paths["users"] . md5($_POST["login"]) . ".json"), true);
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
        case "createPage":
            // TODO
            break;
        default:
            $console->warn("@".$_SESSION["login"]." requires unknown action");
            terminate(501,"HTTP/1.0 501 Not Implemented","{\"error\":\"Action is not supported\"}");
    }
}