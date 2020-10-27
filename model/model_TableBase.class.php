<?php
/**
 * This class uses PDO to interract with the database
 * 
 * @todo Add select columns function and add PDO rowstream ability
 */
class model_TableBase implements ArrayAccess
{
	/////////////////////////
	// Class Constants
	/////////////////////////
	
    /**
     * Return Type Constants for getRows()
     */
    const ROWS_OBJECT = 1;
    const ROWS_ARRAY = 2;
    protected $rowClassName = null;
    
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
	 * @var Object Database connection
	 * @access protected
	 */
	protected $db;
	
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
	 * @var String $select_fields The fields to select from the table
	 * @access protected
	 */
    protected $select_fields = '*';
	
	/**
	 * @var Integer $limit the limit of rows to return
	 * @access protected
	 */
	protected $limit;
	
	/**
	 * @var Integer $page the current page number
	 * @access protected
	 */
	protected $page;
	
	/**
	 * @var Array $joins Holds join clauses
	 */
 	protected $joins = array();
	
	/**
	 * @var Array of Join clause indexes to destroy
	 * @access protected
	 */
	protected $joins_to_destroy = array();
    
    /**
	 * @var Array of Param Ids to unset after execution
	 * @access protected
	 */
	protected $unset_params = array();
	
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
    protected static $debugQueryMode = false;

    /**
     * Holds cache of rows for array access
     */
    protected $rows = array();
	
	/**
	 * Central table registry that caches table information
	 */
	protected static $table_registry = array();
    
    /**
	 * The last query returned by getQuery for debugging purposes
	 */
    public $last_query;
	
	///////////////////////////////
	// Static Object Methods
	///////////////////////////////
	
	/**
	 * model_TableBase::getDB()
	 * 
	 * @return
	 */
	public static function getDB()
	{
        return Control::getDB();
	}
	
	/**
 	 * Factroy to get a table object
 	 */
 	public static function factory($table_name, $db = null)
 	{
		$class_name = 'model_' . util_String::camelize($table_name) . 'Table';
	 	$path = util_Path::join(util_Path::get('model', util_Path::TYPE_SYSTEM), $class_name . '.class.php');
	 	if(!is_file($path)){
	 		$class_name = "model_TableCommon";
	 	}
	 	return new $class_name($table_name);
 	}
    
    /**
     * model_TableBase::setDebugQueryMode()
     * 
     * Will print queries on the page so they can be debugged esier
     * in case of an error
     * 
     * @param bool $mode whether or not to rint query output
     * @return void
     */
    public static function setDebugQueryMode($mode = true) {
        self::$debugQueryMode = $mode;
    }
	
	///////////////////////////////
	// Contructed Object Methods
	///////////////////////////////
	
	/**
	 * model_TableBase::__construct()
	 * 
	 * @param String $table_name
	 * @return
	 */
	public function __construct($table_name)
	{
        
        if(!is_string($table_name)){
			throw new Exception('A table must be constructed with a string table name');
		}
        
		/// set table name with table short
        $pieces = explode(' as ',  strtolower($table_name));
		$this->table_name = $pieces[0];
		$this->table_short = (isset($pieces[1]))? $pieces[1] : $pieces[0];
        
        $this->select_fields = "{$table_name}.*";
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
		// for use with extensions
	}
	
	/**
	 * model_TableBase::getTableName()
	 * 
	 * @return String the name of the table
	 */
	public function getTableName()
	{
		return $this->table_name;
	}
    
    /**
     * model_TableBase::setColumns()
     * 
     * @param string $columns Comma separated Field names to limit the queries to
     * @return void
     */
    public function setFields($fields = '*')
    {
        $split = explode(',', $fields);
        $fields = array();
        foreach($split as $field)
        {
            if(empty($field)) continue;
            if(strpos($field, '.') === FALSE){
                $field = "{$this->table_name}.{$field}";
            }
            $fields[] = $field;
        }
        $fields = implode(', ', $fields);
        $this->select_fields = $fields;
    }

    /**
     * addFields 
     *
     * Add fields to select
     * 
     * @param String $fields 
     * @access public
     * @return void
     */
    public function addFields($fields) 
    {
        $fields = "{$this->select_fields}, {$fields}";
        $this->setFields($fields);
    }
    
