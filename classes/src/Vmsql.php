<?php

/**
 * LIBRERIA SQL per PDO con gestione errori ed altre utility
 * 
 * @package VFront
 * @subpackage DB-Libraries
 * @author Mario Marcello Verona <marcelloverona@gmail.com>
 * @copyright 2007-2010 M.Marcello Verona
 * @version 0.96 $Id: vmsql.mysqli.php 942 2011-03-31 11:20:28Z marciuz $
 * @see vmsql.postgres.php
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License
 */
class Vmsql {
    
    const E_SQL_ERROR = 1501;
    const E_SQL_DOWN = 2801;
    const E_SQL_STATEMENT_ERROR = 2802;
    
    const DBTYPE_MYSQL = 'mysql';
    const DBTYPE_POSTGRES = 'pgsql';
    const DBTYPE_SQLITE = 'sqlite';
    const DBTYPE_ORACLE = 'oci';
    
    const FETCH_ASSOC = 'assoc';
    const FETCH_ROW = 'row';
    const FETCH_OBJECT = 'object';
    const FETCH_CLASS = 'class';

    const FILE_ERRORLOG_DB = FRONT_ROOT.'/log/vmsql-error.log';
    
    public $vmsqltype = 'pdo';
    public $link_db = null;
    protected $transaction_is_open = false;
    protected $connected;
    protected $error_handler = null;
    protected $last_error = null;
    protected $T = array();
    protected $stmt;
    protected $PDO;
    
    static protected $_instance;
    
    private $dbtype = 'pgsql';
    
    private $DEBUG = false;
    
    /**
     * 
     * @return Vmsql
     */
    public static function init($connection_id=0) {

        if (!isset(self::$_instance[$connection_id])) {
            self::$_instance[$connection_id] = new self($connection_id);
        }
        return self::$_instance[$connection_id];
    }
    
    protected function __construct() {
        $this->DEBUG = $GLOBALS['DEBUG_SQL'] ?? false;
    }
    
    public static function close_connection($connection_id=0){
        self::$_instance[$connection_id] = null;
    }
    
    public static function reconnect($db, $connection_id=0) {
        
        self::close_connection($connection_id);
        (self::init($connection_id))->connect($db, 'UTF8');
    }

    public function connect($array_db, $charset='UTF8') {

        $dsn = (isset($array_db['dsn'])) ? $array_db['dsn']
                : $array_db['dbtype'] . ":dbname={$array_db['dbname']};host={$array_db['host']}";

        if (isset($array_db['port']) && $array_db['port'] != '') {
            $dsn .= ";port={$array_db['port']}";
        }
        
        $dsn .= ';charset='.strtolower($charset);

        try {
            $this->PDO = new \PDO($dsn, $array_db['user'], $array_db['passw']);
            $this->PDO->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            if ($this->PDO) {
                $this->connected = true;
            } else {
                throw new DbException(
                "Connection error: is database running? Otherwise please check your conf file.", self::E_SQL_DOWN);
            }
        } catch (DbException $e) {
            $e->setLog(\Monolog\Logger::EMERGENCY);
            exit;
        }
    }

    /**
     * Exec the query 
     * 
     * @param string $sql
     * @param array $params
     * @return boolean
     */
    public function query($sql, $params = array()) {

        $getmicro = microtime(true);
        
        if (trim($sql) == '') {
            return false;
        }
        
        try {
            
            if(count($params) > 0){
            
                $this->stmt = $this->PDO->prepare($sql);

                if(empty($this->stmt)) {

                    $message = 'Cannot query: ' . $sql;
                    $excp = new DbException('Query error', 
                            self::E_SQL_STATEMENT_ERROR, 
                            ['sql'=>$message, 'params'=>$params]
                    );
                    throw $excp;
                }

                $this->stmt->execute((array) $params);
            
            }
            else{
                $this->stmt = $this->PDO->query($sql);
            }
            
            $this->log_query_debug($sql, $getmicro, $params);
            
        }
        catch (PDOException $ee){
            
            $log = new \Monolog\Logger('db_log');
            $log->pushHandler(new \Monolog\Handler\StreamHandler(self::FILE_ERRORLOG_DB));
            $log->info('message: '.$ee->getMessage());
            $log->info('details: '. json_encode(['sql'=>$sql, 'params'=>$params]));

            $details = array(
                'sql'=>$sql,
                'params' => $params,
                'errno'=> $ee->getMessage(),
            );

            try{

                throw new DbException('Errore nella query SQL', self::E_SQL_ERROR, $details);
            }
            catch (DbException $e){

                $e->additional_details = array(
                    'sql'=>$sql,
                    'params' => $params,
                    'errno'=> $ee->getMessage(),
                );
                $e->enable_backtrace();
                $e->setLog(\Monolog\Logger::ERROR);
                $e->redirect(self::E_SQL_ERROR);
            }
        }
        

        return $this->stmt;
    }
    
