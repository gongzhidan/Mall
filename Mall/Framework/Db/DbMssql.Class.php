<?php

namespace Mall\Framework\Db;

class DbMssql
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

    private $dbh;
    private $dbh_master;
    private $dbh_slave;

    function __construct($master = array(), $slaves = array())
    {
        $this->master = $master;
        $this->options = &$this->master;
        if ($slaves) $this->slaves = $slaves;

        // 禁止 SQL Server 进行日期格式转换
        ini_set('mssql.datetimeconvert', 0);

        // 统一编码格式
        $this->_cmstop_charset = $this->_normorlize_charset(config('config', 'charset'));
    }

    function __destruct()
    {
        if ($this->dbh) {
            mssql_close($this->dbh);
        }
    }

    function beginTransaction()
    {
        if (!$this->_transaction_started) {
            $result = $this->exec('BEGIN TRAN');
            $this->_transaction_started = true;
            return $result;
        }
        $this->_error = 'Transaction had already begin';
        return false;
    }

    function commit()
    {
        if ($this->_transaction_started) {
            $result = $this->exec('COMMIT TRAN');
            $this->_transaction_started = false;
            return $result;
        }
        $this->_error = 'No activied transaction';
        return false;
    }

    function rollBack()
    {
        if ($this->_transaction_started) {
            $result = $this->exec('ROLLBACK TRAN');
            $this->_transaction_started = false;
            return $result;
        }
        $this->_error = 'No activied transaction';
        return false;
    }

    function lastInsertId()
    {
        return $this->exec('SELECT SCOPE_IDENTITY()');
    }

    static function get_instance($master = array(), $slaves = array())
    {
        static $instance;
        $key = implode('', $master);
        if (!isset($instance[$key])) {
            $instance[$key] = new DbMssql($master, $slaves);

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

        $handler = $pconnect ? 'mssql_pconnect' : 'mssql_connect';
        $server = $port ? ($host . (stripos(PHP_OS, 'win') === 0 ? ',' : ':') . $port) : $host;

        if (!function_exists($handler)) {
            throw new \Exception("SQL Server extension not enabled");
        }

        if (!($dbh = $handler($server, $username, $password))) {
            return false;
        }

        if (!$dbname) {
            $this->_error = 'No database name specialed';
            return false;
        }

        if (!mssql_select_db($dbname, $dbh)) {
            return false;
        }

        // 处理 SQL Server nchar,nvarchar ... 字段的问题
        mssql_query('SET TEXTSIZE 1024000', $dbh);

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

        $query = mssql_query($this->_prepare_sql($this->sql), $this->dbh);
        if (!$query) {
            return false;
        }

        if (!is_resource($query)) {
            return $query;
        }

        if ($multiple) {
            $result = array();
            while ($row = mssql_fetch_assoc($query)) {
                $result[] = $row;
            }
        } else {
            $result = mssql_fetch_assoc($query);
            if (is_array($result)) {
                $result = array_shift(array_values($result));
            }
        }
        mssql_free_result($query);

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

    function limit($sql, $limit = 0)
    {
        if ($limit
            && preg_match('/^SELECT\s+(.+)\s+FROM\s+(.+?)$/Usim', $sql, $matches)
            && !preg_match('/\bTOP\b/sim', $matches[1])
        ) {
            $fields = $matches[1];
            $from = $matches[2];
            $sql = "SELECT TOP $limit $fields FROM $from";
        }
        return $this->select($sql);
    }

    function select_db($dbname)
    {
        return $this->exec("USE $dbname");
    }

    function list_fields($table)
    {
        static $result = array();

        if (!is_array($result[$table])) {
            if ($fields = $this->select("
                SELECT [column_name], [data_type], [column_default], [is_nullable]
                FROM [information_schema].[tables] AS t
                JOIN [information_schema].[columns] AS c
                ON t.table_catalog = c.table_catalog
                AND t.table_schema = c.table_schema
                AND t.table_name = c.table_name
                WHERE t.table_name = '$table'")
            ) {
                foreach ($fields as $field) {
                    $result[$table][$field['column_name']] = array(
                        'Field' => $field['column_name'],
                        'Type' => $field['data_type'],
                        'Null' => $field['is_nullable'] === '' ? 'NO' : 'YES',
                        'Key' => '',
                        'Default' => $field['column_default'],
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
            if ($fields = $this->select("SELECT [TABLE_NAME] FROM [INFORMATION_SCHEMA].[TABLES] WHERE [TABLE_TYPE] = 'BASE TABLE'")) {
                foreach ($fields as $field) {
                    $result[] = $field['TABLE_NAME'];
                }
            }
        }

        return $result;
    }

    function list_dbs()
    {
        static $result;

        if (!is_array($result)) {
            if ($fields = $this->select("SELECT [name] FROM [master]..[sysdatabases] WHERE [name] NOT IN ('master', 'model', 'msdb', 'tempdb') ORDER BY [name]")) {
                foreach ($fields as $field) {
                    $result[] = $field['name'];
                }
            }
        }

        return $result;
    }

    function get_primary($table)
    {
        static $result = array();

        if (!in_array($result, $table)) {
            $primarys = array();
            foreach ($this->exec("EXEC sp_pkeys '$table'", true) as $primary) {
                $primarys[] = $primary['COLUMN_NAME'];
            }
            $result[$table] = implode(',', $primarys);
        }

        return $result[$table];
    }

    function field_type($table, $field)
    {
        static $result = array();

        $key = md5($table . $field);
        if (!is_array($result[$key])) {
            if ($fields = $this->select("SELECT [DATA_TYPE] FROM [INFORMATION_SCHEMA].[COLUMNS] WHERE [TABLE_NAME] = '$table' AND [COLUMN_NAME] = '$field'")) {
                foreach ($fields as $field) {
                    $result[$key] = $field['DATA_TYPE'];
                }
            }
        }

        return $result[$key];
    }

    function version()
    {
        return $this->exec('SELECT @@VERSION');
    }

    function prefix()
    {
        return $this->master['prefix'];
    }

    function error()
    {
        if (is_null($this->_error)) {
            $this->_error = mssql_get_last_message();
            if ($this->_error && $this->charset != $this->_cmstop_charset) {
                $this->_error = str_charset($this->charset, $this->_cmstop_charset, $this->_error);
            }
        }
        return $this->_error;
    }

    protected function _prepare_sql($sql)
    {
        $this->sql = str_replace('#table_', $this->master['prefix'], trim($sql));
        $this->sql = preg_replace('/(`(\w*)`)/Usim', '[$2]', $this->sql);
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
