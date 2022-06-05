<?php

namespace Xyz;

class Boss
{
    //应用ID
    private $appId = 0;
    //应用密钥
    private $appSecret = null;
    //访问域名
    private $host = null;
    //获取指定账户对应指定业务系统拥有的权限列表:http://yapi.xiaoyezi.com/project/638/interface/api/16795
    const API_GET_FOREIGN_PRIVILEGE = '/foreign/privilege';
    //获取角色列表:http://yapi.xiaoyezi.com/project/638/interface/api/17821
    const API_GET_FOREIGN_ROLE_LIST = '/v1/foreign/role/list';
    //获取业务线的全部权限列表:http://yapi.xiaoyezi.com/project/638/interface/api/17828
    const API_GET_FOREIGN_PRIVILEGE_LIST = '/v1/foreign/privilege/list';
    //查询员工信息:http://yapi.xiaoyezi.com/project/638/interface/api/17170
    const API_GET_FOREIGN_STAFF = '/v1/foreign/staff';
    //查询部门信息
    const API_GET_FOREIGN_DEPARTMENT = '/v1/foreign/department';
    //根据角色查询所有员工
    const API_GET_ROLE_USERS = '/foreign/role/users';
    //查询全部部门信息
    const API_GET_FOREIGN_DEPT_ALL = '/v1/foreign/dept_all';
    //接口权限数据
    private $apiPrivilegeData = [
        'curl_error'            => '',//curl远程请求错误信息，次数据不为空代表请求失败
        'use_privilege'         => false,//api接口使用权限：true可以使用 false不可以使用
        'is_public'             => false,//是否是公共权限：true是 false不是（当为true时，不必在考虑数据权限data_privilege）
        'data_privilege'        => null,//当使用权限=true时，数据权限才有效：当用户拥有多个角色，并勾选了同一个api，此时数据权限取多个api接口的最大权限
        'data_privilege_config' => [],//数据权限枚举值配置定义
    ];
    //sss销售服务系统应用ID
    const SSS_APP_ID = 22;

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
     * 获取指定账户对应指定业务系统拥有的权限列表
     * @param string $uuid
     * @return array
     */
    public function getUserPrivilegeForApp(string $uuid): array
    {
        $privilegeData = curlRequest::curlSendRequest($this->host . self::API_GET_FOREIGN_PRIVILEGE,
            ['app_id' => $this->appId, 'uuid' => $uuid]);
        if (!empty($apiPrivilegeResponseData['curl_error']) || empty($privilegeData['response_data']['data']['list'])) {
            return $privilegeData;
        }
        //去重处理
        $privilegeData['response_data']['data']['list'] = array_values(array_column($privilegeData['response_data']['data']['list'],
            null, 'uri'));
        return $privilegeData;
    }

    /**
     * 分页获取角色详情列表
     * @param int $page 默认为查询全部数据
     * @param int $pageSize
     * @param string $roleName 角色名称
     * @return array
     */
    public function getRoleList(int $page = 1, int $pageSize = 10, string $roleName = ''): array
    {
        return curlRequest::curlSendRequest($this->host . self::API_GET_FOREIGN_ROLE_LIST,
            ['page' => $page, 'page_size' => $pageSize, 'role_name' => $roleName]);
    }

    /**
     * 获取业务线的全部权限接口
     * @param int $appId
     * @return array
     */
    public function getAllPrivilegeForApp(int $appId): array
    {
        return curlRequest::curlSendRequest($this->host . self::API_GET_FOREIGN_PRIVILEGE_LIST,
            ['app_id' => $appId]);
    }

    /**
     * 获取账户针对指定接口权限：使用权限&数据权限
     * @param string $uuid
     * @param string $apiUri
     * @return array
     */
    public function getUserApiPrivileges(string $uuid, string $apiUri): array
    {
        $apiPrivilegeResponseData = $this->getUserPrivilegeForApp($uuid);
        if (!empty($apiPrivilegeResponseData['curl_error']) || empty($apiPrivilegeResponseData['response_data']['data']['list'])) {
            $this->apiPrivilegeData['curl_error'] = $apiPrivilegeResponseData['curl_error'];
            return $this->apiPrivilegeData;
        }
        if (!isset(array_column($apiPrivilegeResponseData['response_data']['data']['list'], 'id', 'uri')[$apiUri])) {
            return $this->apiPrivilegeData;
        }
        $this->apiPrivilegeData['use_privilege'] = true;
        //根据不同系统数据权限定义，采取不同的格式化方法
        if ($this->appId == self::SSS_APP_ID) {
            //sss系统
            $this->formatSssSystemPrivilegeData($apiPrivilegeResponseData['response_data']['data']['list'], $apiUri);
        } else {
            //通用格式化方法
            $this->formatNormalSystemPrivilegeData($apiPrivilegeResponseData['response_data']['data']['list'], $apiUri);
        }
        return $this->apiPrivilegeData;
    }

