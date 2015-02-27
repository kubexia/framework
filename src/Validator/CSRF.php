<?php
namespace Kubexia\Validator;

class CSRF{
    
    protected $config;
    
    protected $session;
    
    public function __construct(){
        $this->config = \Kubexia\Config::getInstance('csrf');
        
        $this->session = \Kubexia\Session\Session::getInstance();
    }
    
    /**
     * 
     * @staticvar \Kubexia\Validator\CSRF $instance
     * @return \Kubexia\Validator\CSRF
     */
    public static function getInstance(){
        static $instance;

        empty($instance) and $instance = new CSRF();
        
        return $instance;
    }
    
    /**
     * 
     * @param type $name
     * @return string
     */
    public function getKey($name){
        return (is_null($name) ? md5('default') : md5($name));
    }
    
    /**
     * 
     * @param type $key
     * @return array|string
     */
    public function set($key=NULL){
        $this->session->set('csrf_'.$this->getKey($key), array(
            'token' => md5(random_string(10).microtime()),
            'time' => time()
        ));
        
        return $this->get($key);
    }
    
    /**
     * 
     * @param type $key
     * @return array|string
     */
    public function get($key=NULL){
        return $this->session->get('csrf_'.$this->getKey($key));
    }
    
    /**
     * 
     * @param type $key
     * @param type $token
     * @param type $die
     * @return string|boolean|exit
     */
    public function validate($key,$token, $die = TRUE){
        $csrf = $this->get($key);
        if(!is_null($csrf) && $csrf->token === $token){
            if((time() - $csrf->time) > $this->config->get('time_expiration')){
                return 'time_expired';
            }
            
            return TRUE;
        }
        
        return ($die ? die('Invalid request') : FALSE);
    }
    
    /**
     * 
     * @param type $key
     * @return boolean
     */
    public function destroy($key=NULL){
        return $this->session->destroy('csrf_'.$this->getKey($key));
    }
}
