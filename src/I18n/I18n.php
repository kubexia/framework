<?php
namespace Kubexia\I18n;

class I18n{
    
    protected static $instance = array();
    
    protected $defaultLanguage = 'en';
    
    protected $language = 'en';
    
    protected static $cache = array();
    
    public function __construct(){}

    /**
     * 
     * @param type $name
     * @return \Kubexia\I18n
     */
    public static function getInstance($name='default'){
        if(isset(static::$instance[$name])){
            return static::$instance[$name];
        }
        
        return static::$instance[$name] = new I18n();
    }
    
    public function fetch($package=NULL,$string,$name,$lang){
        if($lang === NULL){
            $lang = $this->getLanguage();
        }
        
        $translation = $this->load($package,$lang,$name);
        
        
        if(!isset($translation->{$string})){
            $translation = $this->load($package,$this->defaultLanguage,$name);
        }
        
        return $translation->{$string};
    }
    
    public function load($package,$lang,$name){
        if (isset(static::$cache[$package][$lang][$name])) {
            return static::$cache[$package][$lang][$name];
        }
        
        $translations = $this->fetchTranslations($package,$lang,$name);
        
        return ($translations !== FALSE) ? static::$cache[$package][$lang][$name] = $translations : FALSE;
    }
    
    public function fetchTranslations($package,$lang,$name=NULL){
        $array = array();
        
        if($name === NULL){
            $name = 'main';
        }
        
        $filename = NULL;
        if(!is_null($package)){
            if(preg_match('#@([a-zA-Z0-9]+):([a-zA-Z0-9]+)#', $package, $m)){
                $mod = \Kubexia\Package::getInstance()->get($m[1],$m[2]);
                $filename = $mod['path'].'/translations/'.$lang.'/'.$name.'.json';
            }
            else{
                if($package === 'system'){
                    $filename = SYS.'/translations/'.$lang.'/'.$name.'.json';
                }
            }
        }
        else{
            $filename = APP.'/translations/'.$lang.'/'.$name.'.json';
        }
        
        if(is_null($filename)){
            return FALSE;
        }
        
        if(file_exists($filename)){
            $contents = file_get_contents($filename);
            $translations = json_decode($contents);
            
            $obj = new \Kubexia\I18n\Translations($this, $translations);
            $obj->setTranslationsCache($translations);
            
            return $obj;
        }
        
        return FALSE;
    }
    
    public function setLanguage($lang){
        $this->language = $lang;
        $this->setLocale($lang);
    }
    
    public function getLanguage(){
        return $this->language;
    }
    
    public function setDefaultLanguage($lang){
        $this->defaultLanguage = $lang;
    }
    
    public function getDefaultLanguage(){
        return $this->defaultLanguage;
    }
    
    protected function setLocale($lang){
        $locale = strtolower($lang).'_'.strtoupper($lang).'.UTF-8';
        setlocale(LC_ALL, $locale);
    }
    
    public function translate($package,$string,$file='main',$replacement=array(),$lang=NULL){
        $translation = $this->fetch($package,$string,$file,$lang);
        if(!is_string($translation)){
            return new \Kubexia\I18n\Translations($this,$translation);
        }
        
        $string = html_entity_decode($translation, ENT_QUOTES, 'UTF-8');
        return empty($replacement) ? $string : strtr($string, $this->prepareReplacement($replacement));
    }
    
    public function prepareReplacement($replacement){
        $array = array();
        foreach($replacement as $key => $value){
            $array['{'.$key.'}'] = $value;
        }
        
        return $array;
    }
    
    public function getCache(){
        return static::$cache;
    }
    
}
?>