    /**
     * 格式化处理常规系统接口权限：配置格式 [1=>"全部",2=>"部门,3=>"个人"],枚举值越小，权限越大
     * @param $apiPrivilegeList
     * @param $apiUri
     */
    private function formatNormalSystemPrivilegeData($apiPrivilegeList, $apiUri)
    {
        foreach ($apiPrivilegeList as $v) {
            if ($v['uri'] == $apiUri) {
                if ($v['is_public'] == 1) {
                    $this->apiPrivilegeData['is_public'] = true;
                } elseif (!empty($v['data_privilege_config'])) {
                    $maxApiPrivilegeData[] = $v['data_privilege'];
                    $this->apiPrivilegeData['data_privilege_config'] = $v['data_privilege_config'];
                }
            }
        }
        if (!empty($maxApiPrivilegeData)) {
            $this->apiPrivilegeData['data_privilege'] = min($maxApiPrivilegeData);
        }
    }

    /**
     * 格式化处理sss销售服务系统系统权限数据：配置格式 ["user_all"=>"全部","user_dept"=>"部门,"user_self"=>"个人"],位置越靠前，权限越大
     * @param $apiPrivilegeList
     * @param $apiUri
     */
    private function formatSssSystemPrivilegeData($apiPrivilegeList, $apiUri)
    {
        $tmpDataPrivilegeKeys = [];
        foreach ($apiPrivilegeList as $v) {
            if ($v['uri'] == $apiUri) {
                if ($v['is_public'] == 1) {
                    $this->apiPrivilegeData['is_public'] = true;
                } elseif (!empty($v['data_privilege_config'])) {
                    if (empty($this->apiPrivilegeData['data_privilege_config'])) {
                        $this->apiPrivilegeData['data_privilege_config'] = $v['data_privilege_config'];
                        $tmpDataPrivilegeKeys = array_keys($this->apiPrivilegeData['data_privilege_config']);
                    }
                    if (empty($this->apiPrivilegeData['data_privilege'])) {
                        $this->apiPrivilegeData['data_privilege'] = $v['data_privilege'];
                    } else {
                        if (array_search($v['data_privilege'],
                                $tmpDataPrivilegeKeys) < array_search($this->apiPrivilegeData['data_privilege'],
                                $tmpDataPrivilegeKeys)) {
                            $this->apiPrivilegeData['data_privilege'] = $v['data_privilege'];
                        }
                    }
                }
            }
        }
    }


    /**
     * 获取员工数据
     * @param array $uuids
     * @param array $mobiles
     * @param int $departmentId
     * @param string $name
     * @param int $status
     * @return array
     */
    public function getForeignStaff(
        array $uuids = [],
        array $mobiles = [],
        int $departmentId = 0,
        string $name = '',
        int $status = 1
    ): array {
        if (empty($uuids) && empty($mobiles) && empty($departmentId)) {
            return [];
        }
        return curlRequest::curlSendRequest($this->host . self::API_GET_FOREIGN_STAFF,
            [
                'uuid'          => implode(',', $uuids),
                'mobiles'       => implode(',', $mobiles),
                'department_id' => $departmentId,
                'name'          => $name,
                'status'        => $status,
            ]);
    }

    /**
     * 查询部门信息
     * @param int $departmentId
     * @return array
     */
    public function getForeignDepartment(int $departmentId): array
    {
        return curlRequest::curlSendRequest($this->host . self::API_GET_FOREIGN_DEPARTMENT,
            ['department_id' => $departmentId]);
    }

    /**
     * 根据角色查询所有员工
     * @param int $roleId
     * @return array
     */
    public function getRoleUsers(int $roleId): array
    {
        return curlRequest::curlSendRequest($this->host . self::API_GET_ROLE_USERS,
            ['role_id' => $roleId]);
    }

    /**
     * 查询全部部门信息
     * @return array
     */
    public function getDeptAll(): array
    {
        return curlRequest::curlSendRequest($this->host . self::API_GET_FOREIGN_DEPT_ALL);
    }

}