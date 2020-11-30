<?php

namespace app\api\controller;

use app\admin\model\Order as OrderModel;
use app\common\controller\Api;

/**
 * 示例接口
 */
class Demo extends Api
{

    /*
     * 核对商品名称, 核对店铺名称, 核对设置的问题
     * 需要取出表中的answer_json字段,前端传key,和value1 ,后端根据key查找对应的数据库保存的value是否等于value1
     * 如果相等,核对通过
     * */
    public function verify()
    {
        $orderId = $this->request->param('id');
        $key = $this->request->param('key');
        $answer = $this->request->param('answer');
        $json = OrderModel::with('json')->where('order.id',$orderId)->find()->toArray();
        $an = json_decode($json['json']['answer_json'],1);
        if (array_key_exists($key,$an)){//如果存在这个问题标题,去验证答案
            if ($an[$key] == $answer){
                $this->success('验证通过','','0');
            }else{
                $this->success('答案错误','','1');
            }
        }else{
            $this->success('此问题不存在','','1');
        }

    }
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin ="*";
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight ="*";

    /*
     * 测试redis删除key
     * */
    public function redtest()
    {
        require_once 'Redis2.php';
        $redis2 = new \Redis2();
        $res = $redis2->setex('11_3_delay',5, "It is no pay");
        dump($res);
    }

}
