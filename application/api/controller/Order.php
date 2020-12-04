<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/11/6
 * Time: 10:14
 */

namespace app\api\controller;


use app\admin\model\Admin;
use app\admin\model\BrushPlat;
use app\admin\model\OrderBrush;
use app\admin\model\OrderItem;
use app\common\controller\Api;
use app\admin\model\Order as OrderModel;
use think\Db;
use app\admin\model\Comp;
use app\admin\model\Complain;
/*
 * 步骤:
 * 1,商家后台添加主订单 -> 2,商家提交审核 -> 3,平台审核发布,根据主订单的任务数量生成对应数量的子订单
 *                                                                  ↓
 *                                         4,刷手点击接单,修改子订单的刷手id为当前刷手id 更新接单时间
 *                                                                  ↓
 *                                         5,如果刷手在规定时间内完成任务,并且提交了订单,那么插入刷手订单表
 *                                                                  ↓
 *                                         6,商家在后台进行审核,并且返回对应的佣金等操作
 *
 * */
class Order extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $_uid;
    private $_redis;
    protected function _initialize()
    {
        parent::_initialize();
        $jwt = $this->request->header('Authorization');
        if($jwt){
            $this->check_token($jwt);
            $this->_uid = $this->_token['uid'];
            $this->_redis = new \Redis();
            $this->_redis->connect('127.0.0.1','6379');
        }else{
            $this->success('缺少token','','401');
        }
    }



    /*
     * 刷手点击接单
     * 1,根据主订单号,分配给此刷手一个子订单
     * 2,返回订单拼装数据
     * */
    public function doMission()
    {
        $orderId = $this->request->param('order_id');
        //查看是否已接此单
        $is = OrderItem::where(['order_id'=>$orderId,'brush_id'=>$this->_uid])->find();
         if ( $is ){
            //查询出对应的数据给前端
            //查询主订单信息
            $order = new OrderModel();
            $orderInfo = $order->getOrders($orderId);
            $account = BrushPlat::get(['brush_id'=>$this->_uid,'plat_id'=>$orderInfo['plat']['id']]);
            if (!$account){
                $this->success('您尚未绑定'.$orderInfo['plat']['name'].'平台账号,请先绑定','','1');
            }
            
            if ($account->status == '0'  || $account->status == '2'){
                $this->success('平台账号尚未审核通过,不能接单','','1');
            }
            $orderInfo['account'] = $account->account;
            $orderInfo['item_id'] = $is->id;
            //如果查询到了, 说明已接此单, 不能再接了
            $this->success('您已接此单,请操作',$orderInfo,'0');
        }
        Db::startTrans();
        $item = OrderItem::where(['order_id'=>$orderId,'brush_id'=>0])->order('id asc')->find();
        if ( $item ){
            $item->brush_id = $this->_uid;
            $item->btime = time();
            $res = $item->save();

            //查询主订单信息
            $order = new OrderModel();
            $orderInfo = $order->getOrders($orderId);
            //查找订单对应的平台,并且查找到刷手在平台绑定的账号
            $account = BrushPlat::get(['brush_id'=>$this->_uid,'plat_id'=>$orderInfo['plat']['id']]);
            if (!$account){
                $this->success('您尚未绑定'.$orderInfo['plat']['name'].'平台账号,请先绑定','','1');
            }
            if ($account->status == '0' || $account->status == '2'){
                $this->success('平台账号尚未审核通过,不能接单','','1');
            }
            $orderInfo['account'] = $account->account;
            $orderInfo['item_id'] = $item->id;
            //对数据进行拼装
            if ($res) {
                //redis 存入key
                $str = $item->id.'_'.$this->_uid.'_'.'delay';
                $second = 1800;//30分钟*60秒 = 1800秒,如果此时间内未有动作 订单自动回收
                $this->_redis->setex($str,$second, "pay");
                Db::commit();
                $this->success('接单成功',$orderInfo,'0');
            }
            Db::rollback();
            $this->success('接单失败','','1');
        }
        Db::rollback();
        $this->success('任务不存在或任务已抢完','','1');
    }
    
    /*
     * 代操作的订单
     *
     * */
    public function waits()
    {
        $uid = $this->_uid;
        $item_order = OrderItem::all(function ($list)use($uid){
           $list->where('status','1')->whereOr('status','2')->where('brush_id',$uid);
        });
        if (sizeof($item_order) > 0){
            $order = new OrderModel();
            $ret = [];
            //查询主订单信息
            foreach ($item_order as $k=>$v){
                $ret[$k] = $order->getOrders($v->order_id);
                $ret[$k]['money'] = $ret[$k]['goods_repPrice'];
                $ret[$k]['order_id'] = $ret[$k]['id'];
                $ret[$k]['wait'] = 1;
                $ret[$k]['ctime'] = $ret[$k]['ctime_text'];
                $ret[$k]['plat_image'] = $ret[$k]['plat']['image'];
                $ret[$k]['plat_name'] = $ret[$k]['plat']['name'];
            }
            $this->success('成功',$ret,'0');
        }else{
            $this->success('无待操作订单','','1');
        }
    }
    
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
        $item_id = $this->request->param('item_id');
        $str = $item_id.'_'.$this->_uid.'_'.'delay';
        //var_dump($str);exit;
        //先验证这个key是否存在,如果不存在,说明订单已经被收回了
        $isRedis = $this->_redis->get($str);
        if (!$isRedis){
            $this->success('订单已超时收回','','1');
        }
        $json = OrderModel::with('json')->where('order.id',$orderId)->find()->toArray();
        if (!$json){
            $this->success('此订单或问题模板不存在','','1');
        }
        $an = json_decode($json['json']['answer_json'],1);
        //var_dump($key);exit;
        if (array_key_exists($key,$an)){//如果存在这个问题标题,去验证答案
            if ($an[$key] == $answer){
                
                $ret = OrderItem::where('id',$item_id)->setField('status','2');
                if ($ret){//如果成功说明 此订单第一次核对问题,否则就是核对过问题了, 不修改剩余任务时间,直接返回验证通过就行
                    //延长redis key的时间
                    //当前时间 - 接单时间 = 已用时间的秒数 用3600-已用时间= 剩余时间
                    $key_last_time = $this->_redis->ttl($str);//key剩余时间
                    $usedtime = 1800-$key_last_time; //已用的时间
                    $lasttime = 3600-$usedtime;
                    
                    $re = $this->_redis->setex($str,$lasttime, "pay");
                    $this->success('验证通过','','0');
                }else{
                    $this->success('验证通过','','0');
                }
            }else{
                $this->success('答案错误','','1');
            }
        }else{
            $this->success('此问题不存在','','1');
        }
    }


    /*
     * 提交任务
     * */
    public function submitMission()
    {
        $insert = [];
        $insert['images'] = $this->request->param('images');//所有图片的地址,用逗号拼接
        $type = $this->request->param('type');//订单类型
        $insert['act_no'] = $this->request->param('act_no')??'';//实际订单号
        $insert['act_money'] = (float)$this->request->param('act_money')??'';//实际付款金额
        
        //判断刷手提交的这个实际支付金额是否和订单设置的垫付金额一致,如果不一致 给申诉提示
        if ($type == '1'){
            $order = OrderModel::get($this->request->param('order_id'));//获取订单
            // $this->success('调试',$order,'1');
            $premoney = (float)$order->goods_repPrice;//订单的垫付价格
            if ($insert['act_money'] != $premoney){
                $comp = 1;
            }
            //判断如果订单号后6位 和 此订单平台,刷手认证时候填写的最近一笔订单号不同 , 提示申诉
            $orderm = OrderModel::get($this->request->param('order_id'));
            if ($orderm->plat_id){
                $plat_no = Db::name('brush_plat')
                    ->where('brush_id',$this->_uid)
                    ->where('plat_id',$orderm->plat_id)
                    ->value('last_order_no');
                //判断两个订单号的后六位
                $input_ono = substr($insert['act_no'],-6);
                $plat_ono = substr($plat_no,-6);
                if ($input_ono != $plat_ono){
                    $ono = 1;
                }
            }
        }

        $insert['order_no'] = $this->request->param('order_no');//主订单号
        if (!$this->request->param('order_id')){
            $order = OrderModel::get(['order_no'=>$insert['order_no']]);
            $insert['order_id'] = $order->id;//订单id
        }else{
            $insert['order_id'] = $this->request->param('order_id');//订单id
        }
        
        $item = OrderItem::where('id',$this->request->param('item_id'))->find();
        //先验证这个key是否存在,如果不存在,说明订单已经被收回了
        $str = $this->request->param('item_id').'_'.$this->_uid.'_'.'delay';
        $isRedis = $this->_redis->get($str);
        if (!$isRedis){
            $this->success('订单已超时收回','','1');
        }
        $insert['order_item_no'] = $item['item'];//子订单号id,根据此id查询子订单编号
        $insert['admin_id'] = $this->request->param('shop_id');
        $insert['shop_name'] = Admin::where('id',$this->request->param('shop_id'))->value('nickname');//商户名称或店铺昵称
        $insert['ctime'] = time();
        $insert['broker'] = $this->request->param('broker');
        $insert['brush_id'] = $this->_uid;
        $insert['plat_id'] = $this->request->param('plat_id');
        $insert['stime'] = $item['btime'];//任务开始时间=子订单表的用户点击接单的时间
        $insert['ptime'] = time();//付款时间,暂定为提交任务此时的时间
        //如果订单是浏览任务,提交默认status=1 如果是垫付任务 提交默认status=2 待收货/发货
        if($type == '1'){//垫付任务
            $insert['status'] = '2';
             $insert['type'] = $type;
        }elseif($type == '2'){//浏览任务
            $insert['status'] = '1';
             $insert['type'] = $type;
        }
        
        Db::startTrans();
        $res = OrderBrush::create($insert);
        if ($res->id){
            $ret = OrderItem::where('id',$this->request->param('item_id'))->setField('status','3');
            if ($ret){
                $newstr = $this->request->param('item_id').'_'.$this->_uid.'_'.'active';
                $this->_redis->rename($str,$newstr);//改 delay 为 active 到时自动删除
                $this->_redis->setex($newstr,1,'1');//设置时间为1,然后马上会被删除了
                Db::commit();
                if ( isset($comp) || isset($ono) ){
                    $return_data = [
                        'order_brush_id' => $res->id
                    ];
                    if(isset($comp)){
                        $msg = '提交成功,金额核对不同,提示申诉';
                    }elseif(isset($ono)){
                        $msg = '提交成功,订单号核对失败,提示申诉';
                    }elseif( isset($comp) && isset($ono)){
                        $msg = '提交成功,订单号核对失败,金额核对不同,提示申诉';
                    }
                    $this->success($msg,$return_data,'2');
                }else{
                    $this->success('提交成功,等待商家审核','','0');
                }
            }else{
                Db::rollback();
                $this->success('更改子订单状态失败','','1');
            }
        }
        Db::rollback();
        $this->success('失败','','1');
    }

    /*
     * 查看我的订单
     * @param $status int 订单状态 1=全部订单,2=待收货,3=已完成
     * @param $type int 订单类型 1=垫付任务 2=浏览任务
     *
     * */
    public function myMission()
    {
        $status = (int)$this->request->param('status')??1;//任务状态
        if ($status == 1){
            $where = "";
        }else{
             if ($status == '3'){
                //已完成或者已收货
                $where = "order_brush.status = '3' or order_brush.status = '5'";
            }else{
                $where = ['order_brush.status'=>$status];
            }
        }
        //任务类型
        $type = (int)$this->request->param('type')??1;
        if ($type == '2'){
            $where1 = ['order_brush.type' => $type];
            $where = '';
        }else{
            $where1 = '';
        }

        $ret = array();
        $all = OrderBrush::with(['plat','orderb'])
            ->where('brush_id',$this->_uid)
            ->where($where)
            ->where($where1)
            ->select();
        foreach ($all as $key => $item){
            $ret[$key]['id'] = $item['id'];
            $ret[$key]['order_id'] = $item['order_id'];
            $ret[$key]['status'] = $item['status'];
            $ret[$key]['broker'] = $item['broker'];
            $ret[$key]['ctime'] = date('Y-m-d H:i:s',$item['ctime']);
            $ret[$key]['plat_image'] = IMG.$item['plat']['image'];
            $ret[$key]['plat_name'] = $item['plat']['name'];
            $ret[$key]['back'] = $item['back'];
            $ret[$key]['type'] = $item['type'];
            $ret[$key]['order_no'] = $item['order_no'];
            $ret[$key]['money'] = $item['orderb']['goods_repPrice'];
            $ret[$key]['goods_image'] = IMG.$item['orderb']['goods_image'];
            $ret[$key]['title'] = '';
            if( $item['status'] == '4' ){
                //如果状态=4 说明是申诉订单
                $comp = Comp::get(['orderbrush_id'=>$item['id']]);
                $Complain = Complain::get( $comp['complain_id']);
                $ret[$key]['title'] = $Complain->title;
                $ret[$key]['sta'] = $comp->status;
            }
        }
        if ( sizeof($all) > 0 ){
            $this->success('获取成功',$ret,'0');
        }
        $this->success('无数据','','1');
    }
     /*
     * 查看某个已接单的订单的详情
     * */
    public function missionDetail()
    {
        $orderId = $this->request->param('order_id');
        
        $item = OrderItem::where(['order_id'=>$orderId,'brush_id'=>$this->_uid])->find();
        if ( $item ){
            //查询主订单信息
            $order = new OrderModel();
            $orderInfo = $order->getOrderDetail($orderId,$this->_uid);
            //var_dump($orderInfo);exit;
            //查找订单对应的平台,并且查找到刷手在平台绑定的账号
            $account = BrushPlat::get(['brush_id'=>2,'plat_id'=>$orderInfo['plat']['id']]);
            $orderInfo['account'] = $account->account;
            
            $ret = array();
            //判断,如果是申诉订单, 需要查询出对应的申诉字段
            if ($orderInfo['brushs']['status'] == '4'){
                $datas = OrderBrush::with('comps')->find()->toArray();
                $ret['comp']['ctime'] = date('Y-m-d H:i:s',$datas['comps']['ctime']);
                $ret['comp']['type'] = Db::name('complain')->where('id',$datas['comps']['complain_id'])->value('title');
                $ret['comp']['act_money'] = $datas['act_money'];
                $ret['comp']['order_money'] = $orderInfo['goods_repPrice'];
                $ret['comp']['say'] = $datas['comps']['say'];
            }
            
            $ret['plat_name'] = $orderInfo['plat']['name'];
            $ret['plat_img'] = $orderInfo['plat']['image'];
            $ret['good_name'] = $orderInfo['goods_ame'];
            $ret['broker'] = $orderInfo['broker'];
            $ret['goods_image'] = $orderInfo['goods_image'];
            $ret['keywords'] = $orderInfo['keywords'];
            $ret['goods_price'] = $orderInfo['goods_price'];
            $ret['goods_repPrice'] = $orderInfo['goods_repPrice'];
            $ret['goods_sku'] = $orderInfo['goods_sku'];
            $ret['goods_num'] = $orderInfo['goods_num'];
            $ret['shop_desc'] = $orderInfo['shop_desc'];
            $ret['back'] = $orderInfo['brushs']['back'];//返款状态:1=本佣未返,2=本返佣未返,3=本未返佣已返,4=本佣已返
            $ret['imgarr'] = $orderInfo['brushs']['imgarr'];//提交订单时的支付,聊天截图等
            $ret['pj_imgarr'] = $orderInfo['brushs']['pj_imgarr']??'';//提交订单时的支付,聊天截图等
            $ret['account'] = $orderInfo['account'];//买家账号
            $ret['shopname'] = $orderInfo['shop']['nickname'];//店铺名称
            $ret['status'] = $orderInfo['brushs']['status'];//任务状态:1=待返款,2=已发货,3=已完成,4=申诉
            $ret['order_no'] = $orderInfo['order_no'];//订单编号
            $ret['type'] = $orderInfo['type'];//任务模式:1=垫付,2=浏览
            $ret['base_type'] = $orderInfo['base_type'];//本金返款模式:1=立返,2=评价返,3=收货返
            $ret['bro_type'] = $orderInfo['bro_type'];//佣金返款模式:1=立返,2=评价返,3=收货返
            //每一步的时间
            $ret['ctime'] = $orderInfo['brushs']['ptime']?date('Y-m-d H:i:s',$orderInfo['brushs']['ctime']):0;//接收任务时间
            $ret['stime'] = $orderInfo['brushs']['stime']?date('Y-m-d H:i:s',$orderInfo['brushs']['stime']):0;//开始任务的时间
            $ret['ptime'] = $orderInfo['brushs']['ptime']?date('Y-m-d H:i:s',$orderInfo['brushs']['ptime']):0;//刷手支付时间
            $ret['confirmtime'] = $orderInfo['brushs']['confirmtime']?date('Y-m-d H:i:s',$orderInfo['brushs']['confirmtime']):0;//商家确认订单时间,返本金的时间
            $ret['gettime'] = $orderInfo['brushs']['gettime']?date('Y-m-d H:i:s',$orderInfo['brushs']['gettime']):0;//刷手收货,好评的时间
            $ret['donetime'] = $orderInfo['brushs']['donetime']?date('Y-m-d H:i:s',$orderInfo['brushs']['donetime']):0;//任务完成时间,返完本金,返完佣金就算完成订单
            $this->success('成功',$ret,'0');
        }
        $this->success('数据错误','','1');
    }
     /*
     * 点击收货,上传评价截图
     * */
    public function getGoods()
    {
        //1,根据 order_brush id 修改订单状态
        $id = $this->request->param('id');
        $imgarr = $this->request->param('images');
        $orderBrush = OrderBrush::get($id);
        if (!$orderBrush){
            $this->success('无此订单','','1');
        }
        $orderBrush->pj_images = $imgarr;
        $orderBrush->status = '5';
        $orderBrush->gettime = time();
        $orderBrush->save();
        $this->success('收货/评价成功','','0');

    }
    
     /*
     * 查看在执行任务中的订单的剩余时间和状态(已核对待提交, 或 未核对)
     * */
    public function missionTime()
    {
        $order_id = $this->request->param('order_id');
        //根据主订单id ,查询子id
        $item = OrderItem::get(['order_id'=>$order_id,'brush_id'=>$this->_uid]);
        if (!$item){
            $this->success('订单不存在','','1');
        }
        $str = $item->id.'_'.$this->_uid.'_'.'delay';
        $last_time = $this->_redis->ttl($str);//当前订单时间剩余秒数
        $ret = array(
            'last_time' => $last_time,
            'type' => $item->status
        );
        $this->success('成功',$ret,'0');
    }
    
    /*
    * 判断刷手是否接此单
      判断是否绑定平台
    * */
    public function isget()
    {
        $orderId = $this->request->param('order_id');
         //先查看是否实名认证通过了
        $userinfo = Db::name('brush')->find($this->_uid);
        if ($userinfo['status'] != '2'){
            $this->success('实名认证未审核通过,不能接单','','1');
        }
        $orderInfo = OrderModel::get($orderId);
        //查找订单对应的平台,并且查找到刷手在平台绑定的账号
        $account = BrushPlat::get(['brush_id'=>$this->_uid,'plat_id'=>$orderInfo->plat_id]);
        if (!$account){
            $this->success('您尚未绑定'.$orderInfo['plat']['name'].'平台账号,请先绑定','','1');
        }
        if ($account->status == '0' || $account->status == '2'){
            $this->success('平台账号尚未审核通过,不能接单','','1');
        }
        $is = OrderItem::where(['order_id'=>$orderId,'brush_id'=>$this->_uid])->find();
        if ( $is ){
            //判断用户是否提交此订单
            $sub = Db::name('order_brush')->where('order_id',$orderId)->where('brush_id',$this->_uid)->find();
            if ($sub){
                $this->success('您已接提交此订单,不能重复提交','','1');
            }else{
                $this->success('请继续操作','','0');
            }
        }else{
            $this->success('可以接单','','0');
        }
    }

    
}