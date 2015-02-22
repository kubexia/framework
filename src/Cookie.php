<?php

namespace Kubexia;

class Cookie{
    
    protected $config;
    
    protected $encrypt;
    
    public $domain;
    
    public $path = '/';
    
    public $expire = NULL;
    
    public function __construct() {
        $this->config = \Kubexia\Config::getInstance('cookie');
        
        $this->expire = $this->config->get('expire');
        
        $this->encrypt = \Kubexia\Encrypt::getInstance();
        
        $this->domain = \Slim\Slim::getInstance()->request()->getHost();
    }
    
    public static function getInstance(){
        static $instance;

        empty($instance) and $instance = new Cookie();
        
        return $instance;
    }
    
    public function setExpire($unix = 0){
        $this->expire = $unix;
    }
    
    public function set($name, $value, $expire = NULL){
        if(is_null($expire)){
            $expire = $this->expire;
        }
        setcookie($this->hash($name), $this->encrypt->encode($value), time() + $expire, $this->path, $this->domain);
    }
    
    public function get($name){
        return (isset($_COOKIE[$this->hash($name)]) ? $this->encrypt->decode($_COOKIE[$this->hash($name)]) : NULL);
    }
    
    public function destroy($name){
        if(!isset($_COOKIE[$this->hash($name)])){
            return FALSE;
        }
        
        unset($_COOKIE[$this->hash($name)]);
        setcookie($this->hash($name), NULL, time() - $this->expire, $this->path, $this->domain);
    }
    
    protected function hash($name){
        return md5($name.'#'.$this->config->get('cookie_name').'#'.$this->config->get('salt'));
    }
    
    public function config($name){
        return $this->config->get($name);
    }
    
}

