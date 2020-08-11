<?php

namespace PhpDelayQueue\Handler;

use App\Utils\Singleton;
use PhpDelayQueue\Config\Config;
use PhpDelayQueue\Init\Init;
use PhpDelayQueue\Tools\DqLog;
use PhpDelayQueue\Tools\Tools;

Class DelayHandler
{
    use Singleton;

    /**
     * 启动扫码延迟队列服务
     * @param $num
     */
    public function startDelayService($num)
    {
        if (Tools::getInstance()->getChildNum(Config::$processArr['dq_delay']) < $num) {
            for ($i = 1; $i <= $num; $i++) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    DqLog::error('delay', 'fork error', '', 'delay');
                } elseif ($pid) {
                } else {// 子进程处理
                    cli_set_process_title(Config::$processArr['dq_delay'] . '_' . $i);
                    $id = posix_getpid();
                    DqLog::info('delay', 'id', $id, 'delay');
                    $redis = (new RedisHandler())->getRedisHandler();
                    $this->delayRedisZset($redis);
                    $redis->close();
                    // 一定要注意退出子进程,否则pcntl_fork() 会被子进程再fork,带来处理上的影响。
                    exit;
                }
            }
        }
    }

    private function delayRedisZset($redis)
    {
        while (true) {
            try {
                $_res = RedisHandler::getInstance()->zrangebyscoreByLua($redis, 'dq_deply_task', time());
                if ($_res) {
                    $res = json_decode($_res[0], true);
                    if ($res) {
                        //获取topic和jobId 去相应的hash获取具体数据
                        DqLog::info('delay', '检测到一条待消费数据,入待消费队列', $res, 'delay');
                        $detailInfo = json_decode(RedisHandler::getInstance()->hGetTaskInfo($redis, $res['topic'], $res['jobId']), true);
                        DqLog::info('delay', '待消费数据,hash数据', [$detailInfo], 'delay');
                        if ($detailInfo) {
                            $info['body'] = $detailInfo;
                            $info['topic'] = $res['topic'];
                            $info['jobId'] = $res['jobId'];
                            RedisHandler::getInstance()->lPushReadyTask($redis, $info);
                        }
                    }
                }
            } catch (\Exception $e) {
                DqLog::error('delay', '延迟队列有异常 ' . $e->getMessage(), '行数：' . $e->getLine(), 'delay');
            }
            //扫描zset
            usleep(100000);
        }
    }
}