    /**
     * @desc Funzione di fetch_row
     * @return array
     * @param resource $res
     */
    public function fetch(&$res, $method, $classname=null) {

       if($method == self::FETCH_ASSOC){
           return $this->fetch_assoc($res);
       }
       else if($method == self::FETCH_OBJECT){
           return $this->fetch_object($res);
       }
       else if($method == self::FETCH_CLASS){
           return $this->fetch_object($res, $classname);
       }
       else if($method == self::FETCH_ROW){
           return $this->fetch_row($res);
       }
       else{
           return false;
       }
    }

    /**
     * @desc Funzione di fetch_row
     * @return array
     * @param resource $res
     */
    public function fetch_row(&$res) {

        if (is_object($res)) {

            $RS = $res->fetch(PDO::FETCH_NUM);
            if ($RS){
                return $RS;
            }
            else {
                return false;
            }
        }
    }

    /**
     * @desc Funzione di fetch_assoc
     * @return array
     * @param resource $res
     */
    public function fetch_assoc(&$res) {

        if (is_object($res)) {

            $RS = $res->fetch(PDO::FETCH_ASSOC);
            if ($RS)
                return $RS;
            else
                return false;
        }
    }

    /**
     * @desc Funzione di fetch_array
     * @return array
     * @param resource $res
     */
    public function fetch_array(&$res) {

        if (is_object($res)) {

            $RS = $res->fetch(PDO::FETCH_BOTH);
            if ($RS)
                return $RS;
            else
                return false;
        }
    }

    /**
     * @desc Funzione di fetch_object
     * @return object
     * @param resource $res
     */
    public function fetch_object(&$res, $class_name = null) {

        if (is_object($res)) {

            return ($class_name === null) 
                ? $this->_fetch_object($res) 
                : $this->_fetch_class($res, $class_name);
        }
    }
    
    /**
     * Map the result as a new $class_name.
     * 
     * @param object $res
     * @param string $class_name
     * @return object of type $class_name.
     */
    private function _fetch_class(&$res, $class_name) {
        
        $res->setFetchMode(PDO::FETCH_CLASS, $class_name);
        return $res->fetch() ?? false;
    }
    
    /**
     * Map the result as a stdClass.
     * 
     * @param object $res
     * @return stdClass
     */
    private function _fetch_object(&$res) {
        $RS = $res->fetch(PDO::FETCH_OBJ);
        return $RS ?? false;
    }

    /**
     * @desc Funzione di num_rows
     * @return array
     * @param resource $res
     */
    public function num_rows(&$res) {

        if (is_object($res)) {
            return $res->rowCount();
        } else {
            return null;
        }
    }

    /**
     * @desc Funzione di affected_rows
     * @return int
     */
    public function affected_rows($query) {

        return $query->rowCount();
    }

    /**
     * @desc  Funzione di insert ID che restituisce l'ultimo ID autoincrement inserito (MySQL)
     * @param resource $name
     * @return int
     */
    public function insert_id($name = null) {
        
        $name = is_string($name) ? $name : null;

        if (is_object($this->PDO)) {
            return $this->PDO->lastInsertId($name);
        } else {
            return null;
        }
    }

    /**
     * Get DB Version (in SQL)
     * @return string
     */
    public function db_version() {

        $q = $this->query("SELECT VERSION()");
        list($db_version) = $this->fetch_row($q);
        return $db_version;
    }

    /**
     * Get single value
     * 
     * @param string $sql
     * @return string
     */
    public function get_value($sql, $parameters = array()) {

        $q = $this->query($sql, $parameters);

        if ($this->num_rows($q) > 0) {
            return $q->fetchColumn(0);
        } else {
            return null;
        }
    }

    /**
     * Alias of get_value
     * 
     * @param string $sql
     * @return string
     */
    public function get_item($sql, $parameters = array()) {
        return $this->get_value($sql, $parameters);
    }

    /**
     * 
     * @param int $method Fetch style
     * @param PDOStatement $res
     * @param type $reverse
     * @param fetch_argument $class_name
     * @return type
     */
    private function fetch_all($method, &$res, $reverse = false, $class_name=null) {

        $matrice = array();
        
        if($method == \PDO::FETCH_CLASS && $class_name!=null) {
            $class_destination = $class_name;
        }
        else{
            $class_destination = null;
        }

        if (is_object($res)) {
            
            $matrice = ($class_destination!==null) 
                    ? $res->fetchAll($method, $class_destination) 
                    : $res->fetchAll($method);
                    
            if ($reverse)
                return $this->reverse_matrix($matrice);
            else
                return $matrice;
        }
    }

