<?php

use Workerman\Worker;
require_once '../Workerman/Autoloader.php';

// 保存所有用户
$allUsers=[];
// 有连接时调用
function connect($connection)
{
	$connection->onWebSocketConnect = function ($connection) {
        global $allUsers;
        // 保存当前用户到用户列表
        $allUsers[$connection->id] = ['username'=>$_GET['username']];
        // 保存当前用户名到当前连接的 $connection 对象上
        $connection->username = $_GET['username'];
        // 给所有客户端发消息
        sendToAll([
            'username'=>$connection->username,
            'content'=>'加入了聊天室',
            'datetime'=>date('Y-m-d H:i'),
            'allUsers'=>$allUsers,
        ]);
    };
}
// 当收到数据时调用
function message($connection, $data)
{
    // 转发消息给所有客户端
    sendToAll([
        'username'=>$connection->username,
        'content'=>$data,
        'datetime'=>date('Y-m-d H:i')
    ]);
}
// 当有客户端断开连接时调用
function close($connection)
{
    global $allUsers;
    // 从用户列表数组中删除当前退出的用户
    unset($allUsers[$connection->id]);
    // 给所有用户发消息
    sendToAll([
        'username'=>$connection->username,
        'content'=>'离开了聊天室',
        'datetime'=>date('Y-m-d H:i'),
        'allUsers'=>$allUsers
    ]);
}
// 给所有人发消息
function sendToAll($data)
{
    global $worker;
    if(is_array($data))
    {
        $data = json_encode($data);
    }
    // 循环所有客户端
    foreach($worker->connections as $c)
    {
        $c->send($data);
    }
}

// 绑定端口
$worker = new Worker('websocket://0.0.0.0:8888');
// 设置进程数为1
$worker->count = 1; 
// 设置回调函数
$worker->onConnect = 'connect';
$worker->onMessage = 'message';
$worker->onClose = 'close';
// 启动
Worker::runAll();