<?php


class model_RowBase extends model_TableBase
{
	
	/**
	 * Holds data result for row
	 * @var Array
	 * @access protected
	 */
	protected $row = array();	
	protected $needs_update = false;
	protected $pk_name = null;
	protected $pk_val = null;
	protected $table = null;
	
	/**
	 * model_RowBase::__construct()
	 * 
	 * @param mixed $table
	 * @param mixed $value
	 * @param mixed $field
	 * @return
	 */
	public function __construct($table, $value, $field = null)
	{
		$this->table = $table;
		$this->table_name = $table->getTableName();
		$this->pk_name = $table->getPrimaryKeyName();
		if(empty($field) || $field == $this->pk_name)
		{
			$this->pk_val = $value;
		}
		$this->getPrimaryValue($field, $value);
	}
	
	/**
	 * model_RowBase::__destruct()
	 * 
	 * This updates the row if it needs updating
	 */
	public function __destruct()
	{
		$this->update();
	}
	
	/**
	 * model_RowBase::__set()
	 * 
	 * @param String $field Name of the column
	 * @param mixed $value
	 * @return
	 */
	public function __set($field, $value)
	{
		return $this->setFieldValue($field, $value);
	}
	
	/**
	 * model_RowBase::setFieldValue()
	 * 
	 * @param String $field Name of the column
	 * @param mixed $value
	 * @return
	 */
	public function setFieldValue($field, $value)
	{
		if(is_object($value)) return;
		$row = $this->getRow();
		
		// abort conditions
		if(!array_key_exists($field, $row)) return;
		if($row[$field] == $value) return;
		
		$this->row[$field] = $value;
		$this->needs_update = true;
	}
	
	/**
	 * model_RowBase::__isset()
	 * 
	 * @param String $field Name of the column
	 * @return
	 */
	public function __isset($field)
	{
		return $this->getValue($field, false) !== false;
	}
	
	/**
	 * model_RowBase::__get()
	 * 
	 * @param String $field Name of the column
	 * @return
	 */
	public function __get($field)
	{
		return $this->getValue($field, null);
	}
	
	/**
	 * model_RowBase::getValue()
	 * 
	 * gets a value for a field in the table
	 * 
	 * @param String $field The anme of the field to get value from 
	 * @param mixed $default_value The default value to return if field does not exist
	 * @return
	 */
	public function getValue($field, $default_value = null)
	{
		$row = $this->getRow();
		return (isset($row[$field]))
			? stripslashes($row[$field])
			: $default_value;
			
	}
	
	/**
	 * model_RowBase::getTableName()
	 * 
	 * @return String The name of the table this object is an row of
	 */
	public function getTableName()
	{
		return $this->table->getName();
	}
	
	/**
	 * model_RowBase::getRow()
	 * 
	 * @return Array the data for this row
	 */
	public function getRow()
	{
		$pk = $this->getPrimaryKeyName();
		$val = $this->getPrimaryValue();
		if(empty($this->row) && !is_null($pk) && !is_null($val))
		{
			$db = self::getDB();
			$table = $this->getTableName();
			$query = "SELECT * FROM {$table} WHERE {$pk}=?";
			$this->row = $db->getRow($query, null, array($val));
		}
		return (is_null($this->row))? array() : $this->row;
	}
	
	/**
	 * model_RowBase::getPrimaryValue()
	 * 
	 * @param String $field The name of the column to query
	 * @param Mixed $value The value to query
	 * @return
	 */
	public function getPrimaryValue($field = null, $value = null)
	{
		if(empty($this->pk_val) && !is_null($field) && !is_null($value))
		{
			$db = self::getDB();
			$table = $this->getTableName();
			$pk_name = $this->getPrimaryKeyName();
			$query = "SELECT {$pk_name} FROM {$table} WHERE {$field} = ?";
			$this->pk_val = $db->getOne($query, null, array($value));
		}
		return $this->pk_val;
	}
	
	/**
	 * model_RowBase::getId()
	 * 
	 * @return Mixed the id of the primary key
	 */
	public function getId()
	{
		return $this->pk_val;
	}
	
	/**
	 * model_RowBase::isEmpty()
	 * 
	 * @return Bool whether or not this is an empty rowset
	 */
	public function isEmpty()
	{
		 return empty($this->pk_val);
	}
	
	/**
	 * model_RowBase::update()
	 * 
	 * Updates the row in the database 
	 * 
	 * @param Array $fields_values Array of field => value pairs
	 */
	public function update($fields_values = array())
	{	
		// handle passed fields and values
		if(!empty($fields_values))
		{
			// insert for empty rows
			if($this->isEmpty()){
				$table = Control::getTable($this->getTableName());
				$this->pk_val = $table->getNextId();
				$table->insert($fields_values);
				return;
			}
			
			foreach($fields_values as $field => $value)
				$this->setFieldValue($field, $value);
		}
		
		if(!$this->needs_update) return;
		
		$this->needs_update = false;
		$pk = $this->getPrimaryKeyName();
		$row = $this->getRow();
		$table = $this->getTableName();
		unset($row[$pk]);
		
		$db = self::getDB();
		$db->autoExecute($table, $row, MDB2_AUTOQUERY_UPDATE, "$pk = $this->pk_val");
		
	}
	
	/**
	 * model_RowBase::delete()
	 * 
	 * Deletes the row this is an instance of
	 * Reccomended to confirm on the front end
	 */
	public function delete()
	{
		if(empty($this->pk_val)) return false;
		$db = self::getDB();
		$pk = $this->getPrimaryKeyName();
		$table = $this->getTableName();
		$stmt = $db->prepare("DELETE FROM {$table} WHERE {$pk}=?");
		$stmt->execute(array($this->pk_val));
	}
	
}