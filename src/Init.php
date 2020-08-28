<?php

/**
 * 服务入口
 */

namespace PhpDelayQueue\Init;

use PhpDelayQueue\Config\Config;
use PhpDelayQueue\Exception\Exception;
use PhpDelayQueue\Handler\ConsumeHandler;
use PhpDelayQueue\Handler\DelayHandler;
use PhpDelayQueue\Handler\MainHandler;
use PhpDelayQueue\Handler\RedisHandler;
use PhpDelayQueue\Tools\DqLog;
use PhpDelayQueue\Tools\Tools;

class Init
{
    static public $action; //当前命令
    static public $stop = 0; //是否停止服务
    static public $dq_master; //主进程
    static public $dq_slave; //slave进程

    public function __construct($action)
    {
        if (!in_array($action, Config::COMMAND)) {
            DqLog::error('master', '非法命令', $action, 'master');
            exit('非法命令');
        }
        self::$action = $action;
        self::$dq_slave = Config::$processArr['dq_slave'];
        self::$dq_master = Config::$processArr['dq_master'];
    }

    /**
     * 初始化服务
     * @throws Exception
     */
    public function init()
    {
        DqLog::info('master', '服务进入', self::$action, 'master');
        //初始化状态
        RedisHandler::getInstance()->setServerStatus(0);
        //start 检测没有正在运行的则马上启动
        //reload 正在运行的需要设置退出信号kill并重启
        switch (self::$action) {
            case 'start':
                $id = self::checkSelf(self::$dq_master);
                DqLog::info('master', '服务目前id', $id, 'master');
                if (!empty($id)) {
                    DqLog::error('master', '命令为start，但是服务已经运行', $id, 'master');
                    exit('有正在运行的服务，如需要重启服务请使用reload命令');
                }
                break;
            case 'reload':
            case 'stop':
                sleep(1);
                self::killServer(self::$action);
                self::$action == 'stop' ? self::$stop = 1 : self::$stop = 0;
                break;
            case 'slave':
                $id = self::checkSelf(self::$dq_slave);
                DqLog::info('master', '服务目前id', $id, 'master');
                if (!empty($id)) {
                    DqLog::error('master', '命令为slave，但是slave服务已经运行', $id, 'master');
                    exit('有正在运行的slave服务');
                }
                break;
        }
        if (self::$stop == 1) {
            DqLog::error('master', '命令为stop，服务结束', '', 'master');
            exit('server is stop');
        }

        //设置进程名字
        $processTitle = self::$action == 'slave' ? self::$dq_slave : self::$dq_master;
        cli_set_process_title($processTitle);
        DqLog::info('master', '服务开启,' . $processTitle . '进程id', posix_getpid(), 'master');

        while (true) {
            try {
                //master进程
                if (cli_get_process_title() == self::$dq_master) {
                    //启动关键服务
                    self::startDelayService();
                    self::startConsumeService();
                    //注册USR2信号
                    self::masterRegisterUsr2Sin();

                    //检测重启信号
                    if (self::checkStatus()) {
                        MainHandler::getInstance()->actionServer('reload');
                    }
                    //检测config 独立成项目启用
//                    if (self::checkConfig()) {
//                        DqLog::error('master', 'config', time(), 'master');
//                        MainHandler::getInstance()->actionServer('reload');
//                    }

                    //回收子进程，避免成为僵死进程，占用服务器资源
                    $ret = pcntl_waitpid(0, $status, WNOHANG);
                    $id = self::checkSelf(self::$dq_slave);
                    if (empty($id)) {
                        DqLog::error('master', 'slave down,重新拉起', time(), 'master');
                        Tools::getInstance()->sendOriginWarnToDing('slave down,重新拉起');
                        MainHandler::getInstance()->actionServer('slave');
                    }
                } elseif (cli_get_process_title() == self::$dq_slave) {
                    $id = self::checkSelf(self::$dq_master);
                    if (empty($id)) {
                        //todo 报警
                        DqLog::error('master', 'slave重新拉起master', time(), 'master');
                        Tools::getInstance()->sendOriginWarnToDing('master down,重新拉起');
                        //自动拉起
                        MainHandler::getInstance()->actionServer('reload');
                    }
                }
                sleep(2);
                pcntl_signal_dispatch();
            } catch (\Exception $exception) {
                DqLog::error('master', '任务调度异常', $e->getMessage(), 'master');
            }
        }
    }

    //检测自身
    public static function checkSelf($processName)
    {
        return MainHandler::getInstance()->checkSelf($processName);
    }

    //获取所有的服务id
    public static function getAllServerId($processName)
    {
        return MainHandler::getInstance()->getAllServerId($processName);
    }

    //kill 服务
    public static function killServer($action)
    {
        $waitKillProcess = Config::$processArr;
        if ($action == 'reload') {
            unset($waitKillProcess['dq_slave']);
        }
        $processIds = [];
        foreach ($waitKillProcess as $item) {
            $processIds = array_merge($processIds, self::getAllServerId($item));
        }
        DqLog::error('master', $action . '所有的进程id', $processIds, 'master');
        MainHandler::getInstance()->killServerId($processIds);
    }

    //开启延迟队列检测
    public static function startDelayService()
    {
        DelayHandler::getInstance()->startDelayService(Config::DQ_DELAY_NUM);
    }

    //开启消费队列
    public static function startConsumeService()
    {
        ConsumeHandler::getInstance()->startConsumeService(Config::DQ_CONSUME_NUM);
    }

    //master监听usr2信号
    public static function masterRegisterUsr2Sin()
    {
        MainHandler::getInstance()->masterRegisterUsr2Sin();
    }

    //检测config
    public static function checkConfig()
    {
        return MainHandler::getInstance()->checkConfig();
    }

    public static function checkStatus()
    {
        return RedisHandler::getInstance()->getServerStatus();
    }
}
