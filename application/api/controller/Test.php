<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/11/16
 * Time: 16:28
 */

namespace app\api\controller;


use app\admin\model\BrushPlat;
use app\admin\model\Order as OrderModel;
use app\admin\model\OrderItem;
use app\admin\model\OrderBrush;
use app\admin\model\Brush as BrushModel;
use app\common\controller\Api;
use think\Db;

/*
 * 模拟测试
 * */
class Test extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    private $_redis;
    public function _initialize()
    {
        parent::_initialize();
        $this->_redis = new \Redis();
        $this->_redis->connect('127.0.0.1','6379');
    }
    
    public function abc()
    {
        $res = $this->_redis->ttl('20_5_delay');
        var_dump($res);
    }
    
    /*
     * 图片添加水印
     * 参数说明：
        $imgSrc：目标图片，可带相对目录地址，
        $markImg：水印图片，可带相对目录地址，支持PNG和GIF两种格式，如水印图片在执行文件mark目录下，可写成：mark/mark.gif
        $markText：给图片添加的水印文字
        $TextColor：水印文字的字体颜色
        $markPos：图片水印添加的位置，取值范围：0~9
        0：随机位置，在1~8之间随机选取一个位置
        1：顶部居左 2：顶部居中 3：顶部居右 4：左边居中
        5：图片中心 6：右边居中 7：底部居左 8：底部居中 9：底部居右
        $fontType：具体的字体库，可带相对目录地址
        $markType：图片添加水印的方式，img代表以图片方式，text代表以文字方式添加水印
     * */
    public function addwater()
    {
        $markimg= ROOT_PATH.'/public/uploads/20201030/52ef61e08d064caf7dc1822f48319259.png';
        $imgsrc =ROOT_PATH.'/public/uploads/thumb/wat.png';
        $upload = new Upload();
        $res  = $upload->setWater($imgsrc,$markimg,'','',0,'','img');
    }
    
    
     /*
     * 查看在执行任务中的订单的剩余时间和状态(已核对待提交, 或 未核对)
     * */
    public function missionTime()
    {
        $order_id = $this->request->param('order_id');
        $uid = input('uid');
        //根据主订单id ,查询子id
        $item = OrderItem::get(['order_id'=>$order_id,'brush_id'=>$uid]);
        
        if (!$item){
            $this->success('订单不存在','','1');
        }
        $str = $item->id.'_'.$uid.'_'.'delay';
        
        $last_time = $this->_redis->ttl($str);//当前订单时间剩余秒数
        $ret = array(
            'last_time' => $last_time,
            'type' => $item->status
        );
        $this->success('成功',$ret,'0');
    }
    /*
     * 我的佣金明细
     * */
    public function brodetail()
    {
        $uid = input('uid');
        $myinfo = BrushModel::get(function ($list)use($uid){
            $list->where('id',$uid)->field('total,money');
        });
        $ret = [];
        $ret['total'] = $myinfo->total;//账号历史总额
        $ret['money'] = $myinfo->money;//账号当前余额, 可用于提现
        //获取当天佣金金额
        $start = strtotime(date("Y-m-d"),time());
        $end = $start+3600*24;
        $today = Db::name('feed')
            ->where('brush_id',$uid)
            ->where('status','NEQ','1')
            ->whereTime('ctime',[$start,$end])
            ->value('money');
        $ret['today'] = $today;//今日佣金总额
        // 查找支出/收入明细
        //1,提现记录
        $txrecord = Db::name('tx')->where('brush_id',$uid)->field("money,FROM_UNIXTIME(ctime,'%Y-%m-%d %H:%i:%s') as ctime")->where('status','1')->select()->toArray();
        $ret['tixian'] = $txrecord;
        //2,获取佣金记录
        $getrecord = Db::name('feed')->where('brush_id',$uid)->field("money,status,FROM_UNIXTIME(ctime,'%Y-%m-%d %H:%i:%s') as ctime")->select()->toArray();
        $ret['get'] = $getrecord;
        $this->success('成功',$ret,'0');
    }
    
    //模拟接单
    public function jiedan()
    {
        $orderId = $this->request->param('order_id');
        $uid = $this->request->param('uid');
        //给用户分配一个item 订单
        $is = OrderItem::where(['order_id'=>$orderId,'brush_id'=>$uid])->find();
        if ( $is ){
            //如果查询到了, 说明已接此单, 不能再接了
            $this->success('您已接此单,不能重复操作','','1');
        }

        $item = OrderItem::where(['order_id'=>$orderId,'brush_id'=>0])->order('id asc')->find();
        if ( $item ){
            $item->brush_id = $uid;
            $item->btime = time();
            $res = $item->save();
            $str = $item->id.'_'.$uid.'_'.'delay';
            $second = 1800;//30分钟*60秒 = 1800秒,如果此时间内未有动作 订单自动回收
            $this->_redis->setex($str,$second, "pay");
            $this->success('接单成功','','0');
        }

    }

    //模拟核对问题
    public function hedui()
    {
        $uid = $this->request->param('uid');
        $item_id = $this->request->param('item_id');
        $str = $item_id.'_'.$uid.'_'.'delay';
        //先验证这个key是否存在,如果不存在,说明订单已经被收回了
        $isRedis = $this->_redis->get($str);
//        echo $time = $this->_redis->ttl($str);exit;
        //如果key存在,说明未被回收,验证通过后,进行下一步
        if ( $isRedis ){
            //验证答案是否正确,通过 继续往下走
            $key_last_time = $this->_redis->ttl($str);//key剩余时间
            $usedtime = 1800-$key_last_time; //已用的时间
            $lasttime = 3600-$usedtime;
            $this->_redis->setex($str,$lasttime, "pay");
            OrderItem::where('id',$item_id)->setField('2');
            $this->success('验证通过','','1');
        }else{
            $this->success('key不存在,订单已被回收','','0');
        }
    }

    //模拟提交任务
    public function tj()
    {
        $uid = $this->request->param('uid');
        $str = $this->request->param('item_id').'_'.$uid.'_'.'delay';
        $isRedis = $this->_redis->get($str);
        if (!$isRedis){
            $this->success('订单已超时收回','','1');
        }else{
            //删除key
            $newstr = $this->request->param('item_id').'_'.$uid.'_'.'active';
            $this->_redis->rename($str,$newstr);//改 delay 为 active 到时自动删除
            $this->_redis->setex($newstr,'1','1');//设置
            $this->success('提交成功','','0');
        }
    }

    public function getkey()
    {
        echo $this->_redis->ttl('12_5_delay');//查看 key 剩余时间
    }

    public function setr()
    {
//        require_once '../../redis/Redis2.php';
        $this->_redis->setex('abc',20,'aaaa');
        sleep(5);
        $this->_redis->rename('abc','abcd');
        //$res = $this->_redis->delete('abcd');
//        var_dump($res);
    }
    public function getr()
    {
//       echo $this->_redis->get('abc');
       echo $this->_redis->get('abcd');
    }

    /*
    * 查看我的团队
    * 手机号 注册时间 总任务量 获取佣金 --- 四个字段
    * */
    public function myTeam()
    {
        $uid = 2;
        $brush = BrushModel::with(['order_brush','feed'])
            ->field("id,name,mobile,concat('$this->img',avatar) as img")
            ->where('pid',$uid)
            ->select()
            ->each(function ($item){
                $item['totalNum'] = count($item['order_brush']);//下级总单数
                $item['totalMoney'] = array_sum(array_column($item['feed']->toArray(),'money'));//下级总佣金
                unset($item['order_brush']);
                unset($item['feed']);
            })->toArray();
        if ( sizeof($brush) > 0 ){
            $this->success('成功',$brush,'0');
        }else{
            $this->success('无下级',[],'1');
        }
    }

    /*
     * 模拟接单
     * */
    public function doMission()
    {
        $orderId = $this->request->param('order_id');
        $uid = input('uid');
        //查看是否已接此单
        $is = OrderItem::where(['order_id'=>$orderId,'brush_id'=>$uid])->find();
        if ( $is ){
            //如果查询到了, 说明已接此单, 不能再接了
            $this->success('您已接此单,不能重复操作','','1');
        }
        Db::startTrans();
        $item = OrderItem::where(['order_id'=>$orderId,'brush_id'=>0])->order('id asc')->find();
        if ( $item ){
            $item->brush_id = $uid;
            $item->btime = time();
            $res = $item->save();

            //查询主订单信息
            $order = new OrderModel();
            $orderInfo = $order->getOrders($orderId);
            //查找订单对应的平台,并且查找到刷手在平台绑定的账号
            $account = BrushPlat::get(['brush_id'=>$uid,'plat_id'=>$orderInfo['plat']['id']]);
            if (!$account){
                $this->success('您尚未绑定'.$orderInfo['plat']['name'].'平台账号,请先绑定','','1');
            }
            if ($account->status == '1' || '2'){
//                $this->success('平台账号尚未审核通过,不能接单','','1');
            }
            //echo '<pre>'; print_r($account);exit;
            $orderInfo['account'] = $account->account;
            $orderInfo['item_id'] = $item->id;
            //对数据进行拼装
            if ($res) {
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
     * 获取某个redis的key
     * */
    public function keys()
    {
        $str = input('str');
        echo $value = $this->_redis->get($str);
        echo '*******';
        echo $ttl = $this->_redis->ttl($str);
    }
    
    /*
     * 查看我的订单
     * @param $status int 订单状态 1=全部订单,2=待收货,3=已完成
     * @param $type int 订单类型 1=垫付任务 2=浏览任务
     *
     * */
    public function myMission()
    {
        $uid = input('uid');
        
        $status = $this->request->param('status')??1;//任务状态
        if ($status == 1 ){
            $where = "";
        }else{
            $where = ['order_brush.status'=>$status];
        }
        
        //任务类型
        $type = (int)$this->request->param('type')??1;
        if ($type == '2'){
            $where1 = ['order_brush.type' => $type];
            $where = '';
        }else{
            $where1 = ['order_brush.type' => '1'];
        }

        $ret = array();
        $all = OrderBrush::with(['plat','orderb'])
            ->where('brush_id',$uid)
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
            }
        }
        if ( sizeof($all) > 0 ){
            $this->success('获取成功',$ret,'0');
        }
        $this->success('无数据','','1');
    }
    
}