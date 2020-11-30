<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/11/5
 * Time: 11:15
 */

namespace app\api\controller;


use app\admin\model\BrushPlat;
use app\admin\model\Feed;
use app\admin\model\OrderBrush;
use app\admin\model\Plat;
use app\common\controller\Api;
use app\admin\model\BankBrush;
use app\admin\model\Brush as BrushModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class Brush extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    private $_uid = 1;

    protected function _initialize()
    {
        parent::_initialize();
        $jwt = $this->request->header('Authorization');
        if($jwt){
            $arr = $this->deJWT($jwt);
            $this->_uid = $arr['uid'];
        }else{
            $this->success('缺少token','','401');
        }
    }
    
    /*
     * 更换头像
     * */
    public function chead()
    {
        $imgstr = $this->request->param('img');
        $up = BrushModel::where('id',$this->_uid)->update(['avatar'=>$imgstr]);
        if ($up){
            $this->success('成功',['img'=>IMG.$imgstr],'0');
        }else{
            $this->success('修改失败','','1');
        }
    }
    /*
     * 我的邀请码
     * */
    public function mycode()
    {
        $uid = $this->_uid;
        $brush = BrushModel::get($uid);
        if ($brush){
            $data['code'] = $brush->code;
            $data['qrcode'] = 'http://bill.zhoujiasong.top/build.png';
            $this->success('成功',$data,'0');
        }else{
            $this->success('不存在','','1');
        }
    }
    /*
     * 查看头像是否为空
     * */
    public function hedempty()
    {
        $head = Db::name('brush')->where('id',$this->_uid)->value('avatar');
        if ($head){
            $this->success('有头像',['img'=>IMG.$head],'0');
        }else{
            $this->success('无头像','','1');
        }
    }
    /*
     * 获取我的信息
     * 头像,手机号,性别,信用分,邀请码,余额,总单数,本月单数,今日单数
     * */
    public function myinfo()
    {

        $uid = $this->_uid;
        $my = BrushModel::get(function ($list )use($uid){
            $list->where('id',$uid)
                ->field("id,name,indent_name as name,concat('$this->img',avatar) as avatar,gender,code,score,money");
        });
        //查找我的订单
        $ob = OrderBrush::where('brush_id',$my->id);
        $total = $ob->count();
        $my['total'] = $total??0;//全部订单
        //获取本月单数量
        //1,查出当月开始时间戳 结束时间戳 用whereTime()查询就行了
        $BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));
        $last = date('Y-m-d', strtotime("$BeginDate +1 month"));
        $monthcount = $ob->whereTime('ctime',[strtotime($BeginDate),strtotime($last)])->count();
        //获取今日单数量
        //1,查找当天0点时间戳 和 24点时间戳
        $start = strtotime(date("Y-m-d"),time());
        $end = $start+3600*24;
        $todaycount = $ob->whereTime('ctime',[$start,$end])->count();
        $my['today'] = $todaycount??0;
        $my['month'] = $monthcount??0;
        $this->success('成功',$my,'0');
    }
     /*
    * 查看我的团队
    * 手机号 注册时间 总任务量 获取佣金 --- 四个字段
    * */
    public function myTeam()
    {
        $brush = BrushModel::with(['order_brush','feed'])
            ->field("id,name,mobile,concat('$this->img',avatar) as img,FROM_UNIXTIME(ctime,'%Y-%m-%d') as ctime")
            ->where('pid',$this->_uid)
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
     * 用户绑定银行卡
     * */
    public function bind_bank()
    {
        //先查询有没有在审核的 或者已审核通过的
        $is = BankBrush::get(['brush_id'=>$this->_uid]);
        if ($is){
            $this->success('已提交过审核',$is,'1');
        }
        $data = [];
        $data['name'] = $this->request->param('name');
        $data['bankno'] = $this->request->param('bankno');
        $data['indent_name'] = $this->request->param('indent_name');
        $data['indent_no'] = $this->request->param('indent_no');
        $data['ctime'] = time();
        $data['brush_id'] = $this->_uid;
        $res = BankBrush::create($data);
        if ($res->id){
            $this->success('申请成功,等待审核','','0');
        }else{
            $this->success('提交失败','','1');
        }
    }

    /*
     * 用户更换银行卡
     * */
    public function edit_bank()
    {
        $bank = BankBrush::get(function ($query){
            $query->where(['brush_id'=>$this->_uid])->order('id desc');
        });
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
     * 获取我已绑定的银行卡
     * */
    public function my_bank()
    {
        $bank = BankBrush::where(['brush_id'=>$this->_uid])->order('id desc')->field('name,bankno,indent_name,status')->find();
         if ($bank) {
            $bank['bankno'] = substr($bank['bankno'], '-4', 4);
            $this->success('成功', $bank, '0');
        }else{
            $this->success('暂无银行卡', '', '1');
        }
    }

    /*
     * 用户绑定平台,提交审核
     * */
    public function bind_plat()
    {
        $data = array();
        $data['plat_id'] = $this->request->param('plat_id');//要绑定的平台id
        $data['brush_id'] = $this->_uid;//刷手id
        $data['account'] = $this->request->param('account');
        $data['recive'] = $this->request->param('recive');
        $data['mobile'] = $this->request->param('mobile');
        $data['gender'] = $this->request->param('gender');
        $data['recive_city'] = $this->request->param('recive_city');
        $data['recive_address'] = $this->request->param('recive_address');
        $data['my_image'] = $this->request->param('my_image');
        $data['myinfo_image'] = $this->request->param('myinfo_image');
        $data['ctime'] = time();
        $res = BrushPlat::create($data);
        if ($res->id){
            $this->success('申请成功,等待审核','','0');
        }else{
            $this->success('提交失败','','1');
        }
    }

    /*
     * 查看各个平台认证情况
     * */
    public function plat_info()
    {
        $plats = Plat::with(['brushPlat'=>function($query){
            $query->where('brush_id',$this->_uid);
        }])->select()->each(function ($item){
            $item['image'] = IMG.$item['image'];
            if (sizeof($item['brush_plat']) > 0){
                $item['brush_plat'][0]['my_image'] = IMG.$item['brush_plat'][0]['my_image'];
                $item['brush_plat'][0]['myinfo_image'] = IMG.$item['brush_plat'][0]['myinfo_image'];
            }
        })->toArray();
        
        foreach ($plats as  $k=>$v){
            if ( sizeof($plats[$k]['brush_plat']) == 0 ){
                $plats[$k]['brush_plat'][0]['status'] = '3';
            }
        }
        $this->success('成功',$plats,'0');
    }

    /*
     * 刷手,实名认证
     * */
    public function indent()
    {
        $up = [];
        $up['indent_name'] = $this->request->param('indent_name');
        $up['indent_no'] = $this->request->param('indent_no');
        $up['front'] = $this->request->param('front');
        $up['back'] = $this->request->param('back');
        $up['keep'] = $this->request->param('keep');
        $up['ali'] = $this->request->param('ali');
        $up['status'] = '1';
        $res = \app\admin\model\Brush::where('id',$this->_uid)->update($up);
        if ($res){
            $this->success('申请成功,等待审核','','0');
        }else{
            $this->success('提交失败','','1');
        }
    }
    /*
     * 查看实名认证状态
     * */
    public function indent_status()
    {
        $res = \app\admin\model\Brush::get(function ($query){
            $query->where('id',$this->_uid)->field('status');
        });
        if (!$res){
            $res['status'] = '4';
        }
        $this->success('成功',$res,'0');
    }
    
    /*
     * 申请提现
     * */
    public function tixian()
    {
        $uid = $this->_uid;
        $money = $this->request->param('money');//前端发来的金额
        $last = BrushModel::get($uid);
        if ($last->money != $money){
            $this->success('金额与实际账号金额不符,请联系管理员','','1');
        }
        $alredy = Db::name('tx')->where('status','0')->where('brush_id',$uid)->find();
        if ($alredy){
            $this->success('有未审核的订单,不可重复提交','','1');
        }
        //根据唯一索引,拼接用户id和当前日期, 限制每天只有一条提现记录
        $str = $uid.'_'.date('Y-m-d',time());
        $insert['date'] = $str;
        $insert['money'] = $last->money;
        $insert['brush_id'] = $uid;
        $insert['ctime'] = time();
        //查找刷手的银行卡信息
        $bank = Db::name('bank_brush')->where('brush_id',$uid)->where('status','1')->find();
        if (!$bank){
            $this->success('请先绑定银行卡','','1');
        }
        Db::startTrans();
        try {
            $insert['bank_name'] = $bank['name'];
            $insert['bank_no'] = $bank['bank_no'];
            $insert['indent_name'] = $bank['indent_name'];
            $insert['indent_no'] = $bank['indent_no'];
            $res = Db::name('tx')->insertGetId($insert);
            if ($res){
                //将用户余额置0
                Db::name('brush')->where('id',$uid)->setField('money',0);
                Db::commit();
                $this->success('已提交申请','','0');
            }else{
                Db::rollback();
                $this->success('提交失败','','1');
            }
        }catch(Exception $exception){
            Db::rollback();
            $this->success($exception->getMessage(),'','1');
        }catch(PDOException $PDOException ){
            Db::rollback();
            $this->success($PDOException->getMessage(),'','1');
        }
    }
    /*
     * 我的佣金明细
     * */
    public function brodetail()
    {
        $uid = $this->_uid;
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
    
    
    /*
     * 解析jwt token
     * */
    private function deJWT($token)
    {
        $jwt = new JwtController();
        $getPayload = $jwt->verifyToken($token);
        return $getPayload;
    }
}