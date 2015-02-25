<?php
namespace Kubexia\Database\Doctrine;

class Handler {
    
    protected $entityManager = NULL;
    
    protected static $instance = array();
    
    public function __construct($name=NULL){
        $server = (in_array($_SERVER['REMOTE_ADDR'],array('127.0.0.1','::1')) ? 'test' : 'production');
        if(!is_null($name)){
            $server = $name;
        }
        
        $db = \Kubexia\Config::getInstance('database')->get($server);

        $config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(array(
            APP.'/model'
        ), false);
        
        $this->entityManager = \Doctrine\ORM\EntityManager::create(array(
            'driver'   => $db['driver'],
            'user'     => $db['user'],
            'password' => $db['password'],
            'dbname'   => $db['name'],
            'charset' => 'utf8',
            'driverOptions' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            )
        ), $config);
    }
    
    public static function getInstance($name=NULL){
        if(isset(static::$instance[$name])){
            return static::$instance[$name];
        }
        
        static::$instance[$name] = new Handler($name);
        
        return static::$instance[$name];
    }
    
    public function getConnection(){
        return $this->entityManager;
    }
    
}