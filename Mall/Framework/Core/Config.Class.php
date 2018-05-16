<?php

namespace Mall\Framework\Core;

class Config {

    static private $instance;

    /**
     * 所有app的配置集
     * array(
     * 		md5($app_conf_path) => array(
     *
     * 		),
     * 		... ,
     * );
     * @var array
     */
    static protected $appConfigs = array();

    /**
     *
     * 当前app的配置
     * @var array
     */
    protected $appConfig;

    /**
     *
     * 不允许直接 new
     */
    private function __construct() {}

    /**
     *
     * 载入配置，如果成功，返回instance
     * @param string $app_conf_path 配置文件的路径
     */
    static public function load($app_conf_path) {
        $app_conf_path = realpath($app_conf_path);
        $app_conf_key = md5($app_conf_path);
        if (!isset(self::$appConfigs[$app_conf_key])) {


            $config = include($app_conf_path);

            if (!is_array($config)) {
                throw new \Exception("load app_conf_path fail: {$app_conf_path}");
            }
            # 将conf的path存进来
            $config['conf_path'] = $app_conf_path;

            self::$appConfigs[$app_conf_key] = $config;
        }

        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        self::$instance->appConfig = self::$appConfigs[$app_conf_key];

        return self::$instance;
    }

    /**
     *
     * 单例模式
     */
    static public function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        if (is_null(self::$instance->appConfig)) {

            throw new \Exception('instance appconfig is null, pleace run Mall\Core\Config::load !');
        }

        return self::$instance;
    }

    /**
     *
     * 获取指定配置项的值
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        if (empty($key)) {
            return false;
        }

        $keys = explode('.', $key);

        $value = $this->appConfig;

        foreach ($keys as $tmpKey) {
            if (!isset($value[$tmpKey])) {
                return false;
            }
            $value = $value[$tmpKey];
        }
        return $value;
    }

    /**
     *
     * 获取指定配置项下的得某一项
     * @param string $configKey  配置文件下的某一项
     * @param string $appointKey 指定项下的某一个节点
     * @return mixed
     */
    public function getAppoint($configKey, $appointKey) {
        if (!$configKey) {
            return false;
        }

        $allConfig = $this->appConfig;

        if(empty($allConfig[$configKey])){
            return [];
        }

        if(isset($allConfig[$configKey][$appointKey])){
            return $allConfig[$configKey][$appointKey];
        }else{
            return $allConfig[$configKey];
        }
    }

    /**
     *
     * 获取所有app的配置集
     * @return array
     */
     public function all() {
        return $this->appConfig;
    }
}
