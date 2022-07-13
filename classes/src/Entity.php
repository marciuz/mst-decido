<?php

define('STORE_DB_LOG', false);

/**
 * Abstraction of DB table
 *
 * @author Marcello Verona
 */
abstract class Entity {

    public $debug = false;
    
    /**
     * Nome della tabella.
     * @var string
     */
    protected $nometabella = '';
    
    /**
     * PK di tabella.
     * @var string
     */
    protected $pk = '';
    
    /**
     * @var Vmsql
     */
    protected $vmsql;
    
    /**
     * @var bool
     */
    protected $pk_autoincrement = true;
    

    public function __construct() {

        $this->vmsql = Vmsql::init();
    }

    public function delete($id) {

        if ($id == 0) {
            return null;
        }

        // Log
        if (STORE_DB_LOG) {
            $pre = $this->getRecord($id);
            $this->insertLog('delete', $pre, '', $id);
        }

        $sql = "DELETE FROM $this->nometabella WHERE $this->pk=?";

        $q = $this->vmsql->query($sql, [$id]);

        $res = $this->vmsql->affected_rows($q);

        if ($this->debug) {
            return $res;
        } else {
            return ($res == 1) ? true : false;
        }
    }

    public function select($id, $fields='*') {

        $sql = "SELECT {$fields} FROM $this->nometabella WHERE $this->pk=?";

        $q = $this->vmsql->query($sql, [$id]);
        $RS = $this->vmsql->fetch_assoc($q);

        return $RS;
    }
    
    public function select_by($field, $value) {

        $sql = "SELECT * FROM $this->nometabella WHERE $field=?";

        $q = $this->vmsql->query($sql, [$value]);
        $RS = $this->vmsql->fetch_assoc($q);

        return $RS;
    }
    
    /**
     * Permette l'esecuzione della query basata su un array con parametri nominati.
     * @param array $args
     * @return array
     */
    public function _get($args) {
        
        $campi = $args['campi'] ?? ['*'];
        $where = $args['where'] ?? '';
        $params = $args['params'] ?? [];
        $orderby = $args['orderby'] ?? '';
        $limit = $args['limit'] ?? 10;
        $offset = $args['offset'] ?? 0;
        
        return $this->get($campi, $where, $params, $orderby, $limit, $offset);
    }

    public function get($campi=['*'], $where='', $params=[], $orderby='', $limit=0, $offset=0) {

        $sql = "SELECT ".implode(',', $campi)." "
                . "FROM $this->nometabella "
                . "WHERE 1=1 ";
        
        $sql.= (trim($where)!='') ? 'AND '. $where . ' ' : '';
        
        $sql.= ($orderby != null) ? 'ORDER BY '.$orderby . ' ' : '';
        
        $sql.= ($limit > 0) ? ' LIMIT '.intval($limit). ' OFFSET '.intval($offset) : '';

        $data = $this->vmsql->get($sql, $params);

        return $data;
    }
    
    public function get_column($campo='*', $where='', $params=[], $orderby='', $limit=0, $offset=0) {

        $sql = "SELECT {$campo} "
                . "FROM $this->nometabella "
                . "WHERE 1=1 ";
        
        $sql.= (trim($where)!='') ? 'AND '. $where . ' ' : '';
        $sql.= ($orderby != null) ? 'ORDER BY '.$orderby . ' ' : '';
        $sql.= ($limit > 0) ? ' LIMIT '.intval($limit). ' OFFSET '.intval($offset) : '';
        $data = $this->vmsql->get_column($sql, $params);

        return $data;
    }
    
    public function get_keyvalue($campo_value, $campo_key=null) {
        
        if($campo_key === null) {
            $campo_key = $this->pk;
        }
        
        return $this->vmsql->get_keyvalue(sprintf('SELECT %s, %s FROM %s ORDER BY %s ', 
            $campo_key,
            $campo_value,
            $this->nometabella,
            $campo_key,
        ));
    }
    
    
    public function ins($arr) {

        if (isset($arr[$this->pk]) && intval($arr[$this->pk]) > 0) {
            return $this->update($arr);
        } 
        else {
            return $this->insert($arr);
        }
    }

    protected function insert($arr) {

        if (!is_array($arr) || count($arr) == 0) {
            return null;
        }

        $sql_campi = $sql_val = ' ';
        $values = [];

        foreach ($arr as $k => $v) {

            if ($k == $this->pk && $this->pk_autoincrement) {
                continue;
            }

            $sql_campi.="$k,";
            $values[] = $v;
        }

        $sql = "INSERT INTO $this->nometabella (" . substr($sql_campi, 0, -1) . ") "
                . "VALUES (". implode(',', array_fill(0, count($values), '?')) .")";

        if ($this->debug) {
            echo $sql;
        }

        $q = $this->vmsql->query($sql, $values);

        $res = $this->vmsql->affected_rows($q);
        
        if ($res == 1) {

            $id_record = $this->vmsql->insert_id($this->nometabella, $this->pk);
            
            // Log
            if (STORE_DB_LOG) {
                $this->insertLog('insert', '', json_encode($arr), $id_record);
            }

            return $id_record;
        } 
        else {
            return false;
        }
    }

    protected function update($arr) {

        if (!is_array($arr) || count($arr) == 0) {
            return null;
        }

        $_sql = ' ';
        $values = [];

        foreach ($arr as $k => $v) {

            if ($k == $this->pk && $this->pk_autoincrement) {
                continue;
            }

            $_sql.="$k=?,";
            $values[] = $v;
        }
        
        // add pk
        $values[] = $arr[$this->pk];
        

        $sql = "UPDATE $this->nometabella SET " . substr($_sql, 0, -1) . " WHERE $this->pk=?";

        if (STORE_DB_LOG) {
            $pre = $this->getRecord($arr[$this->pk]);
        }

        $q = $this->vmsql->query($sql, $values);
        $res = $this->vmsql->affected_rows($q);

        if ($res >= 0) {
            // Log
            if (STORE_DB_LOG) {
                $this->insertLog('update', $pre, json_encode($arr), $arr[$this->pk]);
            }
            return intval($arr[$this->pk]);
        }
        else {
            return 0;
        }
    }

    protected function getRecord($id) {

        $sql = "SELECT * FROM {$this->nometabella} WHERE {$this->pk}=?";

        $q = $this->vmsql->query($sql, [$id]);
        $RS = $this->vmsql->fetch_assoc($q);
        $json = json_encode($RS);

        return $json;
    }

    /**
     *
     * @param string $type
     * @param string $pre
     * @param string $post
     * @param int $id
     * @return boolean esito
     */
    public function insertLog($type, $pre, $post, $id = 0) {

        $sql = sprintf("INSERT INTO log (tabella,tipo,pre,post,auth,id_record)
                    VALUES('%s', '%s', '%s', '%s', %d, %d)", $this->nometabella, $type, $this->vmsql->escape($pre), $this->vmsql->escape($post), id_user(), intval($id));

        $test = $this->vmsql->query_try($sql, false);

        return $test;
    }

}
