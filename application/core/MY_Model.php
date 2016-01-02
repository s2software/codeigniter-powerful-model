<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Powerful Model
 *
 * A CodeIgniter extension to work (or better, to play!) with database tables
 * with an easy and intuitive Object Oriented / Entity Framework approach.
 *
 * @author	Storti Stefano
 * @copyright	Copyright (c) 2015, S2 Software di Storti Stefano
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	http://www.s2software.it
 * @version 3.2.2
 */
class MY_Model extends CI_Model {
	
	// table name and row object type
	public $table = '';
	public $row_type = '';
	public $id_field = '';
	
	protected $_is_caching = FALSE;
	
	protected $_native_calls = array();
	protected $_cache_native_calls = array();
	
	protected $_call_autojoin = FALSE;
	protected $_cache_call_autojoin = FALSE;
	
	protected $_joined_tables = array();
	protected $_cache_joined_tables = array();
	
	public function __construct()
	{
		parent::__construct();
		
		//$this->load->helper('application');
		$this->load->database();
		$this->load->helper('date');
		
		if (!$this->table)
			$this->table = str_replace('_model', '', strtolower(get_class($this)));
		
		if (!$this->row_type)
		{
			$this->row_type = 'object';
			$row_type = ucfirst(entity($this->table)).'_object';
			if (class_exists($row_type))
				$this->row_type = $row_type;
		}
		
		if (!$this->id_field)
		{
			$this->id_field = 'id';
			if ($this->db->table_exists($this->table))
			{
				$fields = $this->db->field_data($this->table);
				foreach ($fields as $field)
				{
					$fname = $field->name;
					if ($field->primary_key)
					{
						$this->id_field = $fname;
						break;
					}
				}
			}
		}
	}
	
	/**
	 * Call CI db native methods before get() or get_list() (allow method chaining)
	 * @param string $name
	 * @param array $arguments
	 */
	public function __call($name, $arguments)
	{
		call_user_func_array(array($this->db, $name), $arguments);
		// Support for Query Builder caching system
		if ($name == 'start_cache')
		{
			$this->_is_caching = TRUE;
		}
		elseif ($name == 'stop_cache')
		{
			$this->_is_caching = FALSE;
		}
		elseif ($name == 'flush_cache')
		{
			$this->_cache_native_calls = array();
			$this->_cache_call_autojoin = FALSE;
			$this->_cache_joined_tables = array();
		}
		elseif ($name == 'reset_query')
		{
			$this->_reset_native_calls();
		}
		else
		{
			$this->_native_calls[] = $name;
			if ($this->_is_caching)
				$this->_cache_native_calls[] = $name;
			
			// Keep in mind already joined table to avoid rejoins in the method chaining pattern
			// (the extended model can call the is_joined() function to check it this table is already joined)
			if ($name == 'join')
			{
				$this->_joined_tables[] = $arguments[0];
				if ($this->_is_caching)
					$this->_cache_joined_tables[] = $arguments[0];
			}
		}
		return $this;
	}
	
	/**
	 * If this table supports soft delete, this filter need to be applied
	 * to return only the logically not deleted record
	 * @param bool $deleted (NULL = include soft deleted records / FALSE = exclude soft deleted records / TRUE = get only soft deleted records)
	 */
	public function all($deleted = FALSE)
	{
		$table_fields = $this->db->list_fields($this->table);
		if (!in_array('deleted', $table_fields))
			return $this;
		
		if ($deleted !== NULL)
		{
			$this->where($this->table.'.deleted'.($deleted ? ' !=' : ''), MYSQL_EMPTYDATETIME);
		}
		return $this;
	}
	
	/**
	 * Pagination with method chaining
	 */
	public function pagination($page, $pagesize, $check_more = FALSE)
	{
		if ($page > 0 && $pagesize > 0)
			$this->db->limit($pagesize + ($check_more ? 1 : 0), $pagesize*($page-1));
		return $this;
	}
	
	/**
	 * Call autojoin with method chaining
	 */
	public function autojoin()
	{
		$this->_call_autojoin = TRUE;
		return $this;
	}
	
	
	
