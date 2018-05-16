<?php

namespace Mall\Framework\Session\SaveHandler;

use Mall\Framework\Factory;

class RedisSession implements SaveHandlerInterface
{
	/**
	 * Session Save Path
	 *
	 * @var string
	 */
	protected $sessionSavePath;

	/**
	 * Session Name
	 *
	 * @var string
	 */
	protected $sessionName;

	/**
	 * The cache storage
	 *
	 * @var CacheStorage
	 */
	protected $cacheStorage;

	public function __construct($storage = '')
	{
        $storage = $storage ?: Factory::cache();
        $this->setCacheStorage($storage);
	}

    /**
     * Open Session
     *
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open($savePath, $name)
    {
        $this->sessionSavePath = $savePath;
        $this->sessionName = $name;

        return true;
    }

	/**
	 * Close session
	 *
	 * @return bool
	 */
	public function close()
	{
		return true;
	}

	/**
	 * Read session data
	 *
	 * @param string $id
	 * @return string
	 */
	public function read($id)
	{
        return $this->getCacheStorage()->get($id) ?: '';
    }

	/**
	 * Write session data
	 *
	 * @param string $id
	 * @param string $data
	 * @return bool
	 */
	public function write($id, $data)
	{
        return $this->getCacheStorage()->set($id, $data);
	}

	/**
	 * Destroy session
	 *
	 * @param string $id
	 * @return bool
	 */
	public function destroy($id)
	{
		return $this->getCacheStorage()->delete($id);
	}

	/**
	 * Garbage Collection
	 *
	 * @param int $maxlifetime
	 * @return bool
	 */
	public function gc($maxlifetime)
	{
		return true;
	}

	/**
	 * Set cache storage
	 *
	 * @param  CacheStorage $cacheStorage
	 * @return Cache
	 */
	public function setCacheStorage($cacheStorage)
	{
		$this->cacheStorage = $cacheStorage;
		return $this;
	}

	/**
	 * Get cache storage
	 *
	 * @return CacheStorage
	 */
	public function getCacheStorage()
	{
		return $this->cacheStorage;
	}
}