    /**
     * model_TableBase::getFields()
     * 
     * @return String The fields to select for the table
     */
    public function getFields()
    {
        return $this->select_fields;
    }
	
	/**
	 * model_TableBase::getRow()
	 * 
	 * @param mixed $value
	 * @param mixed $field
	 * @return
	 */
	public function getRow($value = 0, $field = null, $return_type = self::ROWS_OBJECT)
	{
		if(empty($value)) {$value = 0;}
        if($return_type == self::ROWS_OBJECT){
		  return $this->getRowObject($value, $field);
		}
        return $this->getRowArray($value, $field);
	}
    
    
    /**
     * model_TableBase::getRowArray()
     * 
     * @param mixed $value
     * @param mixed $field
     * @return void
     */
    protected function getRowArray($value, $field = null)
    {
        $db = self::getDB();
        // get key for where clause
        if(empty($field)){
            $field = $this->pk_name;
        }
        $query = "SELECT {$this->select_fields} FROM {$this->table_name}"
            .    " WHERE {$field}=?";
        $stmt = self::getDB()->prepare($query);
        if(!$stmt->execute(array($value))) $this->debug($stmt);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * model_TableBase::getRowObject()
     * 
     * @param mixed $value
     * @param mixed $field
     * @return
     */
    public function getRowObject($value, $field = null)
	{
		if(empty($this->rowClassName)) {
			$this->rowClassName = Control::getRowClass($this->table_name);	
		}
		$class = $this->rowClassName;
		$row =  new $class($this, $value, $field);
		return $row;
	}
	
	/**
	 * model_TableBase::getRows()
	 * 
	 * @return Row object for each value
     * const ROWS_OBJECT = 1;
     * const ROWS_ARRAY = 2;
     * const ROWSTREAM_OBJECT = 3;
     * const ROWSTREAM_ARRAY = 4;
	 */
	public function getRows($return_type = self::ROWS_OBJECT)
	{
        $rows = array();
		$pk = $this->getPrimaryKeyName();
		$query = "SELECT {$this->table_name}.{$pk} FROM {$this->table_name}";
		$keys = self::getDB()->prepare($this->getQuery($query));
		if(!$keys->execute($this->getParams())) $this->debug($keys);
		while($key = $keys->fetchColumn())
		{
			$rows[] = $this->getRow($key, $pk, $return_type);
		}
		return $rows;
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
				$stmt = $db->prepare('DESC '. $this->table_name);
				$stmt->setFetchmode(PDO::FETCH_ASSOC);
				$stmt->execute();
				self::$table_registry[$this->table_name] = $stmt->fetchAll();		
			}catch(Exception $e){
				Throw new Exception("could not retrieve table info for \"{$this->table_name}\"");
			}
			if(empty(self::$table_registry[$this->table_name])){
				throw new Exception("The Table \"{$this->table_name}\" Does not exist");
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
			$names[] = $field['Field'];
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
				if(!strstr($field['Key'], 'PRI'))
					continue;
				$this->pk_name = $field['Field'];
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
			if($field['Field'] == $field_name)
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
		$db = self::getDB();
		$pk = $this->getPrimaryKeyName();
		$q = $this->getQuery("SELECT COUNT({$this->table_short}.{$this->pk_name}) FROM {$this->table_name}", array('where', 'order'));
		$stmt = $db->prepare($q);
		if(!$stmt->execute($this->getParams())) $this->debug($stmt);
		return $stmt->fetch(PDO::FETCH_COLUMN);
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

		// if the table is not declared default to the table name (excluding fields with distinct)
		if(strpos($colname, '.') === FALSE && strpos($colname, ' ') === FALSE) {
			$colname = "{$this->table_name}.{$colname}";
		}
		
		$q = $this->getQuery("SELECT {$colname} FROM `{$this->table_name}`");
		$stmt = $db->prepare($q);
		if(!$stmt->execute($this->getParams())) $this->debug($stmt);
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}
    
    /**
     * model_TableBase::getOne()
     * 
     * Get one value from the table
     * 
     * @param String $field_name optional name of the field [default primary key]
     * @return void
     */
    public function getOne($field_name = null)
    {
        if(empty($field_name)) $field_name = $this->getPrimaryKeyName();
        $db = self::getDB();
        $field_name = (strpos($field_name, '(') === FALSE)
            ? "{$this->table_name}.{$field_name}"
            : $field_name;
        $q = $this->getQuery("SELECT {$field_name} FROM {$this->table_name}");
        $stmt = $db->prepare($q);
		if(!$stmt->execute($this->getParams())) $this->debug($stmt);
        return $stmt->fetchColumn(0);
    }
    
	/**
	 * model_TableBase::insert()
	 * Inserts data into the table
	 * 
	 * @param Array $fields_values Associative Array of Field => Value Pairs
	 */
	public function insert($fields_values, $replace = false)
	{
		$db = self::getDB();
		
		$fields = array_keys($fields_values);
		$fv = array();
		foreach($fields_values as $field => $value){ 
			$fv[":{$field}"] = $value;
		}
		
        $type = ($replace)? 'REPLACE' : 'INSERT';
		$q = sprintf('%s INTO %s (%s) VALUES(%s)', 
		      $type, $this->table_name, implode(', ', $fields), implode(', ', array_keys($fv)));
		
		$stmt = $db->prepare($q);
		if(!$stmt->execute($fv)) $this->debug($stmt);

		return $db->lastInsertId();
	}
    
    /**
	 * model_TableBase::replace()
	 * replaces data into the table
	 * 
	 * @param Array $fields_values Associative Array of Field => Value Pairs
	 */
	public function replace($fields_values)
	{
		$this->insert($fields_values, true);
	}
    
    /**
     * model_TableBase::getNewestRow()
     *
     * Gets the newest Row from the database
     *  
     * @return
     */
    public function getNewestRow()
    {
        $id = $this->getMaxId();
        return $this->getRow($id);
    }
	
	/**
	 * model_TableBase::getDistinct()
	 * 
	 * @param String $field_name
	 * @return Array of distinct values to field specified
	 */
	public function getDistinct($field_name)
	{
		return $this->getCol("DISTINCT {$field_name}");
	}
	
	/**
	 * model_TableBase::getQuery()
	 * 
	 * @param String $base_query The base of the query [ie. SELECT statement]
	 * @return String The full query with clauses added
	 */
	protected function getQuery($base_query, $parts = array('where', 'group', 'order', 'limit'))
	{
		$query = trim($base_query)
			. $this->getJoins();
        foreach($parts as $part)
        {
            $query .= $this->getClause($part);
        }
        $this->last_query = $query; 
        if(self::$debugQueryMode) {
            echo "<!-- query output: \n$query\n-->";
        }
		return $query;
	}
	
	/**
	 * model_TableBase::getJoins()
	 * 
	 * @return
	 */
	protected function getJoins()
	{
		$joins = '';
		foreach($this->joins as $index => $join)
		{
			$joins .= $join;
			if(isset($this->joins_to_destroy[$index])){
				unset($this->joins_to_destroy[$index]);
				unset($this->joins[$index]);
			}
		}
		return $joins;
	}
	
	////////////////////////////////////////////////
	// Clause Methods
	///////////////////////////////////////////////
	
    /**
     * model_TableBase::removeClauses()
     * 
     * clears all clauses set in the table
     * 
     * @return void
     */
    public function removeClauses()
    {
        $this->clauses = array();
    }
    
	/**
	 * model_TableBase::setClause()
	 * 
	 * @param String $clause_name The name of the clause ['where'|'order']
	 * @param String $clause The full clause to add
	 * @param bool $keep Whether or not to keep the clause after execution
	 * @return void
	 */
	protected function setClause($clause_name, $clause, $keep = true)
	{
		// clauses are stored in arrays for inheritance purposes
        $clause_name = strtolower($clause_name);
        if(!isset($this->clauses[$clause_name])){
            $this->clauses[$clause_name] = array();
        }
        
        // the clause array index
        $index = count($this->clauses[$clause_name]);
        
        // store the clause
        $this->clauses[$clause_name][$index] = $clause; 
        
        // store whether or not to erase the clause after use
		if(!$keep && !isset($this->clauses_to_destroy[$clause_name])){
            $this->clauses_to_destroy[$clause_name] = array($index);
		} elseif(!$keep) {
            $this->clauses_to_destroy[$clause_name][] = $index;
		}
	}
	
	/**
	 * model_TableBase::getClause()
	 * Gets the clause for the name given and 
	 * also removes it if is flagged for single execution 
	 * 
	 * @param String $clause_name The name of the clause ['where'|'order']
	 * @return String the Clause of the name specified
	 */
    // TODO fix bug with clauses being removed wrong
	protected function getClause($clause_name)
	{
		$clause_name = strtolower($clause_name);
        
        if(isset($this->clauses[$clause_name])){
            $clause = $this->clauses[$clause_name];
        } else {
            return '';
        }
        
        // handle where clause
        if($clause_name == 'where')
        {
            // syntax is hard to follow but this writes WHERE (clause) AND (clause) ...
            $clause = 'WHERE (' . implode(') AND (', $clause) . ')';
            
            // cleanup where clauses
            foreach($this->clauses[$clause_name] as $index => $value){
                if(isset($this->clauses_to_destroy[$clause_name][$index])){
                    unset($this->clauses[$clause_name][$index]);
                }   
            }
            
        // handle all other clauses
        } else {
            
            $index = count($clause) - 1;
            $clause = $clause[$index];
            
            /// cleanup the clause
            if(isset($this->clauses_to_destroy[$clause_name][$index])){
                unset($this->clauses[$clause_name][$index]);
            }
        }
		return " {$clause}";
	}
	
	/**
	 * Adds a Join Clause to the table query
	 * 
	 * @param String $table_name 	The table to join
	 * @param String $field1 		The main field to Join (from main or both tables)
	 * @param String $field2		The second field to join (from joining table)
	 * @param Bool   $expire		Whether or not to remove the join after a query is executed 
	 */
	public function join($table_name, $field1, $field2 = null, $expire = false)
	{
		// parse table name
		$pieces = explode(' as ', strtolower($table_name));
		$short = (count($pieces) == 1)? $table_name : trim($pieces[1]);
		
		if(empty($field2)){
			$field_query = "USING {$field1}";
		} else {
			$field_query = "ON {$this->table_name}.{$field1} = {$short}.{$field2}";
		}
        $join = " LEFT JOIN {$table_name} {$field_query}";
        if(!in_array($join , $this->joins)) {
            $key = count($this->joins);
            $this->joins[$key] = $join;
            if($expire){
                $this->joins_to_destroy[$key] = true;
            }
        }
	}
	
	// specific clauses
	
	/**
	 * model_TableBase::setWhere()
	 * 
	 * @param mixed $where_string The content for the where clause [WHERE not needed]
	 * @param mixed $params Array of parameters for the where clause
	 * @param bool $keep Whether or not to keep the clause after execution
	 * @return void
	 */
	public function setWhere($where_string, $params = array(), $keep = true)
	{
		if(!is_array($params)) $params = array($params);
		$where_string = str_ireplace('where ', '', $where_string);
		$this->setClause('where', $where_string, $keep);
        $this->addParams($params, $keep);
	}
    
    /**
     * model_TableBase::addParams()
     * 
     * @param mixed $params Array of parameters for the next execution
	 * @param bool $keep Whether or not to keep the params after execution
     * @return void
     */
    public function addParams($params = array(), $keep = false){
        foreach($params as $param){
            $index = count($this->query_params);
            $this->query_params[$index] = $param;
            if(!$keep){
                $this->unset_params[] = $index;
            }
        }
    }	
    
    /**
     * model_TableBase::getParams()
     * 
     * @return void
     */
    public function getParams(){
        $params = array();
        foreach($this->query_params as $index => $param){
            $params[] = $param;
            if(in_array($index, $this->unset_params)){
                unset($this->query_params[$index]);
            }
        }
        $this->unset_params = array();
        return $params;
    }
    
	/**
	 * model_TableBasePDO::setWhereClause()
	 * 
	 * @deprecated
	 * @see model_TableBase::setWhere()
	 */
	public function setWhereClause($where_string, $params = array(), $keep = true)
	{
		return $this->setWhere($where_string, $params, $keep);
	}
	
	/**
	 * model_TableBase::setOrder()
	 * 
	 * @param String $order_string The content for the order clause [ORDEY BY not needed]
	 * @param bool $keep Whether or not to keep the clause after execution
	 * @return void
	 */
	public function setOrder($order_string, $keep = true)
	{
		$order_string = str_replace('order by ', '', $order_string);
		$this->setClause('order', 'ORDER BY ' . $order_string, $keep);
	}
	
	/**
	 * model_TableBase::setOrderClause()
	 * 
	 * @deprecated
	 */
	public function setOrderClause($order_string, $keep = true)
	{
		return $this->setOrder($order_string);
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
	 * model_TableBasePDO::setLimit()
	 * 
	 * Sets the numeric limit for the results  
	 *  
	 * @param Integer $limit the number of rows to limit
	 * @param Integer $page The page number to show the client
	 * @return void
	 */
	public function setLimit($limit, $page = 1, $keep = true)
	{
		if(!is_numeric($limit)){
			throw new Exception(__FUNCTION__ . ' expects $limit to be numeric instead is ' . gettype($limit));
		}elseif(!is_numeric($page)){
            throw new Exception(__FUNCTION__ . ' expects $page to be numeric instead is ' . gettype($page));
		}
		$this->limit = $limit;
		$this->page = $page;
		$offset = $page * $limit - $limit; 
		$limit_string = "LIMIT {$offset}, {$limit}"; 
		$this->setClause('limit', $limit_string, $keep);
	}
	
	/**
	 * model_TableBasePDO::getMaxId()
	 * 
	 * @return Mixed The most recent primary key value
	 */
	public function getMaxId()
	{
		$db = self::getDB();
		$pk = $this->getPrimaryKeyName();
		$stmt = $db->prepare("Select MAX({$pk}) FROM {$this->table_name}");
		$stmt->execute();
		$max_id = $stmt->fetchColumn();
		return $max_id;
	}
    
	/**
	 * model_TableBase::getMax()
	 * 
	 * @param String $fieldname
	 * @return
	 */
	public function getMax($fieldname)
	{
		$db = self::getDB();
		$pk = $this->getPrimaryKeyName();
		$stmt = $db->prepare("Select MAX({$fieldname}) FROM {$this->table_name}");
		$stmt->execute();
		$max = $stmt->fetchColumn();
		return $max;
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
		$max_id = $this->getMaxId();
		if(is_numeric($max_id)){
			return ++$max_id;
		}
	}
	
	/**
	 * model_TableBasePDO::debug()
	 * Debug wrapper for execute statements
	 * 
	 * @return void
	 */
	public function debug($statement)
	{
		ob_start();
		$statement->debugDumpParams();
		$message = array_pop($statement->errorInfo()) . "\n"
			. ob_get_clean();
		throw new Exception($message);
	}
	
	//HElper functions
	
	/**
	 * model_TableBase::getLimit()
	 * 
	 * @return
	 */
	public function getLimit()
    {
        return $this->limit;
    }
    
    /**
	 * model_TableBase::getTotalPages()
	 * 
	 * @return
	 */
	public function getTotalPages()
	{
        if(!empty($this->limit)){
			$total = $this->getCount();
			return ceil($total / $this->limit);	
		}
		return 1;
	}
	
	/**
	 * model_TableBase::getPagesLeft()
	 * 
	 * @return
	 */
	public function getPagesLeft()
	{
		if($this->page > 0){
			return $this->getTotalPages() - $this->page;
		}
		return 0;
	}
	
	///////////////////////////////
	// Array Access Methods
	///////////////////////////////
	
    /**
     * returns rowsfrom this->rows property for array access
     */
    public function getRowsCached() {
        if(empty($this->rows)) {
            $this->rows = $this->getRows();
        }
        return $this->rows;
    }
	
    public function offsetSet($offset, $value) {
        $this->getRowsCached();
        if (is_null($offset)) {
            $this->rows[] = $value;
        } else {
            $this->rows[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        $this->getRowsCached();
        return isset($this->rows[$offset]);
    }

    public function offsetUnset($offset) {
        $this->getRowsCached();
        unset($this->rows[$offset]);
    }

    public function offsetGet($offset) {
        $this->getRowsCached();
        return isset($this->rows[$offset]) ? $this->rows[$offset] : array();
    }
}
