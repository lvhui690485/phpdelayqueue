<?php

namespace PhpDelayQueue\Handler;

use App\Utils\Singleton;
use PhpDelayQueue\Config\Config;
use PhpDelayQueue\Tools\DqLog;

Class CurdlHandler
{
    use Singleton;

    /**
     * 对外curd接口
     * @param $info
     */
    public static function handleTaskInfo($info)
    {
        if (!isset($info['cmd']) || !in_array($info['cmd'], Config::CURD_COMMAND)) {
            return json_encode(['code' => -1, 'msg' => '非法请求']);
        }

        //hdel 库
        if (empty($info['topic'])) {
            return json_encode(['code' => -1, 'msg' => '非法请求']);
        }

        $topicInfo = MainHandler::getInstance()->getTopicInfo($info['topic'], '*');
        if (empty($topicInfo) || ($topicInfo['status'] ?? 1) == 0) {
            return json_encode(['code' => -1, 'msg' => 'topic未注册']);
        }
        switch ($info['cmd']) {
            case 'add';
                $res = self::addTaskInfo($info);
                break;
            case 'get';
                $res = self::getTaskInfo($info);
                break;
            case 'del';
                $res = self::delTaskInfo($info);
                break;
        }
        return $res;
    }

    protected static function addTaskInfo($info)
    {
        if (empty($info['body'])) {
            return json_encode(['code' => -1, 'msg' => '非法请求']);
        }
        $time = (int)$info['body']['runTime'] ?? 0;
        $time = $time == 0 ? time() + 1 : $time + 1;
        if ($time > time() + 1 + 86400 * 30) {
            return json_encode(['code' => -1, 'msg' => '任务调度最大支持30天延迟任务']);
        }
        $jobId = !empty($info['jobId']) ? $info['jobId'] : md5($info['topic'] . microtime() . mt_rand(1, 100000));
        $zsetValue = ['topic' => $info['topic'], 'jobId' => $jobId];

        $redis = RedisHandler::getInstance()->getRedisHandler();
        RedisHandler::getInstance()->zAddTaskSchedule($redis, json_encode($zsetValue), $time);
        RedisHandler::getInstance()->hSetTaskInfo($redis, $info['topic'], $zsetValue['jobId'], $info['body']);
        DqLog::info('curd', 'add', $info, 'curd');
        return json_encode(['code' => 0, 'data' => ['jobId' => $zsetValue['jobId']]]);
        //入库
    }

    protected static function getTaskInfo($info)
    {
        if (empty($info['jobId'])) {
            return json_encode(['code' => -1, 'msg' => '非法请求']);
        }

        $redis = RedisHandler::getInstance()->getRedisHandler();
        $jobInfo = RedisHandler::getInstance()->hGetTaskInfo($redis, $info['topic'], $info['jobId']);
        return json_encode(['code' => 0, 'data' => ['data' => json_decode($jobInfo, true)]]);
    }

    protected static function delTaskInfo($info)
    {
        if (empty($info['jobId'])) {
            return json_encode(['code' => -1, 'msg' => '非法请求']);
        }
        $redis = RedisHandler::getInstance()->getRedisHandler();
        RedisHandler::getInstance()->hDelTaskInfo($redis, $info['topic'], $info['jobId']);
        return json_encode(['code' => 0, 'msg' => 'ok']);
    }
}