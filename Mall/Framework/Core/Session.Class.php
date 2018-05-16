<?php

namespace Mall\Framework\Core;

use Mall\Framework\Session\SaveHandler\SaveHandlerInterface;
use Mall\Framework\Session\SaveHandler\RedisSession;

class Session
{
    private static $_instance;

    protected static $defaultOptions = array(
        'save_path'               => null,
        'name'                    => null, /* this should be set to a unique value for each application */
        'save_handler'            => null,
        //'auto_start'            => null, /* intentionally excluded (see manual) */
        'gc_probability'          => null,
        'gc_divisor'              => null,
        'gc_maxlifetime'          => null,
        'serialize_handler'       => null,
        'cookie_lifetime'         => null,
        'cookie_path'             => null,
        'cookie_domain'           => null,
        'cookie_secure'           => null,
        'cookie_httponly'         => null,
        'use_cookies'             => null,
        'use_only_cookies'        => 'on',
        'referer_check'           => null,
        'entropy_file'            => null,
        'entropy_length'          => null,
        'cache_limiter'           => null,
        'cache_expire'            => null,
        'use_trans_sid'           => null,
        'bug_compat_42'           => null,
        'bug_compat_warn'         => null,
        'hash_function'           => null,
        'hash_bits_per_character' => null
    );

    static public function getInstance()
    {
        $key = 'SessionRedis';

        if (!isset(self::$_instance[$key])) {
            $obj = new self();
            $obj->connect();

            self::$_instance[$key] = $obj;
        }

        return self::$_instance[$key];
    }

    public function connect()
    {
        $saveHandler = new RedisSession();

        $this->registerSaveHandler($saveHandler);

        session_start();
    }

    public static function setOptions($options)
    {
        if (!is_array($options)) {
            throw new \Exception(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

        // set the options the user has requested to set
        foreach ($options as $name => $value) {

            $name = strtolower($name);

            // set the ini based values
            if (array_key_exists($name, self::$defaultOptions)) {
                ini_set("session.$name", $value);
            }
        }
    }

    public function set($name, $value)
    {
        if (!self::sessionExists()) {
            return FALSE;
        }

        $_SESSION[$name] = $value;
    }

    public function get($name)
    {
        if (!self::sessionExists()) {
            return FALSE;
        }

        if (isset($_SESSION[$name])) {

            return $_SESSION[$name];

        }

        return FALSE;
    }

    /**
     * Does a session exist and is it currently active?
     *
     * @return bool
     */
    public static function sessionExists()
    {
        $sid = defined('SID') ? constant('SID') : false;
        if ($sid !== false && self::getId()) {
            return true;
        }
        if (headers_sent()) {
            return true;
        }
        return false;
    }

    /**
     * Set session ID
     *
     * @param  string $id
     */
    public static function setId($id)
    {
        if (self::sessionExists()) {
            throw new \Exception (
                'Session has already been started, to change the session ID call regenerateId()'
            );
        }

        session_id($id);
    }

    /**
     * Get session ID
     *
     * @return string
     */
    public static function getId()
    {
        return session_id();
    }

    /**
     * session destory
     */
    public function destory()
    {
        if (!self::sessionExists()) {
            return FALSE;
        }

        session_destroy();
    }

    /**
	 * Register Save Handler with ext/session
	 *
	 * @param Mall\Framework\SaveHandler\SaveHandlerInterface $saveHandler
	 * @return bool
	 */
	protected static function registerSaveHandler(SaveHandlerInterface $saveHandler)
	{
		return session_set_save_handler(
			array($saveHandler, 'open'),
			array($saveHandler, 'close'),
			array($saveHandler, 'read'),
			array($saveHandler, 'write'),
			array($saveHandler, 'destroy'),
			array($saveHandler, 'gc')
		);
	}
}