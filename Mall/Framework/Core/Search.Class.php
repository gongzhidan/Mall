<?php

namespace Mall\Framework\Core;

use Mall\Framework\SearchClient\Client;
use Mall\Framework\SearchClient\Transport;
use Exception;

class Search
{
    /**
     * @var array
     */
    private $config;

    /**
     * SearchClient 客户端
     *
     * @var Client
     */
    private $connection = null;

    /**
     * @var int
     */
    private $serverId;

    /**
     * @var array
     */
    private static $badServerIds = array();

    /**
     * @var object
     */
    private static $_instance;

    /**
     * 构造函数
     *
     * @param $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 从服务器组中获取一个可用的ES服务器信息
     *
     * @return array
     * @throws Exception
     */
    private function getServer()
    {
        $config = $this->config;

        $serverId = null;
        if (empty(self::$badServerIds)) {
            $serverId = array_rand($config['servers']);
        } else {
            foreach ($config['servers'] as $id => $val) {
                if (in_array($id, self::$badServerIds)) {
                    continue;
                }
                $serverId = $id;
                break;
            }
        }

        if ($serverId === null) {
            throw new Exception('服务暂时不可用，请稍后再试!');
        }

        $this->serverId = $serverId;

        return array(
            'protocol' => $config['protocol'],
            'timeout' => $config['timeout'],
            'index' => $config['index'],
            'type' => $config['type'],
            'servers' => $config['servers'][$serverId]
        );
    }

    /**
     * 设置Index索引
     *
     * @param $index
     * @return Search|null
     */
    public function setIndex($index)
    {
        $this->config['index'] = $index;
        return is_object($this->getConnection()->setIndex($index)) ? $this : null;
    }

    /**
     * 设置Type
     *
     * @param $type
     * @return Search|null
     */
    public function setType($type)
    {
        $this->config['type'] = $type;
        return is_object($this->getConnection()->setType($type)) ? $this : null;
    }

    /**
     * 创建Index索引
     *
     * @param string $index 索引名称
     * @param array $mappings options
     * @return mixed
     */
    public function createBase($index, $mappings)
    {
        return $this->getConnection()->createBase($index, $mappings);
    }

    /**
     * 删除Index索引
     *
     * @param string $index 索引名称
     * @return mixed
     */
    public function deleteBase($index)
    {
        return $this->getConnection()->deleteBase($index);
    }

    /**
     * 根据 ID 获取文档
     *
     * @param $id
     * @param bool|false $verbose
     * @return array
     */
    public function get($id, $verbose = false)
    {
        $this->checkIndexType();

        return $this->getConnection()->get($id, $verbose);
    }

    /**
     * 创建索引
     *
     * @param string $document 文档格式
     * @param string $id 文档ID
     * @param array $options 自定义选项
     * @return mixed
     */
    public function index($document, $id, $options = array())
    {
        $this->checkIndexType();

        $this->log('index', sprintf(
            'domain:[%s] uri:[%s] contentid:[%s] %s %s',
            empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST'],
            empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'],
            $id,
            var_export($document, true),
            var_export($options, true)
        ));

        return $this->getConnection()->index($document, $id, $options);
    }

    /**
     * 更新索引
     *
     * @param string $document 文档
     * @param string $id 文档ID
     * @param array $options 自定义选项
     * @return mixed
     */
    public function update($document, $id, $options = array())
    {
        $this->checkIndexType();

        $this->log('update', sprintf(
            'domain:[%s] uri:[%s] contentid:[%s] %s %s',
            empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST'],
            empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'],
            $id,
            var_export($document, true),
            var_export($options, true)
        ));

        return $this->getConnection()->index($document, $id, $options);
    }

    /**
     * 删除索引
     *
     * @param string $id 索引ID
     * @return null
     */
    public function delete($id)
    {
        if (!$id) return null;

        $this->checkIndexType();

        $this->log('delete', sprintf(
            'domain:[%s] uri:[%s] contentid:[%s]',
            empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST'],
            empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'],
            $id
        ));

        return $this->getConnection()->delete($id);
    }

    /**
     * 搜索接口
     *
     * @param array $query DSL查询格式
     * @param array $options 选项
     * @return array
     * @throws Exception
     */
    public function search($query = array(), array $options = array())
    {
        try {
            $client = $this->getConnection()->search($query, $options);
           
            return empty($client) ? array() : $client;
        } catch (Transport\HTTPException $e) {
            self::$badServerIds[] = $this->serverId;
            $this->connection = Client::connection($this->getServer());
            return $this->search($query, $options);
        }
    }

    /**
     * 搜索服务链接
     *
     * @return Client
     * @throws Exception
     */
    public function getConnection()
    {
        $key = md5(json_encode($this->config));

        if (!isset(self::$_instance[$key]) || !self::$_instance[$key] instanceof Client) {
            if ($this->connection) {
                return $this->connection;
            }
            $connection = Client::connection($this->getServer());
            $connection->setIndex($this->config['index']);
            $connection->setType($this->config['type']);

            self::$_instance[$key] = $this->connection = $connection;
        }

        return self::$_instance[$key];
    }

    private function checkIndexType()
    {
        $connection = $this->getConnection();

        $index = $connection->getIndex();
        if (empty($index)) {
            throw new \RuntimeException('Invalid search index');
        }

        $type = $connection->getType();
        if (empty($type)) {
            throw new \RuntimeException('Invalid search index type');
        }

        return true;
    }

    protected function log($type, $message)
    {
        if (!defined('DEBUG_MODE') || DEBUG_MODE != 1) {
            return;
        }

        $temp_dir = sys_get_temp_dir();
        if (!is_writable($temp_dir)) {
            return;
        }

        $log_file = sprintf('%s/search_%s.log', $temp_dir, $type);
        $log = sprintf(
            '%s index[%s] type[%s] %s',
            date('r'),
            $this->config['index'],
            $this->config['type'],
            $message
        );
        file_put_contents($log_file, $log . PHP_EOL, FILE_APPEND);
    }
}