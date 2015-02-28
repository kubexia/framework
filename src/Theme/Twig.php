<?php
namespace Kubexia\Theme;

class Twig{
    
    public $themeName;
    
    protected $twig;
    
    public $loader;
    
    /**
     *
     * @var \Twig_Environment 
     */
    protected $env;
    
    protected static $themes = array();
    
    protected static $vars = array();
    
    public function __construct($name){
        $this->themeName = $name;
        
        \Twig_Autoloader::register();
        
        $this->loader = new \Twig_Loader_Filesystem();
        
        //theme path
        $this->loader->addPath(THEMES.'/'.$this->themeName,'theme');
        
        //bower components
        $this->loader->addPath(PUBLIC_DIR.'/bower_components','bower');
        
        //app path
        $this->loader->addPath(APP.'/theme', 'app');
        
        //system path
        $this->loader->addPath(SYS.'/theme', 'sys');
        
        //packages paths
        $this->addPackagesPaths();
        
        $this->env = new \Twig_Environment($this->loader, array(
            'cache' => SYS_STORAGE.'/twig_cache',
            'debug' => true
        ));
        
        $this->registerFunctions();
    }
    
    /**
     * 
     * @param type $name
     * @return \Kubexia\Theme\Twig
     */
    public static function getInstance($name){
        if(isset(static::$themes[$name])){
            return static::$themes[$name];
        }
        
        static::$themes[$name] = new Twig($name);
        
        return static::$themes[$name];
    }
    
    public function __set($name, $value=NULL) {
        $this->add($name, $value);
    }
    
    public function __get($name) {
        return (isset(static::$vars[$name]) ? static::$vars[$name] : NULL);
    }
    
    public function __call($name, $arguments) {
        if(is_callable(array($this->env,$name))){
            return call_user_func_array(array($this->env,$name), $arguments);
        }
    }
    
    protected function addPackagesPaths(){
        $packages = \Kubexia\Package::getInstance()->getAll();
        
        foreach($packages as $type => $items){
            foreach($items as $name => $configs){
                $filename = $configs['path'].'/theme';
                if(file_exists($filename)){
                    $this->loader->addPath($filename, $type.':'.$name);
                }
            }
        }
    }
    
    protected function registerFunctions(){
        $this->env->addFunction(new \Twig_SimpleFunction('asset', array($this,'asset')));
        $this->env->addFunction(new \Twig_SimpleFunction('translate', array(\Kubexia\I18n\I18n::getInstance(),'translate')));
        $this->env->addFunction(new \Twig_SimpleFunction('urlFor', 'urlFor'));
        $this->env->addFunction(new \Twig_SimpleFunction('urlOutFor', 'urlOutFor'));
        $this->env->addFunction(new \Twig_SimpleFunction('getMemoryUsage', 'getMemoryUsage'));
        $this->env->addFunction(new \Twig_SimpleFunction('getExecutionTime', 'getExecutionTime'));
    }
    
    public function add($name,$value=NULL){
        static::$vars[$name] = $value;
    }
    
    public function append($name,$value=NULL){
        return $this->env->addGlobal($name, $value);
    }
    
    public function fetch($name,$index=NULL){
        return $this->env->loadTemplate($name, $index);
    }
    
    public function display($name,$context=array()){
        $this->append('debugQueries', \Kubexia\Database\Doctrine\Debugger::getInstance()->queries());
        return $this->env->display($name, array_merge(static::$vars,$context));
    }
    
    public function meta($name,$value){
        $this->append('meta', array($name => $value));
    }
    
    public function asset($name,$returnHost=false){
        $fullpath = $this->loader->getCacheKey($name);
        
        $asset = '';
        if(preg_match("#(.*)/".basename(PACKAGES)."/([a-zA-Z0-9]+)/([a-zA-Z0-9]+)/theme/(.*)#", $fullpath, $matches)){
            $asset = urlFor('package_asset',array(
                'theme' => $this->themeName,
                'packageType' => $matches[2],
                'packageName' => $matches[3],
                'filename' => $matches[4],
            ));
        }
        else{
            $publicDir = basename(PUBLIC_DIR);
            preg_match("#(.*)/".$publicDir."/(.*)#", $fullpath, $matches);
            $asset = baseurl($publicDir.'/'.$matches[2],$returnHost);
        }
        return $asset;
    }
    
}