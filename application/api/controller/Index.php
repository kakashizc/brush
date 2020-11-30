<?php

namespace app\api\controller;

use app\admin\model\BankBrush;
use app\admin\model\Brush;
use app\admin\model\OrderItem;
use app\common\controller\Api;
use think\Cache;
use think\Db;
use think\Validate;
use app\admin\model\Plat;
use app\admin\model\Order;
/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    /*
     * 版本信息
     * */
    public function version()
    {
        $this->success('1.1','','0');
    }

    public function tered()
    {
        $rds = new \Redis();
        $rds->connect('127.0.0.1','6379');
        $rds->setex('order_id',10,1333);
    }
    /*
     * 获取平台列表
     * */
    public function plats()
    {
        $plats = Plat::all(function($query){
            $query->field("id,name,concat('$this->img',image) as image");
        });
        $this->success('成功',$plats,'0');
    }
    /*
     * 平台入驻协议
     * */
    public function platxy()
    {
        $info = Db::name('plat_xy')->order('id desc')->find();
        $this->success('成功',$info,'0');
    }
    
    /*
     * 点击某个平台图片,查看订单列表
     * */
    public function plat_order()
    {
        $plat_id = $this->request->param('plat_id');//平台id
        $type = $this->request->param('type');//订单类型 垫付,浏览
        //查询对应plat_id下的已审核发布的订单
        $orders = Order::all(function($query)use($type,$plat_id){
            $query->where(['status'=>'2','type'=>$type,'plat_id'=>$plat_id])->field("id,order_no,broker,FROM_UNIXTIME(publish_time,'%Y-%m-%d %H:%i:%s') as ptime,goods_repPrice");
        })->each(function($item) use($plat_id){
            $plat = Plat::where('id',$plat_id)->find();
            $item->img = IMG.$plat['image'];
            //查询此任务剩余单量
            $last_num = OrderItem::where(['order_id'=>$item['id'],'brush_id'=>0])->count();
            $item->last_num = $last_num;
        })->toArray();
        if ( sizeof($orders) > 0 ){
            $this->success('成功',$orders,'0');
        }else{
            $this->success('无订单',$orders,'1');
        }
    }

    /*
     * 预览订单页面接口
     * */
    public function preview()
    {
        $orderId = $this->request->param('order_id');
        $order = Order::with('admin')->where(function($query) use ($orderId){
            $query->where('order.id',$orderId);
        })->find();
        $data = [
            "id" => $order->id,
            "keywords" => $order->keywords,
            "goods_ame" => $order->goods_ame,
            "goods_price" => $order->goods_price,
            "goods_repPrice" => $order->goods_repPrice,
            "goods_sku" => $order->goods_sku,
            "goods_num" => $order->goods_num,
            "goods_image" => IMG.$order->goods_image,
            "shop_desc" => $order->shop_desc,
            "shopname" => $order->admin->nickname,
        ];
        $this->success('成功',$data,'0');
    }

    /**
     * 首页
     * 首页轮播图,通知,注意事项,新手必读,联系客服统一在这里
     */
    public function index()
    {
        $data = [];
        //1,获取轮播图
        $ban = Db::name('banner')->find();
        $banners = [];
        $images = explode(',', $ban['images']);
        foreach ($images as $kk => $vv) {
            $banners[$kk] = IMG.$vv;
        }
        $data['banner'] = $banners;
        //2,获取通知
        $notice = Db::name('notice')->field("id,content,title,FROM_UNIXTIME(ctime,'%Y-%m-%d %H:%i:%s') as ctime")->select();
        foreach ($notice as $k=>$v){
            if ( $notice[$k]['content']) {
                if(strpos($notice[$k]['content'],'src=')!==false){
                    $notice[$k]['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $v['content']);
                }
            }
        }
        $data['notice'] = $notice;
        //3,获取注意事项
        $data['att'] = Db::name('notice_att')->find();
        //4,获取新手必读
        $data['new'] = Db::name('notice_new')->find();
        //6,获取联系客服
        $data['kefu'] = Db::name('kefu')->find();
        $this->success('成功',$data,'0');
    }

    /*
     * 刷手注册
     * */
    public function register()
    {
        $mobile = $this->request->request('mobile');
        $password = $this->request->request('password');
        //$msg = $this->request->request('msg'); //短信验证码
        $rec_code = $this->request->request('rec_code');//上级推荐码
        if ( !$mobile || !$password || !$rec_code ) {
            $this->success('缺少参数','','1');
        }
        //查看当前手机号是否已注册
        $is = Brush::where('mobile',$mobile)->find();
        if($is){
            $this->success('此手机号已注册','','1');
        }
        if ( !Validate::regex($mobile, "^1\d{10}$") ) {
            $this->success('手机号格式错误','','1');
        }
        $parent = Brush::where('code',$rec_code)->find();
        if ( !$parent ){
            $this->success('推荐码不存在','','1');
        }
        //插入
        $data['mobile'] = $mobile;
        $data['password'] = md5($password);
        $data['pid'] = $parent->id;
        $is = Brush::create($data);
        if ( $is->id ) {//添加成功后,生成唯一的推荐码 code
            $this->getCode($is->id);
            $this->success('注册成功','','0');
        }else{
            $this->success('注册失败','','1');
        }
    }

    /*
     * 刷手登录
     * */
    public function login()
    {
        $mobile = $this->request->request('mobile');
        $password = $this->request->request('password');
        if ( !$mobile || !$password ) {
            $this->success('缺少参数','','1');
        }
        $brush = Brush::where(['mobile'=>$mobile,'password'=>md5($password)])->field('id')->find();
        if ( $brush ){
            //登录成功,返回token
            $brush['token'] = $this->getJWT($brush['id']);
            $this->success('登录成功',$brush,'0');
        }else{
            //用户不存在
            $this->success('登录失败','','1');
        }
    }

    /*
     * 刷手修改密码
     * */
    public function edit_pass()
    {
        $mobile = $this->request->request('mobile');//手机号
        $password = $this->request->request('password');//新密码
        //$msg = $this->request->request('msg'); //短信验证码
        if ( !Validate::regex($mobile, "^1\d{10}$") ) {
            $this->success('手机号格式错误','','1');
        }
        //$cache_msg = Cache::get($mobile);
//        if ($msg != $cache_msg) {//如果验证码不正确,退出
//            $this->success('短信验证码错误或者超时', '','1');
//        }
        //验证码通过后,更新 新密码
        $res = Brush::where('mobile',$mobile)->setField('password',md5($password));
        if ( $res ){
            $this->success('修改成功','','0');
        }else{
            $this->success('新旧密码一致','','1');
        }
    }

    /*
     * 生成不重复的推荐码
     * */
    private function getCode($uid)
    {
        $user = Brush::get($uid);
        if (!$user->code){
            //生成code
            $code =  mt_rand(10000,999999);
            //查看生成的推荐码是否已存在
            $is = Brush::where(['code'=>$code])->find();
            if ($is){
                $this->getCode($uid);
            }else{
                //如果没人用这个推荐码, 那么更新为当前用户的推荐码
                $user->code = $code;
                $user->save();
                return  $code;
            }
        }else{
            return $user->code;
        }
    }

    /*
     * 根据用户id, 生成 并 获取JWT token
     * */
    private function getJWT($uid)
    {
        $jwt = new JwtController();
        $payload = array('iss' => 'admin', 'iat' => time(), 'exp' => time() + 72000000, 'nbf' => time(), 'sub' => 'www.admin.com', 'uid' => $uid);
        $token = $jwt::getToken($payload);
        return $token;
    }

    /*
     * 解析jwt token
     * */
    private function deJWT($token)
    {
        $jwt = new JwtController();
        $getPayload = $jwt->verifyToken($token);
        return $getPayload;
    }

    /*
     * 前端获取银行卡列表
     * */
    public function banks()
    {
        $data = Db::name('bank')->select();
        $this->success('成功',$data,0);
    }

    /*
     * 用户更换银行卡
     * */
    public function edit_bank()
    {
        $bank = BankBrush::get(['brush_id'=>2]);
        $bank->name = $this->request->param('name');
        $bank->bankno = $this->request->param('bankno');
        $bank->indent_name = $this->request->param('indent_name');
        $bank->indent_no = $this->request->param('indent_no');
        $bank->ctime = time();
        $res = $bank->save();
        if ($res){
            $this->success('修改成功','','0');
        }else{
            $this->success('提交失败','','1');
        }
    }
    
    /*
     * 获取申诉类型
     * */
    public function titles()
    {
        $list = Db::name('complain')->select()->toArray();
         foreach ($list as $k=>$v){
            $list[$k]['bank'] = $v['title'];
            unset($list[$k]['title']);
        }
        if (sizeof($list)){
            $this->success('成功',$list,'0');
        }else{
            $this->success('失败','','1');
        }
    }
}
