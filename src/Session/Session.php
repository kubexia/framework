<?php
namespace Kubexia;

class Session{
    
    protected $config;
    
    protected $encrypt;
    
    public function __construct() {
        $this->config = \Kubexia\Config::getInstance('session');
        
        $this->encrypt = \Kubexia\Encrypt::getInstance();
        
        if(class_exists('Memcached')){
            ini_set('session.save_handler', $this->config->get('save_handler'));

            ini_set('session.save_path', $this->config->get('save_path'));
        }

        $handler = new \Kubexia\Session\Handler($this->config->get('handler_key'));
        
        session_set_save_handler($handler, true);

        session_start();
    }
    
    public static function getInstance(){
        static $instance;

        empty($instance) and $instance = new Session();
        
        return $instance;
    }
    
    public function set($key,$value){
        $_SESSION[$key] = $this->encrypt->encode($value);
    }
    
    public function get($key){
        return (isset($_SESSION[$key]) ? $this->encrypt->decode($_SESSION[$key]) : NULL);
    }
    
    public function destroy($name){
        return $this->unsetSession($name);
    }
    
    public function unsetSession($key){
        if(isset($_SESSION[$key])){
            unset($_SESSION[$key]);
        }
        
        return FALSE;
    }
    
    public function destroyAll(){
        $sessions = $this->getAll();
        if(empty($sessions)){
            return FALSE;
        }
        
        foreach($sessions as $key => $value){
            unset($_SESSION[$key]);
        }
    }
    
    public function config($name){
        return $this->config->get($name);
    }
    
    public function getAll(){
        return (isset($_SESSION) ? $_SESSION : array());
    }
}

