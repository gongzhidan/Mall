<?php

namespace Mall\Framework\Cache;

abstract class AbstractStorage implements StorageInterface
{
	protected $prefix = 'mallguang::';

    protected $options = array();

    protected $ttl = 0;

	public function __construct($options = null)
	{
        if (!is_array($options)) {
            throw new \Exception('Cache options cannot be found');
        }

        $this->options = $options;
        $prefix = FALSE;

        if ($this->options['prefix']) {
            $prefix = str_replace(':', '', $this->options['prefix']) . ':';
        }

        $this->prefix = $prefix ?: $this->prefix;
    }
}