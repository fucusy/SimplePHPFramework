<?php

abstract class CActiveRecord
{


	public static $db;
	private static $_md=array();				// class name => meta data
	private static $_models=array();			// class name => model


	private $_c;								// query criteria (used by finder only)
	private $_alias='t';						// the table alias being used for query
	/**
	 * Returns the database connection used by active record.
	 * By default, the "db" application component is used as the database connection.
	 * You may override this method if you want to use a different database connection.
	 * @return CDbConnection the database connection used by active record.
	 */
	public function getDbConnection()
	{
		if(self::$db!==null)
			return self::$db;
		else
		{
			self::$db = new CDbConnection("mysql:host=localhost;dbname=bankweb"
									,"root","");
			if(self::$db instanceof CDbConnection)
				return self::$db;
			else
				throw new CDbException('Active Record requires a "db" CDbConnection application component.');
		}
	}

	/**
	 * Returns the meta-data for this AR
	 * @return CActiveRecordMetaData the meta for this AR class.
	 */
	public function getMetaData()
	{
		$className=get_class($this);
		if(!array_key_exists($className,self::$_md))
		{
			self::$_md[$className]=null; // preventing recursive invokes of {@link getMetaData()} via {@link __get()}
			self::$_md[$className]=new CActiveRecordMetaData($this);
		}
		return self::$_md[$className];
	}

	public static function model($className=__CLASS__)
	{
		if(isset(self::$_models[$className]))
			return self::$_models[$className];
		else
		{
			$model=self::$_models[$className]=new $className(null);
			return $model;
		}
	}

	public function findAll()
	{
		return $this->query(null, true);
	}

	/**
	 * Creates a list of active records based on the input data.
	 * This method is internally used by the find methods.
	 * @param array $data list of attribute values for the active records.
	 * @param boolean $callAfterFind whether to call {@link afterFind} after each record is populated.
	 * @param string $index the name of the attribute whose value will be used as indexes of the query result array.
	 * If null, it means the array will be indexed by zero-based integers.
	 * @return CActiveRecord[] list of active records.
	 */
	public function populateRecords($data,$callAfterFind=true,$index=null)
	{

		$records=array();
		foreach($data as $attributes)
		{
			if(($record=$this->populateRecord($attributes,$callAfterFind))!==null)
			{
				if($index===null)
					$records[]=$record;
				else
					$records[$record->$index]=$record;
			}
		}
		return $records;
	}


	/**
	 * Creates an active record instance.
	 * This method is called by {@link populateRecord} and {@link populateRecords}.
	 * You may override this method if the instance being created
	 * depends the attributes that are to be populated to the record.
	 * For example, by creating a record based on the value of a column,
	 * you may implement the so-called single-table inheritance mapping.
	 * @param array $attributes list of attribute values for the active records.
	 * @return CActiveRecord the active record
	 */
	protected function instantiate($attributes)
	{
		$class=get_class($this);
		$model=new $class(null);
		return $model;
	}


	/**
	 * Initializes this model.
	 * This method is invoked when an AR instance is newly created and has
	 * its {@link scenario} set.
	 * You may override this method to provide code that is needed to initialize the model (e.g. setting
	 * initial property values.)
	 */
	public function init()
	{
	}


	/**
	 * Returns the primary key value.
	 * @return mixed the primary key value. An array (column name=>column value) is returned if the primary key is composite.
	 * If primary key is not defined, null will be returned.
	 */
	public function getPrimaryKey()
	{
		$table=$this->getMetaData()->tableSchema;
		if(is_string($table->primaryKey))
			return $this->{$table->primaryKey};
		elseif(is_array($table->primaryKey))
		{
			$values=array();
			foreach($table->primaryKey as $name)
				$values[$name]=$this->$name;
			return $values;
		}
		else
			return null;
	}

