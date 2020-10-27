<?php

/**
 * Overloading Object for database
 * @author Ray Cleveland
 * @copyright 2009
 */

$mdb2_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MDB2-2.4.1';
set_include_path(get_include_path() . PATH_SEPARATOR . $mdb2_path);
class_exists('MDB2', false) || include 'MDB2.php';

// fix for memory leaks 
// @see http://pear.php.net/bugs/bug.php?id=11790#1222420206
$PEAR_Error_skiptrace = &PEAR::getStaticProperty('PEAR_Error', 'skiptrace'); 
$PEAR_Error_skiptrace = true; 

class model_DB {
	
	private $db;
	
	public function __construct()
	{
		
		// get the dsn from config
		$dsn = Control::getVar('dsn', null, false);
		if(empty($dsn))
			Throw new Exception('Please set a database DSN in your config');
            
        $options = array(
            'result_buffering' => false,
            'persistent' => true,
        );
		
		$this->db = MDB2::connect($dsn, $options);
		
		$this->handleError($this->db);
		
		$this->handleError($this->db->loadModule('Extended'));
		$this->handleError($this->db->loadModule('Reverse'));
		$this->handleError($this->db->loadModule('Manager'));
		$this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	}
	
    public function __destruct()
    {
        $this->db->disconnect();
    }
    
	/**
	 * calls on a db class function
	 */ 
    public function __call($name, $arguments) {
        // Note: value of $name is case sensitive.
        
        $callback = array($this->db, $name);
        
        if(!is_callable($callback, $arguments))
        	Throw new Exception("Method {$name} is not callable");
		
		$res = call_user_func_array($callback, $arguments);
        
        $this->handleError($res);
        
        return $res;
    }
    
    /**
     * 
     */
    private function handleError($error_object)
    {
    	if(MDB2::isError($error_object)){
			throw new Exception('DB_ERROR: '. $error_object->getUserInfo());
    	}
        	
    }

    /**  As of PHP 5.3.0  */
    //public static function __callStatic($name, $arguments) {
//        // Note: value of $name is case sensitive.
//        echo "Calling static method '$name' "
//             . implode(', ', $arguments). "\n";
//    }

}