<?php

namespace PhpDelayQueue\Tools;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use PhpDelayQueue\Config\Config;


class DqLog
{
    /**
     * @param string $channel 功能频道
     * @param string $desc 描述
     * @param string $message 内容
     * @param string $moduleType 模块类型，默认为业务类型
     * @param string $logLevel 日志等级：DEBUG INFO WARNING ERROR EMERGENCY
     * @return bool
     * @throws \Exception
     */
    public static function write($channel, $desc, $message = '', $moduleType = 'business', $logLevel = 'info')
    {
        $output = "[%datetime%] %channel%.%level_name%: %message%\n";
        $Logger = new Logger($channel);
        $formatter = new LineFormatter($output, null, true);
        $basePath = Config::LOG_PATH;
        $Logger->pushHandler((new StreamHandler($basePath . '/' . 'dq/' . $moduleType . '/' . date('Y-m-d') . '.log', Logger::INFO))->setFormatter($formatter));
        $desc = is_array($desc) ? json_encode($desc, JSON_UNESCAPED_UNICODE) : $desc;
        $message = is_array($message) ? json_encode($message, JSON_UNESCAPED_UNICODE) : $message;
        return $Logger->log($logLevel, $desc . ':' . $message);
    }

    /**
     * 记录info日志
     * @param string $channel 功能频道
     * @param string $desc 描述
     * @param string $message 内容
     * @param string $moduleType 模块类型，默认为业务类型
     * @return bool
     */
    public static function info($channel, $desc, $message = '', $moduleType = 'business')
    {
        return self::write($channel, $desc, $message, $moduleType, 'info');
    }

    /**
     * 记录error日志
     * @param string $channel 功能频道
     * @param string $desc 描述
     * @param string $message 内容
     * @param string $moduleType 模块类型，默认为业务类型
     * @return bool
     */
    public static function error($channel, $desc, $message = '', $moduleType = 'business')
    {
        return self::write($channel, $desc, $message, $moduleType, 'error');
    }
}
