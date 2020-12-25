<?php

namespace app\admin\model;

use think\Model;


class Json extends Model
{

    

    

    // 表名
    protected $name = 'json';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'imglist_text'
    ];
    

    
    public function getImglistList()
    {
        return ['支付截图' => __('支付截图'), '收货截图' => __('收货截图'),'假聊截图' => __('假聊截图')];
    }


    public function getImglistTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['imglist']) ? $data['imglist'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getImglistList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    protected function setImglistAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }


}
