<?php

namespace Mall\Framework\Core;

use Mall\Framework\Factory;

abstract class SqlHelper
{
    const FETCH_ASSOC = \PDO::FETCH_ASSOC;
    const FETCH_OBJ = \PDO::FETCH_OBJ;

    /**
     * 数据库连接
     *
     * @var db
     */
    protected $db;

    /**
     * 数据库名称
     *
     * @var string
     */
    protected $_table = null;

    /**
     * 数据库查询主键
     *
     * @var string
     */
    protected $_primary = null;

    /**
     * 数据表字段
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * 只读字段
     *
     * @var array
     */
    protected $_readonly = array();

    /**
     * 自动填充字段定义
     *
     * @var array
     */
    protected $_create_autofill = array();

    /**
     * 自动更新字段定义
     *
     * @var array
     */
    protected $_update_autofill = array();

    /**
     * 自动过滤字段定义
     *
     * @var array
     */
    protected $_filters_input = array();

    /**
     * 自动过滤字段定义
     *
     * @var array
     */
    protected $_filters_output = array();

    /**
     * 插入时，字段验证定义
     *
     * @var array
     */
    protected $_validators = array();

    /**
     * 插入时，准备好的数据
     *
     * @var array
     */
    protected $_data = array();

    /**
     * select 查询join where limit 等定义
     *
     * @var array
     */
    protected $_options = array();

    /**
     * 数据获取方式
     *
     * @var int
     */
    protected $_fetch_style = self::FETCH_ASSOC;

    /**
     * 用户ID
     *
     * @var int
     */
    public $_userid = null;

    /**
     * 用户名称
     *
     * @var string
     */
    public $_username = null;

    /**
     * 用户组ID
     *
     * @var int
     */
    public $_groupid = null;

    /**
     * 用户角色ID
     *
     * @var int
     */
    public $_roleid = null;

    /**
     * 错误信息
     *
     * @var mixed
     */
    public $error = null;

