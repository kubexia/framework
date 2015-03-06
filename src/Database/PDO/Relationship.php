<?php
namespace Kubexia\Database\PDO;

class Relationship{
    
    protected $relation;
    
    protected $conn;
    
    protected $model;
    
    protected $result;
    
    public function __construct($relation, $conn, $model){
        $this->relation = $relation;
        
        $this->conn = $conn;
        
        $this->model = $model;
        
        $this->mapRelation();
    }
    
    public function mapRelation(){
        return call_user_func_array(array($this,'relation'.$this->relation['relation']), array());
    }
    
    protected function relationManyToOne(){
        $array = array(
            $this->relation['join']['referencedColumnName'] => call_user_func_array(array($this->model,'get'.ucfirst($this->relation['join']['name'])),array())
        );
        
        $this->result = call_user_func_array(array(new $this->relation['classname'],'findOneBy'), array($array));
    }
    
    protected function relationOneToMany(){
        $array = array(
            $this->relation['join']['name'] => call_user_func_array(array($this->model,'get'.ucfirst($this->relation['join']['referencedColumnName'])),array())
        );
        
        $this->result = call_user_func_array(array(new $this->relation['classname'],'findWhere'), array($array));
    }
    
    protected function relationOneToOne(){
        $array = array(
            $this->relation['join']['name'] => call_user_func_array(array($this->model,'get'.ucfirst($this->relation['join']['referencedColumnName'])),array())
        );
        
        $this->result = call_user_func_array(array(new $this->relation['classname'],'findWhere'), array($array));
    }
    
    public function getResult(){
        return $this->result;
    }
    
}