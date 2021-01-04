<?php

namespace app\admin\behavior;

// 商家财务记录钩子,暂时不用了...
class Finance
{
    //执行插入商家财务记录的逻辑
    public function run(&$params)
    {
        $time = time();
        //1,先查找商户信息
        $insert = [
            'admin_id' => $params['admin_id'],
            'money' => $params['money'],
            'type' => $params['type'],
            'ctime' => $time,
            'admin_money' => $params['admin_money'],
            'admin_name' => $params['admin_name']
        ];
    }
}