    /**
     * @return resource
     * @param resource $res
     * @desc Funzione utility di fetch_row che restituisce tutta la matrice dei risultati
     */
    public function fetch_row_all(&$res, $reverse = false) {

        return $this->fetch_all(\PDO::FETCH_NUM, $res, $reverse);
    }

    /**
     * @return resource
     * @param resource $res
     * @desc Funzione utility di fetch_row che restituisce tutta la matrice dei risultati
     */
    public function fetch_assoc_all(&$res, $reverse = false) {

        return $this->fetch_all(\PDO::FETCH_ASSOC, $res, $reverse);
    }

    /**
     * @return resource
     * @param resource $res
     * @desc Funzione utility di fetch_row che restituisce tutta la matrice dei risultati
     */
    public function fetch_object_all(&$res, $reverse = false) {

        return $this->fetch_all(\PDO::FETCH_OBJ, $res, $reverse);
    }

    /**
     * @return resource
     * @param resource $res
     * @desc Funzione utility di fetch_row che restituisce tutta la matrice dei risultati
     */
    public function fetch_class_all(&$res, $class_name) {
        
        return $this->fetch_all(\PDO::FETCH_CLASS, $res, false, $class_name);
    }

    /**
     * Prende tutta la matrice da SQL
     * 
     * @param string $sql
     * @param array $pars Parametri prepared stmt
     * @param string $type Tipo di risultato: assoc|row|object
     * @param bool $reverse
     * @param $class_name nome della classe per tipo FETCH_CLASS
     * @return mixed Array|stdClass|$class_name
     */
    public function get($sql, $pars=array(), $type = '', $reverse = false, $class_name = null) {

        $q = $this->query($sql, $pars);

        switch ($type) {

            case self::FETCH_ROW : $mat = $this->fetch_row_all($q, $reverse);
                break;

            case self::FETCH_OBJECT : $mat = $this->fetch_object_all($q);
                break;

            case self::FETCH_CLASS : $mat = $this->fetch_class_all($q, $class_name);
                break;

            case self::FETCH_ASSOC :
            default :
                $mat = $this->fetch_assoc_all($q, $reverse);
        }

        return $mat;
    }
    
    /**
     * Get a specific row from vmsql->get.
     * 
     * @param string $sql
     * @param array $pars
     * @param int $i The column number (by default 0, the first one).
     * @return array
     */
    public function get_row($sql, $pars=[], $i=0, $type=Vmsql::FETCH_ASSOC) {
        
        $data = $this->get($sql, $pars, $type);
        return (isset($data[$i])) ? $data[$i] : array();
    }
    
    /**
     * Get a specific row from vmsql->get.
     * 
     * @param string $sql
     * @param array $pars
     * @param int $i The column number (by default 0, the first one).
     * @return array
     */
    public function get_column($sql, $pars=[], $i=0) {
        
        $q = $this->query($sql, $pars);

        if (is_object($q)) {
            return $q->fetchAll(PDO::FETCH_COLUMN, $i);
        }
        else{
            return [];
        }
        
    }
    
    public function get_keyvalue($sql, $pars=[]) {
        
        $mat2 = [];
        
        $q = $this->query($sql, $pars);
        
        if (is_object($q)) {
            $mat = $this->fetch_row_all($q);

            foreach($mat as $k=>$v) {
                $mat2[$v[0]] = $v[1];
            }

            return $mat2;
        }
        else{
            
            return [];
        }
    }


    /**
     * @return  matrix
     * @param matrix $matrix
     * @desc restituisce una traslata della matrice partendo da indici numerici
     */
    public function reverse_matrix($matrix) {

        if (!is_array($matrix) || count($matrix) == 0)
            return false;
        $keys = array_keys($matrix[0]);

        for ($i = 0; $i < count($matrix); $i++) {
            for ($j = 0; $j < count($keys); $j++)
                $rev[$keys[$j]][$i] = $matrix[$i][$keys[$j]];
        }
        return $rev;
    }

    // FUNZIONI DI TRANSAZIONE

    /**
     * @desc Funzione di transazione che corrisponde ad un BEGIN
     * @param resource $this->link_db
     */
    public function begin() {

        $this->PDO->beginTransaction();
        $this->transaction_is_open = true;
    }

