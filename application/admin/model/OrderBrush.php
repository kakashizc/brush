<?php

namespace app\admin\model;

use think\Model;


class OrderBrush extends Model
{

    

    

    // 表名
    protected $name = 'order_brush';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'ctime_text',
        'stime_text',
        'ptime_text',
        'confirmtime_text',
        'gettime_text',
        'donetime_text',
        'back_text',
        'type_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5')];
    }

    public function getBackList()
    {
        return ['1' => __('Back 1'), '2' => __('Back 2'), '3' => __('Back 3'), '4' => __('Back 4')];
    }

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
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


    public function getStimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['stime']) ? $data['stime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ptime']) ? $data['ptime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getConfirmtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['confirmtime']) ? $data['confirmtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getGettimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['gettime']) ? $data['gettime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getDonetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['donetime']) ? $data['donetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getBackTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['back']) ? $data['back'] : '');
        $list = $this->getBackList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setStimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setConfirmtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setGettimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setDonetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function brush()
    {
        return $this->belongsTo('Brush', 'brush_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function plat()
    {
        return $this->belongsTo('Plat', 'plat_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function orderb()
    {
        return $this->belongsTo('Order', 'order_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    /*
     * 关联 comp 申诉表
     * */
    public function comps()
    {
        return $this->hasOne('Comp','orderbrush_id','id',[],'LEFT')->setEagerlyType(0);
    }
}
