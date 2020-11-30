<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/11/19
 * Time: 11:00
 */

namespace app\api\controller;

use app\admin\model\OrderBrush;
use app\common\controller\Api;
use think\Db;
use app\admin\model\Comp as Comps;
use app\admin\model\Complain;
/*
 * 申诉订单类
 * */
class Comp extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $_uid;
    protected function _initialize()
    {
        parent::_initialize();
        $jwt = $this->request->header('Authorization');
        if($jwt){
            $this->check_token($jwt);
            $this->_uid = $this->_token['uid'];
        }else{
            $this->success('缺少token','','401');
        }
    }


    /*
     * 提交申诉订单
     * */
    public function comps()
    {
        $arr = [];
        $id = $this->request->param('id');//从我的订单接口中获取 刷手订单表 主键id
        $orderbrush = OrderBrush::get($id);
        $arr['complain_id'] = $this->request->param('tid');//获取申诉类型订单获取
        $arr['admin_id'] = $orderbrush->admin_id;
        $arr['orderbrush_id'] = $orderbrush->id;
        $arr['brush_id'] = $this->_uid;
        $arr['say'] = $this->request->param('say');//申诉内容
        $arr['ctime'] = time();
        $arr['images'] = $this->request->param('images');//申诉图片
        $res = Db::name('comp')->insertGetId($arr);
        if ($res){
            $orderbrush->status = '4';
            $orderbrush->save();
            $this->success('提交成功,等待审核','','0');
        }else{
            $this->success('提交失败','','1');
        }
    }
    /*
     * 获取我的申诉订单
     * */
    public function myComp()
    {
        $uid = $this->_uid;
        $ret = array();
        $all = OrderBrush::with(['plat','orderb'])
            ->where('brush_id',$uid)
            ->where('order_brush.status','4')
            ->select();
        foreach ($all as $key => $item){
            $ret[$key]['id'] = $item['id'];
            $ret[$key]['broker'] = $item['broker'];
            $ret[$key]['ctime'] = date('Y-m-d H:i:s',$item['ctime']);
            $ret[$key]['plat_image'] = IMG.$item['plat']['image'];
            $ret[$key]['plat_name'] = $item['plat']['name'];
            $ret[$key]['back'] = $item['back'];
            $ret[$key]['type'] = $item['type'];
            $ret[$key]['order_no'] = $item['order_no'];
            $ret[$key]['status'] = '4';
            $ret[$key]['order_id'] = $item['order_id'];
            $ret[$key]['shensu'] = 1;//申诉标记
            $ret[$key]['money'] = $item['orderb']['goods_repPrice'];
            $ret[$key]['goods_image'] = IMG.$item['orderb']['goods_image'];
            $comp = Comps::get(['orderbrush_id'=>$item['id']]);
            $ret[$key]['sta'] = $comp['status'];//申诉状态 状态:1=申诉中,2=已处理
            $ret[$key]['say'] = $comp['say'];//管理员反馈信息
            $Complain = Complain::get( $comp['complain_id']);
            $ret[$key]['title'] = $Complain->title;

        }
        if ( sizeof($all) > 0 ){
            $this->success('获取成功',$ret,'0');
        }
        $this->success('无数据','','1');

    }
}