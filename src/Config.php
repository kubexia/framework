<?php
namespace Kubexia;

class Config{
    
    protected static $instances = array();
    
    protected $configs = array();
    
    public function __construct($name) {
        $filename = CONFIGS.'/'.$name.'.php';
        if(!file_exists($filename)){
            throw new \Exception('Config file ('.$filename.') was not found.');
        }
        
        $this->configs = include $filename;
    }
    
    public static function getInstance($name){
        if(isset(static::$instances[$name])){
            return static::$instances[$name];
        }
        
        static::$instances[$name] = new Config($name);
        
        return static::$instances[$name];
    }
    
    public function get($key=NULL){
        if(is_null($key)){
            return $this->configs;
        }
        
        return (isset($this->configs[$key]) ? $this->configs[$key] : NULL);
    }
    
}

