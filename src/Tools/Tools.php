<?php
/**
 * Created by PhpStorm.
 * User: lvhui
 * Date: 2019/10/17
 * Time: 上午10:49
 */

namespace PhpDelayQueue\Tools;

use App\Libraries\Tools\WriteLog;
use App\Utils\Singleton;
use PhpDelayQueue\Config\Config;
use PhpDelayQueue\Handler\CurlHandler;
use PhpDelayQueue\Handler\MainHandler;

class Tools
{
    use Singleton;

    /**
     * 获取相应进程名字的在线进程数
     * @param $name
     * @return int
     */
    public function getChildNum($name)
    {
        $_cmd = "ps -ef | grep '$name' | grep -v grep -c";
        $fp = popen($_cmd, 'r');
        $num = 0;
        while (!feof($fp) && $fp) {
            $_line = trim(fgets($fp, 1024));
            $arr = explode(" ", $_line);
            if (!empty($arr[0])) {
                $num = $arr[0];
                break;
            }
        }
        fclose($fp);
        return $num;
    }

    /**
     * 获取所有的正在运行的服务id
     * @param $name
     * @return array
     */
    public function getServerId($name)
    {
        $_cmd = "ps -ef | grep '$name' | grep -v grep | awk '{print $3,$8,$2}'";
        $fp = popen($_cmd, 'r');
        $id = [];
        while (!feof($fp) && $fp) {
            $_line = trim(fgets($fp, 1024));
            $arr = explode(" ", $_line);
            if (!empty($arr[2])) {
                $id[] = $arr[2];
            }
        }
        fclose($fp);
        return $id;
    }


    /**
     * 获取所有的正在运行的服务id
     * @param $name
     * @return array
     */
    public function getServerProcessName($name, $cmdName)
    {
        $_cmd = "ps -ef | grep '$name' | grep -v grep |  grep '$cmdName' | awk '{print $8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19}'";
        $fp = popen($_cmd, 'r');
        $id = '';
        while (!feof($fp) && $fp) {
            $_line = trim(fgets($fp, 1024));
            if ($_line) {
                $id = $_line;
            }
        }
        fclose($fp);
        return $id;
    }

    /**
     * kill 进程id
     * @param $ids
     */
    public function killServerId($ids)
    {
        if (empty($ids)) {
            return;
        }
        if (is_array($ids)) {
            foreach ($ids as $id) {
                exec('kill -9 ' . $id . ' > /dev/null 2>&1');
            }
        } else {
            exec('kill -9 ' . $ids . ' > /dev/null 2>&1');
        }
    }

    /**
     * 任务报警
     * @param $text
     * @param $topic
     */
    public function sendWarnToDing($task, $processId, $detail, $topic)
    {
        if (empty(MainHandler::getInstance()->getTopicInfo($topic, 'status'))) {
            exit;
        }

        $at = MainHandler::getInstance()->getTopicInfo($topic, 'ding_at') ? MainHandler::getInstance()->getTopicInfo($topic, 'ding_at') : Config::ORIGIN_DING_AT;
        $img = 'http://image.hzyuewan.com/sucai/15919305363743.jpg';
        $data = [
            "msgtype" => "markdown",
            "markdown" => [
                "title" => "任务调度报警",
                "text" => "#### 任务调度报警 \n> 任务调度脚本：%s\n> 进程id：%s\n> ![screenshot](%s)\n> ###### %s[查看详情](https://www.baidu.com) \n"
            ],
            "at" => [
                "atMobiles" => [
                    $at,
                    Config::ORIGIN_DING_AT,
                ],
                "isAtAll" => false,
            ]
        ];
        $data['markdown']['text'] = sprintf($data['markdown']['text'], $task, $processId, $img, $detail);
        //获取报警url
        $url = Config::DING_WARNING_URL . '?access_token=' . MainHandler::getInstance()->getTopicInfo($topic, 'ding_token');
        //发送报警信息
        $res = CurlHandler::httpSimpleJsonData($url, json_encode($data), 2);
    }

    /**
     * 任务报警
     * @param $text
     * @param $topic
     */
    public function sendOriginWarnToDing($text)
    {
        $data = [
            'msgtype' => 'text',
            'text' => [
                'content' => '报警:' . $text
            ],
            "at" => [
                "atMobiles" => [
                    Config::ORIGIN_DING_AT,
                ],
                "isAtAll" => false
            ]
        ];
        //获取报警url
        $url = Config::DING_WARNING_URL . '?access_token=' . Config::ORIGIN_DING_TOKEN;
        //发送报警信息
        $res = CurlHandler::httpSimpleJsonData($url, json_encode($data), 2);
    }
}