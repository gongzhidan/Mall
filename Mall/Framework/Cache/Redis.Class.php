<?php

namespace Mall\Framework\Cache;

use Redis as RedisSource;
use Mall\Framework\Cache\AbstractStorage as Storage;

class Redis extends Storage
{

    /**
     * @var RedisSource|array
     */
    protected $resource;


    public function __construct($options)
    {
        parent::__construct($options);

        //判断redis扩展是否已经安装
        if(!extension_loaded('redis')){
            throw new \Exception('Redis extension is not found');
        }

        $this->resource = new RedisSource();
        $port = $this->options['port'] ?: 6379;

        if (!$this->resource->connect($this->options['host'], $port, 3)) {
            throw new \Exception(sprintf(
                'Cannot connect to redis server on %s:%d',
                $this->options['host'], $this->options['port']
            ));
        }
        if ($this->options['auth'] && !$this->resource->auth($this->options['auth'])) {
            throw new \Exception(sprintf(
                'Auth failed on %s:%d, auth: %s',
                $this->options['host'], $this->options['port'], $this->options['auth']
            ));
        }

        if (isset($this->options['database']) && !$this->resource->select($this->options['database'])) {
            throw new \Exception(sprintf(
                'Select Database failed on %s:%d, auth: %s, database:%d',
                $this->options['host'], $this->options['port'], $this->options['auth'], $this->options['database']
            ));
        }
        
        $this->resource->setOption(RedisSource::OPT_PREFIX, 'Mall_');

    }

    public function set($key, $value = null, $ttl = null)
    {
        $key = $this->prefix . $key;
        $ttl = $ttl ?: 0;
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;

        if ($ttl > 0) {
            $ret = $this->resource->setex($key, $ttl, $value);
        } else {
            $ret = $this->resource->set($key, $value);
        }

        return $ret;
    }

    public function get($key)
    {
        $key = $this->prefix . $key;

        $value = $this->resource->get($key);
        $jsonData = json_decode($value, true);

        return ($jsonData === NULL) ? $value : $jsonData;
    }

    public function incr($key)
    {
        $key = $this->prefix . $key;

        return $this->resource->incr($key);
    }

    /**
     * 将 key 所储存的值加上增量 increment
     * @param $key
     * @return int
     */
    public function incrby($key, $increment)
    {
        $key = $this->prefix . $key;

        return $this->resource->incrBy($key, $increment);
    }

    public function del($key)
    {
        if(is_array($key)){
            foreach ($key as $k => $v){
                $key[$k] = $this->prefix . $v;
            }
        }else{
            $key = $this->prefix . $key;
        }
        $ret = $this->resource->del($key);
        return $ret;
    }

    public function has($key)
    {
        $key = $this->prefix . $key;

        $ret = $this->resource->exists($key);

        return $ret;
    }

    public function delete($key)
    {
        $key = $this->prefix . $key;

        $ret = $this->resource->delete($key) > 0;

        return $ret;
    }

    public function push($key, $value, $type = 'end')
    {
        $key = $this->prefix . $key;
        $ret = FALSE;

        if ($type == 'end') {
            $ret = $this->resource->rpush($key, $value);
        } else if ($type == 'start') {
            $ret = $this->resource->lpush($key, $value);
        }

        return $ret;
    }

    public function pop($key, $type = 'start')
    {
        $key = $this->prefix . $key;
        $ret =FALSE;

        if ($type == 'end') {
            $ret = $this->resource->rpop($key);
        } else if ($type == 'start') {
            $ret = $this->resource->lpop($key);
        }

        return $ret;
    }

    /**
     * 返回列表 key 中指定区间内的元素，区间以偏移量 start 和 stop 指定。
     * @param $key
     * @param $start
     * @param $stop
     * @return array
     */
    public function lrange($key, $start, $stop)
    {
        $key = $this->prefix . $key;
        return $this->resource->lRange($key, $start, $stop);
    }

    /**
     * 返回列表 key 中，下标为 index 的元素
     * @param $key
     * @param $start
     * @param $stop
     * @return array
     */
    public function lindex($key, $index)
    {
        $key = $this->prefix . $key;
        return $this->resource->lIndex($key, $index);
    }

    /**
     * 根据参数 count 的值，移除列表中与参数 value 相等的元素
     */
    public function lrem($key, $value, $count = 0)
    {
        $key = $this->prefix . $key;
        return $this->resource->lRem($key, $value, $count);
    }

    /**
     * @param $key
     * @param array['field1'=>'value', 'field2'=>'value'] $values
     * @return int
     */
    public function hmset($key, $values){
        $key = $this->prefix . $key;

        return $this->resource->hMSet($key, $values);
    }

    /**
     * @param $key
     * @param  array['field1', 'field2'] $value
     *
     * @return int
     */
    public function hmget($key, $fields){
        $key = $this->prefix . $key;
        return $this->resource->hMGet($key, $fields);
    }

    public function hset($key, $field, $value){
        $key = $this->prefix . $key;
        return $this->resource->hSet($key, $field, $value);
    }

    public function hget($key, $field){
        $key = $this->prefix . $key;
        return $this->resource->hGet($key, $field);
    }

    /**
     * 集合
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function sadd($key, $value)
    {
        $key = $this->prefix . $key;
        return $this->resource->sAdd($key, $value);
    }

    /**
     * 集合sadd统计
     *
     * @param $key
     *
     * @return int
     */
    public function scard($key)
    {
        $key = $this->prefix . $key;
        return $this->resource->sCard($key);
    }

