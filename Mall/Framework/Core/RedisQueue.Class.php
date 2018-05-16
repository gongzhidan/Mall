<?php

namespace Mall\Framework\Core;

use Mall\Framework\Cache\Redis;

class RedisQueue
{
    public static $prefix = 'queue::';
    public static $queueName = 'public_queue';

    protected static $_instance;

    /** @var Redis $redis */
    protected static $redis;

    /**
     * @param Redis $redis      redis实例
     * @param string $queueName 队列名称
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getInstance(Redis $redis ,$queueName = 'public_queue')
    {
        if ($redis instanceof \Redis) {
            throw new \Exception('Redis driver not null');
        }

        self::$redis = $redis;
        self::setQueueName($queueName);

        $key = md5(self::$queueName);
        if (!isset(self::$_instance[$key])) {
            self::$_instance[$key] = new self();
        }

        return self::$_instance[$key];
    }

    public static function setQueueName($queueName)
    {
        if ($queueName) {
            self::$queueName = self::$prefix . $queueName;
        }
    }

    /**
     * 添加队列
     *
     * @param string $value 存储的队列
     *
     * @return bool
     */
    public static function push($value)
    {
        return self::$redis->push(self::$queueName, $value, 'start');
    }

    /**
     * 从队列取出数据
     *
     * @param int $number 数量
     *
     * @return array
     */
    public static function pop($number = 1)
    {
        $result = [];

        for ($i = 0; $i < $number; $i++) {
            if ($value = self::$redis->pop(self::$queueName, 'end')) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 删除队列
     */
    public static function flushQueue()
    {
        return self::$redis->delete(self::$queueName);
    }


    /**
     * 返回队列的长度
     */
    public function llen()
    {
        return self::$redis->llen(self::$queueName);
    }

    /**
     * 查看队列的 key 是否存在
     */
    public function has()
    {
        return self::$redis->has(self::$queueName);
    }
}