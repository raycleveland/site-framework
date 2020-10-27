<?php
/**
 * This class uses PDO to interract with the database
 */

class model_RowBase implements ArrayAccess 
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
	protected $table_name = null;
	
	/**
	 * model_RowBase::__construct()
	 * 
	 * @param mixed $table
	 * @param mixed $value
	 * @param mixed $field
	 * @return
	 */
	public function __construct(model_TableBase &$table, $value, $field = null)
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
	 * model_RowBase::__isset()
	 * 
	 * @param String $field Name of the column
	 * @return
	 */
	public function __isset($field)
	{
        $value = $this->getValue($field, false);
        return ($value !== false && strpos($value, '0000-00-00') === FALSE);
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
		if(is_array($row)) {
			if(!array_key_exists($field, $row)) return false;
			if($row[$field] === $value) return false;
		}
		
		$this->row[$field] = $value;
		$this->needs_update = true;
		return true;
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
	 * model_RowBase::getRow()
	 * 
     * @param String $fields
	 * @return Array the data for this row
	 */
	public function getRow($fields = null)
	{
		$pk = $this->pk_name;
		$val = $this->getPrimaryValue();
		if((empty($this->row) || !empty($fields)) && !is_null($pk) && !is_null($val))
		{
			$fields = (empty($fields))? $this->table->getFields() : $fields;
            $db = $this->table->getDB();
			$table = $this->table_name;
			$stmt = $db->prepare("SELECT {$fields} FROM {$table} WHERE {$pk}=?");
			$stmt->execute(array($val));
			$this->row = $stmt->fetch(PDO::FETCH_ASSOC);
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
			$db = $this->table->getDB();
			$table = $this->table_name;
			$pk_name = $this->pk_name;
			$stmt = $db->prepare("SELECT {$pk_name} FROM {$table} WHERE {$field} = ?");
			$stmt->execute(array($value));
			$this->pk_val = $stmt->fetchColumn();
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
				$this->pk_val = $this->table->getNextId();
				$this->table->insert($fields_values);
				return;
			} else {
				foreach($fields_values as $field => $value)
				 $this->setFieldValue($field, $value);	
			}			
		}
		
		if(!$this->needs_update) return;
		
		$this->needs_update = false;
		$pk = $this->pk_name;
		$row = $this->getRow();
		$table = $this->table_name;
		
		$db = $this->table->getDB();
		
		$values = array();
		$fv = array();
		foreach($row as $field => $value){ 
			$field_marker = ":{$field}"; 
			$fv[$field_marker] = $value;
			if($field == $pk){
				$where = "{$field} = {$field_marker}"; 
			} else {
				$values[] = "{$field} = {$field_marker}";
			}
		}
		$values = implode(', ', $values);
		if(empty($where)){
			throw new Exception('Where clause not found for update');
		}
		
		$q = "UPDATE {$this->table_name} SET {$values} WHERE {$where}";
		$stmt = $db->prepare($q);
		if(!$stmt->execute($fv)) $this->table->debug($stmt);
		return true;
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
		$db = $this->table->getDB();
		$stmt = $db->prepare("DELETE FROM {$this->table_name} WHERE {$this->pk_name}=?");
		$stmt->execute(array($this->pk_val));
	}
    
    /**
     * model_RowBase::getDate()
     * 
     * Utility function to parse dates from a field
     * 
     * @param mixed $field_name the name of the field to get a date from
     * @param string $format if empty it will return the seconds
	 * @return String the date taken formatted by format string
     */
    public function getDate($field_name, $format = 'Y-m-d')
	{
		$dateVal = $this->getValue($field_name);
        $date = strtotime($dateVal);
        if(!$format) return $date;
		if(empty($date) || strpos($dateVal, '0000-00-00') !== false) {
            return '';
		}
		return date($format, $date);
	}

   
    ///////////////////////////////
    // Array Access Methods
    ///////////////////////////////
   
    public function offsetSet($offset, $value) {
        $this->getRow();
        if (is_null($offset)) {
            $this->row[] = $value;
        } else {
            $this->row[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        $this->getRow();
        return isset($this->row[$offset]);
    }

    public function offsetUnset($offset) {
        $this->getRow();
        unset($this->row[$offset]);
    }

    public function offsetGet($offset) {
        $this->getRow();
        return isset($this->row[$offset]) ? $this->row[$offset] : null;
    }
	
}
