<?php
class Console
{
    public static $version=10000;
    private $log = '';
    private $logfile_path = '';
    private $log_level = 0;
    private $logfile_size = 0;
    function Console($logfile_path,$log_level=0,$logfile_size=10485760){
        $this->log_level = $log_level;
        $this->logfile_path = $logfile_path;
        $this->logfile_size = $logfile_size*1024;
        $this->log = "\r\n".date('d.m.Y h:i:s');
    }

    // TODO: Do not rewrite file, cut unnecessary part
    public function save(){
        global $console;
        file_put_contents(
            $this->logfile_path,
            $this->log,
            @filesize($this->logfile_path)+sizeof($this->log) < $this->logfile_size ? FILE_APPEND : false); // Append if filesize+currlogsize < allowed logsize + supress warning of filesize if file is not created
        return $this;
    }
    private function append($type,$data){
        $this->log .= "\r\n[".$type."]\t".$data;
        return $this;
    }
    public function trace($string){
        if($this->log_level<1) $this->append('TRACE',$string);
        return $this;
    }
    public function log($string){
        if($this->log_level<2) $this->append('LOG',$string);
        return $this;
    }
    public function info($string){
        if($this->log_level<3) $this->append('INFO',$string);
        return $this;
    }
    public function warn($string){
        if($this->log_level<4) $this->append('WARN',$string);
        return $this;
    }
    public function error($string){
        $this->append('ERROR',$string);
        return $this;
    }
}