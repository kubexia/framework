<?php
namespace Kubexia\Validator;

class Validator{
    
    protected $errors = array();
    
    protected $language = NULL;
    
    protected $items = array();
    
    protected $filters = array();
    
    protected $filename = 'validations';
    
    protected $attributes = array();
    
    protected $i18n;
    
    protected $translations;
    
    protected $validations;
    
    protected $package = NULL;
    
    public function __construct(){
        $this->i18n = \Kubexia\I18n\I18n::getInstance();
    }
    
    protected function mapFilters($filter){
        foreach($this->filters[$filter] as $key => $value){
            if(is_numeric($key)){
                $method = $value;
                $options = array();
            }
            else{
                $method = $key;
                $options = $value;
            }
            
            $this->mapValidation($method,$filter,$this->getItem($filter),$options);
        }
    }
    
    public function mapValidation($method,$filter,$value,$options){
        if($method !== 'required'){
            if(!$this->filterIsSet($filter)){
                return FALSE;
            }
        }
        if(is_callable($options)){
            $valid = call_user_func_array(array($this->validations,'is_callable'), array($method,$filter,$value,$options));
        }
        else{
            if(is_callable(array($this->validations, $method))){
                $valid = call_user_func_array(array($this->validations,$method), array($filter,$value,$options));
            }
        }
        
        if(!isset($valid)){
            throw  new \Exception($method.' validate method doesn\'t exist');
        }
        
        if(!$valid){
            $this->addError($filter,$method, $options);
        }
    }
    
    public function filterIsSet($filter){
        return (isset($this->items[$filter]) && strlen($this->items[$filter]) > 0 ? TRUE : FALSE);
    }
    
    
    public function validate(array $items = array(), array $filters = array(), $filename = NULL, $package = NULL, $language = NULL){
        $this->package = $package;
        
        $this->setItems($items);
        $this->setFilters($filters);
        $this->setLanguage($language);
        $this->setFilename($filename);
        
        $translations = $this->i18n->load($package,$this->getLanguage(),$this->getFilename());
        if(!$translations){
            return FALSE;
        }
        $this->translations = $translations;
        
        $this->attributes = $translations->attributes;
        
        $this->validations = new \Kubexia\Validator\Validations($this->getItems());
        
        array_map(array($this,'mapFilters'),array_keys($this->filters));
        
        return $this;
    }
    
    public function setItems($items){
        $this->items = array_merge($this->items,$items);
        return $this;
    }
    
    public function getItems(){
        return $this->items;
    }
    
    public function getItem($name){
        return (isset($this->items[$name]) ? $this->items[$name] : NULL);
    }
    
    public function setFilters($filters){
        $this->filters = array_merge($this->filters,$filters);
        return $this;
    }
    
    public function getFilters(){
        return $this->filters;
    }
    
    public function setLanguage($lang=NULL){
        $this->language = (is_null($lang) ? $this->i18n->getLanguage() : $lang);
    }
    
    public function getLanguage(){
        return $this->language;
    }
    
    public function setFilename($filename = NULL){
        $this->filename = (is_null($filename) ? $this->filename : $filename);
    }
    
    public function getFilename(){
        return $this->filename;
    }
    
    public function addError($item, $code, $options=array()){
        $bind = (isset($options['bind']) ? $options['bind'] : array());
        
        $message = $this->i18n->translate($this->package, $item.'_'.$code);
        if($message === $item.'_'.$code && !empty($this->attributes)){
            $replacements = array_merge($bind,array('attribute' => $this->translations->attributes->{$item}));
            $message = $this->i18n->translate($this->package,$code,$this->getFilename(),$replacements);
        }
        
        $this->errors[$item] = array(
            'code' => $code,
            'message' => $message
        );
    }
    
    public function setError($code, $message='', $item='default'){
        $this->errors[$item] = array(
            'code' => $code,
            'message' => $message
        );
    }
    
    public function isErrorCode($item,$code){
        return (isset($this->errors[$item]['code']) && $this->errors[$item]['code'] === $code ? TRUE : (
            $this->hasError($item)
        ));
    }
    
    public function hasError($item){
        return (isset($this->errors[$item]) ? TRUE : FALSE);
    }
    
    public function hasErrors(){
        return (count($this->errors) > 0 ? TRUE : FALSE);
    }
    
    public function getErrors(){
        return (empty($this->errors) ? array() : $this->errors);
    }
    
    public function isError($field){
        return (isset($this->errors[$field]) ? TRUE : FALSE);
    }
    
    public function errors(){
        return $this->getErrors();
    }
    
}
?>