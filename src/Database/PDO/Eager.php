<?php
namespace Kubexia\Database\PDO;

class Eager{
    
    protected $relations;
    
    protected $eager;
    
    protected $results;
    
    protected $loaded = array();
    
    protected $model;
    
    public function __construct($conn, array $relations,array $eager,array $results, $model){
        $this->conn = $conn;
        
        $this->eager = $eager;
        
        $this->results = $results;
        
        $this->relations = $relations;
        
        $this->model = $model;
        
        $this->mapRelations();
    }
    
    public function mapRelations(){
        foreach($this->eager as $item){
            $relation = $this->relations[$item];
            if(!isset($relation['load'])){
                continue;
            }
            
            if($relation['load'] !== 'EAGER'){
                continue;
            }
            
            call_user_func_array(array($this,'relation'.$relation['relation']),array($relation,$item));
        }
    }
    
    protected function relationManyToOne($relation,$name){
        $ids = array();
        foreach($this->results as $item){
            $ids[] = $item->{'get'.ucfirst($relation['join']['name'])}();
        }
        
        $model = new $relation['classname']();
        
        $result = $model->findWhere(array($relation['join']['referencedColumnName'] => $ids));
        
        $hasRelations = array();
        foreach($result as $item){
            $hasRelations[$name][$item->{'get'.ucfirst($relation['join']['referencedColumnName'])}()] = $item;
        }
        
        foreach($this->results as $item){
            $values = $hasRelations[$name][$item->{'get'.ucfirst($relation['join']['name'])}()];
            call_user_func_array(array($item,'set'.ucfirst($name)), array($values));
        }
    }
    
    protected function relationOneToMany($relation,$name){
        $ids = array();
        foreach($this->results as $item){
            $ids[] = $item->{'get'.ucfirst($relation['join']['referencedColumnName'])}();
        }
        
        $model = new $relation['classname']();
        
        $result = $model->findWhere(array($relation['join']['name'] => $ids));
        
        $hasRelations = array();
        foreach($result as $item){
            $hasRelations[$name][$item->{'get'.ucfirst($relation['join']['name'])}()][] = $item;
        }
        
        foreach($this->results as $item){
            $values = (isset($hasRelations[$name][$item->{'get'.ucfirst($relation['join']['referencedColumnName'])}()]) ? $hasRelations[$name][$item->{'get'.ucfirst($relation['join']['referencedColumnName'])}()] : array());
            call_user_func_array(array($item,'set'.ucfirst($name)), array($values));
        }
    }
    
    protected function relationOneToOne($relation,$name){
        $ids = array();
        foreach($this->results as $item){
            $ids[] = $item->{'get'.ucfirst($relation['join']['referencedColumnName'])}();
        }
        
        $model = new $relation['classname']();
        
        $result = $model->findWhere(array($relation['join']['name'] => $ids));
        
        $hasRelations = array();
        foreach($result as $item){
            $hasRelations[$name][$item->{'get'.ucfirst($relation['join']['name'])}()] = $item;
        }
        
        foreach($this->results as $item){
            $values = $hasRelations[$name][$item->{'get'.ucfirst($relation['join']['referencedColumnName'])}()];
            call_user_func_array(array($item,'set'.ucfirst($name)), array($values));
        }
    }
    
    public function load(){
        return $this->results;
    }
    
    
}