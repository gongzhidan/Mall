<?php

function loadFile($filepath, $return = false)
{
    if (file_exists($filepath)) {
        if ($return) {
            return require_once $filepath;
        } else {
            require_once $filepath;
        }
    } else {
        throw new \Exception($filepath . ' file cannot be found');
    }
}

function xssCheck()
{
    if (isset($_SERVER['REQUEST_URI'])) {
        $temp = strtoupper(urldecode(urldecode($_SERVER['REQUEST_URI'])));
        if (strpos($temp, '<') !== false || strpos($temp, '"') !== false || strpos($temp, 'CONTENT-TRANSFER-ENCODING') !== false) {
            throw new \Exception('request_tainting'.$temp);
        }
    }

    return true;
}

/**
 * @param $module string 服务模块名
 * @throws Exception
 */
function loadServiceConfig($module)
{
    if (!$module) {
        throw new \Exception('module not null');
    }

    $serviceConfig = ROOT_PATH . DS . '..' . DS . 'Service' . DS . ucfirst($module) . DS . 'Config.php';

    return loadFile($serviceConfig, true);
}

/**
 * 写入php错误日志
 *
 * @param string $errno 错误编号
 * @param string $errmsg 错误信息
 * @param string $filename 错误文件
 * @param string $linenum 错误行数
 * @param mixed $vars 错误参数
 *
 */
function php_error_log($errno, $errmsg, $filename, $linenum, $vars)
{
    if (!defined('E_STRICT')) define('E_STRICT', 2048);

    $errortype = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice'
    );
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
    $filename = str_replace("\\", '/', $filename);
    $dt = date('Y-m-d H:i:s');

    $err = "#####" . $dt . "\n";
    $err .= "```" . "\n";
    $err .= "datetime     ：" . $dt . "\n";
    $err .= "errornum     ：" . $errno . "\n";
    $err .= "errormsg     ：" . $errortype[$errno] . "\n";
    $err .= "errortype    ：" . $errmsg . "\n";
    $err .= "scriptname   ：" . $filename . "\n";
    if (in_array($errno, $user_errors)) {
        $err .= "scriptname   ：" . wddx_serialize_value($vars, "Variables") . "\n";
    }
    $err .= "scriptlinenum：" . $linenum . "\n";
    $err .= "```\n";

    $logDir = PROJECT_PATH . DS . 'Storage' . DS . 'Logs' . DS . 'SysError' . DS;
    $logFile = $logDir . date('m-d') . '.md';
    if (!is_dir($logDir)) {
        @mkdir($logDir);
    }

    @error_log($err, 3, $logFile);
    @chmod($logFile, 0777);
}


function shutdown_function()
{
    $e = error_get_last();
    if (isset($e['type'])) {
        if (in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
            php_error_log($e['type'], $e['message'], $e['file'], $e['line'], NULL);
        }
    }
}


if (defined('LOG_ERROR') && LOG_ERROR) {
    register_shutdown_function('shutdown_function');
}

function isMobile($mobile)
{
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile)
        ? true
        : false;
}

 /**电子邮箱格式校验*/
 function isEmail($email){
   $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";

     return preg_match_all($pattern, $email) ? true : false;
 }



/**
 * 利用curl模拟浏览器发送请求
 *
 * @param string $url 请求的URL
 * @param array|string $post post数据
 * @param int $timeout 执行超时时间
 * @param boolean $sendcookie 是否发送当前cookie
 * @param array $options 可选的CURL参数
 * @return array
 */