	/**
	 * Creates an active record with the given attributes.
	 * This method is internally used by the find methods.
	 * @param array $attributes attribute values (column name=>column value)
	 * @param boolean $callAfterFind whether to call {@link afterFind} after the record is populated.
	 * @return CActiveRecord the newly created active record. The class of the object is the same as the model class.
	 * Null is returned if the input data is false.
	 */
	public function populateRecord($attributes,$callAfterFind=true)
	{
		if($attributes!==false)
		{
			$record=$this->instantiate($attributes);
			//$record->setScenario('update');
			$record->init();
			$md=$record->getMetaData();
			foreach($attributes as $name=>$value)
			{
				if(property_exists($record,$name))
					$record->$name=$value;
				elseif(isset($md->columns[$name]))
					$record->_attributes[$name]=$value;
			}
			$record->_pk=$record->getPrimaryKey();
			//$record->attachBehaviors($record->behaviors());
			// if($callAfterFind)
			// 	$record->afterFind();
			return $record;
		}
		else
			return null;
	}

	/**
	 * Performs the actual DB query and populates the AR objects with the query result.
	 * This method is mainly internally used by other AR query methods.
	 * @param CDbCriteria $criteria the query criteria
	 * @param boolean $all whether to return all data
	 * @return mixed the AR objects populated with the query result
	 * @since 1.1.7
	 */
	protected function query($criteria,$all=false)
	{
		
		if(empty($criteria->with))
		{
			if(!$all)
				$criteria->limit=1;
			$command=$this->getCommandBuilder()->createFindCommand($this->getTableSchema(),$criteria,$this->getTableAlias());

			

			return $all ? $this->populateRecords($command->queryAll(), true, $criteria->index) : $this->populateRecord($command->queryRow());
		}
		else
		{
			$finder=$this->getActiveFinder($criteria->with);
			return $finder->query($criteria,$all);
		}
	}

	/**
	 * Returns the table alias to be used by the find methods.
	 * In relational queries, the returned table alias may vary according to
	 * the corresponding relation declaration. Also, the default table alias
	 * set by {@link setTableAlias} may be overridden by the applied scopes.
	 * @param boolean $quote whether to quote the alias name
	 * @param boolean $checkScopes whether to check if a table alias is defined in the applied scopes so far.
	 * This parameter must be set false when calling this method in {@link defaultScope}.
	 * An infinite loop would be formed otherwise.
	 * @return string the default table alias
	 * @since 1.1.1
	 */
	public function getTableAlias($quote=false, $checkScopes=true)
	{
		if($checkScopes && ($criteria=$this->getDbCriteria(false))!==null && $criteria->alias!='')
			$alias=$criteria->alias;
		else
			$alias=$this->_alias;
		return $quote ? $this->getDbConnection()->getSchema()->quoteTableName($alias) : $alias;
	}

	/**
	 * Returns the query criteria associated with this model.
	 * @param boolean $createIfNull whether to create a criteria instance if it does not exist. Defaults to true.
	 * @return CDbCriteria the query criteria that is associated with this model.
	 * This criteria is mainly used by {@link scopes named scope} feature to accumulate
	 * different criteria specifications.
	 */
	public function getDbCriteria($createIfNull=true)
	{
		if($this->_c===null)
		{
			if(($c=$this->defaultScope())!==array() || $createIfNull)
				$this->_c=new CDbCriteria($c);
		}
		return $this->_c;
	}

		/**
	 * Returns the default named scope that should be implicitly applied to all queries for this model.
	 * Note, default scope only applies to SELECT queries. It is ignored for INSERT, UPDATE and DELETE queries.
	 * The default implementation simply returns an empty array. You may override this method
	 * if the model needs to be queried with some default criteria (e.g. only active records should be returned).
	 * @return array the query criteria. This will be used as the parameter to the constructor
	 * of {@link CDbCriteria}.
	 */
	public function defaultScope()
	{
		return array();
	}

	public function getCommandBuilder()
	{
		return $this->getDbConnection()->getSchema()->getCommandBuilder();
	}
	
