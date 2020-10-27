<?php

class model_TableBase
{
	/////////////////////////
	// Class Constants
	/////////////////////////
	
	/**
	 * For use with date_time fields
	 */
 	const DATE_TIME = 'Y-m-d H:i:s';
	
	/////////////////////////
	// Class Variables
	/////////////////////////
	
	/**
	 * @var String $table_name The name of the table
	 * @access protected
	 */
	protected $table_name;
	
	/**
	 * @var String $table_short The short name of the table
	 * @access protected
	 */
	protected $table_short;
	
	/**
	 * @var String $pk_name The name of the primary key in the table
	 * @access protected
	 */
	protected $pk_name;
	
	/**
	 * @var String $pk_val The value of the primary key in the table
	 * @access protected
	 */
	protected $pk_val;
	
	/**
	 * @var Array $joins Holds join clauses
	 */
 	protected $joins = array();
	
	/**
	 * @var Array of query clauses such as ORDER or WHERE
	 * @access protected
	 */
	protected $clauses = array();
	
	/**
	 * @var Array of query clauses such as ORDER or WHERE
	 * @access protected
	 */
	protected $clauses_to_destroy = array();
	
	/**
	 * Holds data result for row
	 * @var Array
	 * @access protected
	 */
	protected $row_class = 'model_TableRow';
	
	protected $query_params = array();
	
	/**
	 * Central table registry that caches table information
	 */
	protected static $table_registry = array();
	
	///////////////////////////////
	// Static Object Methods
	///////////////////////////////
	
	/**
	 * model_TableBase::getDB()
	 * 
	 * @return
	 */
	protected static function getDB()
	{
		return Control::getDB();
	}
	
	///////////////////////////////
	// Contructed Object Methods
	///////////////////////////////
	
	/**
	 * model_TableBase::__construct()
	 * 
	 * @param mixed $table_name
	 * @return
	 */
	public function __construct($table_name)
	{
		if(!is_string($table_name)){
			throw new Exception('A table must be constructed with a string table name');
		}
		$pieces = explode(' as ',  strtolower($table_name));
		$this->table_name = $pieces[0];
		$this->table_short = (isset($pieces[1]))? $pieces[1] : $pieces[0];
		$this->init();
	}
	
	/**
	 * model_TableBase::init()
	 * For extension implimentation
	 * 
	 * @return void
	 */
	protected function init()
	{
		
	}
	
	public function getTableName()
	{
		return $this->table_name;
	}
	
	/**
	 * model_TableBase::getRow()
	 * 
	 * @param mixed $value
	 * @param mixed $field
	 * @return
	 */
	public function getRow($value, $field = null)
	{
		$class = Control::getRowClass($this->table_name);
		$row =  new $class($this, $value, $field);
		return $row;
	}
	
	/**
	 * model_TableBase::getRows()
	 * 
	 * @return Row object for each value
	 */
	public function getRows()
	{
		$rows = array();
		$pk = $this->getPrimaryKeyName();
		$query = "SELECT {$this->table_name}.{$pk} FROM {$this->table_name}";
		$keys = self::getDB()->getCol($this->getQuery($query), null, $this->query_params);
		foreach($keys as $key)
		{
			$rows[] = $this->getRow($key, $pk);
		}
		return $rows;
	}
	
	public function select($fields = array('*'))
	{
		if(!is_array($fields)) $fields = array($fields);
		$query = $this->getQuery("SELECT " . implode(', ', $fields) . " FROM $this->table_name");
		echo $query;
	}
	
	/**
	 * model_TableBase::getName()
	 * 
	 * @return
	 */
	public function getName()
	{
		return $this->table_name;
	}
	
	/**
	 * model_TableBase::getTableInfo()
	 * 
	 * @return
	 */
	public function getTableInfo()
	{
		if(!isset(self::$table_registry[$this->table_name]))
		{
			$db = self::getDB();
			try{
				self::$table_registry[$this->table_name] = $db->tableInfo($this->table_name);		
			}catch(Exception $e){
				Throw new Exception("could not retrieve table info for \"{$this->table_name}\"");
			}
		}
		return self::$table_registry[$this->table_name];
	}
	
