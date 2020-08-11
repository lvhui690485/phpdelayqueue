<?php

namespace PhpDelayQueue\Handler;


use App\Utils\Singleton;
use PhpDelayQueue\Config\Config;

Class RedisHandler
{
    use Singleton;
    //任务调度
    const DQ_READY_TASK = 'ready_task';

    const DQ_DEPLY_TASK = 'deply_task';

    //task hash值 以topic隔开
    const TASK_HASH_INFO = 'task_hash_info_%s';

    //计划重试次数
    const TASK_RETRY_TIME = 'task_retry_time_%s';

    /**
     * 获取redis句柄
     * @return \Redis
     */
    public function getRedisHandler()
    {
        $redis = new \Redis();
        $config = Config::REDIS_CONFIG;
        $redis->connect($config['host'], $config['port']);
        if (isset($config['password'])) {
            $redis->auth($config['password']);
        }
        return $redis;
    }

    /**
     * 利用lua脚本获取有序集合中对应的分数的值
     * @param $key
     * @param $score
     * @return mixed
     */
    function zrangebyscoreByLua($redis, $key, $score)
    {
        $script = "local resultDelay = {};
         local result = redis.call('zrangebyscore', KEYS[1], '0', ARGV[1]) ;
         if next(result) == nil then return resultDelay  
         end;
         if redis.call('zrem', KEYS[1], result[1]) > 0 then 
            table.insert(resultDelay, result[1])
            return resultDelay 
         end;
         return resultDelay;";
        return $redis->eval($script, [$key, $score], 1);
    }

    /**
     * 入消费队列
     * @param $data
     * @return int
     */
    public function lPushReadyTask($redis, $data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return $redis->lPush(self::getKey(self::DQ_READY_TASK), $data);
    }

    /**
     * 堵塞出消费队列
     * @return mixed
     */
    public function rpopReadyTask($redis)
    {
        return $redis->rPop(self::getKey(self::DQ_READY_TASK));
    }

    /**
     * 往集合里面添加
     * @param $value
     * @param $score
     * @return int
     */
    public function zAddTaskSchedule($redis, $value, $score)
    {
        return $redis->zAdd(self::getKey(self::DQ_DEPLY_TASK), $score, $value);
    }

    /**
     * hash set
     * @param $topic
     * @param $jobId
     * @param $value
     * @param int $ttl
     * @return int
     */
    public function hSetTaskInfo($redis, $topic, $jobId, $value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $redis->hSet(self::getKey(self::TASK_HASH_INFO, $topic), $jobId, $value);
        return $redis->expire(self::getKey(self::TASK_HASH_INFO, $topic), 86400 * 30);
    }

    /**
     * hash get
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function hGetTaskInfo($redis, $topic, $jobId)
    {
        return $redis->hGet(self::getKey(self::TASK_HASH_INFO, $topic), $jobId);
    }

    /**
     * hash exists
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function hExistsTaskInfo($redis, $topic, $jobId)
    {
        return $redis->hExists(self::getKey(self::TASK_HASH_INFO, $topic), $jobId);
    }

    /**
     * hash del
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function hDelTaskInfo($redis, $topic, $jobId)
    {
        return $redis->hDel(self::getKey(self::TASK_HASH_INFO, $topic), $jobId);
    }

    /**
     * incr
     * @param $topic
     * @param $jobId
     * @return string
     */
    public function incrTaskRetryTime($redis, $jobId)
    {
        $res = $redis->incr(self::getKey(self::TASK_RETRY_TIME, $jobId), 1);
        $redis->expire(self::getKey(self::TASK_RETRY_TIME, $jobId), 3600);
        return $res;
    }

    /**
     * 获取键值,第一个参数是键值,其他参数跟随
     */
    public static function getKey()
    {
        $arr = func_get_args();
        $arr[0] = Config::$prifix . $arr[0];
        return call_user_func_array('sprintf', $arr);
    }
}