    /**
     * 初始化数据库，用户定义
     *
     */
    function __construct($serviceDB)
    {
        $this->db = Factory::db($serviceDB);
//		$current = online();
//		if ($current)
//		{
//			$this->_userid = $current['userid'];
//			$this->_username = $current['username'];
//			$this->_groupid = $current['groupid'];
//			$this->_roleid = $current['roleid'];
//		}
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    public function __call($method, $args)
    {
        if (in_array($method, array('field', 'where', 'order', 'limit', 'offset', 'having', 'group', 'distinct', 'data'), true)) {
            $this->_options[$method] = $args[0];
            return $this;
        } elseif (in_array($method, array('sum', 'min', 'max', 'avg'), true)) {
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->get_field($method . '(' . $field . ') AS `count`');
        } elseif (preg_match("/^(get|gets|delete)_by_(.*)$/", $method, $matches)) {
            $field = $matches[2];
            if (in_array($field, $this->_fields, true)) {
                array_unshift($args, $field);
                return call_user_func_array(array($this, $matches[1] . '_by'), $args);
            }
        } else {
            if (in_array($this->db->prefix() . $method, $this->db->list_tables())) {
                return isset($args[1]) ? table($method, $args[0], $args[1]) : table($method, $args[0]);
            } else {
                throw new \Exception(__CLASS__ . ':' . $method . ' 方法不存在');
            }
        }
        return;
    }

    /**
     * 设置查询读取模式
     *
     * @param int $style
     */
    public function set_fetch_style($style)
    {
        $this->_fetch_style = $style;
    }

    /**
     * 查询数据
     *
     * @param string|array $where
     * @param string|array $fields
     * @param string|array $order
     * @param int $limit
     * @param int $offset
     * @param array $data
     * @param boolean $multiple
     * @return array|boolean
     */
    function select($where = null, $fields = '*', $order = null, $limit = null, $offset = null, $data = array(), $multiple = true)
    {
        $having = $group = NULL;
        if (!empty($this->_options)) {
            $fields = isset($this->_options['distinct']) ? "distinct " . $this->_options['distinct'] : isset($this->_options['field']) ? $this->_options['field'] : $fields;
            $where = isset($this->_options['where']) ? $this->_options['where'] : $where;
            $having = isset($this->_options['having']) ? $this->_options['having'] : null;
            $order = isset($this->_options['order']) ? $this->_options['order'] : $order;
            $group = isset($this->_options['group']) ? $this->_options['group'] : null;
            $limit = isset($this->_options['limit']) ? $this->_options['limit'] : $limit;
            $offset = isset($this->_options['offset']) ? $this->_options['offset'] : $offset;
            $this->_options = array();
        }

        if (is_array($fields)) $fields = '`' . implode('`,`', $fields) . '`';

        $this->_where($where);

        if (!$this->_before_select($where)) return false;

        $sql = "SELECT $fields FROM `$this->_table` ";
        if ($where) $sql .= " WHERE $where ";
        if ($order) $sql .= " ORDER BY $order ";
        if ($group) $sql .= " GROUP BY $group ";
        if ($having) $sql .= " HAVING $having ";
        if (is_null($limit) && !$multiple) $sql .= " LIMIT 1 ";

        $method = $multiple ? 'select' : 'get';
        $result = is_null($limit)
            ? $this->db->$method($sql, $data, $this->_fetch_style)
            : $this->db->limit($sql, $limit, $offset, $data, $this->_fetch_style);
        if ($result === false) {
            if ($this->db->error() && $this->db->errno() != '00000') {
                $this->error = $this->db->error();
                return false;
            }

            return [];
        } else {
            $this->_data = $result;
            $this->_after_select($result, $multiple);
            return $result;
        }
    }

    /**
     * 查询前执行
     *
     * @param array|string $where
     * @return boolean
     */
    protected function _before_select(&$where)
    {
        return true;
    }

    /**
     * 查询后执行
     *
     * @param array $result 查询后的结果
     * @param boolean $multiple 是否多条数据
     */
    protected function _after_select(&$result, $multiple = true)
    {
    }

    /**
     * 分页查询
     *
     * @param array|string $where
     * @param array|string $fields
     * @param string $order
     * @param int $page
     * @param int $size
     * @param array $data
     * @return array
     */
    public function page($where = null, $fields = '*', $order = null, $page = 1, $size = 20, $data = array())
    {
        $offset = ($page - 1) * $size;
        return $this->select($where, $fields, $order, $size, $offset, $data, true);
    }

    /**
     * 获得单条数据
     *
     * @param array|string $where
     * @param array|string $fields
     * @param string $order
     * @return array
     */
    public function get($where = null, $fields = '*', $order = null)
    {
        return $this->select($where, $fields, $order, null, null, array(), false);
    }

    /**
     * 根据字段值获得单条数据
     *
     * @param string $field
     * @param mixed $value
     * @param array|string $fields
     * @param string $order
     * @return array
     */
    public function get_by($field, $value, $fields = '*', $order = null)
    {
        return $this->select("`$field`=?", $fields, $order, null, null, array($value), false);
    }

    /**
     * 根据字段值查询多条数据
     *
     * @param string $field
     * @param mixed $value
     * @param array|string $fields
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function gets_by($field, $value, $fields = '*', $order = null, $limit = null, $offset = 0)
    {
        return $this->select("`$field`=?", $fields, $order, $limit, $offset, array($value), true);
    }

    /**
     * 获取单条字段值
     *
     * @param string $field
     * @param array|string $where
     * @param array $data
     * @return mixed
     */
    public function get_field($field, $where = null, $data = array())
    {
        $r = $this->select($where, $field, null, null, null, $data, false);
        return array_shift($r);
    }

    /**
     * 获取字段集合
     *
     * @param string $field
     * @param array|string $where
     * @param array $data
     * @return array
     */
    public function gets_field($field, $where = null, $data = array())
    {
        $result = array();
        $data = $this->select($where, $field, null, null, null, $data, true);
        foreach ($data as $r) {
            $result[] = array_shift($r);
        }
        return $result;
    }

    /**
     * 获取总数
     *
     * @param array|string $where
     * @param array $data
     * @return int
     */
    public function count($where = null, $data = array())
    {
        $this->_where($where);
        if (!empty($where)) $where = " WHERE $where";
        $r = $this->db->get("SELECT count(*) as `count` FROM `$this->_table` $where", $data);
        return $r ? $r['count'] : false;
    }

    /**
     * 获取主健定义
     *
     * @return string
     */
    public function primary()
    {
        return isset($this->_primary) ? $this->_primary : $this->db->get_primary($this->_table);
    }

    /**
     * 是否存在符合条件的记录
     *
     * @param array|string $field
     * @param mixed $value
     * @return boolean
     */
    public function exists($field, $value)
    {
        return $this->db->get("SELECT `$field` FROM `$this->_table` WHERE `$field`=?", array($value)) ? true : false;
    }

    /**
     * 插入数据
     *
     * @param array $data
     * @return int|boolean
     */
    protected function insert($data = array())
    {
        $this->_data($data);

        if (!$this->_before_insert($data)) return false;

        $this->_create_autofill($data);
        $id = $this->db->insert("INSERT INTO `$this->_table` (`" . implode('`,`', array_keys($data)) . "`) VALUES(" . implode(',', array_fill(0, count($data), '?')) . ")", array_values($data));


        if ($id === false) {
            $this->error = $this->db->error();
            return false;
        } else {
            $this->_after_insert($data);
            return $id;
        }
    }

    /**
     * 替换方式插入数据
     *
     * @param array $data
     * @return int|boolean
     */
    protected function replace($data = array())
    {
        $this->_data($data);

        if (!$this->_before_insert($data)) return false;

        $this->_create_autofill($data);

        $id = $this->db->insert("REPLACE INTO `$this->_table` (`" . implode('`,`', array_keys($data)) . "`) VALUES(" . implode(',', array_fill(0, count($data), '?')) . ")", array_values($data));
        if ($id === false) {
            $this->error = $this->db->error();
            return false;
        } else {
            $this->_after_insert($data);
            return $id;
        }
    }

    /**
     * 插入之前执行
     *
     * @param array $data
     * @return boolean
     */
    protected function _before_insert(&$data)
    {
        return true;
    }

    /**
     * 插入之后执行
     *
     * @param array $data
     */
    protected function _after_insert(&$data)
    {
    }

    /**
     * 根据ID拷贝一份
     *
     * @param int $id
     * @param array $data
     * @return int/boolean
     */
    public function copy_by_id($id, $data = array())
    {
        $r = $this->db->get("SELECT * FROM `$this->_table` WHERE `$this->_primary`=?", array($id));
        if (!$r) return false;
        unset($r[$this->_primary]);
        if ($data) $r = array_merge($r, $data);
        return $this->insert($r);
    }

    /**
     * 更新
     *
     * @param array $data
     * @param array /string $where
     * @param int $limit
     * @param array /string $order
     * @return int/boolean
     */
    protected function update($data = array(), $where = null, $limit = null, $order = null)
    {
        if (!empty($this->_options)) {
            $where = isset($this->_options['where']) ? $this->_options['where'] : $where;
            $order = isset($this->_options['order']) ? $this->_options['order'] : $order;
            $limit = isset($this->_options['limit']) ? $this->_options['limit'] : $limit;
            $offset = isset($this->_options['offset']) ? $this->_options['offset'] : $offset;
            $this->_options = array();
        }

        $this->_data($data);

        $this->_where($where);

        if (!$this->_before_update($data, $where)) return false;

        $this->_update_autofill($data);

        $this->_readonly($data);


        $sql = "UPDATE `$this->_table` SET `" . implode('`=?,`', array_keys($data)) . "`=?";
        if ($where) $sql .= " WHERE $where ";
        if ($order) $sql .= " ORDER BY $order ";
        if ($limit) $sql .= " LIMIT $limit ";

        $result = $this->db->update($sql, array_values($data));

        if ($result === FALSE) {
            $this->error = $this->db->error();
            return false;
        } else {
            $this->_after_update($data, $where);
            return $result;
        }
    }

    /**
     * query方法
     */
    protected function query($sql)
    {
        if(!empty($sql)){
            $result = $this->db->query($sql);
            if(!$result){
                $this->error = $this->db->error();
                return false;
            }else{
                return $result;
            }
        }else{
            $this->error = 'sql is null';
            return false;
        }
    }

    /**
     * 更新记录之前执行
     *
     * @param array $data
     * @param array|string $where
     * @return boolean
     */
    protected function _before_update(&$data, $where)
    {
        return true;
    }

    /**
     * 更新记录之后执行
     *
     * @param array $data
     * @param array|string $where
     */
    protected function _after_update(&$data, $where)
    {
    }

    /**
     * 更新一个字段值
     *
     * @param string $field
     * @param mixed $value
     * @param string /array $where
     * @return int/boolean
     */
    public function set_field($field, $value, $where = null)
    {
        return $this->update(array($field => $value), $where);
    }

    /**
     * 递增一个int字段值
     *
     * @param string $field
     * @param string /array $where
     * @param int $step
     * @param array $data
     * @return int/boolean
     */
    public function set_inc($field, $where = null, $step = 1, $data = array())
    {
        $this->_where($where);
        return $this->db->update("UPDATE `$this->_table` SET `$field`=`$field`+$step WHERE $where", $data);
    }

    /**
     * 递减一个int字段值
     *
     * @param string $field
     * @param array /string $where
     * @param int $step
     * @param array $data
     * @return int/boolean
     */
    public function set_dec($field, $where = null, $step = 1, $data = array())
    {
        $this->_where($where);
        return $this->db->update("UPDATE `$this->_table` SET `$field`=`$field`-$step WHERE $where", $data);
    }

    /**
     * 删除
     *
     * @param array /string $where
     * @param int $limit
     * @param array /string $order
     * @param array $data
     * @return int/boolean
     */
    protected function delete($where = null, $limit = null, $order = null, $data = array())
    {
        if (!empty($this->_options)) {
            $where = isset($this->_options['where']) ? $this->_options['where'] : $where;
            $order = isset($this->_options['order']) ? $this->_options['order'] : $order;
            $limit = isset($this->_options['limit']) ? $this->_options['limit'] : $limit;
            $offset = isset($this->_options['offset']) ? $this->_options['offset'] : $offset;
            $this->_options = array();
        }

        $this->_where($where);

        if (!$this->_before_delete($where)) return false;

        $sql = "DELETE FROM `$this->_table`";
        $asql = $sql;
        if ($where) $sql .= " WHERE $where ";
        if ($limit) {
            if ($order) $sql .= " ORDER BY $order ";
            $sql .= " LIMIT $limit ";
        }
        if ($sql == $asql) {
            return false;
        }

        $result = $this->db->delete($sql, $data);
        if ($result === FALSE) {
            $this->error = $this->db->error();
            return false;
        } else {
            $this->_after_delete($where);
            return $result;
        }
    }

    /**
     * 删除前执行
     *
     * @param array|string $where
     * @return boolean
     */
    protected function _before_delete(&$where)
    {
        return true;
    }

    /**
     * 删除后执行
     *
     * @param array|string $where
     */
    protected function _after_delete(&$where)
    {
    }

    /**
     * 根据字段值查询
     *
     * @param string $field
     * @param mixed $value
     * @param int $limit
     * @param array|string $order
     * @return int|boolean
     */
    public function delete_by($field, $value, $limit = null, $order = null)
    {
        return $this->delete("`$field`=?", $limit, $order, array($value));
    }


    /**
     * 获得一个字段的类型
     *
     * @param string $field
     * @return string
     */
    protected function _fieldtype($field)
    {

        static $fields;
        if (is_null($fields) || !isset($fields[$this->_table])) {
            if ($data = $this->db->list_fields($this->_table)) {
                foreach ($data as $k => $v) {
                    $fields[$v['Field']] = $v;
                }
            }
        }
        return isset($fields[$field]) && isset($fields[$field]['Type']) ? $fields[$field]['Type'] : '';

    }


    private function _create_autofill(& $data)
    {
        if (empty($this->_create_autofill)) return true;
        foreach ($this->_create_autofill as $field => $val) {
            if (!isset($data[$field])) $data[$field] = $val;
        }
    }

    private function _update_autofill(& $data)
    {
        if (empty($this->_update_autofill)) return true;
        foreach ($this->_update_autofill as $field => $val) {
            if (!isset($data[$field])) $data[$field] = $val;
        }
    }

    private function _readonly(& $data)
    {
        if (empty($this->_readonly)) return true;
        foreach ($this->_readonly as $field => $val) {
            if (isset($data[$field])) unset($data[$field]);
        }
    }

    private function checkVaule($where){
        if(is_string($where)){
           return addslashes($where);
        }
        return $where;
    }


    private function _where(& $where)
    {

        if (empty($where) && isset($this->_data[$this->_primary])) $where = $this->_data[$this->_primary];

        if (is_numeric($where)) {
            $where = "`$this->_primary`=$where";
        } elseif (is_array($where)) {
            $where = array_map(array(__CLASS__,'checkVaule'), $where);
            if (isset($where[0])) {
                $ids = is_numeric($where[0]) ? implode(',', $where) : "'" . implode("','", $where) . "'";
                $where = "`$this->_primary` IN($ids)";
            } else {
                $condition = array();
                foreach ($where as $field => $value) {
                    if (in_array($field, $this->_fields)) {
                        if(is_array($value)){
                            $condition[] = "`$field` in(".implode(',',$value).")";
                        }else{
                            $condition[] = "`$field`='$value'";
                        }
                    }
                }
                $where = implode(' AND ', $condition);
            }
        } elseif (preg_match("/^[0-9a-z\'\"\,\s]+$/i", $where)) {
            $where = strpos($where, ',') === false ? "`$this->_primary`='$where'" : "`$this->_primary` IN($where)";
        }

        $notice_flags = array('select', 'delete', 'insert', 'drop', 'update', 'benchmark', 'database');
        $quote = strpos($where, '"') === true ? '"' : "'";
        foreach ($notice_flags as $w) {
            $where = preg_replace('#([\s\(\)\!])' . $w . '([\s\(\)\*])#i', "$1" . substr($w, 0, 1) . $quote . '/**/' . $quote . substr($w, 1) . "$2", $where);
        }
    }

    private function _data(& $data)
    {
        if (empty($data)) {
            if (!empty($this->_options['data'])) {
                $data = $this->_options['data'];
            } elseif (!empty($this->_data)) {
                $data = $this->_data;
            } elseif (!empty($_POST)) {
                $data = $_POST;
            }
        }
    }

    /**
     * 过滤不需要的字段
     *
     * @param array $data 输入数据
     * @param array $keys 需要的字段定义
     * @return array
     */
    protected function filter_array($data, $keys)
    {
        // 合并附加字段
        $fields = $this->_get_extra_fileds();
        if ($fields) {
            $fields = array_filter(array_keys($fields));
            if ($fields) {
                $keys = array_unique(array_merge($keys, $fields));
            }
        }

        foreach ($data as $field => $v) {
            if (!in_array($field, $keys)) unset($data[$field]);
        }
        return $data;
    }

    /**
     * 获取模型操作中的错误，并返回字符串类型的错误
     *
     * @return string
     */
    public function error()
    {
        $error = $this->error;
        if (empty($error)) {
            $error = $this->db->error();
        }
        if (empty($error)) {
            return '';
        } else {
            if (is_array($error)) {
                $error = implode("*****", $error);
            }
            return $error;
        }
    }



    public function set_Table($tablename)
    {
        $this->_table = $tablename;
    }
}