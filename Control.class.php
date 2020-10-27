<?php

/**
 * Controller for the site archetecture
 * @author Ray Cleveland
 */

Class Control
{
	
	///////////////////////
	// Class Variables
	///////////////////////
	
	/**
	 * @var array $registry
	 * global registry for the site
	 * accessed by setvar and get var methods
	 */
	private static $registry = array();
	
    /**
	 * @var Smarty $smarty
	 * global Smarty instance if use_smarty is set
	 * accessed by setvar and get var methods
	 */
	private static $smarty;
	
	/**
	 * @var DB $db
	 * Data Base Object
	 */
	private static $dbs = array();
	
	/**
	 * @var Array $inherit_parameters
	 * Array of parameter names to inherit in build links
	 */
	private static $inherit_parameters = array('action');
	
	///////////////////////
	// Data Methods
	///////////////////////
	
	/**
	 * retrieves a database object
	 */
	public static function getDB($new = false)
	{
		$dsn = self::getVar('dsn');
		if(empty($dsn) || !isset($dsn['database'])) {
			throw new Exception('invalid dsn for getting database');
		}
		
		if(!isset(self::$dbs[$dsn['database']]) || $new){
		
			// test for proper DSN
			if(!is_array($dsn) || !isset($dsn['phptype']) || !isset($dsn['hostspec']) 
				|| !isset($dsn['database']) || !isset($dsn['username']) || !array_key_exists('password', $dsn))					 { throw new Exception('Please Set your DSN properly with Control::setVar("dsn", array())');	}
			
			$dsn_str = "{$dsn['phptype']}:dbname={$dsn['database']};host={$dsn['hostspec']}";
			
			try {
				self::$dbs[$dsn['database']] = new PDO($dsn_str, (string) $dsn['username'], (string) $dsn['password']);
			} catch (PDOException $e) {
				// get rid of the connection info from exceptions
				throw new PDOException('Error connecting to Database');
			}
		}
		return self::$dbs[$dsn['database']];
	}
    
    /**
     * Control::setDB()
     * Method to quickly switch connection objects within an action 
     * to communicate between similar databases
     * 
     * @param Object $db
     * @return void
     */
    public static function setDB($db)
    {
        if(is_object($db)) self::$db = $db;
    }
	
	///////////////////////
	// Registry Methods
	///////////////////////
	
	/**
	 * Control::setVar()
     * Adds a variable to the registry
	 * 
	 * @param String $index the name to store the variable under
	 * @param Mixed $value the value for the registry
	 * @param bool $overwrite Whether or not to overwrite the existing var
	 * @return
	 */
	public static function setVar($index, $value, $overwrite = true)
	{
		if(!is_null(self::$smarty)) {
            self::$smarty->assign($index, $value);
        }
        
        if(!isset(self::$registry['static_keys'])){
				self::$registry['static_keys'] = array();
		}
		// do not register var if reserved
		if(in_array($index, self::$registry['static_keys'])) return;
		
		// register the var
        if($overwrite || !Control::getVar($index)){
            self::$registry[$index] = $value;    
        }
		
        // session messages
        if(in_array($index, array('message', 'error', 'notice'))){
            if(!isset($_SESSION[$index]) || $_SESSION[$index] != $value){
                $_SESSION[$index] = $value;
            } else {
                unset($_SESSION[$index]);
            }
        }
        
		return $value;
	}

    /**
     * addFeedback 
     * 
     * @param $message the feedback messagei
     * @param string $type the class for the feedback ie message | error
     * @static
     * @access public
     * @return void
     */
    public static function addFeedback($message, $type = 'message')
    {
        $feedback = self::getVar('feedback', array());
        $feedback[] = array(
            'className' => $type, // (var is called className for javascript)
            'text' => $message,
            );
        self::setVar('feedback', $feedback);
    }
    public static function setMessage($message, $type = 'message') {
    	self::addFeedback($message, $type);
    }
	
	/**
	 * Gets a variable from the registry
	 * @param String $index the index of the var n the registry
	 * @param Mixed $default_value the default value to return if the var is not set
	 * @param Bool $erase Whether or not to erase the registered var after it is returned
	 */
	public static function getVar($index, $default_value = null, $erase = false)
	{
		$value = $default_value;
        
        // handle  smarty mode
		if(!is_null(self::$smarty)) {
            $smarty_val = @self::$smarty->getTemplateVars($index);
            
            if($erase) {
                self::$smarty->clearAssign($index);    
            }    
            
            return (is_null($smarty_val)) ? $value : $smarty_val;
        }
         
        // session messages
        if(in_array($index, array('message', 'error', 'notice')) && $msg = util_Request::session($index)){
            unset($_SESSION[$index]);
            return $msg;
        }
        
        // the usual
		if(isset(self::$registry[$index])){
			$value = self::$registry[$index];
			if($erase) unset(self::$registry[$index]);
		}
		return $value;
	}
	
	/**
	 * Gets a variable from the registry
	 * @param String $index the index of the var n the registry
	 * @return Bool if the varible is set in the registry or not
	 */
	public static function isVar($index)
	{
		return isset(self::$registry[$index]);
	}

	/**
	 * Ads an instance of smarty to the controller and starts smarty mode
	 * @param Smarty $smarty a Smarty instance
	 */
    public static function setSmarty($smarty) 
    {
        if(is_a($smarty, 'Smarty')) {
            self::$smarty = $smarty;
        } else {
            throw new Exception('Variable passed to Control::setSmarty() must be a Smarty instance');  
        }
    }

	/**
	 * Gets an instance of smarty from controller
	 * @return Smarty $smarty a Smarty instance
	 */
    public static function getSmarty() 
    {
        if(is_null(self::$smarty)) {
            throw new Exception('Control::getSmarty called without a smarty instance');    
        }
        return self::$smarty;
    }
	
	///////////////////////
	// Action Methods
	///////////////////////
	
	/**
	 * Performs an action of the name specified
	 * @param String $action_name
	 */
	public static function doAction($action_name, $exception = true)
	{			
        util_Session::getInstance();
        //todo add 
        // execute old code if no action found

        $action_path = util_Path::requireName('action', util_Path::TYPE_SYSTEM);
		
		$action_table = self::getTable('actions');
		$action = $action_table->getRow($action_name, 'action_name');
		// do from action map
		if(!$action->isEmpty())
		{
			// handle auth
			try{  
				$user = Auth::getUser();
				foreach($action->getPermissions() as $p) {
					if(!$user->hasPermission($p->name)) {
						throw new Exception('You do not have permissions to view this page');
					}
				}
			} catch (Exception $e){
				self::setVar('error', $e->getMessage());
				return self::doAction(401);
			}
		
			if(!self::getVar('page_title', false)) {
				$title = ($action->title != '')? $action->title : str_replace('_', ' ', $action_name);
				self::setVar('page_title', $title, false);
			}

			$file = util_Path::join($action_path, $action->file_path);
			if(is_file($file)){
				#echo "<!--HAS ACTION MAP $file-->";
				return include $file;
			}	
		}
		
		// set paths to look in
		$exts = array('.action.php', '.php');
		
		$file = null;
		foreach($exts as $ext)
		{
			$file = util_Path::getFilePath($action_name . $ext, 'action');
			if($file) break;
		}
				
		//TODO impliment better
		$dirs = explode('_', $action_name);
		Control::setVar('page_title', ucwords(implode(' ', $dirs)));
		
		if(!$file && $exception){
			throw new Exception("Action {$action_name} does not exist");
		}elseif(!$file){
			return false;
		}
		// if found include the file
		return include($file);
	}
	
	/**
	 * Does a command, similar to actions but is not always involved in page views
	 * more like small actions
	 */
	public static function doCommand($command) {
		require 'commands/' . $command;
	}
	
	/**
	 * Gets the current action name
	 * Uses 'action_name' in the registry or $_GET['action']
	 * @return string action_name The name of the action default 'index'
	 */
	public static function getActionName()
	{
		if(!self::isVar('action_name'))
		{
			$action =  isset($_GET['action'])
				? $_GET['action']
				: self::getVar('default_action', 'index');
			self::setVar('action_name', $action);
		}		
		return self::getVar('action_name');
	}
	
	/**
	 * Runs the controller and processes the current action
	 */
	public static function run()
	{		
		// make sure it executes only once
		if(self::isVar('run_executed')) return;
		self::setVar('run_executed', true);
		
		try{
			self::parseUri();
			$action = self::getActionName();

			// add permissions variables for templates
        	self::setVar('permissions', Auth::getPermissions());
			
			// handle banned users
			$user = Auth::getUser();
			if($user && $user->inGroup('banned')) {
				//TODO add the ip address to the ban list
				header('Location: /banned.php');
			}
			
			// add javascript and style to action
			self::addCSS($action, true);
			self::addJS($action);
			// run the post action first for forms
			if(isset($_POST['action']))
				self::doAction($_POST['action']);
			// run the regular action now
			if(self::doAction($action, false) === false){
				self::doAction('404');
			}
		} catch (Exception $e) {
			self::handleException($e);
		}
		// load the footer view
		self::displayFooterSmarty();
		self::displayFooter();
	}
	
	/** 
	 * parses the server URI and checks the database for the correct action
	 */
	// TODO add URI smarty caching
	public static function parseUri() {
		
		// uri is not necessary to parse if action is set
		if(isset($_GET['action'])) return;
		
		$uri = util_Request::server('REQUEST_URI');
		
		// default action
		if($uri == '/') return;

		// strip query string from the uri
		$query_string = '';
		if(strpos($uri, '?') !== false) {
			$parts = explode('?', $uri);
			$query_string = '?' . array_pop($parts);
			$uri = array_shift($parts);
		}
		
		// force trailing slash
		if(substr($uri, (strlen($uri) - 1)) != '/' && strpos($uri, '?') === false) {
			header("location: {$uri}/{$query_string}");
		}
		
		// search for the uri in the db from the first part
		$parts = explode('/', $uri);
		$db = self::getDb();
		$actions = $db->prepare('SELECT action_name, uri, rewrite_params FROM actions WHERE uri LIKE ?');
		$actions->execute(array('/' . $parts[1] . '%'));
		$actions = $actions->fetchAll(PDO::FETCH_ASSOC);
		
		// match the current uri for an action
		$current_action = null;
		foreach($actions as $action) {
			
			// handle any request params
			if($action['rewrite_params']) {
				// the array is reversed to get the params out of the url in reverse order
				$params = array_reverse(explode(',', $action['rewrite_params']));
				$uriParts = explode('/', $uri); array_shift($uriParts); array_pop($uriParts);
				$values = array();
				//TODO improve this logic for mutiple param possibilities with missing params
				foreach($params as $param) {
					$values[$param] = array_pop($uriParts);
					$testUri = '/' . implode('/', $uriParts) . '/';
					if($testUri == $action['uri']) {
						$current_action = $action;
						foreach($values as $key => $value) {
							$_GET[$key] = $value;
							$_REQUEST[$key] = $value;
						}
						break;
					}
				}
			}
			if($current_action) break;
			
			// does the uri match for an action
			if($uri == $action['uri']) {
				$current_action = $action;
				break;
			}
		}
		
		// test with an id uri (last uri folder is an id)
		if(is_null($current_action)) {
			$potential_id = $parts[count($parts) - 2];
			$test_uri = str_replace($potential_id . '/', '', $uri);
			foreach($actions as $action) {
				if($test_uri == $action['uri']) {
					$current_action = $action;
					$_GET['id'] = $potential_id;
					$_REQUEST['id'] = $potential_id;
					break;
				}
			}
		}
		
		// if no current action found give a 404
		if(is_null($current_action)) {
			self::setVar('action_name', '404');
		} else {
			self::setVar('action_name', $current_action['action_name']);
		}
		
	}
	
	///////////////////////
	// View Methods
	///////////////////////
	
	/**
	 * Control::display()
	 * Displays a view file on the page
	 * 
	 * @param mixed $name
	 * @param String $name the name of the view
	 * @return Result of include
	 */
	public static function display($name, $silent_fail = false, $do_headers = true)
	{
		if(empty($name)) return;

		// show the headers on first display
		if($do_headers && !self::getVar('header_displayed', false)) 
			self::displayHeader();
		// strip extension
		list($name) = explode('.', $name);
		
		$view_path = util_Path::requireName('view', util_Path::TYPE_SYSTEM);
		
		// set paths to look in
		$exts = array('.view.php', '.php');
		
		$file = null;
		foreach($exts as $ext)
		{
			$file = util_Path::getFilePath($name . $ext, 'view');
			if($file) break;
		}
		
		if(!$file && !$silent_fail){
			throw new Exception("Unable to load View \"{$name}\"");
		}elseif(!$file){
			return false;
		} else {
			return include $file;
		}
	}

    /** 
     * Displays a smarty template
     * @see http://www.smarty.net/docs/en/api.fetch.tpl
     */
    public static function displaySmarty($template, $cache_id = null, $compile_id = null) {
        echo self::getDisplaySmarty($template, $cache_id, $compile_id);
    }
	
    /** 
     * Displays a smarty template
     * @see http://www.smarty.net/docs/en/api.fetch.tpl
     * //TODO add caching support
     */
    public static function getDisplaySmarty($template, $cache_id = null, $compile_id = null) {

		if(empty($template)) return;

        $smarty = self::getSmarty();
        
        // User links for smarty
        if(self::getVar('userLinks', false) === false) {
            $isUser = Auth::isUser();
            $user = Auth::getUser();
            $current = urlencode(Link::getCurrent());
            self::setVar('userLinks', array(
                'join' => !$isUser ? (string) Link::getClean('action=signup') : '',
                'login' => !$isUser ? (string) Link::getClean('action=login') : '',
                'fblogin' => !$isUser ? (string) Link::getClean('action=login&fb=true') : '',
                'logout' => $isUser ? (string) Link::getClean("action=logout&last={$current}") : '',
                'user' => $isUser ? (string) Link::getClean("action=friend&id={$user->user_id}") : '',
                'name' => $isUser ? $user->name : '',
    			'facebook_id' => $isUser ? $user->facebook_id : '',
            ));
            Control::setVar('user', Auth::getUserInfo());
        }

        // show the headers on first display
		if(!self::getVar('header_displayed_smarty', false)) self::displayHeaderSmarty();

        if(strstr($template, 'skin:') !== false) {
            $path = self::getVar('skinPath');
            $path and $path = "file:{$path}/";
            $template = str_replace('skin:', $path, $template);
        }

        return $smarty->fetch($template, $cache_id, $compile_id);
    }
	
	/**
	 * Control::getDisplay()
	 * An output buffered string display
	 * 
	 * @param mixed $name
	 * @return
	 */
	public static function getDisplay($name)
	{
		ob_start(); self::display($name);
		return ob_get_clean();
	}
    
	/**
	 * Get json for ajax actions
	 * adds the control feedback etc and uses json var from the registry
	 */
	public static function getJson() {
		$json = self::getVar('json', array());
		$feedback = self::getVar('feedback', array());
		if(empty($json) && empty($feedback)) return;
		$json['feedback'] = $feedback;
        $json = array_merge(array(
            'href' => (string) Link::getCurrent(),
            'pageTitle' => control::getVar('page_title'),
        ), $json); 

        $json = json_encode($json);
        if($callback = util_Request::request('callback', false)) {
			return "{$callback}({$json});";
		}
		return $json;
	}
	
	/**
	 * Displays he headers if they haven't been displayed yet
	 */
	public static function displayHeader()
	{
        // init messages
        $messages = array('message', 'error', 'notice');
        foreach($messages as $index)
        {
            if($message = util_Request::session($index)){
                // session var is unset during set var
                self::setVar($index, $message);
            }
        }
        
        if(self::isAjax()) return;
		$displayed = self::getVar('header_displayed', false);
		if($displayed) return;
		self::setVar('header_displayed', true);
		self::display('header');
		self::display('skin_header', true);
		self::display('message', true);
		return true;
	}

	/**
	 * Displays he headers if they haven't been displayed yet
	 */
	public static function displayHeaderSmarty()
	{
        if(self::isAjax()) return;
       
        // add request object for templates
        self::setVar('request', new util_Request());
        
        // for smarty action links and user links
        $action_table = Control::getTable('actions');
        
        $actions = $action_table->getNavTemplateVars();
        self::setVar('actions', $action_table->getNavTemplateVars(Auth::getUser()));

        self::setVar('home_url', Link::getClean('action=index'));
        
        // init messages
        $mtypes = array('message', 'error', 'notice');
		$messages = Control::getVar('messages', array());
        foreach($mtypes as $type)
        {
            if($message = util_Request::session($type)){
                // session var is unset during set var
				$messages[] = array(
					'class' => $type,
					'text' => $message,
				);
            }
            self::setVar($type, '');
        }
		foreach(self::getVar('feedback', array()) as $feedback) {
			$messages[] = $feedback;
		}
		Control::setVar('messages', $messages);
		$displayed = self::getVar('header_displayed_smarty', false);
		if($displayed) return;
		self::setVar('header_displayed_smarty', true);
		//self::displaySmarty('header.tpl');
		self::displaySmarty('skin:header.tpl');
		self::displaySmarty('message.tpl');
		return true;
	}
	
	/**
	 * Loads the template footers of the site
	 */
	public static function displayFooter()
	{
		if(!self::getVar('header_displayed', false)) return;
		self::display('message', true);
		self::display('skin_footer',  true);
		self::display('footer');
	}

	/**
	 * Loads the template footers of the site
	 */
	public static function displayFooterSmarty()
	{
		if(!self::getVar('header_displayed_smarty', false)) return;
        self::setVar('year', date('Y'));
        self::displaySmarty('skin:footer.tpl');
        //self::displaySmarty('footer.tpl');
	}

	///////////////////////
	// Helper Methods
	///////////////////////
	
	/**
	 * Gets a link from the params given
	 * @param Array $params associative var => name array of query params
	 */
	public static function getLink($params)
	{
		// setup inherited parameters
		$names = array_keys($params);
		foreach(self::$inherit_parameters as $name)
		{
			if(in_array($name, $names)) continue;
			$params[$name] = Request::GET($name, null);
		}
		
		// setup link base
		$link = util_Path::get('root');
		if(!is_array($params))
			throw new Exception('$params needs to be an array');
		
		// mod rewrite rules
		if(self::getVar('mod_rewrite') && isset($params['action'])){
			// split dir actions
			$action = $params['action']; unset($params['action']);
			$dirs = explode('_', $action);
			if(count($dirs) == 2) $action = implode('/', $dirs);
			// rewrite the action
			$link = util_Path::join($link, $action . '/');
		}
		 
		// build the query string
		$query = http_build_query($params, '', '&amp;');
		return (empty($query))
			? $link
			: $link . '?' . $query;
	}
	
	/**
	 * Gets an action link Useful for links to actions
	 * @param String $action_name the name of the action for the link 
	 * @param Array $params associative var => name array of query params
	 */
	public static function getActionLink($action_name, $params = array())
	{
		if(empty($action_name)) $action_name = self::getActionName();
		if(!is_array($params))
			throw new Exception('$params needs to be an array');
		$params['action'] = $action_name;
		return self::getLink($params);
	}
	
	/**
	 * Gets a link with inherited parameters minus the names specified
	 * 
	 * @param Mixed $param_names the name(s) of the param(s) to remove
	 * @return Link
	 */
	public static function getLinkWithoutParams($param_names = array())
	{
		if(!is_array($param_names)) 
			$param_names = array($param_names);
		
		$params = $_GET;
		foreach($param_names as $name)
		{
			if(isset($params[$name]))
			unset($params[$name]);
		}
		return self::getLink($params);
	}
	
	/**
	 * Adds parameter names to inherit in getLink  method Calls
	 */
	public static function inheritParamNames($names = array())
	{
		if(!is_array($names)) $names = array($names);
		foreach($names as $name)
		{
			self::$inherit_parameters[] = $name;
		}
		self::$inherit_parameters = array_unique(self::$inherit_parameters);
	}
	
	/**
	 * Redirect to another page
	 */
	public static function redirect($link)
	{
		if(is_array($link)) $link = self::getLink($link);
		$link = str_replace('&amp;', '&', $link);
		
		// javascript redirect
		if(headers_sent() || isset($_REQUEST['ajax'])){
			printf('<script type="text/javascript">document.location="%s"</script>', $link);
			return;
		}
		
		header("Location: {$link}");
		exit;
	}
 	
 	/**
 	 * Router function for table classes
 	 */
 	public static function getTable($table_name, $db = null)
 	{
 		//TODO depricate this function
        class_exists('model_TableBase', false) or require 'model/model_TableBase.class.php';
		return model_TableBase::factory($table_name);
 	}
 	
 	/**
 	 * Router function for row classes
 	 */
 	public static function getRowClass($table_name)
 	{
 		$class_name = 'model_' . util_String::camelize($table_name) . 'Row';
        $path = util_Path::join(util_Path::get('model', util_Path::TYPE_SYSTEM), $class_name . '.class.php');
	 	if(is_file($path)){
	 		return $class_name;	
	 	} else {
	 		return "model_RowCommon";
	 	}
 	}
 	
 	/**
 	 * Exception handler
 	 */
 	public static function handleException(Exception $e)
 	{
 		if(Auth::isAdmin()){
 			echo "<pre>$e</pre>";
 		} else {
			Control::displayHeader();
			if($e->getCode() == util_Error::CODE_AUTHORIZATION){
				Control::setVar('error', 'You are not authorized to use this page. Login Below');
				return Control::doAction('login');
			}
			echo '<div class="error">Error on Page</div>';	
 		}
		
 	}
 	
	/**
	 * Control::addCSS()
	 * Adds a stylesheet to the registry
	 * 
	 * @param String $file The location of the CSS file
     * @param Bool Priority, whether or nto to put it on the top of the list
	 */
	public static function addCSS($file, $priority = false)
	{
		// add css path
		if(!strstr($file, '.css')) $file.='.css';
		
		// test the file presence
		if(!strstr($file, '://'))
		{
			$path = util_Path::requireName('css', util_Path::TYPE_SYSTEM);
			$file = util_Path::getFilePath($file, 'css');
			if(!$file) return;
			$url = util_Path::makeUrl($file);
			$file = $url;
		}
		
		// add file to css array
		$files = self::getVar('stylesheets', array());
		if(in_array($file, $files)) return;
        if($priority) {
            array_unshift($files, $file);
        } else {
            $files[] = $file;
        }
		self::setVar('stylesheets', $files);
	}
	
	/**
 	 * Adds a stylesheet to the registry
 	 */
	public static function addJS($file)
	{
		// add js path
		if(!strstr($file, '.js')) $file.='.js';
		
		if(!strstr($file, '://'))
		{
			$path = util_Path::requireName('js', util_Path::TYPE_SYSTEM);
			$file = util_Path::getFilePath($file, 'js');
			if(!$file) return;
			$file = util_Path::makeURL($file);
		}
		
		// add file to js array
		$files = self::getVar('javascripts', array());
		if(in_array($file, $files)) return;
		$files[] = $file;
		self::setVar('javascripts', $files);
	}
	
	/**
	 * Control::isAjax()
	 * 
	 * @return
	 */
	public static function isAjax()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
	
	/**
	 * Control::setSkin()
	 * 
	 * @return void
	 */
	public static function setSkin($name)
	{
		$path = util_Path::requireName('skin', util_Path::TYPE_SYSTEM);
		$path = util_Path::join($path, $name);
		if(!is_dir($path)){
			throw new exception('The skin "'.$name.'" does not exist');
		}
		util_Path::addOverride('css', $path);
		util_Path::addOverride('view', $path);
		util_Path::addOverride('action', $path);
		util_Path::addOverride('js', $path);
		util_Path::addOverride('image', $path);
		self::addCSS('skin');
		@include util_Path::join($path, 'skin_config.php');
        self::setVar('skinPath', $path);

        $base = dirname($_SERVER['SCRIPT_FILENAME']);
		$path = str_replace($base, '', $path);
		self::setVar('relSkinPath', $path);
	}
	
	/**
	 * Test script directories with this!
	 */
	public static function testPath($path)
	{
		$base = dirname($_SERVER['SCRIPT_NAME']) . '/';
		$path = str_replace($base, '', $path);
		$result = (is_dir($path) || is_file($path));
		return $result;
	}
	
	public static function getImage($image)
	{
		return util_Path::getImage($image);
	}
    
    /**
     * Control::sendMail()
     * 
     * Sends an email from a message template
     * Requires following setVars mail_headers and mail_recipients
     *  
     * @param mixed $mail_template
     * @return void
     */
    public static function sendMail($mail_template, $to, $subject, $send_headers = array())
    {

    	// Build the headers
    	$header_str = '';
    	$headers = array(
    		'From' => null,
    		'Reply-To' => null,
    		'CC' => null,
    		'MIME-Version' => '1.0',
    		'Content-Type' => 'text/html; charset=ISO-8859-1'
    	);
    	foreach($send_headers as $key => $val) {
    		$headers[$key] = $val;
    	}
    	foreach($headers as $key => $val) {
    		if(!is_null($val)) {
    			$header_str .= 	"{$key}: {$val}\r\n";
    		}
    	}

        // get email contents
        $body = self::getDisplaySmarty($mail_template);
        
		mail($to, $subject, $body, $header_str);
    }

    /**
     * get a copy of the hashid object that will be a singleton
     */
    protected static function getHashIdObject() {
    	$obj = self::getVar('hashIdsObj');
    	if(!is_object($obj)) {
    		include dirname(__FILE__) . '/Hashids.php';
    		$obj = new Hashids('saltyRClev', 5, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ94287');
    		self::setVar('hashIdsObj', $obj);
    	}
    	return $obj;
    }
	
	/**
     * get a copy of the hashid object that will be a singleton
     */
    public static function hashEncodeId($id) {
    	if(is_null(self::getHashIdObject())) {
    		throw new Exception("Unable to encode a hash Id");
    	}
    	return self::getHashIdObject()->encode($id);
    }

	/**
     * get a copy of the hashid object that will be a singleton
     */
    public static function hashDecodeId($id) {
    	if(is_null(self::getHashIdObject())) {
    		throw new Exception("Unable to decode a hash Id");
    	}
    	return self::getHashIdObject()->decode($id);
    }
}
