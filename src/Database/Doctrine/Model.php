<?php
namespace Kubexia\Database\Doctrine;

class Model{
    
    protected static $model = array();
    
    public function __construct(){}
    
    public function __call($name, $arguments) {}
    
    /**
     * 
     * @return \Slim\Slim
     */
    public function app(){
        return \Slim\Slim::getInstance();
    }
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    private function db(){
        return \Kubexia\Database\Doctrine\Handler::getInstance()->getConnection();
    }
    
    public function __set($name, $value) {
        $this->{$name} = $value;
    }
    
    public function __get($name) {
        return $this->{$name};
    }
    
    /**
     * 
     * @return \Http\Base\Model
     */
    public static function model(){
        $className = get_called_class();
        
        if(!isset(static::$model[$className])){
            static::$model[$className] = new $className();
        }
        
        return static::$model[$className];
    }
    
    public function insert(array $array){
        foreach($array as $field => $value){
            call_user_func_array(array($this,'__set'), array($field,$value));
        }
        
        $this->triggerBeforeInsert();
        
        $this->db()->persist($this);
        $this->db()->flush();
        
        $this->triggerAfterInsert();
        
        return $this;
    }
    
    public function update(array $array){
        foreach($array as $field => $value){
            call_user_func_array(array($this,'__set'), array($field,$value));
        }
        
        $this->triggerBeforeUpdate();
        
        $this->db()->persist($this);
        $this->db()->flush();
        
        $this->triggerAfterUpdate();
        
        return $this;
    }
    
    
    public function delete(){
        $this->triggerBeforeDelete();
        
        $this->db()->remove($this);
        $this->db()->flush();
        
        $this->triggerAfterDelete();
        
        return $this;
    }
    
    public function batch($sql=""){
        $sql = preg_replace("#:table#", get_called_class(), $sql);
        return $this->db()->createQuery($sql);
    }
    
    public function formatDate($field,$format = 'Y-m-d H:i:s'){
        if($this->{$field} === NULL){
            return NULL;
        }
        
        $date = new \DateTime($this->{$field});
        return $date->format($format);
    }
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    public function entity(){
        return $this->db()->getRepository(get_called_class());
    }
    
}