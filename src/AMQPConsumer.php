<?php

namespace AliyunIoT\Demo;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * AMQP消费者示例
 */
class AMQPConsumer
{
    // 日志实例
    private $logger;

    // MQ连接信息
    private const HOST = 'xxx.xxx.xxx.xxx';
    private const PORT = 5672;
    private const USERNAME = 'wbfFUieAoR';
    private const PASSWORD = 'ctuXCKwyMK';
    private const QUEUE_NAME = 'queue_GPdMhwpisB';

    // 连接和通道
    private $connection;
    private $channel;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        // 初始化日志
        $this->logger = new Logger('amqp_consumer');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/amqp-consumer.log', Logger::INFO));
    }
    
    /**
     * 连接AMQP服务器
     */
    public function connect()
    {
        try {
            $this->logger->info('正在连接AMQP服务器...');
            
            $this->connection = new AMQPStreamConnection(
                self::HOST,
                self::PORT,
                self::USERNAME,
                self::PASSWORD
            );
            
            $this->channel = $this->connection->channel();
            $this->logger->info('AMQP服务器连接成功');
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('连接AMQP服务器失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 开始消费消息
     */
    public function consume()
    {
        if (!$this->channel) {
            $this->logger->error('通道未初始化');
            return;
        }
        
        $this->logger->info('等待接收消息...');
        
        // 设置回调函数
        $callback = function (AMQPMessage $message) {
            $originalMessage = $message->getBody();
            
            $this->logger->info('------ 消息内容 ------');
            $this->logger->info('接收到原始消息: ' . $originalMessage);
            
            // 处理消息内容
            $this->processMessage($originalMessage);
        };
        
        // 开始消费消息
        $this->channel->basic_consume(
            self::QUEUE_NAME,
            '',
            false,
            true,
            false,
            false,
            $callback
        );
        
        $this->logger->info('消费者已启动，按Ctrl+C退出程序...');
        
        // 保持进程运行
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }
    
    /**
     * 处理接收到的消息，尝试多种方式解析
     * 
     * @param string $message 原始消息
     */
    private function processMessage($message)
    {
        // 处理可能的额外引号
        $processedMessage = $message;
        if (substr($message, 0, 1) == '"' && substr($message, -1) == '"') {
            // 去掉开头和结尾的引号，并处理转义
            try {
                $processedMessage = json_decode($message, true);
                if (is_string($processedMessage)) {
                    $this->logger->info('处理后的消息 (去掉额外引号): ' . $processedMessage);
                } else {
                    $processedMessage = $message;
                }
            } catch (\Exception $e) {
                // 如果解析失败，保留原始消息
                $processedMessage = $message;
            }
        }
        
        // 尝试解析JSON
        try {
            $rootNode = json_decode($processedMessage, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // 格式化并输出
                $prettyJson = json_encode($rootNode, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $this->logger->info('格式化的JSON:' . PHP_EOL . $prettyJson);
                
                // 提取并显示topic字段
                if (isset($rootNode['topic'])) {
                    $this->logger->info('消息内容中的Topic字段: ' . $rootNode['topic']);
                }
                
                // 检查是否有content字段，并尝试解析
                if (isset($rootNode['content'])) {
                    try {
                        $content = $rootNode['content'];
                        
                        // 尝试解析为JSON
                        $contentNode = json_decode($content, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $prettyContent = json_encode($contentNode, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            $this->logger->info('内层content字段 (格式化):' . PHP_EOL . $prettyContent);
                            
                            // 检查内层JSON中是否也有topic字段
                            if (isset($contentNode['topic'])) {
                                $this->logger->info('内层消息中的Topic字段: ' . $contentNode['topic']);
                            }
                        } else {
                            $this->logger->info('content字段 (非JSON): ' . $content);
                        }
                    } catch (\Exception $e) {
                        // content字段解析出错
                        $this->logger->info('content字段解析出错: ' . $e->getMessage());
                    }
                }
            } else {
                // 不是有效的JSON
                $this->logger->info('消息不是有效的JSON格式，以纯文本显示:' . PHP_EOL . $processedMessage);
            }
        } catch (\Exception $e) {
            // JSON处理出错
            $this->logger->error('处理消息时出错: ' . $e->getMessage());
            $this->logger->info('原始消息:' . PHP_EOL . $message);
        }
    }
    
    /**
     * 关闭连接
     */
    public function close()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        
        if ($this->connection) {
            $this->connection->close();
        }
        
        $this->logger->info('AMQP连接已关闭');
    }
} 