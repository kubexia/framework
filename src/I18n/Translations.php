<?php
namespace Kubexia\I18n;

class Translations{
    
    protected $i18n;
    
    protected $translations = array();
    
    protected static $translationsCache = array();
    
    public function __construct(\Kubexia\I18n\I18n $i18n, $translations = array()){
        $this->i18n = $i18n;
        
        $this->translations = $translations;
    }
    
    public function __get($name) {
        
        $translation = (isset($this->translations->{$name}) ? $this->translations->{$name} : $name);
        
        if(is_string($translation)){
            if(preg_match("#{([a-zA-Z0-9_>-]+)}#", $translation)){
                $translation = $this->findReplacement($translation);
            }
        }
        
        return $translation;
    }
    
    public function setTranslationsCache($translations){
        static::$translationsCache = $translations;
    }
    
    public function getTranslationsCache(){
        return static::$translationsCache;
    }
    
    public function translate($name, $replacement=array()){
        $translation = $this->{$name};
        
        if(!is_string($translation)){
            return new \Kubexia\I18n\Translations($this->i18n, $translation);
        }
        
        $string = html_entity_decode($translation, ENT_QUOTES, 'UTF-8');
        
        return empty($replacement) ? $string : strtr($string, $this->i18n->prepareReplacement($replacement));
    }
    
    public function findReplacement($translation){
        preg_match_all("#{([a-zA-Z0-9_>-]+)}#", $translation, $match);
        
        $cache = $this->getTranslationsCache();
        $replacement = array();
        if(!empty($match[1])){
            
            foreach($match[1] as $key => $item){
                $found = $this->string($cache, $item);
                if($found !== NULL){
                    $found = $this->findReplacement($found);
                    $replacement[$item] = $found;
                }
                else{
                    $replacement[$item] = $this->findReplacementValue($this->{$item}, $cache);
                }
            }
        }
        
        $string = html_entity_decode($translation, ENT_QUOTES, 'UTF-8');
        
        return empty($replacement) ? $string : strtr($string, $this->i18n->prepareReplacement($replacement));
    }
    
    protected function findReplacementValue($key, $cache){
        $replacement = NULL;
        foreach($cache as $item => $value){
            if(!is_string($value)){
                return $this->findReplacementValue($key, $cache->{$item});
            }
            else{
                if($item === $key){
                    $replacement = $value;
                    break;
                }
            }
        }
        
        return (is_null($replacement) ? '{'.$key.'}' : $replacement);
    }
    
    
    public function string($obj, $path_str) {
        $val = null;

        $path = preg_split('/->/', $path_str);
        $node = $obj;
        while (($prop = array_shift($path)) !== null) {
            if (!is_object($obj) || !property_exists($node, $prop)) {
                $val = null;
                break;
            }
            $val = $node->$prop;
            
            $node = $node->$prop;
        }

        return $val;
    }
    

}