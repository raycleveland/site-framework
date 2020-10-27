<?php

/**
 * @author Ray Cleveland
 * @copyright 2009
 */

class Auth
{
	const LEVEL_GUEST = 1;
	const LEVEL_USER = 2;
	const LEVEL_MEMBER = 2;
	const LEVEL_FRIEND = 3;
	const LEVEL_ADMIN = 4;
	
	private static $user;
	private static $facebook;
	private static $facebookAuth;
	private static $shareParams;
	
	/**
	 * @return array of common questions and answers about the current user session
	 */
	public static function getUserInfo() {
		return array(
			'id' => self::getUserId(),
			'is_user' => self::isUser(),
			'is_admin' => self::isAdmin(),
			'is_friend' => self::isFriend(),
			'is_guest' => self::isGuest(),
		);
	}
	
	/**
	 * Add a facebook auth array
	 * @param Array $auth ex: array(
	 *	  'appId'  => value,
	 *	  'secret' => value,
	 *	)
	 * @throws exception if inavelid param
	 */
	public static function setFacebookAuth($auth) {
		if(!is_array($auth)) {
			throw new Exception('setFacebookAuth expects an array');
		}
		self::$facebookAuth = $auth;
	}
	
	/**
	 * Get instance of facbook class
	 */
	public static function getFacebook() {
		if(is_null(self::$facebook)) {
			if(!is_array(self::$facebookAuth)) {
				throw new Exception('please call setFacebookAuth before getFacebook');
			}
			class_exists('Facebook', false) or require('api/facebook/facebook.php');
			self::$facebook = new Facebook(self::$facebookAuth);
		}
		return self::$facebook;
	}
    
	/**
	 * Auth::setUser()
	 * 
	 * @param mixed $user
	 * @return void
	 */
	public static function setUser($user, $expire = 0)
	{
		if(is_object($user)){
            $user->date_prior_visit = $user->date_last_visit;
            $cookie_data = array($user->getId(), $user->password);
			util_Request::setCookie('user', serialize($cookie_data), $expire);	
		}
	}
	
	/**
	 * Auth::unsetUser()
	 * 
	 * @return void
	 */
	public static function unsetUser()
	{
		util_Request::setCookie('user', null, time() - 3600);
        unset($_COOKIE['user']);
	}
	
	/**
	 * Auth::getUser()
	 * 
	 * @return Mixed Row user object or false
	 */
	public static function getUser()
	{
        if(!is_null(self::$user)) return self::$user;
        if($user = util_Request::cookie('user', false)){
            $data = unserialize($user);
            if(is_object($data) || !is_array($data) || count($data) < 2){ 
                self::unsetUser();
                return false;
            }
            
            list($id, $pass) = $data;
            $table = Control::getTable('users');
            $user = $table->getRow($id);
            if($pass != $user->password){
                self::unsetUser();
                return false;
            }
            $user->date_last_visit = date('Y-m-d H:i:s');
            $user->ip = util_Request::server('REMOTE_ADDR');
            self::$user = $user;
            
            return $user;
        } else {
			$table = Control::getTable('users');
            self::$user = $table->getRow(0);
		}
        return self::$user;
	}
    
    /**
     * Auth::getUserId()
     * 
     * @return Integer The Id of the current user or 0 for no user
     */
    public static function getUserId()
    {
        if(self::isUser()){
            return self::getUser()->getId();
        }
        return 0;
    }
	
	/**
     * Auth::hasPermission()
     * 
	 * @param Mixed $permission Permission identifier
     * @return Bool Whether or not the current user has the permission
     */
	public static function hasPermission($permission) 
	{
		if(self::isUser()) {
			return self::getUser()->hasPermission($permission);
		}
		return false;
	}

    /**
     * Obtain an array of all permissions and whether a user has it set or not
     * @return array of all permisisons such as array('photos_download' => true|false)
     */
    public static function getPermissions() {
    	$table = Control::getTable('permissions');
        $perms = array();
        $rows = $table->getRows();
        foreach($rows as $row) {
        	$perms[$row->name] = self::hasPermission($row->name);
        }
        $perms['is_user'] = self::isUser();
        return $perms;
    }
	
	/**
	 * @param Int $level Class constant value [LEVEL_USER|LEVEL_ADMIN]
	 */
	public static function requireLevel($level)
	{
		if(self::getLevel() < $level)
		 throw new Exception('You are not Authorized to access this page', util_Error::CODE_AUTHORIZATION);
	}
	
	/**
	 * Auth::isUser()
	 * 
	 * @return bOOL WHETHER OR NOT THE CURRENT PERSON IS A USER
	 */
	public static function isUser()
	{
		$user = self::getUser();
		if(is_object($user)){
			return !$user->isEmpty();	
		}
		return false;
	}
	
	/**
	 * Auth::getLevel()
	 * 
	 * Retrieves the auth level from the user that is currently using the site
	 * If not set this returns Auth level of Guest 
	 *  
	 * @return Int Auth_level constant
	 */
	public static function getLevel()
	{
		#if(in_array($_SERVER['SERVER_NAME'], array('localhost')))
		#	return self::LEVEL_ADMIN;
		
		if(self::isUser())
		{
			$user = self::getUser();
			return $user->auth_level;	
		}
		return self::LEVEL_GUEST;
	}
	
	/**
	 * Auth::isAdmin()
	 * 
	 * @return Bool whether or not the current user is admin
	 */
	public static function isAdmin()
	{
		return self::getLevel() == self::LEVEL_ADMIN;
	}
	
	/**
	 * Auth::isFriend()
	 * 
	 * @return Bool whether or not the client is a friend
	 */
	public static function isFriend()
	{
		return in_array(self::getLevel(), array(
            self::LEVEL_FRIEND, 
            self::LEVEL_ADMIN, 
        ));
	}
	
	/**
	 * Auth::isMember()
	 * 
	 * @return Bool whether or not the client is a member
	 */
	public static function isMember()
	{
		return in_array(self::getLevel(), array(
            self::LEVEL_MEMBER,
            self::LEVEL_FRIEND, 
            self::LEVEL_ADMIN, 
        ));
	}
	
	/**
	 * Auth::isGuest()
	 * 
	 * @return Bool whether or not the client is a guest
	 */
	public static function isGuest()
	{
		return self::getLevel() == self::LEVEL_GUEST;
	}

	/**
	 * Get the permissions for the current page share key
	 */
	public static function getShareKeyPermissions($share_key = null) {
		if(is_null(self::$shareParams)) {

			if(empty($share_key)) {
				$share_key = util_Request::get('key', false);
			}

			self::$shareParams = array();
			
			if(!empty($share_key)) {
				$table = Control::getTable('share_keys');
				$shareKeyRow = $table->getRow($share_key, 'share_key');
				if($shareKeyRow->validateParams($_GET)) {
					self::$shareParams = $shareKeyRow->getPermissions();	
				}
			}
		}
		return self::$shareParams;
	}
}

?>