	/**
	 * Returns the metadata of the table that this AR belongs to
	 * @return CDbTableSchema the metadata of the table that this AR belongs to
	 */
	public function getTableSchema()
	{
		return $this->getMetaData()->tableSchema;
	}



}


/**
 * CActiveRecordMetaData represents the meta-data for an Active Record class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.db.ar
 * @since 1.0
 */
class CActiveRecordMetaData
{
	/**
	 * @var CDbTableSchema the table schema information
	 */
	public $tableSchema;
	/**
	 * @var array table columns
	 */
	public $columns;
	/**
	 * @var array list of relations
	 */
	public $relations=array();
	/**
	 * @var array attribute default values
	 */
	public $attributeDefaults=array();

	private $_modelClassName;

	/**
	 * Constructor.
	 * @param CActiveRecord $model the model instance
	 * @throws CDbException if specified table for active record class cannot be found in the database
	 */
	public function __construct($model)
	{
		$this->_modelClassName=get_class($model);

		$tableName=$model->tableName();
		if(($table=$model->getDbConnection()->getSchema()->getTable($tableName))===null)
			throw new CDbException(Yii::t('yii','The table "{table}" for active record class "{class}" cannot be found in the database.',
				array('{class}'=>$this->_modelClassName,'{table}'=>$tableName)));


		if($table->primaryKey===null)
		{
			$table->primaryKey=$model->primaryKey();
			if(is_string($table->primaryKey) && isset($table->columns[$table->primaryKey]))
				$table->columns[$table->primaryKey]->isPrimaryKey=true;
			elseif(is_array($table->primaryKey))
			{
				foreach($table->primaryKey as $name)
				{
					if(isset($table->columns[$name]))
						$table->columns[$name]->isPrimaryKey=true;
				}
			}
		}
		$this->tableSchema=$table;
		$this->columns=$table->columns;

		foreach($table->columns as $name=>$column)
		{
			if(!$column->isPrimaryKey && $column->defaultValue!==null)
				$this->attributeDefaults[$name]=$column->defaultValue;
		}

		foreach($model->relations() as $name=>$config)
		{
			$this->addRelation($name,$config);
		}
	}

	/**
	 * Returns the primary key of the associated database table.
	 * This method is meant to be overridden in case when the table is not defined with a primary key
	 * (for some legency database). If the table is already defined with a primary key,
	 * you do not need to override this method. The default implementation simply returns null,
	 * meaning using the primary key defined in the database.
	 * @return mixed the primary key of the associated database table.
	 * If the key is a single column, it should return the column name;
	 * If the key is a composite one consisting of several columns, it should
	 * return the array of the key column names.
	 */
	public function primaryKey()
	{
	}
	/**
	 * Adds a relation.
	 *
	 * $config is an array with three elements:
	 * relation type, the related active record class and the foreign key.
	 *
	 * @throws CDbException
	 * @param string $name $name Name of the relation.
	 * @param array $config $config Relation parameters.
	 * @return void
	 * @since 1.1.2
	 */
	public function addRelation($name,$config)
	{
		if(isset($config[0],$config[1],$config[2]))  // relation class, AR class, FK
			$this->relations[$name]=new $config[0]($name,$config[1],$config[2],array_slice($config,3));
		else
			throw new CDbException(Yii::t('yii','Active record "{class}" has an invalid configuration for relation "{relation}". It must specify the relation type, the related active record class and the foreign key.', array('{class}'=>$this->_modelClassName,'{relation}'=>$name)));
	}

	/**
	 * Checks if there is a relation with specified name defined.
	 *
	 * @param string $name $name Name of the relation.
	 * @return boolean
	 * @since 1.1.2
	 */
	public function hasRelation($name)
	{
		return isset($this->relations[$name]);
	}

	/**
	 * Deletes a relation with specified name.
	 *
	 * @param string $name $name
	 * @return void
	 * @since 1.1.2
	 */
	public function removeRelation($name)
	{
		unset($this->relations[$name]);
	} 
}