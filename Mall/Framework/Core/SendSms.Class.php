<?php

namespace Mall\Framework\Core;

use Mall\Framework\Core\ResultWrapper;
use Mall\Framework\SearchClient\Exception;

class SendSms {

    private static $_instance;

    protected $error;

    static public function getInstance()
    {
        $key = md5('sendSms');

        if (!self::$_instance[$key] instanceof self) {
            self::$_instance[$key] = new self;
        }

        return self::$_instance[$key];
    }


    /**
     * 发送短信消息方法
     * 官方文档地址： https://help.aliyun.com/document_detail/55451.html?spm=5176.doc55288.6.556.rZK9dj
     * 业务限流       https://help.aliyun.com/knowledge_detail/57710.html?spm=5176.doc55451.6.583.sHFzrz
     *
     * @param string $mobile 接收短信的手机号
     * @param string $signname  短信签名
     * @param string $templatecode  短信模板编号
     * @param string $templateparam 短信模板替换变量
     * @param string $source        发送短信业务来源
     */
    public function send($mobile, $signname, $templatecode, $templateparam, $source = '')
    {
        $options = Config::getInstance()->get('sms');

        if(empty($options)){
            throw  new \ErrorException('短信配置错误');
        }

        $post = [
            'phonenumbers' => $mobile,
            'signname' => $signname,
            'templatecode' => $templatecode,
            'templateparam' => $templateparam,
        ];
        $result = request($options['api_url'], $post);
        if( $result['httpcode'] == 200 && !empty($result['content'])){
            $resultContent = json_decode($result['content'],true);
            if($resultContent['state']){
                return ResultWrapper::success('发送成功');
            }else{
                return ResultWrapper::fail($resultContent['data'], ErrorCode::$apiNotResult);
            }
        }else{
            if(!empty($result['content'])){
                file_put_contents('/tmp/sendSms.log',date('Y-m-d H:i:s').var_export($result['content'],true).PHP_EOL,FILE_APPEND);
                return ResultWrapper::fail('请求接口失败,错误原因请留意日志', ErrorCode::$apiNotResult);
            }else{
                return ResultWrapper::fail('发送失败,接口未返回消息', ErrorCode::$apiNotResult);
            }
        }
    }

    public function getError()
    {
        return $this->error;
    }
}