    /**
     * @desc Funzione di transazione di ROLLBACK
     * @param resource $this->link_db
     */
    public function rollback() {

        if ($this->transaction_is_open) {
            $this->PDO->rollBack();
            $this->transaction_is_open = false;
        }
    }

    /* Prepared statemens alternative methods */

    public function prepare($sql) {
        return (is_string($sql) && $sql != '') ? $this->PDO->prepare($sql) : NULL;
    }

    public function execute($stmt, $params = array()) {
        return (is_object($stmt)) ? $stmt->execute((array) $params) : NULL;
    }

    /**
     * @desc Funzione di transazione di COMMIT
     * @param resource $this->link_db
     */
    public function commit() {

        if ($this->transaction_is_open) {
            $this->PDO->commit();
            $this->transaction_is_open = false;
        }
    }

    public function close() {

        if ($this->error_handler !== null) {
            $this->db_error_log($this->error_handler);
        }

        if ($this->transaction_is_open) {
            if ($this->error_handler === null)
                $this->commit();
            else
                $this->rollback();
        }

        // $this->PDO = null;
    }

    /**
     * Escape function
     *
     * @param string $string
     * @return string
     */
    public function escape($string = null) {
        
        return $string;
    }

    /**
     * Recursive escape. Work on strings, numbers, array, objects
     *
     * @param mixed $mixed
     * @return mixed
     */
    public function recursive_escape($mixed) {

        if (is_string($mixed)) {

            $escaped = $this->escape($mixed);
        } else if (is_numeric($mixed)) {

            $escaped = $mixed;
        } else if (is_array($mixed)) {
            $escaped = array();
            foreach ($mixed as $k => $val)
                $escaped[$k] = $this->recursive_escape($val);
        } else if (is_object($mixed)) {
            $escaped = new stdClass();
            foreach ($mixed as $k => $val)
                $escaped->{$k} = $this->recursive_escape($val);
        } else {
            $escaped = $mixed;
        }

        return $escaped;
    }

    /**
     * Concat DB sintax
     *
     * @param string $args
     * @param string $args
     * @return string
     */
    public function concat($args, $as = '') {

        $str = "CONCAT($args)";
        if ($as != '')
            $str .= " AS $as";

        return $str;
    }

    /**
     * @desc Funzione di free_result
     * @return void
     */
    public function free_result() {

        $this->stmt->closeCursor();
    }

    /**
     * 	For Oracle and MySQLi compatibility
     *
     * @param statement $stmt
     * @return bool
     */
    public function stmt_close() {

        $this->free_result();
        $this->stmt = null;
        return true;
    }

    /**
     * Set the LIMIT|OFFSET sintax
     *
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function limit($limit, $offset = '') {

        if ($offset != '')
            $str = "LIMIT $offset,$limit";
        else
            $str = "LIMIT $limit";

        return $str;
    }

    /**
     * Esegue una query $sql  e restisce vero|falso a seconda dell'esito
     * il secure_mode (di default) permette l'uso di sole query SELECT.
     * Se l'sql contiene errori la funzione restituisce false, ma l'esecuzione prosegue.
     *
     * @param string $sql Query SQL da testare
     * @param bool $secure_mode Imposta il secure mode per le query, invalidando tutte le query con comandi pericolosi
     * @return bool Esito della query
     */
    public function query_try($sql, $secure_mode = true, $params = array()) {

        $sql = trim(str_replace(array("\n", "\r"), " ", $sql));

        if ($secure_mode) {
            // piccolo accorgimento per la sicurezza...
            if (!preg_match("'^SELECT 'i", $sql))
                return 0;
            $sql2 = preg_replace("'([\W](UPDATE)|(DELETE)|(INSERT)|(GRANT)|(DROP)|(ALTER)|(UNION)|(TRUNCATE)|(SHOW)|(CREATE)|(INFORMATION_SCHEMA)[\W])'ui", "", $sql);
            if ($sql2 != $sql) {
                return -1;
            }
        }
        
        $res = $this->query($sql, $params);
        
        return ($res) ? 1 : 0;
    }

    public function __destruct() {
        $this->close();
    }
    
    /**
     * Funzione di debug della query
     * Restituisce l'SQL con le sostituzioni.
     * Da NON usare per query al database, ma solo per debug.
     * 
     * @param string $sql
     * @param array $params
     * @return type
     */
    public function sql_debug($sql, $params) {
        $indexed=$params==array_values($params);
        foreach($params as $k=>$v) {
            if(is_string($v)) $v="'$v'";
            if($indexed) $string=preg_replace('/\?/',$v,$sql,1);
            else $string=str_replace(":$k",$v,$sql);
        }
        return $string;
    }
    
