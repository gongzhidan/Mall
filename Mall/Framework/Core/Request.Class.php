<?php

namespace Mall\Framework\Core;

class Request
{
    /**
     *
     * 存放当前请求的参数
     * @var array
     */
    protected $params;

    static protected $instance;

    private function __construct()
    {
    }

    static public function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        self::$instance->params = self::params();

        return self::$instance;
    }

    /**
     * 获取请求的参数集。
     * 支持http访问的参数 以及 命令行下访问的参数
     * demo1：php test.php -p3 -t=abc --opt=valopt --opt2 valopt2
     * demo2 http://test.kimiss.com/index.php?c=xxx&a=ddd
     * @return array
     */
    public static function params()
    {
        if (self::isCLI()) {
            return self::cliParams();
        } else {
            return $_REQUEST;
        }
    }

    /**
     *
     * 获取命令行下传递进来的参数
     * 只支持以 - 或 -- 开头的参数
     * demo：php test.php -p3 -t=abc --opt=valopt --opt2 valopt2
     * @return array
     */
    private static function cliParams()
    {
        $result = array();
        $params = $GLOBALS['argv'];

        array_shift($params);
        do {
            $tmpEachResult = array_shift($params);
            if (!$tmpEachResult) {
                break;
            }
            $p = $tmpEachResult;
            if ($p{0} == '-') {
                $pname = substr($p, 1);
                $value = false;
                if ($pname{0} == '-') {// 长选项 (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                } else {// 短选项
                    if (strpos($p, '=') !== false) {
                        // value specified inline (-<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 1), 2);
                    } else if (strlen($p) > 1) {
                        $pname = substr($p, 1, 1);
                        $value = substr($p, 2);
                    }
                }
                # 如果上面没有取到值，并且下一个不是以-开头的，则下一个值为当前参数的值
                $nextparm = current($params);
                if ($value === false
                    && $nextparm !== false
                    && $nextparm{0} != '-'
                ) {
                    $value = array_shift($params);
                }
                $result[$pname] = (string)$value;// 将 false转为空串，以便与http访问时对参数的处理一致
            } else {
                # 不是以-指定开始的参数，一律丢弃
                //$result[] = $p;
            }
        } while (true);

        return $result;
    }


    /**
     *
     * 分发请求，调用Controller以及对应的action
     */
    public function dispatch()
    {
        $controllerName = self::getController();

        if (!class_exists($controllerName)) {
            throw new \Exception("controller: {$controllerName} not exists!");
        }

        $action = self::getAction();

        $controller = new $controllerName();
        $controller->$action();
//
//        if (method_exists($controller, 'display')) {
//            $controller->display();
//        }
    }

    public function getModule()
    {
        $m = self::param('m') ?: '';
        if ($m) {
            return ucfirst(trim($m, '_'));
        }
    }

    public function getModuleType()
    {
        $t = self::param('t') ?: '';
        if ($t) {
            return ucfirst(trim($t, '_'));
        }
    }

    public function getController()
    {
        $c = self::param('c');
        $c = trim($c, '_');
        //默认给一个控制器名
        if (!$c) {
            $c = DEFAULT_CLASS_CONTROLLER_NAME;
        }

        $arr_class_path = array_map(function ($tmpV) {
            return ucfirst($tmpV);
        }, explode('_', $c));
        $c = join('\\', $arr_class_path);
        $NS = Config::getInstance()->get('app_name');
        $controller = "{$NS}\\Controller\\";
        if ($t = self::getModuleType()) {
            $controller .= $t . "\\";
        }
        if ($m = self::getModule()) {
            $controller .= $m . "\\";
        }
        $controller .= "{$c}";
        return $controller;
    }

    public function getAction()
    {
        $action = self::param('a');
        //默认给一个方法名
        if (!$action) {
            $action = DEFAULT_CLASS_ACTION_NAME;
        }
        return $action;
    }

    public function param($name)
    {
        if (isset($this->params[$name])) {
            return self::paramFilter($this->params[$name]);
        }

        return false;
    }

    /**
     * 是否POST请求
     *
     * @return boolean
     */
    static public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST' ? true : false;
    }

    /**
     * 是否GET请求
     *
     * @return boolean
     */
    static public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] == 'GET' ? true : false;
    }

    /**
     * 是否处于命令行下
     *
     * @return Boolean
     */
    static public function isCLI()
    {
        if (php_sapi_name() == "cli" || empty($_SERVER['PHP_SELF'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取客户端真实Ip
     * @return string
     */
    static public function get_onlineip()
    {
        $onlineip = '';

        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $onlineip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $onlineip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $onlineip = $_SERVER['REMOTE_ADDR'];
        }

        return $onlineip;
    }


    /**
     * 获取$_SERVER下面的方法
     */
    function getServerParam($param)
    {
        if (isset($_SERVER[$param])) {
            return self::paramFilter($_SERVER[$param]);
        }

        return '';
    }

    /**
     * 全局的接收参数过滤
     */
    private function paramFilter($param)
    {
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                if(is_array($value)){
                    self::paramFilter($value);
                    continue;
                }
                if(!is_numeric($value)){
                    $param[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                }
            }
        } else if (!empty($param) && !is_numeric($param)) {
            $param = htmlspecialchars(trim($param), ENT_QUOTES, 'UTF-8');
        }

        return $param;
    }

    /**
     * 获取上传的文件信息
     *
     * @access public
     * @param string|array $name 名称
     *
     * @return null|array| \Mall\Framework\Core\File
     */
    public function file($name = '')
    {
        $files = isset($_FILES) ? $_FILES : [];
        if (is_array($name)) {
            return $files = array_merge($files, $name);
        }
        if (!empty($files)) {
            // 处理上传文件
            $array = [];
            foreach ($files as $key => $file) {
                if (is_array($file['name'])) {
                    $item = [];
                    $keys = array_keys($file);
                    $count = count($file['name']);
                    for ($i = 0; $i < $count; $i++) {
                        if (empty($file['tmp_name'][$i]) || !is_file($file['tmp_name'][$i])) {
                            continue;
                        }
                        $temp['key'] = $key;
                        foreach ($keys as $_key) {
                            $temp[$_key] = $file[$_key][$i];
                        }
                        $item[] = (new File($temp['tmp_name']))->setUploadInfo($temp);
                    }
                    $array[$key] = $item;
                } else {
                    if ($file instanceof File) {
                        $array[$key] = $file;
                    } else {
                        if (empty($file['tmp_name']) || !is_file($file['tmp_name'])) {
                            continue;
                        }
                        $array[$key] = (new File($file['tmp_name']))->setUploadInfo($file);
                    }
                }
            }
            if (strpos($name, '.')) {
                list($name, $sub) = explode('.', $name);
            }
            if ('' === $name) {
                // 获取全部文件
                return $array;
            } elseif (isset($sub) && isset($array[$name][$sub])) {
                return $array[$name][$sub];
            } elseif (isset($array[$name])) {
                return $array[$name];
            }
        }

        return;
    }

}