	/**
	 * model_TableBase::getFieldNames()
	 * 
	 * @return
	 */
	public function getFieldNames()
	{
		$names = array();
		$table_info = $this->getTableInfo();
		foreach($table_info as $num => $field)
		{
			$names[] = $field['name'];
		}
		return $names;
	}
	
	/**
	 * model_TableBase::getPrimaryKeyName()
	 * 
	 * @return
	 */
	public function getPrimaryKeyName()
	{
		if(empty($this->pk_name))
		{
			$table_info = $this->getTableInfo();
			foreach($table_info as $num => $field)
			{
				if(!strstr($field['flags'], 'primary_key'))
					continue;
				$this->pk_name = $field['name'];
			}	
		}
		return $this->pk_name;
	}
	
	/**
	 * model_TableBase::getFieldInfo()
	 * 
	 * @param mixed $field_name
	 * @return
	 */
	public function getFieldInfo($field_name)
	{
		$table_info = $this->getTableInfo();
		foreach($table_info as $num => $field)
		{
			if($field['name'] == $field_name)
				return $field;
		}
		// the field is not found here
		throw new Exception('could not find info for field "'.$field_name.'"');
	}
	
	/**
	 * model_TableBase::getCount()
	 * 
	 * @return Int the count for the selected table
	 */
	public function getCount()
	{
		//TODO fix count
		$db = self::getDB();
		$pk = $this->getPrimaryKeyName();
		$q = $this->getQuery("SELECT COUNT(*) FROM {$this->table_name}");
		#echo $q ."\n";
		try{
		return $db->getOne($q, null, $this->query_params);
		} catch(Exception $e){
			die($q);
		}
	}
	
	/**
	 * model_TableBase::getCol()
	 * Gets an array of results for the column name specified
	 * 
	 * @param string $colname The name of the column to get results for
	 * @return array of results for the column name specified
	 */
	public function getCol($colname)
	{
		$db = self::getDB();
		$pk = $this->getPrimaryKeyName();
		$q = $this->getQuery("SELECT {$colname} FROM {$this->table_name}");
		return $db->getCol($q, null, $this->query_params);
	}
	
	/**
	 * model_TableBase::insert()
	 * Inserts data into the table
	 * 
	 * @param Array $fields_values Associative Array of Field => Value Pairs
	 */
	public function insert($fields_values)
	{
		$db = self::getDB();
		$db->autoExecute($this->table_name, $fields_values, MDB2_AUTOQUERY_INSERT);
		return true;
	}
	
	/**
	 * model_TableBase::getDistinct()
	 * 
	 * @param String $field_name
	 * @return Array of distinct values to field specified
	 */
	public function getDistinct($field_name)
	{
		$db = self::getDB();
		$query = "SELECT DISTINCT {$field_name} FROM {$this->table_name}";
		return $db->getCol($this->getQuery($query), null, $this->query_params);
	}
	
	/**
	 * model_TableBase::getQuery()
	 * 
	 * @param String $base_query The base of the query [ie. SELECT statement]
	 * @return String The full query with clauses added
	 */
	protected function getQuery($base_query)
	{
		$query = trim($base_query)
			. implode($this->joins)
			. $this->getClause('where') 
			. $this->getClause('group')
			. $this->getClause('order')
			. $this->getClause('limit')
		;
		return $query;
	}
	
	////////////////////////////////////////////////
	// Clause Methods
	///////////////////////////////////////////////
	
	/**
	 * model_TableBase::setClause()
	 * 
	 * @param String $clause_name The name of the clause ['where'|'order']
	 * @param String $clause The full clause to add
	 * @param bool $keep Wherther or not to keep the clause after execution
	 * @return void
	 */
	protected function setClause($clause_name, $clause, $keep = true)
	{
		$clause_name = strtolower($clause_name);
		$this->clauses[$clause_name] = $clause;
		if(!$keep) $this->clauses_to_destroy[$clause_name] = true;
	}
	
