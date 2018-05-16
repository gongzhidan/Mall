<?php

namespace Mall\Framework\Core;

class Cache {

    protected static $_instance;

    private function __construct(){}

    public function connect($storage, $options=array())
    {
        $cache = FALSE;
        $class = 'Mall\\Framework\\Cache\\'.ucwords(strtolower($storage));

        if(class_exists($class)) {
            $cache = new $class($options);
        }

        return $cache;
    }

    public static function getInstance($options = [])
    {
        $options = $options ?: Config::getInstance()->get('cache');
        $storage = $options['storage'] ?: 'Redis';
        $key = $storage . md5(implode(',', $options));

        if (!isset(self::$_instance[$key])) {
            $obj = new self();
            self::$_instance[$key] = $obj->connect($storage, $options);
        }

        return self::$_instance[$key];
    }
}