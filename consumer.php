<?php

require_once __DIR__ . '/vendor/autoload.php';

use AliyunIoT\Demo\AMQPConsumer;

// 创建日志目录
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// 捕获CTRL+C信号
declare(ticks = 1);
pcntl_signal(SIGINT, function () use (&$consumer) {
    echo PHP_EOL . "接收到退出信号，正在关闭连接..." . PHP_EOL;
    if (isset($consumer)) {
        // 先取消消费者
        $consumer->cancelConsumer();
        
        // 等待一小段时间确保取消消费者命令被处理
        sleep(1);
        
        // 关闭连接
        $consumer->close();
    }
    exit(0);
});

// 创建并启动消费者实例
$consumer = new AMQPConsumer();

// 连接服务器
if ($consumer->connect()) {
    // 开始消费消息
    $consumer->consume();
}

// 关闭连接
$consumer->close(); 