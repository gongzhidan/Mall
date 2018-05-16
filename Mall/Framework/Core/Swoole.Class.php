<?php

namespace Mall\Framework\Core;

class Swoole
{

    static protected $instance;

    static public function getInstance($options = [], $driver = 'Client')
    {
        $driver = $driver ?: 'Client';

        if (!is_array($options) && !$options) {
            throw new \Exception('Need default Swoole ' . $driver . ' config');
        }

        $key = $driver . md5(implode(',', $options)) . $driver;

        if (!isset(self::$instance[$key])) {
            $class = 'Mall\\Framework\\Swoole\\' . ucwords(strtolower($driver));
            if(class_exists($class)) {
                self::$instance[$key] = new $class($options);
            }
        }

        return self::$instance[$key];
    }
}