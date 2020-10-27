<?php

/**
 * Session Singleton
 * API for session interaction
 * 
 * @author Ray Cleveland
 * @copyright 2009
 * @version $Id$
 * @access public
 */
class util_Session
{
	
	private static $instance = null;
	
	///////////////////////////////////
	// Static methods
	///////////////////////////////////
	
	/**
	 * Session::getInstance()
	 * 
	 * @return Session instance
	 */
	public static function getInstance()
	{
		if(empty(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Session::getUser()
	 * 
	 * @return model_User or null
	 */
	public static function getUser()
	{
		$instance = self::getInstance();
		return $instance->user;
	}
	
	/**
	 * Session::setUser()
	 * 
	 * @param model_User $user
	 * @return void
	 */
	public static function setUser($user)
	{
		$instance = self::getInstance();
		$instance->user = $user;
	}
	
	/**
	 * Session::isAdmin()
	 * 
	 * @return Bool whether or not the user is admin
	 */
	public static function isAdmin()
	{
		$user = self::getUser();
		if($user)
		{
			return $user->isAdmin();
		}
		return false;
	}
	
	/**
	 * Session::isUser()
	 * 
	 * @return Bool Whether or not the session is a registered user session
	 */
	public static function isUser()
	{
		$user = self::getUser();
		return !empty($user);
	}
	
	/**
	 * Session::setPageView()
	 * 
	 * @param mixed $view
	 * @return void
	 */
	public static function setPageView($view)
	{
		$instance = self::getInstance();
		if(empty($instance->views)){
			$instance->views = array();
		}
		$instance->views[] = $view;
	}
	
	///////////////////////////////////
	// Instance methods
	///////////////////////////////////
	
	/**
	 * Session::__construct()
	 * 
	 * @return instance of session
	 */
	private function __construct()
	{
		session_start();
		if(empty($_SESSION))
		{
			$this->time_start = time();
		}
	}
	
	/**
	 * Session::__get()
	 * 
	 * @param mixed $index Index for the $_SESSION variable
	 * @return Mixed Value of the variable or null
	 */
	public function __get($index)
	{
		return isset($_SESSION[$index])
			? $_SESSION[$index]
			: null;
	}
	
	/**
	 * Session::__set()
	 * 
	 * @param mixed $index Index for the $_SESSION variable
	 * @param mixed $value
	 * @return void
	 */
	public function __set($index, $value)
	{
		$_SESSION[$index] = $value;
	}
	
}