	/**
	 * model_TableBase::getClause()
	 * Gets the clause for the name given and 
	 * also removes it if is flagged for single execution 
	 * 
	 * @param String $clause_name The name of the clause ['where'|'order']
	 * @return String the Clause of the name specified
	 */
	protected function getClause($clause_name)
	{
		$clause_name = strtolower($clause_name);
		$clause = (isset($this->clauses[$clause_name]))
			? ' ' . $this->clauses[$clause_name]
			: '';
		// destroy clause
		if(isset($this->clauses_to_destroy[$clause_name])){
			unset($this->clauses_to_destroy[$clause_name]);
			unset($this->clauses[$clause_name]);
		}
		return $clause;
	}
	
	/**
	 * Adds a Join Clause to the table query
	 * 
	 * @param String $table_name 	The table to join
	 * @param String $field1 		The main field to Join (from main or both tables)
	 * @param String $field2		The second field to join (from joining table)
	 */
	public function join($table_name, $field1, $field2 = null)
	{
		// parse table name
		$pieces = explode(' as ', strtolower($table_name));
		$short = (count($pieces) == 1)? $table_name : trim($pieces[1]);
		
		if(empty($field2)){
			$field_query = "USING {$field1}";
		} else {
			$field_query = "ON {$this->table_name}.{$field1} = {$short}.{$field2}";
		}
		$this->joins[] = " JOIN {$table_name} {$field_query}";
	}
	
	// specific clauses
	
	/**
	 * model_TableBase::setWhereClause()
	 * 
	 * @param mixed $where_string The content for the where clause [WHERE not needed]
	 * @param bool $keep Wherther or not to keep the clause after execution
	 * @return void
	 */
	public function setWhereClause($where_string, $params = array(), $keep = true)
	{
		if(!is_array($params)) $params = array($params);
		$order_string = str_replace('where ', '', $order_string);
		$where_string = (self::getClause('where') == '')
			? 'WHERE ' . $where_string
			: self::getClause('where') . ' AND ' .$where_string;
		$this->setClause('where', $where_string, $keep);
		$this->query_params = array_merge($this->query_params, $params);
	}
	
	/**
	 * model_TableBase::setOrderClause()
	 * 
	 * @param String $order_string The content for the order clause [ORDEY BY not needed]
	 * @param bool $keep Wherther or not to keep the clause after execution
	 * @return void
	 */
	public function setOrderClause($order_string, $keep = true)
	{
		$order_string = str_replace('order by ', '', $order_string);
		$this->setClause('order', 'ORDER BY ' . $order_string, $keep);
	}
	
	/**
	 * model_TableBase::setGroupClause()
	 * 
	 * @param String $group_string The content for the order clause [ORDEY BY not needed]
	 * @param bool $keep Whether or not to keep the clause after execution
	 * @return void
	 */
	public function setGroupClause($group_string, $keep = true)
	{
		$group_string = str_replace('group by ', '', $group_string);
		$this->setClause('group', 'GROUP BY ' . $group_string, $keep);
	}
		
	/**
	 * model_TableBase::setLimitClause()
	 * 
	 * @param Integer $limit_string The limit number
	 * @param Integer $offset The offset number
	 * @param bool $keep Whether or not to keep the clause after execution
	 * @return void
	 */
	public function setLimitClause($limit_string, $offset = 0, $keep = true)
	{
		$limit_string = 'LIMIT ' . str_replace('limit ', '', $limit_string);
		if(is_numeric($offset) && $offset > 0)
			$limit_string .= ', ' . $offset;
		$this->setClause('limit', $limit_string, $keep);
	}
	
	
	/**
	 * model_TableBase::getNextId()
	 * 
	 * Gets the next primary key id for adding rows. Useful for forms
	 * 
	 * @return void
	 */
	public function getNextId()
	{
		$db = self::getDB();
		$pk = $this->getPrimaryKeyName();
		$max_id = $db->getOne("Select MAX({$pk}) FROM {$this->table_name}");
		if(is_numeric($max_id)){
			return ++$max_id;
		}
	}
}