function request($url, $post = null, $timeout = 40, $sendcookie = true, $options = array(), $ssl = false)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'internalloginuseragent');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout ? $timeout : 40);

    if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    if ($sendcookie) {
        $cookie = '';
        foreach ($_COOKIE as $key => $val) {
            $cookie .= rawurlencode($key) . '=' . rawurlencode($val) . ';';
        }
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    }

    if (!ini_get('safe_mode') && ini_get('open_basedir') == '') {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  //检查证书中是否设置域名且是否与提供的主机名匹配
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //是否只信任CA颁发的证书
    curl_setopt($ch, CURLOPT_SSLVERSION, 1);  //CURL_SSLVERSION_TLSv1
    if ($ssl){
        curl_setopt($ch,CURLOPT_SSLCERT,'/data/www/weixinCert/apiclient_cert.pem');
        curl_setopt($ch,CURLOPT_SSLKEY,'/data/www/weixinCert/apiclient_key.pem');
        curl_setopt($ch,CURLOPT_CAINFO,'/data/www/weixinCert/rootca.pem');
    }

    foreach ($options as $key => $value) {
        curl_setopt($ch, $key, $value);
    }

    $ret = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    if (!$content_length) $content_length = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $errorMsg = curl_error($ch);
    curl_close($ch);
    return array(
        'httpcode' => $httpcode,
        'errorMsg' => $errorMsg,
        'content_length' => $content_length,
        'content_type' => $content_type,
        'content' => $ret
    );
}

/**
 * 支持代理的请求方法
 * @param $url
 * @return array
 */
function get_data($url, $cookie, $referer){
    $ip_long = array(
        array('607649792', '608174079'), //36.56.0.0-36.63.255.255
        array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
        array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
        array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
        array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
        array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
        array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
        array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
        array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
        array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
    );

    $rand_key = mt_rand(0, 9);
    $ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));//随机生成国内某个ip

    $header = array(
        "CLIENT-IP:{$ip}",
        "X-FORWARDED-FOR:{$ip}",
    );

    $cookie_jar = @tempnam('/tmp/cookies/','cookie');

    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER  => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_ENCODING =>  'gzip,deflate',
        CURLOPT_FOLLOWLOCATION => 1, //302
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.109 Safari/537.36",
        //CURLOPT_PROXY => 'http://'.$ip,
        CURLOPT_COOKIE=> $cookie,
        CURLOPT_REFERER => $referer,
        CURLOPT_COOKIEFILE => $cookie_jar,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_COOKIEJAR => $cookie_jar,
        CURLOPT_TIMEOUT => 60
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $html = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    unset($ch);

    return [
        'httpcode' => $httpcode,
        'info' => $info,
        'error'=> $error,
        'content' => $html,
    ];
}

/**
 * 获取当前毫秒时间戳
 */
