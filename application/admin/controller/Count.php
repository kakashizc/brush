<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
class Count extends Backend
{
    /**
     * 查看
     */
    public function index()
    {
        //查询数据
        $arr = array(
            ['类型', '总额(万元)', '数量', '功率'],
            ['风扇', '43.3', '85.8', '913.7'],
        );
        $this->view->assign("row", $arr);
        return $this->view->fetch();
    }
    /*
     * 获取数据
     * 1,平台总人数 商户总数,订单总额度,任务完成数,待完成任务数
     *
     */
    public function getData()
    {
        //平台总人数
        $brushs = Db::name('brush')->where('status','NEQ','3')->count();
        //商户总数
        $admins = Db::name('admin')->count();
        //订单总额度
        $total = Db::name('order_brush')->where('status','3')->sum('broker');
        //总任务完成数
        $all = Db::name('order_brush')->where('status','3')->count();
        $arr = array(
            ['数据', '平台总人数', '商户总数', '订单总额度(万元)','任务完成数'],
            ['数据',$brushs,$admins,$total,$all]
        );
        $this->success('ok','',$arr);
    }
}