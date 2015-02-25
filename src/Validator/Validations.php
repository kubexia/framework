<?php

namespace Kubexia\Validator;

class Validations{
    
    protected $items = array();
    
    public function __construct($items = array()){
        $this->items = $items;
    }
    
    public function required($item,$value,$options){
        return (!is_null($value) && strlen($value) > 0) ? TRUE : FALSE;
    }
    
    public function is_email($item,$value,$options){
        return (
            preg_match('!@.*@|\.\.|\,|\;!', $value) || 
            preg_match('!^.+\@(\[?)[a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$!', $value)
        ) ? TRUE : FALSE;
    }
    
    public function is_integer($item,$value,$options){
        return (preg_match('/^([0-9]+)$/',$value) ? TRUE : FALSE);
    }
    
    public function is_alpha($item,$value,$options){
        return (preg_match('/^([a-zA-Z]+)$/', $value) ? TRUE : FALSE);
    }
    
    public function is_alpha_numeric($item,$value,$options){
        return (preg_match('/^([a-zA-Z0-9]+)$/', $value) ? TRUE : FALSE);
    }
    
    public function is_name($item,$value,$options){
        return (preg_match('/^([a-zA-Z- ]+)$/', $value) ? TRUE : FALSE);
    }
    
    public function is_decimal($item,$value,$options){
        return (preg_match('/^([0-9]+)(.|,)([0-9]+)$/', $value) ? TRUE : FALSE);
    }
    
    public function is_in_array($item,$value,array $array){
        return (in_array($value, $array) ? TRUE : FALSE);
    }
    
    public function is_callable($name,$item,$value,$callback){
        return call_user_func_array($callback, array($item,$value,$this->validator));
    }
    
    public function is_min_length($item,$value,$length){
        return (strlen($value) >= $length ? TRUE : FALSE);
    }
    
    public function is_max_length($item,$value,$length){
        return (strlen($value) <= $length ? TRUE : FALSE);
    }
    
    public function is_equal_to_item($item,$value,$matchItem){
        return ($value === (isset($this->items[$matchItem]) ? $this->items[$matchItem] : '') ? TRUE : FALSE);
    }
    
    public function is_regex($item,$value,$options){
        $regex = (is_array($options) ? $options['expr'] : $options);
        return (preg_match($regex, $value) ? TRUE : FALSE);
    }
    
    public function is_url($item,$value,$options){
        $type = (!empty($options) ? $options : 'http(s)?');
        return (preg_match('!^'.$type.'://[\w-]+\.[\w-]+(\S+)?$!i', $value) ? TRUE : FALSE);
    }
    
    public function is_domain($item,$value,$options){
        return (preg_match('!^[\w-]+\.[\w-]+(\S+)?$!i', $value) ? TRUE : FALSE);
    }
    
    public function is_in_range($item,$value,$range){
        return (($range['min'] <= $value) && ($value <= $range['max']) ? TRUE : FALSE);
    }
    
    public function is_ip($item,$value,$options){
        return (filter_var($value, FILTER_VALIDATE_IP) ? TRUE : FALSE);
    }
}
