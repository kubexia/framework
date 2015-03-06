<?php
namespace Kubexia\Database\PDO;

class Statement extends \PDOStatement{
    
    protected $debug = TRUE;
    
    protected $connection;
    
    protected function __construct(\PDO $connection){
        $this->connection = $connection;
        
        if($this->debug){
            \Kubexia\Database\PDO\Debug::addQuery($this->queryString);
        }
        
    }
    
}