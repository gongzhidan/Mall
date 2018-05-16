<?php

namespace Mall\Framework\Core;

class Aes
{
    private $_iv = "0102030405060708";

    private $_encryptKey;

    private static $_instance;

    public function __construct ($encryptKey = '')
    {
        $this->_encryptKey = $encryptKey ?: defined('SITE_SECRET') ?: 'h7WPzPi87}MC7AxM6Jz)L?9%NXy*jB';
    }

    public static function getInstance($encryptKey = [])
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($encryptKey);
        }

        return self::$_instance;
    }

    public function encrypt($encryptStr)
    {
        $localIV = $this->_iv;
        $encryptKey = $this->_encryptKey;

        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);

        mcrypt_generic_init($module, $encryptKey, $localIV);

        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = $block - (strlen($encryptStr) % $block);
        $encryptStr .= str_repeat(chr($pad), $pad);

        $encrypted = mcrypt_generic($module, $encryptStr);

        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);

        return base64_encode($encrypted);

    }

    public function decrypt($encryptStr)
    {
        $localIV = $this->_iv;
        $encryptKey = $this->_encryptKey;

        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);

        mcrypt_generic_init($module, $encryptKey, $localIV);

        $encryptedData = base64_decode($encryptStr);
        $encryptedData = mdecrypt_generic($module, $encryptedData);

        return $encryptedData;
    }
}