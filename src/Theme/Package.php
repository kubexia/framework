<?php
namespace Kubexia\Theme;

class Package{
    
    public $package;
    
    protected $viewsDir = array();
    
    protected static $vars = array();
    
    protected static $assets = array();
    
    protected $themePath;
    
    protected $parentTheme = NULL;
    
    protected $packageType;
    
    protected $packageName;


    public function __construct(\Kubexia\Theme\Theme $parentTheme, $packageType, $packageName, $location='theme') {
        $package = \Kubexia\Package::getStatic($packageType, $packageName);
        
        $this->packageName = $packageName;
        
        $this->packageType = $packageType;
        
        $this->themePath = $package['path'].DIRECTORY_SEPARATOR.(!is_null($location) ? $location : '');
        
        $this->addViewsDir($this->themePath);
        
        $this->parentTheme = $parentTheme;
        
        $this->package = $this;
    }
    
    public function __set($name, $value) {
        static::$vars[$name] = $value;
    }
    
    public function __get($name) {
        return (isset(static::$vars[$name]) ? static::$vars[$name] : NULL);
    }
    
    public function assign($name, $value) {
        static::$vars[$name] = $value;
    }
    
    public function getVars(){
        return array_merge(static::$vars,$this->parentTheme->getVars(), get_object_vars($this));
    }

    public function render($template = '', $fullpath = FALSE, $ext = '.php') {
        ob_start();
        
        foreach ($this->getVars() as $key => $val) {
            $$key = $val;
        }
        
        $filename = NULL;
        if($fullpath){
            $filename = $template.$ext;
        }
        else{
            
            foreach($this->viewsDir as $path){
                $filename = $path . DIRECTORY_SEPARATOR . $template . $ext;
                if(file_exists($filename)){
                    break;
                }
            }
        }
        if (is_null($filename) || !file_exists($filename)) {
            throw new \Exception('VIEW FAILED: '.$filename);
        }
        
        include $filename;

        $buffer = ob_get_contents();
        ob_end_clean();
        
        echo $buffer;
    }
    
    public function fetch($template = '', $vars = array(), $fullpath = FALSE, $ext = '.php') {
        ob_start();
        
        foreach ($this->getVars() as $key => $val) {
            $$key = $val;
        }
        
        if (is_array($vars)) {
            foreach ($vars as $key => $val) {
                $$key = $val;
            }
        }
        
        if(!$fullpath){
            $templateLocation = $this->getTemplateLocation($template,'');
            if($templateLocation['fullpath']){
                $fullpath = TRUE;
                $template = $templateLocation['path'];
            }
        }
        
        $filename = NULL;
        if($fullpath){
            $filename = $template.$ext;
        }
        else{
            foreach($this->viewsDir as $path){
                $filename = $path . '/' . $template . $ext;
                if(file_exists($filename)){
                    break;
                }
            }
        }
        
        
        if (is_null($filename) || !file_exists($filename)) {
            throw new \Exception('FETCH FAILED: '.$filename);
        }
        
        include $filename;
        
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }
    
    public function layout(){
        $args = func_get_args();
        $templateLocation = $this->getTemplateLocation($args[0],'layouts');
        $args[0] = $templateLocation['path'];
        if($templateLocation['fullpath']){
            $args[1] = TRUE;
        }
        return call_user_func_array(array($this,'render'),$args);
    }
    
    public function view(){
        $args = func_get_args();
        $templateLocation = $this->getTemplateLocation($args[0],'views');
        $args[0] = $templateLocation['path'];
        if(!isset($args[1])){
            $args[1] = array();
        }
        if($templateLocation['fullpath']){
            $args[2] = TRUE;
        }
        
        return call_user_func_array(array($this,'fetch'),$args);
    }
    
    public function template(){
        $args = func_get_args();
        $templateLocation = $this->getTemplateLocation($args[0],'templates');
        $args[0] = $templateLocation['path'];
        if($templateLocation['fullpath']){
            $args[2] = TRUE;
        }
        
        return call_user_func_array(array($this,'fetch'),$args);
    }
    
    private function getTemplateLocation($filename,$toDir=''){
        $fullpath = FALSE;
        $path = (strlen($toDir) > 0 ? $toDir.'/' : '').$filename;
        
        
        return array(
            'path' => $path,
            'fullpath' => $fullpath
        );
    }
    
    public function addViewsDir($dir){
        $this->viewsDir[] = $dir;
    }
    
    private function getLocationType($type,$asset,$filename){
        switch($type){
            case "url": return urlFor('package_asset',array(
                'theme' => $this->parentTheme->themeName,
                'packageType' => strtolower($this->packageType),
                'packageName' => strtolower($this->packageName),
                'type' => $asset,
                'file' => $filename,
            ));
                
            case "path": return $this->themePath;
        }
        return '';
    }
    
    public function img($file,$type='url'){
        return $this->getLocationType($type,'img',$file);
    }
    
    public function css($file,$type='url'){
        return $this->getLocationType($type,'css',$file);
    }
    
    public function js($file,$type='url'){
        return $this->getLocationType($type,'js',$file);
    }
    
    public function fonts($file,$type='url'){
        return $this->getLocationType($type,'fonts',$file);
    }
    
    public function assets($file,$type='url'){
        return $this->getLocationType($type,'assets',$file);
    }
    
    public function setAsset($type,$file){
        if(in_array($type,array('js','css'))){
            $this->parentTheme->addAsset($type,$this->{$type}($file));
        }
        else{
            static::$assets[$type][] = $this->custom($file);
        }
    }
}
?>
