<?php
/**
 * Created by PhpStorm.
 * User: lvhui
 * Date: 2020/7/29
 * Time: 下午7:34
 */

namespace PhpDelayQueue\Handler;

use App\Utils\Singleton;
use PhpDelayQueue\Tools\DqLog;

Class CurlHandler
{
    use Singleton;

    //json post
    public static function httpSimpleJsonData($url, $data_string, $timeout = 1)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POST, 1);

        //连接时间
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_URL, $url);

        // Post提交的数据包求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

        //返回响应时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string)
            ]
        );

        $ftime = microtime(true);
        $result = curl_exec($ch);
        $errorCode = curl_errno($ch);
        DqLog::write('requestTime', 'info', $url . '请求时间: ' . round(microtime(true) - $ftime, 4) . 's', 'pay-request-time');
        if (curl_errno($ch) > 0) {
            WriteLog::error('httpSimpleJsonData', 'error_info', $url . "-)" . $errorCode, 'httpSimpleJsonData');
        }
        curl_close($ch);

        if (empty(json_decode($result))) {
            DqLog::error('httpSimpleJsonData', 'error_info', $result, 'httpSimpleJsonData');
        }

        return json_decode($result);
    }

    //curl post
    public static function curlPost($url, $postFields, $timeOut = 60)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }
}