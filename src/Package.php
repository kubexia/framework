<?php
namespace Kubexia;

class Package{
    
    protected static $packages = array();
    
    public function __construct(){}
    
    public static function getInstance(){
        static $instance;

        empty($instance) and $instance = new Package();
        
        return $instance;
    }
    
    public static function registerAutoloader(){
        spl_autoload_register(array('\Kubexia\Package','autoloader'));
    }
    
    public static function autoloader($className){
        if (class_exists($className, FALSE)){
            return FALSE;
        }

        $array = explode('\\',$className);
        $location = array_shift($array);
        
        $package = array_shift($array);
        if(in_array($package, array_keys(static::$packages))){
            $dir = strtolower(array_shift($array));
            
            $mod = static::getStatic($package);
            $filename = $mod['path'].'/'.$dir.'/'.join('/',$array);
        }

        $filename = $filename.'.php';

        if(file_exists($filename)){
            include $filename;
        }
        else{
            throw  new \Exception($filename.' was not found');
        }
    }
    
    public function register($name,$options){
        static::$packages[$name] = $options;
    }
    
    public function getAll(){
        return static::$packages;
    }
    
    public static function getStatic($name){
        return (isset(static::$packages[$name]) ? static::$packages[$name] : FALSE);
    }
    
    public function get($name){
        $packages = $this->getAll();
        return (isset($packages[$name]) ? $packages[$name] : FALSE);
    }
    
    public static function exists($name){
        return (isset(static::$packages[$name]) ? TRUE : FALSE);
    }
    
    
}