    /**
     * Funzione di hashing
     * 
     * @param string $sql
     * @param array $params
     * @return type
     */
    public function query_hash($sql, $params) {
        return md5($sql . serialize($params));
    }



    /*
     * ------------------------------------------------------------
     */

    /**
     * @desc Esegue diverse query $sql
     * @param string $sql
     * @return object
     */
    public function multi_query($sql, $params=array()) {

        $getmicro = microtime(true);

        try {
            $this->stmt = $this->PDO->prepare($sql);
            $this->stmt->execute((array) $params);
            $this->log_query_debug($sql, $getmicro, $params);
        }
        catch (DbException $e) {
            $e->setLog(\Monolog\Logger::EMERGENCY);
        }
    }
    

    /**
    

    #########################################################################################
    #
	#
	#	FUNZIONI DI ELABORAZIONE
    #

	
    /**
     *  Recupera informazioni dal file e dalla query ed apre la funzione openError del file design/layouts.php dove cancella il buffer e manda a video l'errore codificato
     *
     * @return void
     * @param string $sql
     * @param string $message
     * @desc Handler degli errori per le query.
     */
    public function error($sql, $message = '') {

        if (!is_object($this->error_handler)) {

            $this->error_handler = new stdClass();
            $this->error_handler->dbtype = $this->vmsqltype;
            $this->error_handler->errors = array();
        }

        $trace = debug_backtrace();
        $last = count($trace) - 1;
        $file_line = str_replace(FRONT_ROOT, '', $trace[$last]['file']) . ":" . $trace[$last]['line'];

        $ee = array('date' => date("c"),
            'sql' => $sql,
            'code' => mysqli_errno($this->link_db),
            'msg' => mysqli_error($this->link_db),
            'file' => $file_line
        );

        $this->error_handler->errors[] = $ee;

        $this->last_error = $ee;


        if ($GLOBALS['DEBUG_SQL']) {

            $this->error_debug();
        } 
        else {
            
        }
    }

    /**
     * Questa funzione viene eseguita da {@link $this->query} qualora il debug sia attivato
     * @desc Funzione che restituisce a video l'SQL che ha generato l'errore
     * @param string $format default "string"
     */
    public function error_debug($format = "string") {

        if ($format == 'string') {
            var_dump($this->last_error);
        }
    }

    public function get_last_error(){
        return $this->last_error;
    }

    protected function db_error_log($obj) {

        $fp = @fopen(FRONT_ERROR_LOG, "a");
        $towrite = '';

        if (is_array($obj->errors)) {

            foreach ($obj->errors as $e) {

                // prende il tipo query (SELECT , INSERT, UPDATE, DELETE) se il tipo � diverso ahi ahi
                $tipo_query = substr(trim($e['sql']), 0, strpos(trim($e['sql']), " "));

                // restituisci la query che ha dato errore
                $sql_una_linea = trim(preg_replace("'\s+'", " ", $e['sql']));

                $SERVER_HOST = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
                $SERVER_ADDR = (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : '';

                // Scrittura del file di errore
                $towrite .= "[" . $e['date'] . "]\t"
                        . $e['file'] . "\t"
                        . $SERVER_HOST . " (" . $SERVER_ADDR . ")\t"
                        . "<" . $tipo_query . ">\t"
                        . $obj->dbtype . "\t"
                        . $e['code'] . "\t"
                        . $e['msg'] . "\t"
                        . $sql_una_linea . "\n";
            }

            @fwrite($fp, $towrite);
        }
        @fclose($fp);
    }
    
    public function _print_query_debug() {

        $OUT = "<!-- \n";
        $OUT .= " Queries count: " . $this->get_n_debug_queries() . "\n";
        $OUT .= " Tot execution time: " . $this->get_total_debug_time() . "\n\n";
        $OUT .= implode("\n", $GLOBALS['DEBUG_SQL_STRING']);
        $OUT .= "\n-->\n";
        
        return $OUT;
    }
    
    public function set_debug($set) {
        if($set){
            $this->DEBUG = true;
        }
        else{
            $this->DEBUG = false;
        }
    }
    
    public function get_total_debug_time() {
        return array_sum($this->T);
    }

    public function get_n_debug_queries() {
        return count($this->T);
    }
    
    private function log_query_debug($sql, $getmicro, $params=array()) {

        if ($this->DEBUG) {
            $_sql = (count($params)>0) ? $this->sql_debug($sql, $params) : $sql;
            $exec_time = round((microtime(true) - $getmicro), 4);
            $GLOBALS['DEBUG_SQL_STRING'][] = $exec_time . " --- " . $_sql;
            $this->T[] = $exec_time;
        }
    }

}

