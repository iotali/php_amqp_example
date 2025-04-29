# IoT平台服务端订阅的PHP消费者示例

这是一个简单的IoT平台服务端订阅功能的AMQP消费者示例，用PHP实现，用于订阅指定的队列并接收消息。

## 环境要求

- PHP 7.2+
- Composer
- PHP扩展：pcntl, sockets

## 安装

1. 克隆代码库后，使用Composer安装依赖：

```bash
composer install
```

## 配置说明

MQ连接信息已在代码中配置（src/AMQPConsumer.php）：

- 服务器: xxx.xxx.xxx.xxx
- 端口: 5672
- 用户名: ZHiGPqEVwC
- 密码: JyAfWnXoXR
- 队列名: queue_NUWiXpCwlE

## 功能特点

- 自动连接MQ服务器并订阅指定队列
- 接收并显示原始消息
- 自动格式化JSON消息，提高可读性
- 支持嵌套JSON结构的格式化显示
- 详细的日志记录

## 如何运行

执行以下命令：

```bash
php consumer.php
```

## 日志

日志将输出到控制台和`logs/amqp-consumer.log`文件中。

## 消息格式示例

原始消息格式：
```
"{\"content\":\"{\\\"clientIp\\\":\\\"172.16.116.61\\\",\\\"time\\\":\\\"2025-04-28 15:54:51\\\",\\\"productKey\\\":\\\"kdlxqvXX\\\",\\\"deviceName\\\":\\\"qLeIdlTiUI\\\",\\\"status\\\":\\\"online\\\"}\",\"generateTime\":1745826891,\"messageId\":\"1745826891727\",\"topic\":\"/as/mqtt/status/kdlxqvXX/qLeIdlTiUI\"}"
```

格式化后：
```json
{
  "content": "{\"clientIp\":\"172.16.116.61\",\"time\":\"2025-04-28 15:54:51\",\"productKey\":\"kdlxqvXX\",\"deviceName\":\"qLeIdlTiUI\",\"status\":\"online\"}",
  "generateTime": 1745826891,
  "messageId": "1745826891727",
  "topic": "/as/mqtt/status/kdlxqvXX/qLeIdlTiUI"
}

内层content内容:
{
  "clientIp": "172.16.116.61",
  "time": "2025-04-28 15:54:51",
  "productKey": "kdlxqvXX",
  "deviceName": "qLeIdlTiUI",
  "status": "online"
}
``` 