	/**
	 * Get an array of rows
	 * 
	 * @param mixed $filter
	 * @param string $order_by
	 * @param array $params: Extra paramters
	 */
	public function get_list($filter = NULL, $order_by = NULL, $params = array())
	{
		// extra params
		$autojoin = (isset($params['autojoin']) ? $params['autojoin'] : $this->_call_autojoin || $this->_cache_call_autojoin);	// perform autojoin
		$page = (isset($params['page']) ? $params['page'] : 0);					// current page
		$pagesize = (isset($params['pagesize']) ? $params['pagesize'] : 0);		// page size
		$like = (isset($params['like']) ? $params['like'] : FALSE);				// use LIKE operator for filters on string field
		$joins = (isset($params['joins']) ? $params['joins'] : FALSE);			// define JOIN clauses array or array of arrays (table, cond, type)
		$distinct = (isset($params['distinct']) ? $params['distinct'] : FALSE);	// make select DISTINCT
		$select = (isset($params['select']) ? $params['select'] : FALSE);		// other fields to append in select clause
		$where = (isset($params['where']) ? $params['where'] : FALSE);			// add custom WHERE string
		
		if ($this->_is_caching)
			$this->db->stop_cache();
		
		if ($distinct)
			$this->db->distinct();
		
		if (!in_array('select', $this->_native_calls) && !in_array('select', $this->_cache_native_calls))
			$this->db->select("$this->table.*".($autojoin ? $this->_autojoin_fields() : '').($select ? ', '.$select : ''));
		
		if (!in_array('from', $this->_native_calls) && !in_array('from', $this->_cache_native_calls))
			$this->db->from($this->table);
		
		if ($joins)	// explicit joins from caller
			$this->_joins($joins);
		
		if ($autojoin)	// automatic join with foreign keys
		{
			$this->_autojoin();
			$this->_autojoin_fields(TRUE, $filter);	// reverse autojoin
		}
		
		if ($like)
			$this->_likes($filter);
		
		if ($filter)
			$this->db->where($filter);
		
		if ($where)
			$this->db->where($where);
		
		if ($order_by)
			$this->db->order_by($order_by);
		
		if ($page > 0 && $pagesize > 0)
			$this->db->limit($pagesize, $pagesize*($page-1));
		
		/* @var $query CI_DB_result */
		$query = $this->db->get();
		$this->_reset_native_calls();
		$rows = $query->result($this->row_type);
		
		if ($this->_is_caching)
			$this->db->start_cache();
		
		return $rows;
	}
	
	/**
	 * Get a single row
	 * 
	 * @param mixed $filter (can be id)
	 * @param string $order_by
	 * @param array $params: Extra paramters
	 */
	public function get($filter = NULL, $order_by = NULL, $params = array())
	{
		$id_field = $this->id_field;
		
		if (is_bool($filter))
			$filter = ($filter === FALSE) ? 0 : 1;
		
		if (is_numeric($filter))
			$filter = array($id_field => $filter);
		
		$rows = $this->get_list($filter, $order_by, $params);
		
		if (count($rows) > 0)
			return $rows[0];
		
		return NULL;
	}
	
	/**
	 * Return number of rows
	 * 
	 * @param mixed $filter
	 */
	public function count($filter = NULL, $params = array())
	{
		// extra params
		$autojoin = (isset($params['autojoin']) ? $params['autojoin'] : $this->_call_autojoin || $this->_cache_call_autojoin);	// perform autojoin
		$like = (isset($params['like']) ? $params['like'] : FALSE);				// use like operator if filter on string field
		$joins = (isset($params['joins']) ? $params['joins'] : FALSE);			// define JOIN clauses array or array of arrays (table, cond, type)
		$distinct = (isset($params['distinct']) ? $params['distinct'] : FALSE);	// make select DISTINCT
		$where = (isset($params['where']) ? $params['where'] : FALSE);			// add custom WHERE string
		
		if ($this->_is_caching)
			$this->db->stop_cache();
		
		if ($distinct)
			$this->db->distinct();
		
		$this->db->select("COUNT(*) AS _count");
		$this->db->from($this->table);
		
		if ($joins)	// explicit joins from caller
			$this->_joins($joins);
		
		if ($autojoin)	// automatic join with foreign keys
		{
			$this->_autojoin();
			$this->_autojoin_fields(TRUE, $filter);	// reverse autojoin
		}
		
		if ($like)
			$this->_likes($filter);
		
		if ($filter)
			$this->db->where($filter);
		
		if ($where)
			$this->db->where($where);
		
		/* @var $query CI_DB_result */
		$query = $this->db->get();
		$this->_reset_native_calls();
		$rows = $query->result();
		
		if ($this->_is_caching)
			$this->db->start_cache();
		
		if (count($rows) > 0)
			return $rows[0]->_count;
		return 0;
	}
	
