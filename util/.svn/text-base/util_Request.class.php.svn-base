<?php

/**
 * @purpose Handles various requests by the user and 
 * 	wraps them with handy trimming and decoding functions
 * @author Ray Cleveland
 * @copyright 2009
 */

class util_Request implements ArrayAccess
{

    private $array_name = '_REQUEST';
    private $values = array();
    private $default_value = null;

    /**
     * Constructs a new request object
     * @param Mixed $array_name The name of the array to use ex[_POST|_GET] or an array of values to store as the request
     */
    public function __construct($array_name = '_REQUEST')
    {
    	// if this is a string this is a request for use of a request type
        if(is_string($array_name)) {
        	$this->array_name = $array_name;
        	if(isset($GLOBALS[$this->array_name])) {
        		$this->values = $GLOBALS[$this->array_name];
        	}
        }
        elseif(is_array($array_name)) {
        	$this->array_name = '_REQUEST';
        	$this->values = $array_name;
        }
        else {
        	throw new Exception("Invalid Request Instantiation");
        }
        
    }

    /**
     * @param Index of array to get
     * @return String Value of variable or default
     */
    public function getVal($index, $default = null)
    {
        if(is_null($default)) {
        	$default = $this->default_value;
        }
        return isset($this->values[$index]) ? $this->values[$index] : $default;
    }

    /**
     * @param Index of array to get
     * @return String Value of variable or default
     */
    public function __get($index)
    {
        return $this->getVal($index, $this->default_value);
    }
    
    /**
     * util_Request::__isset()
     * 
     * @param mixed $index
     * @return
     */
    public function __isset($index)
    {
        return (bool) self::getValue($this->array_name, $index, FALSE);
    }

    /**
     * @param Index of array to get
     * @return String Value of variable or default
     */
    public function __set($index, $value)
    {
        return self::setValue($this->array_name, $index, $value);
    }
    
    /**
     * @return String Debug value of constructed array
     */
    public function __toString()
    {
    	return (!isset($GLOBALS[$this->array_name]))
			? '' : sprintf('<pre>%s</pre>', print_r($GLOBALS[$this->array_name], true));
    }

    /**
     * Sets the default value to return for accessed variables on a constructed object
     * @param Mixed $default_value The default value
     */
    public function setDefault($default_value)
    {
        $this->default_value = $default_value;
    }

    /**
     * @param String $array_name the name of the globals array Ex. _GET _POST
     * @param String $index The varible name to get
     * @param Mixed $default_value The value to return if the index is not set
     */
    private static function getValue($array_name, $index, $default_value = null)
    {
        if(isset($GLOBALS[$array_name][$index])){
        	if(!is_string($GLOBALS[$array_name][$index])) return $GLOBALS[$array_name][$index];
        	return stripslashes(trim(urldecode($GLOBALS[$array_name][$index])));
        }
        return $default_value;
    }

    /**
     * Sets a value to the variable array
     * @param String $array_name the name of the globals array Ex. _GET _POST
     * @param String $index The varible name to get
     * @param Mixed $value The value to set to the index
     */
    private static function setValue($array_name, $index, $value = null)
    {
        if ($array_name == '_COOKIE' && !isset($_COOKIE[$index])) {
            self::setCookie($index, $value);
        }
        $GLOBALS[$array_name][$index] = $value;
    }

    /**
     * @return Bool Whether or not the post variable is not empty
     */
    public static function isPosted()
    {
        return !empty($_POST);
    }

    /**
     * @return Mixed value from the _REQUEST Array
     * @param String $index The varible name to get
     * @param Mixed $default_value The value to return if the index is not set
     */
    public static function _REQUEST($index, $default_value = null)
    {
        $value = self::getValue('_REQUEST', $index, $default_value);
        return $value;
    }
    public static function request($index, $default_value = null){
    	return self::_REQUEST($index, $default_value);
    }

    /**
     * @return Mixed value from the _GET Array
     * @param String $index The varible name to get
     * @param Mixed $default_value The value to return if the index is not set
     */
    public static function _GET($index, $default_value = null)
    {
        $value = self::getValue('_GET', $index, $default_value);
        return $value;
    }
    public static function get($index, $default_value = null){
    	return self::_GET($index, $default_value);
    }

    /**
     * @return Mixed value from the _POST Array
     * @param String $index The varible name to get
     * @param Mixed $default_value The value to return if the index is not set
     */
    public static function _POST($index, $default_value = null)
    {
        $value = self::getValue('_POST', $index, $default_value);
        return $value;
    }
    public static function post($index, $default_value = null){
    	return self::_POST($index, $default_value);
    }

    /**
     * @return Mixed value from the _SESSION Array
     * @param String $index The varible name to get
     * @param Mixed $default_value The value to return if the index is not set
     */
    public static function _SESSION($index, $default_value = null)
    {
        $value = self::getValue('_SESSION', $index, $default_value);
        return $value;
    }
    public static function session($index, $default_value = null){
    	return self::_SESSION($index, $default_value);
    }

    /**
     * @return Mixed value from the _COOKIE Array
     * @param String $index The varible name to get
     * @param Mixed $default_value The value to return if the index is not set
     */
    public static function _COOKIE($index, $default_value = null)
    {
        $value = self::getValue('_COOKIE', $index, $default_value);
        return $value;
    }
    public static function cookie($index, $default_value = null){
    	return self::_COOKIE($index, $default_value);
    }
    
    /**
     * @return Mixed value from the _COOKIE Array
     * @param String $index The varible name to get
     * @param Mixed $default_value The value to return if the index is not set
     */
    public static function _SERVER($index, $default_value = null)
    {
        $value = self::getValue('_SERVER', $index, $default_value);
        return $value;
    }
    public static function server($index, $default_value = null){
    	return self::_SERVER($index, $default_value);
    }

    /**
     * Sets a cookie for the site
     * @param String $name the name of the cookie
     * @param Mixed  $vaue the value of the cookie
     * @param Int 	 $expire The time form now to expire [Default = 0 or expire when browser close]
     * @param String $path The paths cookie is available on server [Default = '/']
     * @param String $domain The domain for the site the cookie works on
     * @param Bool 	 $secure Default False
     */
    public static function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false)
    {
        if (headers_sent())
            return false;
        if (isset($_COOKIE[$name])) {
            self::unsetCookie($name);
        }
        $flag = setcookie($name, $value, $expire, $path, $domain, $secure);
        if ($flag) {
            $GLOBALS['_COOKIE'][$name] = $value;
        }
        return $flag;
    }


    /**
     * Removes a cookie with $name
     */
    public static function unsetCookie($name)
    {
        if (headers_sent()) throw new Exception("Attempt to unset $name Cookie when headers sent");
        $res = setcookie($name, '', time() - 3600);
        unset($_COOKIE[$name]);
        return $res;
    }

	///////////////////////////////
	// Array Access Methods
	///////////////////////////////
	
	
    public function offsetSet($offset, $value) {
        return $this->$offset = $value;
    }

    public function offsetExists($offset) {
        return isset($GLOBALS[$this->array_name][$offset]);
    }

    public function offsetUnset($offset) {
        unset($GLOBALS[$this->array_name][$offset]);
    }

    public function offsetGet($offset) {
        return $this->$offset;
    }
}

?>
