<?php

namespace Xyz;
use Xyz\CurlRequest;
class UserCenter
{
    //应用ID
    private $appId = 0;
    //应用密钥
    private $appSecret = null;
    //访问域名
    private $host = null;
    //校验token：http://yapi.xiaoyezi.com/project/46/interface/api/1101
    const API_USER_CENTER_TOKEN_CHECK = '/rapi/v1/auth/tokencheck';
    //service ticket验证:http://yapi.xiaoyezi.com/project/58/interface/api/7616
    const API_USER_CENTER_TICKET_VERIFY = '/api/sso/verify';
    //登出
    const API_SSO_LOGOUT = '/api/sso/logout';

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

    /**
     * ticket校验
     * @param string $ticket
     * @return array
     */
    public function ticketCheck(string $ticket): array
    {
        return curlRequest::curlSendRequest(
            $this->host . self::API_USER_CENTER_TICKET_VERIFY . "?sso_service_ticket=" . $ticket);
    }

    /**
     * 返回退出登录地址
     * @return string
     */
    public function getSsoLogoutUrl(): string
    {
        $formatHost = strpos($this->host, '.pri') === false ? $this->host : str_replace('.pri', '', $this->host);
        return $formatHost . self::API_SSO_LOGOUT . "?app_id=" . $this->appId;
    }
}
