<?php

namespace app\admin\model;

use think\Model;


class Brush extends Model
{

    

    

    // 表名
    protected $name = 'brush';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'gender_text',
        'status_text'
    ];
    

    
    public function getGenderList()
    {
        return ['1' => __('Gender 1'), '2' => __('Gender 2')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


    public function getGenderTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['gender']) ? $data['gender'] : '');
        $list = $this->getGenderList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    
     /*
     *
     * */
    public function orderBrush()
    {
       return $this->hasMany('order_brush','brush_id')->where('back','4')->field('brush_id,broker,id');
    }
    public function feed()
    {
        return $this->hasMany('feed','brush_id')->where('status','2')->whereOr('status','3');
    }



}
