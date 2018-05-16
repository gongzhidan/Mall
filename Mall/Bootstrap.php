<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__);
define('FRAMEWORK_PATH', ROOT_PATH . DS . 'Framework');
define('FRAMEWORK_NAME', 'Mall');
define('PACKAGE_EOF', '--END--');

# 默认将显示错误关闭
error_reporting(E_ALL);
ini_set('display_errors', false);
# 记录所有错误到日志文件
define('LOG_ERROR', TRUE);

# 设置默认时区
date_default_timezone_set('PRC');

require_once FRAMEWORK_PATH . DS . 'Common' . DS . 'Functions.php';
require_once FRAMEWORK_PATH . DS . 'Core' . DS . 'AutoLoad.Class.php';

spl_autoload_register(array('Mall\Framework\Core\AutoLoad', '_autoLoad'));
