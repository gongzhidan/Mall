<?php

namespace Mall\Framework\Swoole;

class Server
{
    private $moduleServer;

    private $moduleData;

    private $serviceModuleName;

    private $isSetConfig;

    function __construct($options = [])
    {
        class_exists('Swoole_Server') or die("Swoole_Server: check swoole extension");
        $this->moduleServer = new \Swoole_Server($options['host'], $options['port']);

        if (!isset($options['module']) && $options['module']) {
            throw new \Exception('Please define the module name first');
        }
        $this->serviceModuleName = ucfirst($options['module']);
    }

    public function setConfig($options = [])
    {
        $defaultOptions = [
            'worker_num' => 8, // 建议开启核数的1-4倍
            'daemonize' => true,
        ];
        $options = $options ?: $defaultOptions;

        $this->moduleServer->set($options);
        $this->isSetConfig = true;

        return $this;
    }

    public function begin()
    {
        if (!$this->isSetConfig) {
            throw new \Exception('Please set the configuration file first');
        }

        $this->registerEvent();

        $this->moduleServer->start();
    }

    private function registerEvent()
    {
        $this->moduleServer->on('Start', array($this, 'onStart'));
        $this->moduleServer->on('Connect', array($this, 'onConnect'));
        $this->moduleServer->on('Receive', array($this, 'onReceive'));
        $this->moduleServer->on('Close', array($this, 'onClose'));
        $this->moduleServer->on('ManagerStart', array($this, 'onManagerStart'));
        $this->moduleServer->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->moduleServer->on('WorkerStop', array($this, 'onManagerStop'));
    }

    //启动server时候会触发。
    public function onStart($server)
    {
        echo "Start\n";
    }

    //client连接成功后触发。
    public function onConnect($server, $fd, $from_id)
    {
        //$a = $serv->send( $fd, "Hello {$fd}!" );
        //var_dump($a); //成功返回true
        echo "Client id {$fd}:{$from_id} \n";

    }

    //接收client发过来的请求
    public function onReceive(\swoole_server $server, $fd, $from_id, $moduleData)
    {

        //$serv->send($fd, $data);
        //关闭该work进程
        //$serv->stop();
        //宕机
        //$serv->shutdown();
        $moduleData = str_replace(PACKAGE_EOF, '', $moduleData);
        echo "Get Message From Client {$fd}:{$moduleData}\n";

        if (!$moduleData = json_decode($moduleData, true)) {
            throw new \Exception('Please send data to JSON format');
        }

        $namespace = '\Service\\' . $this->serviceModuleName . '\\Controller\\' . $moduleData['contorller'];
        $resource = new $namespace();
        if (isset($moduleData['params']) && $moduleData['params']) {
            $data = call_user_func([$resource, $moduleData['action']], $moduleData['params']);
        } else {
            $data = call_user_func([$resource, $moduleData['action']]);
        }

        $result = $server->send($fd, $data);

        if(!$result){
            throw new \Exception( $this->moduleServer->getLastError());
        }

        //主动关闭 客户端连接,也会触发onClose事件
        //$server->close($fd);

        //$list = $serv->connection_list();
        //           foreach ($list as $fd) {
        //               $serv->send($fd, $data);
        //           }
    }

    //客户端断开触发
    public function onClose(\swoole_server $server, $fd, $from_id)
    {
        echo "Client {$fd} close connection\n";
    }

    public function onManagerStart(\swoole_server $server) {
        echo "On manager start.";
    }

    public function onManagerStop(\swoole_server $server) {
        echo "On manager start.";
    }

    public function onWorkerStart(\swoole_server $server, $workerId) {
        echo $workerId . '---';
    }

    public function getData()
    {
        return $this->moduleData;
    }

}