<?php

namespace Mall\Framework;

use Mall\Framework\Cache\Redis;
use Mall\Framework\Core\Cache;
use Mall\Framework\Core\Config;
use Mall\Framework\Core\Cookie;
use Mall\Framework\Core\Db;
use Mall\Framework\Core\RedisQueue;
use Mall\Framework\Core\Request;
use Mall\Framework\Core\View;
use Mall\Framework\Core\Session;
use Mall\Framework\Core\SendMail;
use Mall\Framework\Core\SendSms;
use Mall\Framework\Core\Swoole;
use Mall\Framework\Core\Aes;
use Mall\Framework\Core\Search;
use Mall\Framework\Core\BaseImg;
use Mall\Framework\Core\VerificationCode;
use Mall\Framework\Core\Logs;


abstract class Factory
{
    public static function cache($options = [])
    {
        $cacheConfig = self::config()->get('cache');
        $options = $options ?: $cacheConfig['default'];

        if (!$options) {
            throw new \Exception('The Factory::cache need default cache config');
        } else if (is_string($options)) {
            $options = $cacheConfig[$options];
        }

        if (!is_array($options)) {
            throw new \Exception('The Factory::cache config is error');
        }

        return Cache::getInstance($options);
    }

    public static function config()
    {
        return Config::getInstance();
    }

    public static function logs($logPath='', $logSaveFileApp='', $logSystem = '')
    {
        return Logs::getInstance($logPath, $logSaveFileApp, $logSystem);
    }

    public static function cookie($options = [])
    {
        $options = $options ?: self::config()->get('cookie');
        if (!$options || !is_array($options)) {
            throw new \Exception('The Factory::cookie need default cookie config');
        }

        return Cookie::getInstance($options);
    }

    public static function request()
    {
        return Request::getInstance();
    }

    public static function view($options = [])
    {
        $options = $options ?: self::config()->get('smarty');
        if (!$options || !is_array($options)) {
            throw new \Exception('The Factory::view need default smarty config');
        }

        return View::getInstance($options);
    }

    /**
     * 另外一种直接修改php.ini 配置方式，配置项如下:
     * session.save_handler = Redis
     * session.save_path = “tcp://192.168.5.114:6379?auth=password&database=3”
     */
    public static function session($options = [])
    {
        $options = $options ?: self::config()->get('session');

        if (!$options || !is_array($options)) {
            throw new \Exception('The Factory::session need default session config');
        }

        foreach ($options as $k => &$v) {
            if (empty($v)) {
                unset($options[$k]);
            }
            if ($k == 'save_path') {
                $options[$k] = urldecode($options[$k]);
            }
        }
        Session::setOptions($options);

        return Session::getInstance();
    }

    public static function sendmail()
    {
        return SendMail::getInstance();
    }

    /**
     * 发送短信消息
     *
     * @return mixed
     */
    public static function sendSms()
    {
        return SendSms::getInstance();
    }

    public static function db($options = 'default')
    {

        $options = $options ?: self::config()->get('db');
        if (!$options) {
            throw new \Exception('The Factory::db need default db config');
        } else if (is_string($options)) {
            $dbconfig = self::config()->get('db');
            $options = $dbconfig[$options];
        }

        if (!is_array($options)) {
            throw new \Exception('The Factory::db config is error');
        }

        return Db::getInstance($options);
    }

    public static function swoole($options = [], $driver = 'Client')
    {

        if (!is_array($options) || empty($options)) {
            throw new \Exception('Need default Swoole ' . $driver . ' config');
        }

        return Swoole::getInstance($options, $driver);
    }

    public static function aes($encryptKey = '')
    {
        return aes::getInstance($encryptKey);
    }

    public static function search($options = 'default')
    {
        $options = $options ?: self::config()->get('search');
        if (!$options) {
            throw new \Exception('The Factory::search need default search config');
        } else if (is_string($options)) {
            $dbconfig = self::config()->get('search');
            $options = $dbconfig[$options];
        }

        if (!is_array($options)) {
            throw new \Exception('The Factory::search config is error');
        }

        $search = new Search($options);

        return $search->getConnection();
    }

    public static function baseImg()
    {
        return BaseImg::getInstance();
    }

    public static function redisQueue(Redis $redis, $queueName = '')
    {
        return RedisQueue::getInstance($redis, $queueName);
    }

    public static function verificationCode()
    {
        return VerificationCode::getInstance();
    }
   
}