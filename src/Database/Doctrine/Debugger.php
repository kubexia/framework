<?php
namespace Kubexia\Database\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;

class Debugger implements SQLLogger{
    
    protected static $instance = NULL;
    
    protected $name = NULL;
    
    protected $startMemoryUsage = NULL;
    
    protected $startExecutionTime = NULL;
    
    protected static $queries = array();
    
    public static function getInstance(){
        if(!is_null(static::$instance)){
            return static::$instance;
        }
        
        static::$instance = new Debugger();
        
        return static::$instance;
    }
    
    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null){
        $this->name = md5($sql);
        
        $this->startExecutionTime = microtime();
        
        $this->startMemoryUsage = memory_get_usage();
        
    	static::$queries[$this->name] = array(
            'sql' => $this->mapParams($sql, $params)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery(){
        static::$queries[$this->name]['execution_time'] = $this->getExecutionTime();
        static::$queries[$this->name]['memory_usage'] = $this->getMemoryUsage();
    }
    
    protected function mapParams($sql,$params){
        if(!empty($params)){
            foreach ($params as $key=>$param) {
                $sql = join(var_export($param, true), explode('?', $sql, 2));
            }

        }
        return $sql;
    }
    
    protected function getMemoryUsage() {
        return convertBytes(memory_get_usage() - $this->startMemoryUsage);
    }

    protected function getExecutionTime() {
        $startarray = explode(" ", $this->startExecutionTime);
        $starttime = $startarray[1] + $startarray[0];

        $endarray = explode(" ", microtime());
        $endtime = $endarray[1] + $endarray[0];
        $totaltime = round($endtime - $starttime, 5);

        return $totaltime;
    }


    public function queries(){
        return static::$queries;
    }
    
    
}
