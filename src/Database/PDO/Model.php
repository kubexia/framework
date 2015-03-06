<?php
namespace Kubexia\Database\PDO;

class Model{
    
    protected static $entity = array();
    
    protected static $repository = array();
    
    protected static $conn = NULL;
    
    protected static $data = array();
    
    protected static $fieldAssociation = array();
    
    public function __construct(){
        
    }
    
    /**
     * 
     * @return \Kubexia\Database\PDO\Connection
     */
    public function conn(){
        if(!is_null(static::$conn)){
            return static::$conn;
        }
        
        static::$conn = \Kubexia\Database\PDO\Connection::getInstance();
        
        return static::$conn;
    }
    
    /**
     * 
     * @return \Kubexia\Database\PDO\Model
     */
    public static function getInstance(){
        $className = get_called_class();
        if(isset(static::$entity[$className])){
            return static::$entity[$className];
        }
        
        static::$entity[$className] = new $className();
        
        return static::$entity[$className];
    }
    
    protected function model(){
        return $this;
    }
    
    protected function getTable(){
        return '`'.$this->model()->table().'`';
    }
    
    protected function getPrimaryKey(){
        return '`id`';
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
    
    public static function insert(){
        return call_user_func_array(array(static::getInstance(),'_insert'), func_get_args());
    }
    
    protected function _insert(array $array){
        $columns = $this->conn()->getColumns($this->getTable(),array('id'));
        
        foreach($array as $field => $value){
            $this->{$field} = $value;
        }
        
        
        $this->trigger('BeforeInsert');
        
        $bindParams = array();
        $bindValues = array();
        $i=0;
        foreach($this as $field => $value){
            if($this->isRelationship($field) || $field === 'id' || !in_array($field,$columns)){
                continue;
            }
            $param = ':'.$field.$i;
            
            $bindParams['`'.$field.'`'] = $param;
            $bindValues[$param] = $value;
            
            $i++;
        }
        
        try {
            
            $sth = $this->conn()->prepare("INSERT INTO ".$this->getTable()." (".join(',', $columns).") VALUES (".join(',', array_values($bindParams)).")");
            $sth->execute($bindValues);
            $this->id = $this->conn()->lastInsertId();
        } 
        catch (\Exception $e) {
            throw new \Exception('Insert failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $this->trigger('AfterInsert');
        
        return $this;
    }
    
    public function insertMultiple(array $array){
        $columns = $this->conn()->getColumns($this->getTable(),array('id'));
        
        $className = get_called_class();
        $objects = array();
        $objectsValues = array();
        $bindValues = array();
        $j=0;
        foreach($array as $key => $batch){
            $obj = new $className();
            
            foreach($batch as $f => $v){
                $obj->{$f} = $v;
            }
            
            $obj->trigger('BeforeInsert');
            
            $bindParams = array();
            $i=0;
            foreach($obj as $field => $value){
                if($obj->isRelationship($field) || $field === 'id' || !in_array($field,$columns)){
                    continue;
                }
                
                $param = ':'.$field.'_'.$i.'_'.$j;

                $bindParams['`'.$field.'`'] = $param;
                $bindValues[$param] = $value;
                
                $i++;
            }
            
            $objects[] = $obj;
            $objectsValues[] = "(".join(',', array_values($bindParams)).")";
            
            $j++;
        }
        
        try {
            $sth = $this->conn()->prepare(trimWhiteSpaces("INSERT INTO ".$this->getTable()." (".join(',',$columns).") VALUES ".join(',',$objectsValues)));
            $sth->execute($bindValues);
        } 
        catch (\Exception $e) {
            throw new \Exception('insertMultiple failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        foreach($objects as $obj){
            $obj->trigger('AfterInsert');
        }
        
        return ($sth->rowCount() > 0 ? TRUE : FALSE);
    }
    
    public function update(array $array){
        $this->trigger('BeforeUpdate');
        
        $bindParams = array();
        $bindValues = array(':primaryKey' => $this->getId());
        $i=0;
        foreach($array as $field => $value){
            $this->{$field} = $value;
            $param = ':'.$field.$i;
            
            $bindParams[] = '`'.$field.'` = '.$param;
            $bindValues[$param] = $value;
            
            $i++;
        }
        
        try {
            $sth = $this->conn()->prepare("UPDATE ".$this->getTable()." SET ".join(',',$bindParams)." WHERE ".$this->getPrimaryKey()." = :primaryKey ");
            $sth->execute($bindValues);
        } 
        catch (\Exception $e) {
            throw new \Exception('Update failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $this->trigger('AfterUpdate');
        
        return $this;
    }
    
    public function updateWhere(array $array, array $criteria){
        $className = get_called_class();
        $objects = array();
        $bindParams = array();
        $bindValues = array();
        $i=0;
        foreach($array as $field => $value){
            $obj = new $className;
            $obj->{$field} = $value;
            $obj->trigger('BeforeUpdate');
            
            $objects[] = $obj;
            
            $param = ':'.$field.$i;
            
            $bindParams[] = '`'.$field.'` = '.$param;
            $bindValues[$param] = $value;
            
            $i++;
        }
        
        $bindWhereParams = array();
        $i=0;
        foreach($criteria as $field => $value){
            $param = ':'.$field.$i;

            if(is_array($value)){
                $j=0;
                $in = array();
                foreach($value as $k => $v){
                    $inParam = ':in_'.$field.'_'.$j;
                    $in[] = $inParam;
                    $bindValues[$inParam] = $v;
                    $j++;
                }
                $bindWhereParams[] = '`'.$field.'` IN ('.join(',',$in).')';
            }
            else{
                $bindWhereParams[] = '`'.$field.'` = '.$param;
                $bindValues[$param] = $value;
            }
            $i++;
        }
        
        try {
            $sth = $this->conn()->prepare("UPDATE ".$this->getTable()." SET ".join(',',$bindParams)." WHERE ".join(',',$bindWhereParams));
            $sth->execute($bindValues);
        } 
        catch (\Exception $e) {
            throw new \Exception('updateWhere failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        foreach($objects as $obj){
            $obj->trigger('AfterUpdate');
        }
        
        return ($sth->rowCount() > 0 ? TRUE : FALSE);
    }
    
    public function save(){
        $this->trigger('BeforeUpdate');
        
        $bindParams = array();
        $bindValues = array(':primaryKey' => $this->getId());
        $i=0;
        foreach($this as $field => $value){
            if($this->isRelationship($field) || $field === 'id'){
                continue;
            }
            $param = ':'.$field.$i;
            
            $bindParams[] = '`'.$field.'` = '.$param;
            $bindValues[$param] = $value;
            
            $i++;
        }
        
        try {
            $sth = $this->conn()->prepare("UPDATE ".$this->getTable()." SET ".join(',',$bindParams)." WHERE ".$this->getPrimaryKey()." = :primaryKey");
            $sth->execute($bindValues);
        } 
        catch (\Exception $e) {
            throw new \Exception('Save failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $this->trigger('AfterUpdate');
        
        return $this;
    }
    
    public function delete(){
        $this->trigger('BeforeDelete');
        
        try {
            $sth = $this->conn()->prepare("DELETE ".$this->getTable()." WHERE ".$this->getPrimaryKey()." = :primaryKey");
            $sth->execute(array(
                ':primaryKey' => $this->getId()
            ));
        } 
        catch (\Exception $e) {
            throw new \Exception('Delete failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $this->trigger('AfterDelete');
        
        return $this;
    }
    
    public function deleteWhere(array $criteria,array $bind = array(),$limit=0){ 
        $this->trigger('BeforeMultipleDelete',$criteria);
        
        $bindValues = array();
        $bindWhereParams = array();
        $i=0;
        foreach($criteria as $field => $value){
            $param = ':'.$field.$i;

            if(is_array($value)){
                $j=0;
                $in = array();
                foreach($value as $k => $v){
                    $inParam = ':in_'.$field.'_'.$j;
                    $in[] = $inParam;
                    $bindValues[$inParam] = $v;
                    $j++;
                }
                $bindWhereParams[] = '`'.$field.'` IN ('.join(',',$in).')';
            }
            else if(is_integer($field) && is_string($value)){
                $bindWhereParams[] = $value;
            }
            else{
                $bindWhereParams[] = '`'.$field.'` = '.$param;
                $bindValues[$param] = $value;
            }
            $i++;
        }
        
        try {
            $sth = $this->conn()->prepare("DELETE FROM ".$this->getTable()." WHERE ".join(',',$bindWhereParams).($limit > 0 ? ' LIMIT '.$limit : ''));
            $sth->execute(array_merge($bindValues,$bind));
        } 
        catch (\Exception $e) {
            throw new \Exception('deleteWhere failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $this->trigger('AfterMultipleDelete',$criteria);
        
        return ($sth->rowCount() > 0 ? TRUE : FALSE);
    }
    
    public function formatDate($field,$format = 'Y-m-d H:i:s'){
        if($this->$field === NULL){
            return NULL;
        }
        
        $date = new \DateTime($this->$field);
        return $date->format($format);
    }
    
    protected function trigger($method,array $args=array()){
        if(is_callable(array($this,'trigger'.$method))){
            return call_user_func(array($this,'trigger'.$method),$args);
        }
        
        return FALSE;
    }
    
    public function __call($method, $arguments) {
        $func = substr($method, 0, 3);
        $fieldName = substr($method, 3, strlen($method));
        $fieldName = lcfirst($fieldName);
        
        if(!isset($this->{$fieldName})){
            $fieldName = $this->fieldAssociation($fieldName);
        }
        
        if ($func === 'get') {
            $relations = $this->relationships();
            if(isset($relations[$fieldName])){
                if(isset($relations[$fieldName]['load']) && $relations[$fieldName]['load'] === 'EAGER'){
                    //load eager
                }
                else{
                    $relation = new \Kubexia\Database\PDO\Relationship($relations[$fieldName], $this->conn(), $this);
                    return $this->{$fieldName} = $relation->getResult();
                }
            }
            
            return $this->{$fieldName};
        } 
        else if ($func === 'set') {
            $this->{$fieldName} = $arguments[0];
        } 
        else if ($func === 'has') {
            return $this->__isset($fieldName);
        } 
        else {
            return FALSE;
        }
    }
    
    protected function isRelationship($name){
        return (isset($this->relationships()[$name]) ? TRUE : FALSE);
    }


    public function repository($className=NULL, array $args = array()){
        $array = explode('\\', get_called_class());
        $name = array_pop($array);
        if(is_null($className)){
            $className = $name;
        }
        
        $className = '\\'.join('\\',$array).'\\Repository\\'.$className;
        
        if(isset(static::$entity[$className])){
            return static::$entity[$className];
        }
        
        if(count($args) === 0){
            static::$entity[$className] = new $className($this);
        }
        else{
            $reflection = new \ReflectionClass($className);
            static::$entity[$className] = $reflection->newInstanceArgs(array_merge($this,$args));
        }
        
        return static::$entity[$className];
    }
    
    protected function updateModel($result){
        if(empty($result)){
            return FALSE;
        }
        foreach($result as $field => $value){
            call_user_func_array(array($this,'set'.ucfirst($field)),array($value));
        }
    }
    
    //QUERIES
    public function find($id,array $eager=NULL){
        try{
            $sth = $this->conn()->prepare(trimWhiteSpaces("SELECT * FROM ".$this->getTable()." WHERE ".$this->getPrimaryKey()." = :primaryKey LIMIT 1"));
            $sth->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
            $sth->execute(array(
                ':primaryKey' => $id
            ));
        } catch (\Exception $e) {
            throw new \Exception('find failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $result = $sth->fetch();
        if($result){
            $this->updateModel($result);

            if(is_null($eager)){
                return $result;
            }
            
            $eagerLoading = new \Kubexia\Database\PDO\Eager($this->conn(),$this->relationships(),$eager,array($result),$this);
            return $eagerLoading->load()[0];
        }
        
        return FALSE;
    }
    
    /**
     * 
     * @param array $criteria
     * @param type $select
     * @return model
     * @throws \Exception
     */
    public function findOneBy(array $criteria,array $eager=NULL, array $bind = array()){
        $bindParams = array();
        $bindValues = array();
        $i=0;
        foreach($criteria as $field => $value){
            $param = ':'.$field.$i;
            
            if(is_array($value)){
                $j=0;
                $in = array();
                foreach($value as $k => $v){
                    $inParam = ':in_'.$field.'_'.$j;
                    $in[] = $inParam;
                    $bindValues[$inParam] = $v;
                    $j++;
                }
                $bindParams[] = '`'.$field.'` IN ('.join(',',$in).')';
            }
            else if(is_integer($field) && is_string($value)){
                $bindParams[] = $value;
            }
            else{
                $bindParams[] = '`'.$field.'` = '.$param;
                $bindValues[$param] = $value;
            }
            $i++;
        }
        
        try{
            $sth = $this->conn()->prepare(trimWhiteSpaces("SELECT * FROM ".$this->getTable()." WHERE ".join(' AND ',$bindParams)." LIMIT 1"));
            $sth->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
            $sth->execute(array_merge($bindValues,$bind));
        } catch (\Exception $e) {
            throw new \Exception('findOneBy failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $result = $sth->fetch();
        if($result){
            $this->updateModel($result);

            if(is_null($eager)){
                return $result;
            }

            $eagerLoading = new \Kubexia\Database\PDO\Eager($this->conn(),$this->relationships(),$eager,array($result),$this);
            return $eagerLoading->load()[0];
        }
        
        return FALSE;
    }
    
    public function findAll(array $eager = NULL,array $criteria = array(), array $orderBy = array(), array $groupBy = array(), $offset = NULL, $limit = NULL){
        $bindParams = array();
        $bindValues = array();
        $i=0;
        foreach($criteria as $field => $value){
            $param = ':'.$field.$i;
            
            if(is_array($value)){
                $j=0;
                $in = array();
                foreach($value as $k => $v){
                    $inParam = ':in_'.$field.'_'.$j;
                    $in[] = $inParam;
                    $bindValues[$inParam] = $v;
                    $j++;
                }
                $bindParams[] = '`'.$field.'` IN ('.join(',',$in).')';
            }
            else if(is_integer($field) && is_string($value)){
                $bindParams[] = $value;
            }
            else{
                $bindParams[] = '`'.$field.'` = '.$param;
                $bindValues[$param] = $value;
            }
            $i++;
        }
        
        $orderByItems = array();
        foreach($orderBy as $key => $value){
            $orderByParams[] = '`'.$key.'` = '.$value;
        }
        
        $groupByItems = array();
        foreach($groupBy as $key => $value){
            $groupByItems[] = '`'.$key.'` = '.$value;
        }
        
        $limitQuery = NULL;
        if(!is_null($offset) && !is_null($limit)){
            $limitQuery = "LIMIT $offset,$limit";
        }
        else if(is_null($offset) && !is_null($limit)){
            $limitQuery = "LIMIT $limit";
        }
        else{
            $limitQuery = '';
        }
        
        try{
            $sth = $this->conn()->prepare(trimWhiteSpaces("
                SELECT * 
                FROM ".$this->getTable()."
                ".(!empty($criteria) ? ' WHERE '.join(',', $bindParams) : '')."
                ".(!empty($groupBy) ? ' GROUP BY '.join(',', $groupByItems) : '')."
                ".(!empty($orderBy) ? ' ORDER BY '.join(',', $orderByItems) : '')."
                ".$limitQuery."
            "));
            $sth->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
            $sth->execute($bindValues);
        } catch (\Exception $e) {
            throw new \Exception('findAll failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $result = $sth->fetchAll();
        
        if($result){
            
            if(is_null($eager)){
                return $result;
            }
            
            $eagerLoading = new \Kubexia\Database\PDO\Eager($this->conn(),$this->relationships(),$eager,$result,$this);
            return $eagerLoading->load();
        }
        
        return array();
    }
    
    public function findWhere(array $criteria, array $eager = NULL, array $bind = array()){
        $bindParams = array();
        $bindValues = array();
        $i=0;
        foreach($criteria as $field => $value){
            $param = ':'.$field.$i;
            
            if(is_array($value)){
                $j=0;
                $in = array();
                foreach($value as $k => $v){
                    $inParam = ':in_'.$field.'_'.$j;
                    $in[] = $inParam;
                    $bindValues[$inParam] = $v;
                    $j++;
                }
                $bindParams[] = '`'.$field.'` IN ('.join(',',$in).')';
            }
            else if(is_integer($field) && is_string($value)){
                $bindParams[] = $value;
            }
            else{
                $bindParams[] = '`'.$field.'` = '.$param;
                $bindValues[$param] = $value;
            }
            $i++;
        }
        
        try{
            $sth = $this->conn()->prepare(trimWhiteSpaces("SELECT * FROM ".$this->getTable()." WHERE ".join(' AND ',$bindParams)));
            $sth->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
            $sth->execute(array_merge($bindValues,$bind));
        } catch (\Exception $e) {
            throw new \Exception('findWhere failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $result = $sth->fetchAll();
        
        if($result){
            
            if(is_null($eager)){
                return $result;
            }
            
            $eagerLoading = new \Kubexia\Database\PDO\Eager($this->conn(),$this->relationships(),$eager,$result,$this);
            return $eagerLoading->load();
        }
        
        return FALSE;
    }
    
    public function queryAll($sql,array $bind=array(),array $eager = NULL){
        try{
            $sth = $this->conn()->prepare(trimWhiteSpaces($this->mapTables($sql)));
            $sth->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
            $sth->execute($bind);
        } catch (\Exception $e) {
            throw new \Exception('queryAll failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $result = $sth->fetchAll();
        
        if($result){
            
            if(is_null($eager)){
                return $result;
            }
            
            $eagerLoading = new \Kubexia\Database\PDO\Eager($this->conn(),$this->relationships(),$eager,$result,$this);
            return $eagerLoading->load();
        }
        
        return FALSE;
    }
    
    public function queryOne($sql,array $bind=array(),array $eager = NULL){
        try{
            $sth = $this->conn()->prepare(trimWhiteSpaces($this->mapTables($sql)));
            $sth->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
            $sth->execute($bind);
        } catch (\Exception $e) {
            throw new \Exception('queryOne failed on: ' . get_class($this)." | ".$e->getMessage());
        }
        
        $result = $sth->fetch();
        if($result){
            $this->updateModel($result);

            if(is_null($eager)){
                return $result;
            }

            $eagerLoading = new \Kubexia\Database\PDO\Eager($this->conn(),$this->relationships(),$eager,array($result),$this);
            return $eagerLoading->load()[0];
        }
        
        return FALSE;
    }
    
    protected function mapTables($sql){
        $sql = preg_replace("#:table#", $this->getTable(), $sql);
        
        return preg_replace_callback("#\\\\(.*)#", function($match){
            $array = explode(' ', $match[1]);
            
            $array[0] = '\\'.$array[0];
            $table = call_user_func(array(new $array[0],'getTable'));
            $array[0] = '`'.$table.'`';
            
            return join(' ',$array);
        }, $sql);
    }
    
    protected function fieldAssociation($fieldName){
        if(empty(static::$fieldAssociation)){
            $reflection = new \ReflectionClass(get_called_class());
            foreach($reflection->getProperties() as $prop){
                $camelCase = str_replace(' ','',ucwords(str_replace('_',' ',$prop->name)));
                static::$fieldAssociation[$camelCase] = $prop->name;
            }
        }
        $fieldNameCamel = str_replace(' ','',ucwords(str_replace('_',' ',$fieldName)));
        
        $field = (isset(static::$fieldAssociation[$fieldNameCamel]) ? static::$fieldAssociation[$fieldNameCamel] : NULL);
        if(is_null($field)){
            return FALSE;
        }
        
        return $field;
    }
    
}
