<?php
namespace FluentCQL;

class Query{

	/**
	 *
	 * @var \Cassandra\Connection
	 */
	protected $_dbAdapter;
	
	/**
	 * Bind variables for query
	 *
	 * @var array
	 */
	protected $_bind = [];

	/**
	 *
	 * @var array
	 */
	protected $_segments = [];
	
	/**
	 * 
	 * @var int
	 */
	protected $_consistency;
	
	/**
	 * 
	 * @var array
	 */
	protected $_options = [];

    protected $_profileStartTime;


	/**
	 * Class constructor
	 *
	 * @param \Cassandra\Connection $adapter
	 */
	public function __construct($adapter = null){
		$this->_dbAdapter = $adapter;
	}
	
	/**
	 * Get bind variables
	 *
	 * @return array
	 */
	public function getBind()
	{
		return $this->_bind;
	}
	
	/**
	 * Set bind variables
	 *
	 * @param mixed $bind
	 * @return self
	 */
	public function bind($bind)
	{
		$this->_bind = $bind;
	
		return $this;
	}
	
	/**
	 * 
	 * @param int $consistency
	 * @return self
	 */
	public function setConsistency($consistency){
		$this->_consistency = $consistency;
		
		return $this;
	}
	
	/**
	 * 
	 * @param array $options
	 * @return self
	 */
	public function setOptions(array $options){
		$this->_options = $options + $this->_options;
		
		return $this;
	}
	
	/**
	 * 
	 * @param \Cassandra\Connection $adapter
	 * @return self
	 */
	public function setDbAdapter($adapter){
		$this->_dbAdapter = $adapter;
		return $this;
	}

    protected  function profileStart($info,$options)
    {
        if(Table::getProfiler()){
            $this->_profileStartTime = microtime(true);
            Table::getProfiler()->info($info,$options);
        }
    }

    protected  function profileEnd($info,$options)
    {
        if(Table::getProfiler()){
            $options['duration']= microtime(true)-$this->_profileStartTime;
            Table::getProfiler()->info($info,$options);
        }
    }


	/**
	 * Executes the current query and returns the response
	 *
	 * @throws \Cassandra\Response\Exception
	 * @return \Cassandra\Response
	 */
	public function querySync(){
        $this->profileStart('querySync:'.$this->assemble(),['bind'=>$this->_bind,'consistency'=>$this->_consistency,'options'=>$this->_options]);

		$adapter = $this->_dbAdapter ?: Table::getDefaultDbAdapter();

		$ret = $adapter->querySync($this->assemble(), $this->_bind, $this->_consistency, $this->_options);
        $this->profileEnd('querySyncEnd:',[]);
        return $ret;
	}
	
	/**
	 * Executes the current query and returns the statement
	 * 
	 * @return \Cassandra\Statement
	 */
	public function queryAsync(){
        $this->profileStart('queryAsync:'.$this->assemble(),['bind'=>$this->_bind,'consistency'=>$this->_consistency,'options'=>$this->_options]);
		$adapter = $this->_dbAdapter ?: Table::getDefaultDbAdapter();
	
		$ret = $adapter->queryAsync($this->assemble(), $this->_bind, $this->_consistency, $this->_options);
        $this->profileEnd('queryAsyncEnd:',[]);
        return $ret;
	}

	/**
	 * Prepares the current query and returns the response
	 *
	 * @throws \Cassandra\Response\Exception
	 * @return \Cassandra\Result
	 */
	public function prepare(){
        $this->profileStart('prepare:'.$this->assemble(),[]);
		$adapter = $this->_dbAdapter ?: Table::getDefaultDbAdapter();
	
		$ret = $adapter->prepare($this->assemble());
        $this->profileEnd('prepareEnd:',[]);
        return $ret;
	}
	
	/**
	 * Converts this object to an CQL string.
	 *
	 * @return string|null This object as a SELECT string. (or null if a string cannot be produced.)
	 */
	public function assemble(){
		return implode(' ', $this->_segments);
	}
	
	/**
	 * Implements magic method.
	 *
	 * @return string This object as a SELECT string.
	 */
	public function __toString()
	{
		return $this->assemble();
	}
	
	/**
	 * 向segments列表中追加CQL片段
	 * 
	 * @param string $command
	 * @param array $args
	 * @return self
	 */
	public function _appendClause($command, array $args = []){
		if (!empty($command)){
			$this->_segments[] = $command;
		}
		
		if (!empty($args)){
			$this->_segments[] = array_shift($args);
			
			foreach($args as $arg){
				if (is_array($arg)){
					foreach($arg as $subArg)
						$this->_bind[] = $subArg;
				} else {
					$this->_bind[] = $arg;
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param array $arguments
	 * @return self
	 */
	public static function __callStatic($name, array $args = []){
		$command = \strtoupper(\implode(' ', preg_split('/([[:upper:]][[:lower:]]+)/', $name, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY)));
		return (new self())->_appendClause($command, $args);
	}
	
	public static function alter(){
		return (new self())->_appendClause('ALTER', func_get_args());
	}
	
	public static function create(){
		return (new self())->_appendClause('CREATE', func_get_args());
	}
	
	public static function delete(){
		return (new self())->_appendClause('DELETE', func_get_args());
	}
	
	public static function drop(){
		return (new self())->_appendClause('DROP', func_get_args());
	}
	
	public static function insertInto(){
		return (new self())->_appendClause('INSERT INTO', func_get_args());
	}
	
	public static function grant(){
		return (new self())->_appendClause('GRANT', func_get_args());
	}
	
	public static function listQuery(){
		return (new self())->_appendClause('LIST', func_get_args());
	}
	
	public static function revoke(){
		return (new self())->_appendClause('REVOKE', func_get_args());
	}
	
	public static function select(){
		return (new self())->_appendClause('SELECT', func_get_args());
	}
	
	public static function truncate(){
		return (new self())->_appendClause('TRUNCATE', func_get_args());
	}
	
	public static function update(){
		return (new self())->_appendClause('UPDATE', func_get_args());
	}
	
	public static function useQuery(){
		return (new self())->_appendClause('USE', func_get_args());
	}
	
	/**
	 * 
	 * @param string $name
	 * @param array $arguments
	 * @return self
	 */
	public function __call($name, array $arguments){
		$command = \strtoupper(\implode(' ', preg_split('/([[:upper:]][[:lower:]]+)/', $name, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY)));
		return $this->_appendClause($command, $arguments);
	}
	
	public function clause(){
		return $this->_appendClause('', func_get_args());
	}
	
	public function from(){
		return $this->_appendClause('FROM', func_get_args());
	}
	
	public function where(){
		return $this->_appendClause('WHERE', func_get_args());
	}
	
	public function andClause(){
		return $this->_appendClause('AND', func_get_args());
	}
	
	public function set(){
		return $this->_appendClause('SET', func_get_args());
	}
	
	public function ifClause(){
		return $this->_appendClause('IF', func_get_args());
	}
	
	public function ifExists(){
		return $this->_appendClause('IF EXISTS', func_get_args());
	}
	
	public function ifNotExists(){
		return $this->_appendClause('IF NOT EXISTS', func_get_args());
	}
}