	/**
	 * Insert/update row (primary key is row->id)
	 * 
	 * @param Model_object $row
	 * @return int New record ID / 0 = update
	 */
	public function save($row)
	{
		$id_field = $this->id_field;
		
		// keep only effective table fields
		$table_fields = $this->db->list_fields($this->table);
		foreach ($row as $field => $value)
		{
			if (!in_array($field, $table_fields))
				unset($row->$field);
		}
		
		// modified date
		if (in_array('modified', $table_fields))
			$row->modified = mysqldatetime(now());
		
		$new_id = 0;
		/* @var $query CI_DB_result */
		$query = $this->db->get_where($this->table, $row->pk());
		if ($query->num_rows() == 0)
		{
			// fix record with id 0 issue
			if (isset($row->$id_field) && !$row->$id_field) {
				unset($row->$id_field);
			}
			
			// created date
			if (in_array('created', $table_fields))
				$row->created = mysqldatetime(now());
			
			// insert
			$this->db->insert($this->table, $row);
			$new_id = $this->db->insert_id();
		}
		else
		{
			// update (safe: update only changed fields)
			$changes = $row->changes();
			if ($changes)
				$this->db->update($this->table, $changes, $row->pk());
		}
		
		// if new, update to assigned id
		if ($new_id)
			$row->$id_field = $new_id;
		
		// apply changes to local object to reflect updated database record
		$row->apply_changes();
		
		// return insert id (if update, return 0)
		return $new_id;
	}
	
	/**
	 * Delete row
	 * 
	 * @param mixed $filter
	 * @param bool $soft: Soft delete (NULL = auto)
	 */
	public function delete($filter, $soft = NULL)
	{
		$id_field = $this->id_field;
		
		if (!$filter)
			return;
		
		if (is_numeric($filter))
			$filter = array($id_field => $filter);
		
		if (!is_array($filter) && !is_string($filter))
			return;
		
		$this->db->trans_start();
		
		// soft delete mode automatically detected
		if ($soft === NULL)
		{
			$table_fields = $this->db->list_fields($this->table);
			$soft = in_array('deleted', $table_fields);
		}
		
		// delete or clean dependend records, to be set in the extended model
		$to_delete = $this->get_list($filter);
		foreach ($to_delete as $row)
		{
			$this->_before_delete($row, $soft);
			if ($soft)
			{
				// soft delete the record
				$this->db->update($this->table, array('deleted' => mysqldatetime(now())), $row->pk());
			}
		}
		
		if (!$soft)
		{
			// hard delete the record(s)
			$this->db->delete($this->table, $filter);
		}
		
		$this->db->trans_complete();
	}
	
	/**
	 * Get new row with default values
	 * 
	 * @return Model_object
	 */
	public function new_row()
	{
		$row = new $this->row_type();
		$fields = $this->db->field_data($this->table);
		foreach ($fields as $field)
		{
			$name = $field->name;
			if (in_array($field->type, array('int', 'decimal', 'double', 'float')))
				$row->$name = 0;
			elseif ($field->type == 'datetime')
				$row->$name = MYSQL_EMPTYDATETIME;
			elseif ($field->type == 'date')
				$row->$name = MYSQL_EMPTYDATE;
			else
				$row->$name = '';
		}
		
		return $row;
	}
	
	/**
	 * Check if table is already joind in the current query building
	 * @param string $table
	 */
	public function is_joined($table)
	{
		return in_array($table, $this->_joined_tables) || in_array($table, $this->_cache_joined_tables);
	}
	
