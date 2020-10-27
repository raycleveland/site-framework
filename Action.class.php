<?php

/**
 * Singleton Action class
 */

final class Action{	
	
	private static $table;
	//constructed vars
	private $row;
	private $action_name;
	
	private function __construct($action_name)
	{
		$table = self::table();
		$this->row = $table->getRow($action_name, 'action_name');
	}
	
	/**
	 * Action::__get()
	 * 
	 * @param string $field
	 * @return Gets a field form the current action map for the current action
	 */
	public function __get($field)
	{
		return $this->row->$field;
	}
	
	/**
	 * Action::getCurrent()
	 * 
	 * @return Action instance of current action
	 */
	public static function getCurrent()
	{
		return new self(Control::getActionName());
	}
	
	/**
	 * Action::getAction()
	 * 
	 * @return Action instance for name passed
	 */
	public static function getAction($action_name)
	{
		return new self($action_name);
	}
	
	/**
	 * Action::table()
	 * 
	 * @return
	 */
	private static function table()
	{
		if(empty(self::$table)){
			self::$table = Control::getTable('actions');
		}
		return self::$table;
	}
	
	/**
	 * Action::getChildren()
	 * 
	 * @return
	 */
	public static function getChildren()
	{
		$action_name = (isset($this))
			? $this->action_name 
			: Control::getActionName();
		$table = self::table();
		$table->setWhere('parent = ?', $action_name);
		return $table->getCol('action_name');
	}
	
	/**
	 * Control::getActionList()
	 * 
	 * @return Array List of actions for site
	 */
	public static function getActionList()
	{
		return self::table()->getCol('action_name');
	}
	
	//TODO finish
	/**
	 * Action::getNav()
	 * 
	 * @return void
	 */
	public static function getNav()
	{
		$table = self::table();
		$table->setWhere('is_nav > 0');
		return $table->getCol('action_name');
	}
	
}