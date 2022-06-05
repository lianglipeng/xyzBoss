<?php

namespace Xyz;

class UserCenter
{
    //应用ID
    private $appId = 0;
    //应用密钥
    private $appSecret = null;
    //访问域名
    private $host = null;
    //用户中心校验token：http://yapi.xiaoyezi.com/project/46/interface/api/1101
    const API_USER_CENTER_TOKEN_CHECK = '/rapi/v1/auth/tokencheck';

    /**
     * 构造函数
     * @param int $appId
     * @param string $appSecret
     * @param string $host
     */
    public function __construct(int $appId, string $appSecret, string $host)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->host = $host;
    }

    /**
     * token校验
     * @param string $token
     * @return array
     */
    public function tokenCheck(string $token): array
    {
        return curlRequest::curlSendRequest(
            $this->host . self::API_USER_CENTER_TOKEN_CHECK . '?appId=' . $this->appId . '&secret=' . $this->appSecret,
            ['token' => $token],
            'POST');
    }
}