	/**
	 * Apply explicit joins
	 * 
	 * @param array $joins
	 */
	protected function _joins($joins)
	{
		if (isset($joins['table']))
			$joins = array($joins);
		foreach ($joins as $join)
			$this->db->join($join['table'], $join['cond'], $join['type']);
	}
	
	/**
	 * Automatic joins based on field names (entity_id -> table.id)
	 */
	protected function _autojoin()
	{
		$tables = $this->_autojoin_tables();
		foreach ($tables as $table)
		{
			$entity = entity($table);
			$this->db->join($table, "$this->table.{$entity}_id = $table.id", 'LEFT');
		}
	}
	protected function _autojoin_fields($reverse = FALSE, &$filters = array())
	{
		$id_field = $this->id_field;
		
		$select = '';
		$tables = $this->_autojoin_tables();
		foreach ($tables as $table)
		{
			$entity = entity($table);
			$fields = $this->db->list_fields($table);
			foreach ($fields as $field)
			{
				if (!$reverse)	// entities.field becomes entity_field
				{
					if ($field != $id_field)
						$select .= ", $table.$field AS {$entity}_$field";
				}
				elseif (is_array($filters)) 	// entity_field becomes entities.field
				{
					if (isset($filters["{$entity}_$field"]))
					{
						$filters["$table.$field"] = $filters["{$entity}_$field"];
						unset($filters["{$entity}_$field"]);
					}
				}
			}
		}
		// avoid join field name collisions
		if ($reverse && is_array($filters))
		{
			foreach ($filters as $field => $filter)
			{
				if (strpos($field, '.') === FALSE)
				{
					$filters[$this->table.'.'.$field] = $filter;
					unset($filters[$field]);
				}
			}
		}
		
		return $select;
	}
	private function _autojoin_tables()
	{
		$tables = array();
		$fields = $this->db->list_fields($this->table);
		foreach ($fields as $field)
		{
			if (substr($field, -3) == '_id')
			{
				$entity = substr($field, 0, strlen($field) - 3);
				$table = table($entity);	// table to join
				if ($this->db->table_exists($table))
				{
					$tables[] = $table;
				}
			}
		}
		return $tables;
	}
	
	/**
	 * Likes for strings, and clean $filters array from filters already used with like operator
	 * 
	 * @param array $filters
	 */
	protected function _likes(&$filters)
	{
		$id_field = $this->id_field;
		
		$field_data = $this->db->field_data($this->table);
		foreach ($this->_autojoin_tables() as $table)	// consider also auto join fields
		{
			$autojoin_fields = $this->db->field_data($table);
			for ($i = 0; $i < count($autojoin_fields); $i++)
				if ($autojoin_fields[$i]->name != $id_field)
				{
					$autojoin_fields[$i]->name = $table.'.'.$autojoin_fields[$i]->name;
					$field_data[] = $autojoin_fields[$i];
				}
		}
		
		foreach ($field_data as $field)
		{
			if (($field->type == 'varchar' || $field->type == 'text') && !$this->_likes_except($field))
			{
				$field_name = isset($filters[$field->name]) ? $field->name : $this->table.'.'.$field->name;
				if (isset($filters[$field_name]))
				{
					// like
					$this->db->like($field_name, $filters[$field_name]);
					unset($filters[$field_name]);
				}
			}
		}
	}
	
	/**
	 * Auto Like exception
	 * 
	 * @param object $field
	 */
	protected function _likes_except($field)
	{
		if (!$field->max_length)
			return FALSE;
		
		return $field->max_length <= 20;	// is a key/code string
	}
	
	/**
	 * Reset db native calls array after get()
	 */
	protected function _reset_native_calls()
	{
		$this->_native_calls = array();
		$this->_call_autojoin = FALSE;
		$this->_joined_tables = array();
	}
	
	/*
	 * Can be overridden to modify base model actions
	 */
	protected function _before_delete($row, $soft)
	{
	}
}


class Model_object {
	
	protected $_model = '';	// protected to be skipped while listing db fields
	protected $_old = array();	// old values (track changes for safe update)
	protected $_force_changes = FALSE;	// force all fields to the changed status
	
	public function __construct()
	{
		/* @var $CI MY_Controller */
		$CI = &get_instance();
		//$CI->load->helper('application');
		
		if (!$this->_model)
			$this->_model = model(str_replace('_object', '', get_class($this)));
	}
	
