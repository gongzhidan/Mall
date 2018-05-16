<?php
namespace Mall\Framework\Core;

class Db
{

    static protected $instance;

    static public function getInstance($options = [])
    {
        $options = $options ?: Config::getInstance()->get('db');
        $options['driver'] =  strtolower($options['driver']);

        $key = $options['driver'] . md5(implode(',', $options));
        if (!isset(self::$instance[$key])) {
            switch ($options['driver'])
            {
                case 'mssql':
                    $class = 'Mall\\Framework\\Db\\DbMssql';
                    break;
                case 'oci':
                    $class = 'Mall\\Framework\\Db\\DbOci';
                    break;
                default:
                    $class = 'Mall\\Framework\\Db\\Db';
                    break;
            }
            self::$instance[$key] = new $class($options);
        }

        return self::$instance[$key];
    }
}