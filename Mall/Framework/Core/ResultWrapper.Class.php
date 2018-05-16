<?php

namespace Mall\Framework\Core;

/**
 * 内部结果传递类
 * 目的：标准化方法执行后返回值的表述。
 *
 */
class ResultWrapper
{
    /** @var Boolean $status 存放处理状态 */
    private $status;

    /** @var mixed $data 存放处理状态 */
    private $data;

    /** @var int $errorCode 错误编码 */
    private $errorCode;

    /**
     * 构造函数私有，这个类不允许从外部实例化
     */
    private function __construct()
    {
    }


    /**
     * 表示处理成功
     *
     * @param mixed $data
     *
     * @return ResultWrapper
     */
    public static function success($data = null)
    {
        $objResultWrapper = new self();

        $objResultWrapper->status = true;
        $objResultWrapper->data = $data;

        return $objResultWrapper;
    }

    /**
     * 表示处理失败
     *
     * @param mixed $data
     *
     * @return ResultWrapper
     */
    public static function fail($data = null, $errorCode)
    {
        $objResultWrapper = new self();

        $objResultWrapper->status = false;
        $objResultWrapper->data = $data;
        $objResultWrapper->errorCode = $errorCode;

        return $objResultWrapper;
    }

    /**
     * 判断处理是否成功
     *
     * @return Boolean
     */
    public function isSuccess()
    {
        return $this->status === true;
    }

    /**
     * 获取数据
     * 
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取错误编码
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}