	public function __set($name, $value)
	{
		// field initialization (implement safe update support)
		$this->$name = $value;
		if (!isset($this->_old[$name])) $this->_old[$name] = $value;
	}
	
	public function __call($name, $arguments)
	{
		/* @var $CI MY_Controller */
		$CI = &get_instance();
		//$CI->load->helper('application');
		
		// implements undefined get_<entity>()
		if (substr($name, 0, 4) == 'get_')
		{
			$entity = substr($name, 4);
			$field = $entity.'_id';
			$model = model($entity);
			
			return $CI->$model->get($this->$field);
		}
		else
		{
			trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);
		}
	}
	
	/**
	 * Return an array to get this record by its primary key
	 */
	public function pk()
	{
		/* @var $CI MY_Controller */
		$CI = &get_instance();
		$model = $this->_model;
		$table = $CI->$model->table;
		
		$pk = array();
		$fields = $CI->db->field_data($table);
		foreach ($fields as $field)
		{
			$fname = $field->name;
			if ($field->primary_key)
				$pk[$fname] = isset($this->$fname) ? $this->$fname : 0;
		}
		
		return $pk;
	}
	
	/**
	 * Return relative model class
	 */
	public function model()
	{
		return $this->_model;
	}
	
	/**
	 * Merge new post data in object
	 * @param array $post
	 */
	public function merge($post)
	{
		if ($post)
		{
			foreach ($post as $key => $value)
			{
				$this->$key = $value;
			}
		}
		
		return $this;
	}
	
	/**
	 * Return only changed fields
	 * @return array
	 */
	public function changes()
	{
		$changes = array();
		$reflect = new ReflectionObject($this);	// loop only public properties (db fields)
		
		foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $prop)
		{
			$key = $prop->getName();
			if (isset($this->_old[$key]) && $this->$key !== $this->_old[$key] || $this->_force_changes)
				$changes[$key] = $this->$key;
		}
		
		return $changes;
	}
	
	/**
	 * Apply changes to _old array (called by model after saved)
	 */
	public function apply_changes()
	{
		$reflect = new ReflectionObject($this);	// loop only public properties (db fields)
		foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $prop)
		{
			$key = $prop->getName();
			$this->_old[$key] = $this->$key;
		}
	}
	
	/**
	 * Cancel changes back to the original values
	 */
	public function reset_changes()
	{
		foreach ($this->_old as $key => $value)
		{
			$this->$key = $value;
		}
	}
	
	/**
	 * Force all fields to the changed status
	 * @param bool $force
	 */
	public function force_changes($force = TRUE)
	{
		$this->_force_changes = $force;
	}
	
	/**
	 * Shortcut to parent model save function
	 */
	public function save()
	{
		/* @var $CI MY_Controller */
		$CI = &get_instance();
		$model = $this->_model;
		
		return $CI->$model->save($this);
	}
	
	/**
	 * Shortcut to parent model delete function
	 * @param bool $soft
	 */
	public function delete($soft = NULL)
	{
		/* @var $CI MY_Controller */
		$CI = &get_instance();
		$model = $this->_model;
		
		return $CI->$model->delete($this->pk(), $soft);
	}
}

// For standalone distrubution
if (!defined('MYSQL_EMPTYDATE')) define('MYSQL_EMPTYDATE', '0000-00-00');
if (!defined('MYSQL_EMPTYDATETIME')) define('MYSQL_EMPTYDATETIME', '0000-00-00 00:00:00');
if (!function_exists('mysqldatetime'))
{
	function mysqldatetime($timestamp)
	{
		return date('Y-m-d H:i:s', $timestamp);
	}
}
if (!function_exists('model'))
{
	function model($entity)
	{
		get_instance()->load->helper('inflector');
		return ucfirst(plural($entity));
	}
}
if (!function_exists('table'))
{
	function table($entity)
	{
		get_instance()->load->helper('inflector');
		return plural($entity);
	}
}
if (!function_exists('entity'))
{
	function entity($table)
	{
		get_instance()->load->helper('inflector');
		return singular($table);
	}
}