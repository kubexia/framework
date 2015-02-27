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
        $packageType = strtolower(array_shift($array));
        
        $filename = NULL;
        $packageName = strtolower(array_shift($array));
        
        if(isset(static::$packages[$packageType]) && in_array($packageName, array_keys(static::$packages[$packageType]))){
            $dir = strtolower(array_shift($array));
            $mod = static::getStatic($packageType,$packageName);
            $filename = $mod['path'].'/'.$dir.'/'.join('/',$array);
        }
        
        if(is_null($filename)){
            return FALSE;
        }
        
        $filename = $filename.'.php';
        
        if(file_exists($filename)){
            include $filename;
        }
        else{
            throw  new \Exception($filename.' was not found');
        }
    }
    
    public function register($type,$name,$options){
        $type = strtolower($type);
        $name = strtolower($name);
        
        static::$packages[$type][$name] = $options;
    }
    
    public function getAll(){
        return static::$packages;
    }
    
    public static function getStatic($type,$name){
        $type = strtolower($type);
        $name = strtolower($name);
        return (isset(static::$packages[$type][$name]) ? static::$packages[$type][$name] : FALSE);
    }
    
    public static function getStaticLower($type,$name){
        return static::getStatic($type, $name);
//        $packages = static::$packages;
//        
//        $type = strtolower($type);
//        $name = strtolower($name);
//        
//        $pName = NULL;
//        $pType = NULL;
//        foreach($packages as $packageType => $items){
//            if(strtolower($packageType) === $type){
//                $pType = $packageType;
//                
//                foreach($items as $packageName => $value){
//                    if(strtolower($packageName) === $name){
//                        $pName = $packageName;
//                        break;
//                    }
//                }
//            }
//        }
//        
//        if(is_null($pName) || is_null($pType)){
//            return FALSE;
//        }
//        
//        return self::getStatic($pType, $pName);
    }
    
    public function get($type,$name){
        $type = strtolower($type);
        $name = strtolower($name);
        
        $packages = $this->getAll();
        return (isset($packages[$type][$name]) ? $packages[$type][$name] : FALSE);
    }
    
    public static function exists($type,$name){
        $type = strtolower($type);
        $name = strtolower($name);
        
        return (isset(static::$packages[$type][$name]) ? TRUE : FALSE);
    }
    
}
