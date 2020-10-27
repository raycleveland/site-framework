<?php

/**
 * util_Link
 * 
 * @package   
 * @author Ray Cleveland
 * @version 2009
 * @access public
 */
class util_Link
{
	
	private static $inherit_params = array();
	protected static $ignore_params = array();
	private static $base_path = '/';
	private static $uris;
	protected $rewrite = false;
	protected $params = array();
	public $path = '';
	protected $rewrite_params = array();
    
	/**
	 * util_Link::__construct()
	 * 
	 * @param mixed $params
	 * @return void
	 */
	public function __construct($params = array(), $inherit = true)
	{
        // inherit the set parameters
        if($inherit){
            if(is_string($params)) $params = self::parseLinkString($params);
            if(!is_array($params)) {
            	throw new Exception("Invalid Parameters", 1);
            }
            $params = array_merge(self::$inherit_params, $params);  
		}
        
        $this->setParams($params);
        
		$this->path = self::$base_path;
	}
	
    /**
     * util_Link::getCurrent()
     * 
     * @param mixed $params
     * @return
     */
    public static function getCurrent($params = array())
    {
        // ignore any params from the current link
        $inherit = $_GET;
        foreach($inherit as $name => $val) {
            if(in_array($name, self::$ignore_params)) {
                unset($inherit[$name]);
            }
        }
        
        if(is_string($params)) $params = self::parseLinkString($params);
        $params = array_merge($inherit, $params);
        return new self($params);
    }
    
    /**
     * util_Link::pathParam()
     * 
     * Appends a parameter to the link path
     * 
     * @param mixed $param
     * @return
     */
    protected function pathParam($param)
    {
        if(!isset($this->params[$param])) return;
        $this->path .= urlencode($this->params[$param]) . "/";
        unset($this->params[$param]);
    }
    
    /**
     * util_Link::getClean()
     * 
     * @return util_Link A instance of itself with no inherited params
     */
    public static function getClean($params = array())
    {
        return new self($params, false);
    }
    
    /**
     * util_Link::getDomainLink()
     * 
     * Gets a link object containing the domain
     *  
     * @param mixed $params
     * @return void
     */
    public static function getDomainLink($params)
    {
        $link = new self($params);
        $path = strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') !== FALSE
            ? 'https://' : 'http://';
        $path .= $_SERVER['SERVER_NAME'];
        return $path . $link;
    }
    
	/**
 	 * util_Link::useRewriteRules()
 	 * Use this in extensions to use rewrite rules
 	 * 
 	 * @return void
 	 */
 	protected function useRewriteRules(){}
 	
	/**
	 * util_Link::__tostring()
	 * 
	 * @return
	 */
	public function __tostring()
	{
		return $this->getLink();
	}
    
    /**
     * util_Link::getLink()
     * 
     * @return String the link this object contains
     */
    public function getLink($params = array())
    {
        $this->setParams($params);
        if($this->rewrite) $this->useRewriteRules();
        $param_str = http_build_query($this->getParams(), null, '&amp;');
		if(!empty($param_str)) $param_str = '?' . $param_str;
		$retval = $this->path . $param_str;
        //reset path
        $this->path = self::$base_path;
		return $retval;
    }
    
    /**
     * util_Link::setParams()
     * 
     * @param mixed $params
     * @return void
     */
    public function setParams($params = array())
    {
        if(is_string($params)) $params = self::parseLinkString($params);

        if(empty($params)) $params = array();

		$this->params = array_merge($this->params, $params);
    }
    
    /**
     * util_Link::clearParams()
     * 
     * Clears all inherited params in the link object
     * 
     * @return void
     */
    public function clearParams()
    {
        $this->params = array();
    }
    
    /**
     * util_Link::getParams()
     * 
     * @return Array  Parameters filtered after rewrites are processed
     */
    protected function getParams()
    {
        $params = array();
        foreach($this->params as $name => $value)
        {
            if(in_array($name, $this->rewrite_params)) continue;
            $params[$name] = $value;
        }
        return $params;
    }
	
	/**
	 * util_Link::__get()
	 * 
	 * @param mixed $param_name
	 * @return String Value of parameter or Null
	 */
	public function __get($param_name)
	{
		return (isset($this->params[$param_name]))
			? $this->params[$param_name]
			: null;
	}
	
