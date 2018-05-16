<?php

namespace Mall\Framework\Db;

class DbOci
{
    static $queries = 0;

    public $options = array();
    public $master = array();
    public $slaves = array();
    public $slave = array();
    public $slave_key;
    public $sql;

    public $dbname;
    public $prefix;
    public $charset;

    protected $_error;
    protected $_cmstop_charset;
    protected $_transaction_started;
    protected $_table;
    protected $_mode;

    private $dbh;
    private $dbh_master;
    private $dbh_slave;

    function __construct($master = array(), $slaves = array())
    {
        $this->master = $master;
        $this->options = &$this->master;
        if ($slaves) $this->slaves = $slaves;

        // 设置全局字符集
        putenv('NLS_LANG=AMERICAN_AMERICA.UTF8');

        // 设置会话期间的日期格式
        $this->exec("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");

        // 统一编码格式
        $this->_cmstop_charset = $this->_normorlize_charset(config('config', 'charset'));
    }

    function __destruct()
    {
        if ($this->dbh) {
            oci_close($this->dbh);
        }
    }

    function beginTransaction()
    {
        if (!$this->_transaction_started) {
            $this->_mode = OCI_DEFAULT;
            $this->_transaction_started = true;
            return true;
        }
        $this->_error = 'Transaction had already begin';
        return false;
    }

    function commit()
    {
        if (!$this->dbh()) return false;

        if ($this->_transaction_started) {
            $result = oci_commit($this->dbh);
            $this->_transaction_started = false;
            return $result;
        }
        $this->_error = 'No activied transaction';
        return false;
    }

    function rollBack()
    {
        if (!$this->dbh()) return false;

        if ($this->_transaction_started) {
            $result = oci_rollback($this->dbh);
            $this->_transaction_started = false;
            return $result;
        }
        $this->_error = 'No activied transaction';
        return false;
    }

    function lastInsertId($table = null, $primary_key = null)
    {
        if ($table !== null) {
            $this->_table = $table;
        }
        if (!$this->_table) {
            return 0;
        }

        $sequence_name = $this->_table;
        if ($primary_key) {
            $sequence_name .= "_{$primary_key}";
        }
        $sequence_name .= '_seq';
        return $this->lastSequenceId($sequence_name);
    }

    function lastSequenceId($sequence_name)
    {
        if (!$this->dbh()) return false;
        $sql = 'SELECT ' . $sequence_name . '.CURRVAL FROM dual';
        return $this->get($sql);
    }

    function nextSequenceId($sequence_name)
    {
        if (!$this->dbh()) return false;
        $sql = 'SELECT ' . $sequence_name . '.NEXTVAL FROM dual';
        return $this->get($sql);
    }

    static function get_instance($master = array(), $slaves = array())
    {
        static $instance;
        $key = implode('', $master);
        if (!isset($instance[$key])) {
            $instance[$key] = new DbOci($master, $slaves);

        }
        return $instance[$key];
    }

    function connect($options = array())
    {
        $host = value($options, 'host');
        $port = value($options, 'port');
        $dbname = value($options, 'dbname');
        $prefix = value($options, 'prefix', '');
        $charset = value($options, 'charset');
        $username = value($options, 'username');
        $password = value($options, 'password');
        $pconnect = value($options, 'pconnect');

        $handler = $pconnect ? 'oci_pconnect' : 'oci_connect';
        $server = '//' . $host . ($port ? (':' . $port) : '') . '/' . $dbname;

        if (!function_exists($handler)) {
            throw new \Exception("OCI extension not enabled");
        }

        if (!($dbh = $handler($username, $password, $server))) {
            return false;
        }

        $this->dbname = $dbname;
        $this->prefix = $prefix;

        // 统一编码格式
        $this->charset = $this->_normorlize_charset($charset);

        return $dbh;
    }

    private function connect_slave()
    {
        $this->slave_key = array_rand($this->slaves);
        $this->slave = $this->slaves[$this->slave_key];
        $this->dbh_slave = $this->connect($this->slave);
        if (!$this->dbh_slave && count($this->slaves) > 1) {
            unset($this->slaves[$this->slave_key]);
            return $this->connect_slave();
        }
        return $this->dbh_slave;
    }