    /**
     * 移除集 key 中的一个或多个成员，不存在的成员将被忽略。
     */
    public function srem($key, $value){
        $key = $this->prefix . $key;
        return $this->resource->sRem($key, $value);
    }

    /**
     * 随机取集 key 中的一个或多个成员，不存在的成员将被忽略。
     * @param $value 获取的条数，默认1
     */
    public function srandmember($key, $value=1){
        $key = $this->prefix . $key;
        return $this->resource->sRandMember($key,$value);
    }
    /**
     * 有序集合
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function zadd($key, $score , $value)
    {
        $key = $this->prefix . $key;
        return $this->resource->zAdd($key, $score, $value);
    }

    /**
     * 返回有序集 key 中，所有 score 值介于 min 和 max 之间(包括等于 min 或 max )的成员
     */
    public function zrangebyscore($key, $start, $end){
        $key = $this->prefix . $key;
        return $this->resource->zRangeByScore($key, $start, $end);
    }

    /**
     * 移除有序集 key 中的一个或多个成员，不存在的成员将被忽略。
     */
    public function zrem($key, $value){
        $key = $this->prefix . $key;
        return $this->resource->zRem($key, $value);
    }

    /**
     * 除有序集 key 中，所有 score 值介于 min 和 max 之间(包括等于 min 或 max )的成员
     */
    public function zremrangebyscore($key, $start, $end){
        $key = $this->prefix . $key;
        return $this->resource->zRemRangeByScore($key, $start, $end);
    }

    /**
     * 判断member元素是否是集合Key的成员
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function sismember($key, $value)
    {
        $key = $this->prefix . $key;
        return $this->resource->sIsMember($key, $value);
    }

    /**
     * 返回集合中的所有成员
     *
     * @param $key
     *
     * @return bool
     */
    public function smembers($key)
    {
        $key = $this->prefix . $key;
        return $this->resource->sMembers($key);
    }

    /**
     * 返回集合之间的差集
     *
     * @param string $key1
     * @param string $key2
     *
     * @return object
     */
    public function sdiff($key1, $key2)
    {
        $key1 = $this->prefix . $key1;
        $key2 = $this->prefix . $key2;

        return $this->resource->sDiff($key1, $key2);
    }

    /**
     * 返回集合之间的交集
     *
     * @param string $key1
     * @param string $key2
     *
     * @return object
     */
    public function sinter($key1, $key2)
    {
        $key1 = $this->prefix . $key1;
        $key2 = $this->prefix . $key2;

        return $this->resource->sInter($key1, $key2);
    }

    /**
     * 计算集合之间的交集并写入一个新的key中
     *
     * @param string $deskey  要存入的新key
     * @param string $key1
     * @param string $key2
     *
     * @return int 新集合得数量
     */
    public function sinterStore($deskey, $key1, $key2)
    {
        $deskey = $this->prefix .$deskey;
        $key1 = $this->prefix . $key1;
        $key2 = $this->prefix . $key2;

        return $this->resource->sInterStore($deskey, $key1, $key2);
    }

    /**
     * 返回集合之间的并集
     *
     * @param string $key1
     * @param string $key2
     *
     * @return object
     */
    public function sunion($key1, $key2)
    {
        $key1 = $this->prefix . $key1;
        $key2 = $this->prefix . $key2;

        return $this->resource->sUnion($key1, $key2);
    }

    /**
     * 事物支持
     */
    public function multi()
    {
        return $this->resource->multi();
    }

    /**
     * 执行事务
     */
    public function exec()
    {
        return $this->resource->exec();
    }

    /**
     * 返回整数为列表键长度
     *
     * @param string $key
     *
     * @return int
     */
    public function llen($key)
    {
        $key = $this->prefix . $key;
        return $this->resource->llen($key);
    }

    /**
     * 有序集合中对指定成员的分数加上增量 increment
     *
     * @param $key
     * @param $value
     * @param $member
     *
     * @return float
     */
    public function zincrby($key, $value, $member)
    {
        $key = $this->prefix . $key;
        return $this->resource->zIncrBy($key, $value, $member);
    }

    /**
     * 返回有序集中，成员的分数值
     *
     * @param $key
     * @param $member
     *
     * @return float
     */
    public function zscore($key, $member)
    {
        $key = $this->prefix . $key;
        return $this->resource->zScore($key, $member);
    }

    /**
     * 获取有序集合的成员数
     *
     * @param $key
     *
     * @return int
     */
    public function zcard($key)
    {
        $key = $this->prefix . $key;
        return $this->resource->zCard($key);
    }

    /**
     * 返回有序集 key 中， score 值在 min 和 max 之间(默认包括 score 值等于 min 或 max )的成员的数量
     *
     * @return int
     */
    public function zcount($key, $min, $max)
    {
        $key = $this->prefix . $key;
        return $this->resource->zCount($key, $min, $max);
    }

    /**
     * 返回有序集 key 中，指定区间内的成员。
     *
     * @param $key
     * @param $start
     * @param $end
     *
     * @return array
     */
    public function zrange($key, $start, $end)
    {
        $key = $this->prefix . $key;
        return $this->resource->zRange($key, $start, $end);
    }

    /**
     * 获取所有的keys
     */
    public function keys($key)
    {
        $key = $this->prefix . $key;
        return $this->resource->keys($key);
    }
}