	/**
	 * util_Link::__set()
	 * 
	 * @param string $param_name
	 * @param string $param_value
	 * @return void
	 */
	public function __set($param_name, $param_value)
	{
		$this->params[$param_name] = $param_value;
	}
	 
	
	/**
	 * util_Link::setInheritNames()
	 * 
	 * @param mixed $param_names
	 * @return
	 */
	public static function setInheritNames($param_names = array())
	{
		if(is_string($param_names)) $param_names = array($param_names);
        foreach($param_names as $name){
			self::addInheritName($name);
		}
	}
	
	/**
	 * util_Link::addInheritName()
	 * 
	 * @param mixed $name
	 * @return
	 */
	public static function addInheritName($name)
	{
		if(isset($_GET[$name])){
			self::$inherit_params[$name] = $_GET[$name];
		}	
	}
	
	/**
	 * util_Link::addInheritParams()
	 * 
	 * @param mixed $fields_values
	 * @return
	 */
	public static function addInheritParams(array $fields_values)
	{
		foreach($fields_values as $name => $value)
			self::$inherit_params[$name] = $value;	
	}
	
	/**
	 * util_Link::ignoreParams()
	 * 
	 * @param mixed $params a title of a parameter or array of multiple parameter names to ignore
	 * @return void
	 */
	public static function ignoreParams($params)
	{
        if(!is_array($params)) {
            $params = array($params);
        }
        foreach($params as $name) {
            if(!is_string($name)) continue;
            self::$ignore_params[] = $name;
        }
	}
	
	
	/**
	 * util_Link::folder()
	 * 
	 * Generates a folder link for the string given
	 * 
	 * @param mixed $string The folder to link to
	 * @return void
	 */
	public static function folder($string, $query = null)
	{
		$folder = dirname($_SERVER['SCRIPT_NAME']) . '/' . $string . '/';
		$folder = str_replace('//', '/', $folder);
		
		if(!empty($query))
		{
			if(is_string($query)) $query = self::parseLinkString($params);
			$query_str = http_build_query($query, null, '&amp;');
			$folder .= '?' . $query_str;
		}
		
		return $folder;
	}
	
	/**
	 * util_Link::setInheritParams()
	 * 
	 * @param mixed $fields_values
	 * @return
	 */
	public static function setInheritParams(array $fields_values)
	{
		self::$inherit_params = array();
		if(is_string($params)) $params = self::parseLinkString();
		foreach($fields_values as $name => $value)
			self::$inherit_params[$name] = $value;	
	}
	
	/**
	 * util_Link::addInheritParam()
	 * 
	 * @param mixed $name
	 * @param mixed $value
	 * @return
	 */
	public static function addInheritParam($name, $value)
	{
		self::$inherit_params[$name] = $value;
	}
	
	/**
	 * util_Link::resetInheritParams()
	 * 
	 * @return
	 */
	public static function resetInheritParams()
	{
		self::$inherit_params = array();
	}
	
	/**
	 * util_Link::parseLinkString()
	 * 
	 * @param mixed $string
	 * @return
	 */
	public static function parseLinkString($string)
	{
		if(is_array($string)) return $string;
		$delimeters = array('&amp;', '&');
		foreach($delimeters as $delimeter)
		{
			$var_vals = explode($delimeter, $string);
			if(count($var_vals) > 1) break;
		}
		$params = array();
		foreach($var_vals as $var_val)
		{
			$pieces = explode('=', $var_val);
			if(count($pieces) < 2) break;
			$params[$pieces[0]] = $pieces[1];
		}
		return $params;
	}
	
	/**
	 * util_Link::setBasePath()
	 * 
	 * @param mixed $path
	 * @return void
	 */
	public static function setBasePath($path)
	{
		self::$base_path = $path;
	}
    
	/**
	 * util_Link::getActionUri()
	 * 
	 * @param String Action
	 * @return String the action base uri
	 */
	public static function getActionUri($action)
	{
		if(!isset(self::$uris[$action])) {
			$table = Control::getTable('actions');
			$data = $table->getRow($action, 'action_name');
			self::$uris[$action] = $data->uri;
		}
		return self::$uris[$action];
	}
	
	/**
	 * util_Link::setActionPathParams()
	 * 
	 * @param String Action
	 * @return String the action base uri
	 */
	public function setActionPathParams($action)
	{
		$table = Control::getTable('actions');
		$data = $table->getRow($action, 'action_name');
		if(!empty($data->rewrite_params)) {
			$params = explode(',', $data->rewrite_params);
			foreach($params as $param) {
				$this->pathParam(trim($param));
			}
		}
	}
	
}
