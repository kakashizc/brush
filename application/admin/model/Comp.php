<?php

namespace app\admin\model;

use think\Model;


class Comp extends Model
{

    

    

    // 表名
    protected $name = 'comp';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'ctime_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function brush()
    {
        return $this->belongsTo('Brush', 'id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function orderbrush()
    {
        return $this->belongsTo('OrderBrush', 'orderbrush_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function complain()
    {
        return $this->belongsTo('Complain', 'complain_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
