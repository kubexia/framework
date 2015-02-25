<?php
namespace Kubexia\Database\Doctrine;

abstract class ActiveEntity{
    
    protected static $entity = array();
    
    protected static $entityManager = NULL;
    
    protected static $data = array();
    
    protected static function getInstance(){
        $className = get_called_class();
        if(isset(static::$entity[$className])){
            return static::$entity[$className];
        }
        
        static::$entity[$className] = new $className();
        
        return static::$entity[$className];
    }
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    protected static function getEntityManager(){
        if(!is_null(static::$entityManager)){
            return static::$entityManager;
        }
        
        static::$entityManager = \Kubexia\Database\Doctrine\Handler::getInstance()->getConnection();
        
        return static::$entityManager;
    }
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    protected function entityManager(){
        return static::getEntityManager();
    }
    
    public function __set($key, $value) {
        static::$data[$key] = $value;
    }
    
    public function __get($key) {
        return static::$data[$key];
    }
    
    public function __isset($key) {
        return isset(static::$data[$key]);
    }
    
    /**
     * 
     * @param array $array
     * @return \Kubexia\Database\Doctrine\ActiveEntity
     */
    public static function insert(){
        return call_user_func_array(array(static::getInstance(),'_insert'), func_get_args());
    }
    
    /**
     * 
     * @param array $array
     * @return \Kubexia\Database\Doctrine\ActiveEntity
     */
    protected function _insert(array $array){
        foreach($array as $field => $value){
            $this->{$field} = $value;
        }
        
        $this->triggerBeforeInsert();
        
        $this->entityManager()->persist($this);
        $this->entityManager()->flush();
        
        $this->triggerAfterInsert();
        
        return $this;
    }
    
    /**
     * 
     * @param array $array
     * @return \Kubexia\Database\Doctrine\ActiveEntity
     */
    public function update(array $array){
        foreach($array as $field => $value){
            $this->{$field} = $value;
        }
        
        $this->triggerBeforeUpdate();
        
        $this->entityManager()->persist($this);
        $this->entityManager()->flush();
        
        $this->triggerAfterUpdate();
        
        return $this;
    }
    
    public function save(){
        $this->triggerBeforeUpdate();
        
        $this->entityManager()->persist($this);
        $this->entityManager()->flush();
        
        $this->triggerAfterUpdate();
        
        return $this;
    }
    
    public function delete(){
        $this->triggerBeforeDelete();
        
        $this->entityManager()->remove($this);
        $this->entityManager()->flush();
        
        $this->triggerAfterDelete();
        
        return $this;
    }
    
    public function formatDate($field,$format = 'Y-m-d H:i:s'){
        if($this->$field === NULL){
            return NULL;
        }
        
        $date = new \DateTime($this->$field);
        return $date->format($format);
    }
    
    public function __call($method, $arguments) {
        $func = substr($method, 0, 3);
        $fieldName = substr($method, 3, strlen($method));
        $fieldName = lcfirst($fieldName);
        
        if ($func === 'get') {
            return $this->$fieldName;
        } 
        else if ($func === 'set') {
            $this->$fieldName = $arguments[0];
        } 
        else if ($func === 'has') {
            return $this->__isset($fieldName);
        } 
        else if(in_array($method,array('triggerBeforeInsert','triggerAfterInsert','triggerBeforeUpdate','triggerAfterUpdate','triggerBeforeDelete','triggerAfterDelete'))){
            //return nothing
        }
        else {
            throw new \Exception('Method ' . $method . ' does not exist on ActiveEntity ' . get_class($this));
        }
    }
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    public static function __callStatic($method, $arguments) {
        return call_user_func_array(array(static::getEntityManager()->getRepository(get_called_class()), $method), $arguments);
    }

}
