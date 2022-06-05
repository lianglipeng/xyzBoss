<?php

namespace Xyz;

class CurlRequest
{
    /**
     * curl发起http请求
     * @param string $url
     * @param array $params
     * @param string $method
     * @return array
     */
    public static function curlSendRequest(string $url, array $params = [], string $method = "GET"): array
    {
        //处理请求参数
        $requestParamsFormatData = '';
        if ($method == "GET" && !empty($params)) {
            $url = $url . '?' . http_build_query($params);
        } elseif ($method == "POST" && !empty($params)) {
            $requestParamsFormatData = json_encode($params);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);//不返回头部信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //结果是否显示出来，1不显示，0显示
        //设置请求头
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        if (!empty($requestParamsFormatData)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestParamsFormatData);
        }
        //判断是否https
        if (strpos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = [
            'curl_error'    => '',
            'response_data' => []
        ];
        if ($response === false) {
            $responseData['curl_error'] = curl_error($ch);
        } else {
            $responseData['response_data'] = json_decode($response, true);
        }
        return $responseData;
    }
}