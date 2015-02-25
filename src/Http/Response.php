<?php
namespace Kubexia\Http;

class Response {
    
    protected $success = FALSE;
    
    protected $message = NULL;
    
    protected $errors = array();
    
    protected $response = array();
    
    protected $output = array();
    
    protected $debug = FALSE;
    
    protected $i18n;
    
    public function __construct(){
        $this->output = array(
            'response' => 'object'
        );
        
        $this->i18n = \Kubexia\I18n\I18n::getInstance();
        
    }
    
    static public function getInstance(){
        static $instance;

        empty($instance) and $instance = new Response();
        
        return $instance;
    }
    
    public function setSuccess($bool = FALSE){
        $this->success = $bool;
        return $this;
    }
    
    public function setMessage($text=NULL){
        $this->message = $text;
        return $this;
    }
    
    public function setError($code, $message='', $field='default'){
        $this->errors[$field] = array(
            'code' => $code,
            'message' => $message
        );
        return $this;
    }
    
    public function setErrors($array){
        if(!empty($array) || $array !== FALSE){
            foreach($array as $code => $message){
                $this->errors[$code] = $message;
            }
        }
        
        return $this;
    }
    
    public function setResponse($data=array()){
        $this->response = array_merge($data,$this->response);
        return $this;
    }
    
    public function output($type='json',$returnit=FALSE){
        $output = '';
        switch($type){
            case "json":
                header('Content-Type: application/json');
                
                $json = array(
                    'response' => (($this->output['response'] === 'object') ? (object) $this->response : (array) $this->response),
                    'errors' => (object) $this->errors,
                    'success' => (bool) $this->success,
                    'message' => (empty($this->message) ? NULL : $this->message),
                );
                
                $output = json_encode($json,JSON_PRETTY_PRINT);
                break;
        }
        
        if($returnit) return $output;
        else echo $output;
    }
    
}
?>