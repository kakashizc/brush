<?php

namespace app\admin\model;

use think\Model;


class Plat extends Model
{

    

    

    // 表名
    protected $name = 'plat';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];


    /*
     * 查找平台对应的审核状态
     * */
    public function brushPlat()
    {
        return $this->hasMany('BrushPlat', 'plat_id');
    }








}
