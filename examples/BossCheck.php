<?php

use Xyz\Boss;

require __DIR__.'/../autoload.php';

//获取用户当前业务线拥有的接口权限
$obj = new Boss(22,'ebf28ee3b32f026d', 'https://boss-backend-pre.xiaoyezi.com');
//$result = $obj->getUserPrivilegeForApp("319828319971365247");
//var_dump($result);
//检测用户是否拥有指定api接口使用权限
$result = $obj->getUserApiPrivileges("319828319971365247",'/crm/leads/change_cc_not_private');
var_dump($result);
//获取员工数据
//$result = $obj->getForeignStaff([],[],30050401);
//var_dump($result);


//获取部门
//$result = $obj->getForeignDepartment(30000000);
//var_dump($result);

//根据角色查询所有员工
//$result = $obj->getRoleUsers(32);
//var_dump($result);
//查询全部部门信息
//$result = $obj->getDeptAll();
//var_dump($result);



