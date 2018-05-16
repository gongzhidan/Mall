<?php
namespace Mall\Framework\Swoole;

class Client{
    private $client;

    public function __construct($options){
        class_exists('Swoole_Client') or die("Swoole_Client: check swoole extension");
        $this->client = new \Swoole_Client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
        $this->client->connect($options['host'], $options['port'], isset($options['time_out'])?$options['time_out']:2);
    }

    /**
     * 发送Swoole 数据
     */
    public function sendMsg($msg)
    {
        //发送给消息到服务端
        $this->client->send( $msg );

        //接受服务端发来的信息
        $message = $this->client->recv();
        if(!$message){
            throw new \Exception(socket_strerror($this->client->errCode), $this->client->errCode);
        }

        //关闭客户端
        //$this->client->close();

        return $message;
    }
}