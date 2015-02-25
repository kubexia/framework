<?php
namespace Kubexia\Theme;

class Theme{
    
    public $themeName;
    
    public $themeURI;
    
    public $themePath;
    
    protected $themeLocation;
    
    protected $viewsDir = array();
    
    protected static $vars = array();
    
    protected static $assets = array();
    
    protected static $packages = array();
    
    public function __construct($theme,$location=NULL) {
        $this->themeName = $theme;
        $this->themeLocation = $location;
        
        $this->themeURI = app()->request()->getRootUri().'/'.basename(PUBLIC_DIR).'/'.  basename(THEMES).'/'.(!is_null($location) ? $location.'/' : '').$theme;
        $this->themePath = THEMES.DIRECTORY_SEPARATOR.(!is_null($location) ? $location.'/' : '').$theme;
        
        $this->viewsDir[] = APP.'/theme';
        $this->viewsDir[] = $this->themePath;
        
        $this->tpl = $this;
    }
    
    public function __set($name, $value) {
        static::$vars[$name] = $value;
    }
    
    public function __get($name) {
        return (isset(static::$vars[$name]) ? static::$vars[$name] : NULL);
    }
    
    public function assign($key, $value) {
        static::$vars[$key] = $value;
    }
    
    public function getVars(){
        return static::$vars;
    }
    
    public function translate($name,$file='main',$replacement=array(),$lang=NULL){
        return __(NULL,$name,$file,$replacement,$lang);
    }
    
