<?php
namespace Kubexia\Database\PDO;

class Debug{
    
    public static $queries = array();
    
    public function __construct(){
        
    }
    
    public static function addQuery($sql){
        static::$queries[] = $sql;
    }
    
    public static function getQueries(){
        return static::$queries;
    }
    
}