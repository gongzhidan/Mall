<?php

namespace Mall\Framework\Cache;

interface StorageInterface
{
	/**
	 * @param string $key
     *
	 * @return mixed
	 */
	public function get($key);

    /**
     * @param $key
     * @param null $value
     * @param null $ttl
     *
     * @return mixed
     */
    public function set($key, $value = null, $ttl = null);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete($key);

    /**
     * @param string $key
     * @param mixed $value
     * @param string $type start | end
     *
     * @return int|bool The new value on success, false on failure
     */
    public function push($key, $value, $type = 'end');
    /**
     * @param string $key
     * @param string $type start | end
     * @return int|bool The new value on success, false on failure
     */
    public function pop($key, $type = 'start');

}