function msectime() {
    list($msec, $sec) = explode(' ', microtime());
    return  (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
}


/**
 * 断点调试输出方法
 *
 * @param mixed $msg 输出的内容
 *
 * @return string
 */
function  V($msg)
{
    echo "<pre>";
    var_dump($msg);
    exit();
}

/**
 * 校验日期格式是否正确
 *
 * @param string $date 日期
 * @param array $formats 需要检验的格式数组
 *
 * @return bool|int
 */
function checkDateIsValid($date, $formats = ["Y-m-d", "Y/m/d", "Y-m-d H:i:s", "Y/m/d H:i:s"])
{
    $unixTime = strtotime($date);
    if (!$unixTime) {
        return false;
    }

    foreach ($formats as $format) {
        if (date($format, $unixTime) == $date) {
            return $unixTime;
        }
    }

    return false;
}

/**
 * 二维数组根据某个字段排序
 *
 * @param array $needArray 需要排序的二维数组
 * @param string $field 根据的字段排序
 * @param array $sort 排序规则ASC 升序 | DESC 降序
 *
 * @return array;
 */
function arrayMultiSort(array $needArray, $field, $sort = 'ASC')
{
    $rule = [];
    $arrSort = [];

    $sort = strtoupper($sort);
    $rule['direction'] = 'SORT_' . $sort;
    $rule['field'] = $field;

    foreach ($needArray AS $uniqid => $row) {
        foreach ($row AS $key => $value) {
            $arrSort[$key][$uniqid] = $value;
        }
    }
    if ($rule['direction']) {
        array_multisort($arrSort[$rule['field']], constant($rule['direction']), $needArray);
    }

    return $needArray;
}

/**
 * 利用firephp输出调试到header
 *
 * 需要安装：
 * firefox：FireBug | chrome：FirePHP for Chrome
 *
 * @param mixed $message 要输出的信息
 * @param bool $showtime 是否显示执行时间
 */
function console($message, $showtime = false)
{
    static $lasttime = TIME;
    static $_getInstance;

    $thistime = microtime(true);
    $usedtime = $thistime - $lasttime;
    $lasttime = $thistime;

    $label = $showtime ? sprintf("%09.5fs", $usedtime) : NULL;

    if (is_null($_getInstance)) {
        $_getInstance = \Mall\Framework\Core\FirePHP::getInstance(true);
    }

    $_getInstance->info($message, $label);
}

/**
 * 检测数组的值不允许为空
 *
 * @param array $params 需要检测的数组
 * @param array $checkKey 需要检测的key默认全部检测
 * @param array $excludeKey 过虑不检测的Key
 *
 * @return string
 */
function checkNotEmpty($params, $excludeKey = [], $checkKey = [])
{
    $newParams = [];
    if ($checkKey && is_array($checkKey)) {
        foreach ($checkKey as $key) {
            if (isset($params[$key])) {
                $newParams[$key] = $params[$key];
            }
        }
    }

    if ($newParams) {
        $params = $newParams;
    }

    if (is_array($params)) {
        foreach ($params as $k => $v) {
            if (in_array($k, $excludeKey, true)) {
                continue;
            }

            if (is_array($v)) {
                checkNotEmpty($v);
            }

            if (empty($v)) {
                return $k . ' 不允许为空';
            }
        }
    }
}

/**
 * 友好格式化时间
 * @param int $timestamp 时间
 * @param array $formats
 * @return string
 */
function formatDateTime($timestamp, $formats = null)
{
    if ($formats == null) {
        $formats = array(
            'DAY' => '%s天前',
            'DAY_HOUR' => '%s天%s小时前',
            'HOUR' => '%s小时',
            'HOUR_MINUTE' => '%s小时%s分前',
            'MINUTE' => '%s分钟前',
            'MINUTE_SECOND' => '%s分钟%s秒前',
            'SECOND' => '%s秒前',
        );
    }

    /* 计算出时间差 */
    $seconds = time() - $timestamp;
    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $days = floor($hours / 24);

    if ($days > 0 && $days < 31) {
        $diffFormat = 'DAY';
    } elseif ($days == 0) {
        $diffFormat = ($hours > 0) ? 'HOUR' : 'MINUTE';
        if ($diffFormat == 'HOUR') {
            $diffFormat .= ($minutes > 0 && ($minutes - $hours * 60) > 0) ? '_MINUTE' : '';
        } else {
            $diffFormat = (($seconds - $minutes * 60) > 0 && $minutes > 0)
                ? $diffFormat . '_SECOND' : 'SECOND';
        }
    } else {
        $diffFormat = 'TURE_DATE_TIME';//超出30天, 正常时间显示
    }

    $dateDiff = null;
    switch ($diffFormat) {
        case 'DAY':
            $dateDiff = sprintf($formats[$diffFormat], $days);
            break;
        case 'DAY_HOUR':
            $dateDiff = sprintf($formats[$diffFormat], $days, $hours - $days * 60);
            break;
        case 'HOUR':
            $dateDiff = sprintf($formats[$diffFormat], $hours);
            break;
        case 'HOUR_MINUTE':
            $dateDiff = sprintf($formats[$diffFormat], $hours, $minutes - $hours * 60);
            break;
        case 'MINUTE':
            $dateDiff = sprintf($formats[$diffFormat], $minutes);
            break;
        case 'MINUTE_SECOND':
            $dateDiff = sprintf($formats[$diffFormat], $minutes, $seconds - $minutes * 60);
            break;
        case 'SECOND':
            $dateDiff = sprintf($formats[$diffFormat], $seconds);
            break;
        default:
            $dateDiff = date('Y-m-d H:i:s');
    }
    return $dateDiff;
}

/**
 * 数组交叉排序并合并
 *
 * @param array $needo
 * @param array $needt
 *
 * @return array
 */
function sortCross(array $needo = [], array $needt = [])
{
    $newData = [];
    $needoCount = count($needo);
    $needtCount = count($needt);
    $size = $needoCount > $needtCount ? $needoCount : $needtCount;

    for ($i = 0; $i < $size; $i++) {
        if (isset($needo[$i])) {
            array_push($newData, $needo[$i]);
        }
        if (isset($needt[$i])) {
            array_push($newData, $needt[$i]);
        }
    }

    return $newData;
}

/**
 * 二维数组去重
 *
 * @param $list
 * @param $key
 *
 * @return array
 */
function assocUnique($list, $key)
{
    $arr = array();
    for ($i = 0; $i < count($list); $i++) {
        if (isset($list[$i][$key])) {
            if (!isset($arr[$list[$i][$key]])) {
                $arr[$list[$i][$key]] = $list[$i];
            }
        }
    }
    return array_values($arr);
}

/**
 * 为相对路径的缩略图加绝对地址
 *
 * @param string|array $thumbs 图片集合
 * @param string $key 图片地址的Key
 *
 * @return array
 */
function absoluteImg($thumbs, $key = '')
{
    if ($thumbs) {
        if (is_array($thumbs)) {
            foreach ($thumbs as &$t) {
                if ($key) {
                    $value = $t[$key];
                } else {
                    $value = $t;
                }
                if (!$value) {
                    continue;
                }
                if (!filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
                    if ($key) {
                        $t[$key] = URL_UPLOAD . $value;
                    } else {
                        $t = URL_UPLOAD . $value;
                    }
                }
            }
        }

        if (is_string($thumbs)) {
            if (!filter_var($thumbs, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
                $thumbs = URL_UPLOAD . $thumbs;
            }
        }
    }

    return $thumbs;
}

/**
 * 对内容字段进行 int 整形转换
 *
 * @param array $params
 *
 * @return array
 */
function contentIntValFormat(array $params)
{
    $intVal = [
        'id',
        'appid',
        'has_thumb',
        'catid',
        'created',
        'createdby',
        'published',
        'publishedby',
        'modified',
        'modifiedby',
        'digg',
        'pv',
        'virtual_pv',
        'status',
        'indexid',
        'contentid',
        'pagecount',
        'weight',
        'commend',
        'noexpiration',
        'commend_pv',
        'iscount'
    ];
    foreach ($params as $k => $param) {
        if (in_array($k, $intVal, true)) {
            $params[$k] = intval($param);
        }
    }

    return $params;
}

/**
 * 根据用户年月日算出星座
 *
 * @param string $dob 出生年月 1990-12-18
 *
 * @return string
 */
function constellation($dob)
{
    $dob = date("m-d", strtotime($dob));
    list($month, $day) = explode("-", $dob);

    $constellation = '';
    if (($month == 3 || $month == 4) && ($day > 22 || $day < 21)) {
        $constellation = "Aries";
    } else if (($month == 4 || $month == 5) && ($day > 22 || $day < 22)) {
        $constellation = "Taurus";
    } else if (($month == 5 || $month == 6) && ($day > 23 || $day < 22)) {
        $constellation = "Gemini";
    } else if (($month == 6 || $month == 7) && ($day > 23 || $day < 23)) {
        $constellation = "Cancer";
    } else if (($month == 7 || $month == 8) && ($day > 24 || $day < 22)) {
        $constellation = "Leo";
    } else if (($month == 8 || $month == 9) && ($day > 23 || $day < 24)) {
        $constellation = "Virgo";
    } elseif (($month == 9 || $month == 10) && ($day > 25 || $day < 24)) {
        $constellation = "Libra";
    } else if (($month == 10 || $month == 11) && ($day > 25 || $day < 23)) {
        $constellation = "Scorpio";
    } else if (($month == 11 || $month == 12) && ($day > 24 || $day < 23)) {
        $constellation = "Sagittarius";
    } else if (($month == 12 || $month == 1) && ($day > 24 || $day < 21)) {
        $constellation = "Cpricorn";
    } else if (($month == 1 || $month == 2) && ($day > 22 || $day < 20)) {
        $constellation = "Aquarius";
    } else if (($month == 2 || $month == 3) && ($day > 21 || $day < 21)) {
        $constellation = "Pisces";
    }

    return $constellation;
}

/**
 * 随机生成短信验证码
 * @param int $length 验证码长度
 * @return int
 */
function generate_code($length = 6)
{
    return rand(pow(10, ($length - 1)), pow(10, $length) - 1);
}

/**
 * 计算两点地理坐标之间的距离
 * @param  string $longitude1 起点经度
 * @param  string $latitude1 起点纬度
 * @param  string $longitude2 终点经度
 * @param  string $latitude2 终点纬度
 * @param  int $unit 单位 1:米 2:公里
 * @param  int $decimal 精度 保留小数位数
 * @return string
 */
function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
{

    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;

    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;

    if ($unit == 2) {
        $distance = $distance / 1000;
    }

    return round($distance, $decimal);

}

/**
 * @param int $month 月份
 * @param int $year 年
 *
 * @return mixed
 */
function getMonthBeginToEnd($month, $year = 0)
{
    $beginDate = 0;
    $endDate = 0;

    if ($month = intval($month)) {
        if (!$year) {
            $year = date('Y', time());
        }

        $beginDate = strtotime("{$year}-{$month}-01");
        $endDate = strtotime(date('Y-m-t', $beginDate));
    }

    return [
        'beginTime' =>$beginDate,
        'endTime' => $endDate
    ];
}

/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float   $price  商品价格
 * @return  string
 */
function priceFormat($price)
{
    return number_format($price, 2, '.', '');
}

/**
 * 数组转xml内容
 *
 * @param   array   $arr  数组
 * @return  string
 */
function arrayToXml($arr)
{
    $xml = '';
    foreach($arr as $key => $value){
        if( is_string($value) ){
            $xml .= '<'.$key.'><![CDATA['.$value.']]></'.$key.'>';
        }else{
            $xml .= '<'.$key.'>'.$value.'</'.$key.'>';
        }
    }
    return $xml;
}

/**
 * xml转json格式数据
 *
 * @param string $xml  xml格式文件内容
 * @return string
 */
function XML2JSON($xml)
{

    function normalizeSimpleXML($obj, &$result) {
        $data = $obj;
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $res = null;
                normalizeSimpleXML($value, $res);
                if (($key == '@attributes') && ($key)) {
                    $result = $res;
                } else {
                    $result[$key] = $res;
                }
            }
        } else {
            $result = $data;
        }
    }
    normalizeSimpleXML(simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA), $result);
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