    /**
     * 
     * @param type $template
     * @param type $fullpath
     * @param type $ext
     * @throws \Exception
     */
    public function render($template = '', $fullpath = FALSE, $ext = '.php') {
        ob_start();
        
        foreach (static::$vars as $key => $val) {
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
    
    /**
     * 
     * @param type $template
     * @param type $vars
     * @param boolean $fullpath
     * @param type $ext
     * @return type
     * @throws \Exception
     */
    public function fetch($template = '', $vars = array(), $fullpath = FALSE, $ext = '.php') {
        ob_start();

        foreach (static::$vars as $key => $val) {
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
    
    /**
     * 
     * @param type $template
     * @param type $fullpath
     * @param type $ext
     * @throws \Exception
     */
    public function layout(){
        $args = func_get_args();
        $templateLocation = $this->getTemplateLocation($args[0],'layouts');
        $args[0] = $templateLocation['path'];
        if($templateLocation['fullpath']){
            $args[1] = TRUE;
        }
        return call_user_func_array(array($this,'render'),$args);
    }
    
    /**
     * 
     * @param type $template
     * @param type $vars
     * @param boolean $fullpath
     * @param type $ext
     * @return type
     * @throws \Exception
     */
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
    
    /**
     * 
     * @param type $template
     * @param type $vars
     * @param boolean $fullpath
     * @param type $ext
     * @return type
     * @throws \Exception
     */
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
        if(preg_match('#@([a-zA-Z0-9]+):([a-zA-Z0-9]+)/(.*)#', $filename, $m)){
            $module = \Kubexia\Package::getInstance()->get($m[1],$m[2]);
            
            if($module){
                $path = $module['path'].'/theme/'.(strlen($toDir) > 0 ? $toDir.'/' : '').$m[3];
                $fullpath = TRUE;
            }
        }
        else{
            $fullpath = FALSE;
            $path = (strlen($toDir) > 0 ? $toDir.'/' : '').$filename;
        }
        
        return array(
            'path' => $path,
            'fullpath' => $fullpath
        );
    }
    
    private function getLocationType($type){
        switch($type){
            case "url": return $this->themeURI;
            case "path": return $this->themePath;
        }
        return '';
    }
    
    public function img($file,$type='url'){
        return $this->getLocationType($type).'/assets/img/'.$file;
    }
    
    public function css($file,$type='url'){
        return $this->getLocationType($type).'/assets/css/'.$file;
    }
    
    public function js($file,$type='url'){
        return $this->getLocationType($type).'/assets/js/'.$file;
    }
    
    public function fonts($file,$type='url'){
        return $this->getLocationType($type).'/assets/fonts/'.$file;
    }
    
    public function assets($file,$type='url'){
        return $this->getLocationType($type).'/assets/'.$file;
    }
    
    public function media($file,$type='url'){
        switch($type){
            case "url": return app()->request->getRootUri().'/'.basename(PUBLIC_DIR).'/media/'.$file;
            case "path": return PUBLIC_DIR.'/media/'.$file;
        }
        return '';
    }
    
    public function bower($file,$type='url'){
        switch($type){
            case "url": return app()->request->getRootUri().'/'.basename(PUBLIC_DIR).'/bower_components/'.$file;
            case "path": return PUBLIC_DIR.'/bower_components/'.$file;
        }
        return '';
    }
    
    public function custom($file, $type='url'){
        return $this->getLocationType($type).'/assets/'.$file;
    }
    
    public function setAsset($type,$file){
        if(in_array($type, array('plainjs','plaincss'))){
            static::$assets[$type][] = $file;
        }
        elseif(in_array($type,array('mediajs','mediacss'))){
            static::$assets[$type][] = $this->media($file);
        }
        elseif(in_array($type,array('bowerjs','bowercss'))){
            static::$assets[$type][] = $this->bower($file);
        }
        elseif(in_array($type,array('js','css'))){
            static::$assets[$type][] = $this->{$type}($file);
        }
        else{
            static::$assets[$type][] = $this->custom($file);
        }
    }
    
    public function getAssets($type){
        return (isset(static::$assets[$type]) ? static::$assets[$type] : FALSE);
    }
    
    public function addAsset($type,$file){
        static::$assets[$type][] = $file;
    }
    
    public function import($type){
        if(is_array($type)){
            $output = '';
            foreach($type as $item){
                $output .= $this->import($item);
            }
            return $output;
        }
        
        $assets = $this->getAssets($type);
        if(empty($assets)){
            return '';
        }
        
        $output = '';
        switch($type){
            case "bowercss":
            case "mediacss":
            case "css":
                foreach($assets as $path){
                    $output .= '<link rel="stylesheet" href="'.$path.'">'."\n";
                }
                break;
            case "plaincss":
                foreach($assets as $plain){
                    $output .= '<style type="text/css">'.$plain.'</style>'."\n";
                }
                break;
            case "bowerjs":
            case "mediajs":
            case "js":
                foreach($assets as $path){
                    $output .= '<script type="text/javascript" src="'.$path.'"></script>'."\n";
                }
                break;
            case "plainjs":
                foreach($assets as $plain){
                    $output .= '<script type="text/javascript">'.$plain.'</script>'."\n";
                }
                break;
                
            default:
                if(preg_match('#css.#',$type)){
                    foreach($assets as $path){
                        $output .= '<link rel="stylesheet" href="'.$path.'">'."\n";
                    }
                }
                
                if(preg_match('#js.#',$type)){
                    foreach($assets as $path){
                        $output .= '<script type="text/javascript" src="'.$path.'"></script>'."\n";
                    }
                }
                break;
        }
        return $output;
    }
    
    public function getMeta($name, $value=NULL){
        if(is_array($name)){
            $output = '';
            foreach($name as $item){
                $output .= $this->getMeta($item);
            }
            return $output;
        }
        
        if(!is_null($value)){
            $this->meta[$name] = $value;
        }
        
        switch($name){
            case "title":
                return  (isset($this->meta['title']) && !empty($this->meta['title'])) ? '<title>'.$this->meta['title'].'</title>'."\n" : '';
            case "description":
                return  (isset($this->meta['description']) && !empty($this->meta['description'])) ? '<meta name="description" content="'.$this->meta['description'].'" />'."\n" : '';
            case "keywords":
                return  (isset($this->meta['keywords']) && !empty($this->meta['keywords'])) ? '<meta name="keywords" content="'.$this->meta['keywords'].'" />'."\n" : '';
        }
        
        return '';
    }
    
    public function getMetaInfo($infos){
        $output = '';
        foreach(explode(',',$infos) as $item){
            $output .= $this->getMeta($item);
        }
        return $output;
    }
    
    public function addViewsDir($dir){
        $this->viewsDir[] = $dir;
    }
    
    public function package($type,$name){
        if(isset(static::$packages[$type][$name])){
            return static::$packages[$type][$name];
        }
        
        $package = \Kubexia\Package::getStatic($type, $name);
        if(!$package){
            return NULL;
        }
        
        $template = new \Kubexia\Theme\Package($this, $type, $name);
        
        $template->setConfigs($package);
        
        static::$packages[$type][$name] = $template;
        
        return $template;
    }
}
?>
