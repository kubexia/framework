<?php
namespace Kubexia;

class PackageLoader{
    
    protected $i18n;
    
    protected $package;
    
    protected $packageName;
    
    protected $packageType;
    
    public function __construct($packageClass,$customPackage=NULL){
        $this->i18n = \Kubexia\I18n\I18n::getInstance();
        
        if(!is_null($customPackage)){
            $this->setCustomPackage($customPackage);
        }
        else{
            $this->setPackage($packageClass);
        }
    }
    
    protected function setPackage($packageClass){
        $array = explode('\\',$packageClass);
        
        $packageType = array_shift($array);
        $packageName = array_shift($array);
        
        $package = \Kubexia\Package::getStatic($packageType,$packageName);
        if($package){
            $this->package = $package;
            $this->packageName = $packageName;
            $this->packageType = $packageType;
        }
    }
    
    protected function setCustomPackage($customPackage){
        $array = explode('\\',$customPackage);
        $packageType = (isset($array[0]) ? $array[0] : NULL);
        $packageName = (isset($array[1]) ? $array[1] : NULL);
        
        $package = \Kubexia\Package::getStatic($packageType,$packageName);
        if($package){
            $this->package = $package;
            $this->packageName = $packageName;
            $this->packageType = $packageType;
        }
    }


    public function get(){
        return $this->package;
    }


    public function translate(){
        return call_user_func_array(array($this->i18n,'translate'), array_merge(array('@'.$this->packageType.':'.$this->packageName),func_get_args()));
    }
    
}