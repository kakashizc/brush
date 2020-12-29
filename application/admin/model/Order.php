<?php

namespace app\admin\model;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $name = 'order';
    
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
        'type_text',
        'base_type_text',
        'bro_type_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3') ,'4' => __('Status 4') ];
    }

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getBaseTypeList()
    {
        return ['1' => __('Base_type 1'), '2' => __('Base_type 2'), '3' => __('Base_type 3')];
    }

    public function getBroTypeList()
    {
        return ['1' => __('Bro_type 1'), '2' => __('Bro_type 2'), '3' => __('Bro_type 3')];
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


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getBaseTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['base_type']) ? $data['base_type'] : '');
        $list = $this->getBaseTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getBroTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['bro_type']) ? $data['bro_type'] : '');
        $list = $this->getBroTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'shop_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function plat()
    {
        return $this->belongsTo('Plat', 'plat_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function json()
    {
        return $this->belongsTo('Json', 'json_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function brushs()
    {
        return $this->hasOne('OrderBrush', 'order_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /*
    * 获取order主表的信息
     * @param $orderId int 订单id
    * */
    public function getOrders($orderId)
    {
        $data = collection($this->with(['admin','plat','json'])->where('order.id',$orderId)->select())->toArray();
        //对数据进行拼装
        $info = array_shift($data); //取出二维数组第一个元素,同时删除原二维数组第一个元素
//        $info['shop']['nickname'] = mb_substr( $info['admin']['nickname'], 0, 1 )."*****";
        $info['shop']['nickname'] = tostar($info['act_sname']);
        unset($info['admin']);
        unset($info['goods_ame']);
        unset($info['goods_link']);
        unset($info['broker']);
        $info['goods_image'] = IMG.$info['goods_image'];
        $info['broker'] = $info['act_bro'];
        $info['plat']['image'] = IMG.$info['plat']['image'];
        foreach ($info['json'] as $k=>$v){
            if(strpos($k,'_json') !== false){
                $info['json'][$k] = json_decode($v,1);

                if ( $k == 'answer_json'){//如果是问答选项, 删除对应的value
                    //删除key对应的value
                    foreach ($info['json'][$k] as $i=>$j){
                        $info['json'][$k][$i] = '';
                    }
                }
            }
        }
        return $info;
    }
    
    public function getOrderDetail($orderId,$uid)
    {
       $data = collection($this->with(['admin','plat','json','brushs'=>function($list)use($uid){
            $list->where('brush_id',$uid);
        }])->where('order.id',$orderId)->select())->toArray();
        //对数据进行拼装
        $info = array_shift($data); //取出二维数组第一个元素,同时删除原二维数组第一个元素
//        $info['shop']['nickname'] = mb_substr( $info['admin']['nickname'], 0, 1 )."*****";
        $info['shop']['nickname'] = tostar($info['act_sname']);
        unset($info['admin']);
        unset($info['goods_link']);
        unset($info['broker']);
        $info['broker'] = $info['act_bro'];
        $info['goods_image'] = IMG.$info['goods_image'];
        $info['plat']['image'] = IMG.$info['plat']['image'];
        //处理提交任务时的图片
        if ($info['brushs']['images'] != ''){
            $imgarr = explode(',',$info['brushs']['images']);
            if ( sizeof($imgarr) > 0 ){
                foreach ($imgarr as $k => $v){
                    $info['brushs']['imgarr'][$k] = IMG.$v;
                }
            }
        }
        //处理上传的评价图片
        if ($info['brushs']['pj_images'] != ''){
            $imgarr = explode(',',$info['brushs']['pj_images']);
            if ( sizeof($imgarr) > 0 ){
                foreach ($imgarr as $k => $v){
                    $info['brushs']['pj_imgarr'][$k] = IMG.$v;
                }
            }
        }
        return $info;
    }
}