    function exec($statement, $multiple = false)
    {
        if (!$this->dbh($statement)) return false;

        if (preg_match('/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i', $this->sql, $matches)) {
            $this->_table = $matches[2];
        }

        $this->_mode = OCI_COMMIT_ON_SUCCESS;
        $stmt = oci_parse($this->dbh, $this->_prepare_sql($this->sql));
        if (false === oci_execute($stmt, $this->_mode)) {
            return false;
        }

        if ($multiple) {
            $result = array();
            oci_fetch_all($stmt, $result, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
        } else {
            $result = oci_fetch_assoc($stmt);
            if (is_array($result)) {
                $result = array_shift(array_values($result));
            }
        }
        oci_free_statement($stmt);

        /*if ($this->charset != $this->_cmstop_charset)
        {
            $result = str_charset($this->charset, $this->_cmstop_charset, $result);
        }*/

        return $result;
    }

    function query($statement)
    {
        return $this->exec($statement);
    }

    function get($sql)
    {
        return $this->exec($sql);
    }

    function select($sql)
    {
        return $this->exec($sql, true);
    }

    function insert($sql)
    {
        return $this->exec($sql);
    }

    function update($sql)
    {
        return $this->exec($sql);
    }

    function replace($sql)
    {
        return $this->update($sql);
    }

    function delete($sql)
    {
        return $this->exec($sql);
    }

    public function limit($sql, $limit = 0, $offset = 0)
    {
        $limit_sql = "SELECT c2.*
            FROM (
                SELECT c1.*, ROWNUM AS \"cmstop_db_rownum\"
                FROM (
                    {$sql}
                ) c1
            ) c2
            WHERE c2.\"cmstop_db_rownum\" BETWEEN " . ($offset + 1) . " AND " . ($offset + $limit);
        return $this->select($limit_sql);
    }

    public function page($sql, $page = 1, $size = 20)
    {
        $page = isset($page) ? max(intval($page), 1) : 1;
        $size = max(intval($size), 1);
        $offset = ($page - 1) * $size;
        return $this->limit($sql, $size, $offset);
    }

    function select_db($dbname)
    {
        return true;
    }

    function list_fields($table)
    {
        static $result = array();

        if (!is_array($result[$table])) {
            if ($fields = $this->select("SELECT a.column_name,
                data_type, decode(nullable, 'Y', 0,1) notnull,
                data_default, decode(a.column_name, b.column_name, 1, 0) pk
                FROM user_tab_columns a, (
                    SELECT column_name
                    FROM user_constraints c,user_cons_columns col
                    WHERE c.constraint_name = col.constraint_name
                    AND c.constraint_type='P'
                    AND c.table_name = '" . strtoupper($table) . "'
                ) b
                WHERE table_name = '" . strtoupper($table) . "'
                AND a.column_name = b.column_name(+)")
            ) {
                foreach ($fields as $field) {
                    $result[$table][$field['COLUMN_NAME']] = array(
                        'Field' => $field['COLUMN_NAME'],
                        'Type' => $field['DATA_TYPE'],
                        'Null' => $field['NOTNULL'] === 0 ? 'NO' : 'YES',
                        'Key' => '',
                        'Default' => $field['DATA_DEFAULT'],
                        'Extra' => ''
                    );
                }
            }
        }

        return $result[$table];
    }

    function list_tables()
    {
        static $result;

        if (!is_array($result)) {
            if ($tables = $this->select("SELECT TABLE_NAME FROM USER_TABLES ORDER BY TABLE_NAME ASC")) {
                foreach ($tables as $table) {
                    $result[] = $table['TABLE_NAME'];
                }
            }
        }

        return $result;
    }

    function list_dbs()
    {
        return array(
            $this->master['dbname']
        );
    }

    function get_primary($table)
    {
        static $result = array();

        if (!array_key_exists($table, $result)) {
            $primarys = array();
            foreach ($this->exec("SELECT *
                FROM ALL_CONS_COLUMNS A
                JOIN ALL_CONSTRAINTS C
                    ON A.CONSTRAINT_NAME = C.CONSTRAINT_NAME
                WHERE C.TABLE_NAME = '$table'
                AND C.CONSTRAINT_TYPE = 'P'", true) as $primary) {
                $primarys[] = $primary['COLUMN_NAME'];
            }
            $result[$table] = implode(',', $primarys);
        }

        return $result[$table];
    }

    function field_type($table, $field)
    {
        return false;
    }

    function version()
    {
        if (!$this->dbh()) return false;

        return oci_server_version($this->dbh);
    }

    function prefix()
    {
        return $this->master['prefix'];
    }

    function error()
    {
        if (is_null($this->_error)) {
            $this->_error = oci_error();
            if ($this->_error && $this->charset != $this->_cmstop_charset) {
                $this->_error = str_charset($this->charset, $this->_cmstop_charset, $this->_error);
            }
        }
        return $this->_error;
    }

    protected function _prepare_sql($sql)
    {
        $this->sql = str_replace('#table_', $this->master['prefix'], trim($sql));
        $this->sql = preg_replace('/(`(\w*)`)/Usim', '"$2"', $this->sql);
        return $this->sql;
    }

    protected function _normorlize_charset($charset)
    {
        return strtolower(str_replace('-', '', $charset));
    }

    private function dbh($sql = null)
    {
        if (is_null($sql)) {
            $this->sql = null;
            if (is_null($this->dbh)) {
                if (is_null($this->dbh_master)) $this->dbh_master = $this->connect($this->master);
                $this->dbh = $this->dbh_master;
            }
            return $this->dbh;
        }

        self::$queries++;
        $this->sql = str_replace('#table_', $this->master['prefix'], trim($sql));
        if ($this->slaves && is_null($this->dbh_master) && stripos($this->sql, 'select') === 0) {
            if (is_null($this->dbh_slave)) $this->dbh_slave = $this->connect_slave();
            $this->dbh = $this->dbh_slave;
        } else {
            if (is_null($this->dbh_master)) $this->dbh_master = $this->connect($this->master);
            $this->dbh = $this->dbh_master;
        }
        return $this->dbh;
    }
}
