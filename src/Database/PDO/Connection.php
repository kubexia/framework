<?php
namespace Kubexia\Database\PDO;

class Connection extends \PDO{
    
    static protected $instance = array();
    
    public function __construct($dbname=NULL) {
        $configs = \Kubexia\Config::getInstance('configs');
        $db = \Kubexia\Config::getInstance('database')->get($configs->get('environment'));
        
        $dsn = "mysql:host=" . $db['host'] . ";dbname=" . $db['name'];
        $options = array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        );
        
        parent::__construct($dsn, $db['user'], $db['password'], $options);
        
//        $emulate_prepares_below_version = '5.1.17';
//        $serverversion = $this->getAttribute(\PDO::ATTR_SERVER_VERSION);
//        $emulate_prepares = (version_compare($serverversion, $emulate_prepares_below_version, '<'));
//        $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, $emulate_prepares);
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\Kubexia\Database\PDO\Statement', array($this)));
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * 
     * @param type $dbname
     * @return \PDO
     */
    static public function getInstance($dbname=NULL){
        if(isset(static::$instance[$dbname])){
            return static::$instance[$dbname];
        }
        
        static::$instance[$dbname] = new Connection($dbname);
        return static::$instance[$dbname];
    }
    
    public function getColumns($table,array $exclude=array()){
        try {
            $sth = $this->prepare("SHOW COLUMNS FROM $table");
            $sth->setFetchMode(\PDO::FETCH_OBJ);
            $sth->execute();
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
        
        $cols = array();
        while($col = $sth->fetch()){
            if(in_array($col->Field,$exclude)){
                continue;
            }
            $cols[] = $col->Field;
        }
        
        return $cols;
    }
}