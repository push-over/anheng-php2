<?php

use Workerman\Worker;
require_once '../Workerman/Autoloader.php';

$allUsers = [];

$userConn = [];


function ws_connection($connection) {
    $connection->onWebSocketConnect = function($connection) {
        global $worker, $allUsers , $userConn;
        $connection->id = $_GET['id'];
        $connection->name = $_GET['username'];
        $userConn[$_GET['id']] = $connection;
        $allUsers[$_GET['id']] = $connection->name;
        
        sendToAll([
            'username' => $connection->name,
            'content' => '加入了聊天室',
            'datetime' => date('Y-m-d H:i'),
            'allUsers' => $allUsers
        ]);
    };
};

function ws_message($connection, $data) {

    $data = explode(':', $data);

    sendToAll([
        'username' => $connection->name,
        'content' => $data[1],
        'datetime' => date('Y-m-d H:i')
    ],$data[0]);
}

function ws_close($connection) {
    global $allUsers;
    unset($allUsers[$connection->id]);
    sendToAll([
        'username' => $connection->name,
        'content' => '离开了聊天室',
        'datetime' => date('Y-m-d H:i'),
        'allUsers' => $allUsers
    ]);
}

function sendToAll($data, $userId=null)
{   
    global $userConn, $allUsers;
    if(is_array($data)) {
        $data = json_encode($data);
    }
    if($userId == 'all' || $userId == null) {
        foreach($userConn as $c) {
            $c->send($data);
        }
    }else{
        $userConn[$userId]->send($data);
    }
}

$worker = new Worker('websocket://0.0.0.0:8888');
$worker->count = 1; 

$worker->onConnect = 'ws_connection';
$worker->onMessage = 'ws_message';
$worker->onClose = 'ws_close';

Worker::runAll();