/**
 * 分页方法
 */
function pageToOffset($page =1, $pageSize =10)
{
    $page = min($page, 50);
    return [
        'limit'  => $pageSize,
        'offset' => ($page - 1) * $pageSize,
    ];
}

/**
 * PHP截取UTF-8字符串，解决半字符问题。
 * 英文、数字（半角）为1字节（8位），中文（全角）为2字节
 * 取出的字符串, 当$len小于等于0时, 会返回整个字符串
 * @param string $str 源字符串
 * @param int    $len 左边的子串的长度
 */
function utf_substr($str,$len)
{
    $new_str =[];
    for($i=0;$i<$len;$i++){
        $temp_str=substr($str,0,1);
        if(ord($temp_str) > 127){
            if($i<$len){
                $new_str[]=substr($str,0,3);
                $str=substr($str,3);
            }
        }else{
            $new_str[]=substr($str,0,1);
            $str=substr($str,1);
        }
    }
    return join($new_str);
}


function isCardNo($vStr)
{
    $vCity = array(
        '11','12','13','14','15','21','22',
        '23','31','32','33','34','35','36',
        '37','41','42','43','44','45','46',
        '50','51','52','53','54','61','62',
        '63','64','65','71','81','82','91'
    );
    if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;
    if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
    $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
    $vLength = strlen($vStr);
    if ($vLength == 18) {
        $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
    } else {
        $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
    }
    if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
    if ($vLength == 18) {
        $vSum = 0;
        for ($i = 17 ; $i >= 0 ; $i--) {
            $vSubStr = substr($vStr, 17 - $i, 1);
            $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
        }
        if($vSum % 11 != 1) return false;
    }
    return true;
}

/**
 * try_catch 统一写入日志方法
 * @param object $logger Factory::logs
 * @param $exception  捕获的异常内容
 */
function catchError($logger, $exception)
{
    $error  = '错误类型：' . get_class($exception) . PHP_EOL;
    $error .= '错误代码：' . $exception->getCode() . PHP_EOL;
    $error .= '错误信息：' . $exception->getMessage() . PHP_EOL;
    $error .= '错误堆栈：' . $exception->getTraceAsString() . PHP_EOL;

    $logger && $logger->log($error, 'error');
}