<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\controller\Redis;
use app\common\controller\sendMsg;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\Admin;
use app\admin\model\OrderItem;
use app\api\controller\Upload;
use think\Hook;

/**
 * 订单
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    
    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;
    protected $noNeedRight = ['*'];
    private $_redis;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("baseTypeList", $this->model->getBaseTypeList());
        $this->view->assign("broTypeList", $this->model->getBroTypeList());
        $this->_redis = Redis::getInstance()->getRedisConn();
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /*
     * 商户提交任务
     * */
    public function examine()
    {
        $orderid = $this->request->param('ids');
        //1,更改订单状态为 已发布待审核
        $ret = $this->model->where('id',$orderid)->setField('status','1');
        if ($ret){
            $send = new sendMsg();
            $send->send('有新的商家发单,请及时审核!');
            $this->success('成功','',$orderid);
        }else{
            $this->error('提交失败','',$orderid);
        }
    }

    /*
       后台管理 输入佣金
       商家发单,输入垫付金额,自动计算对应范围的佣金
       比如商家输入的任务垫付金额2元, 后台管理审核通过,发布任务时候改成了1.8元,那么实际佣金金额就是1.8元 ,  0.2元的差价给了平台了
       后台管理员可以自己设定 金额对应的佣金范围
     */
    public function do_publish()
    {
        $params = $this->request->param();
        $id = $params['id'];
        $act_bro = $params['act_bro'];
        if (!is_numeric($act_bro))$this->error('错误的金额');
        $this->publish($id,$act_bro);
    }

    /*
     * 平台管理员审核通过并发布商家的刷单任务
     * 1,根据主订单的发单数量,生成对应数量的子item订单号
     * 2,用户点击接单,更新item订单的刷手id,
     * */
    public function publish($id,$act_bro)
    {
        //主订单id ---- $id
        //获取主订单设置的刷单数量
        $order = $this->model->where('id',$id)->find();
        //循环生成子订单数量
        $item = array();
        $ctime = time();
        for ( $i=0; $i<$order['order_num']; $i++ ){
            $item[$i]['item'] = $order['order_no'].'+'.$i;//子订单号  主订单号+循环的变量$i,保证不重复就行
            $item[$i]['order_id'] = $id;//子订单中绑定主订单id
            $item[$i]['ctime'] = $ctime;//子订单创建的时间
            $item[$i]['store_id'] = $order['shop_id'];//所属店铺id
            $item[$i]['plat_id'] = $order['plat_id'];//所属平台id 淘宝 京东 拼多多等
        }
        //开启事务
        $this->model->startTrans();
        try{
            //更新订单的act_bro字段
            $this->model->where('id',$id)->setField('act_bro',$act_bro);
            $res = Db::name('order_item')->insertAll($item);
            if ($res){
                //插入成功后修改主订单状态
                $up = array(
                    'status' => 2,
                    'publish_time' => time()
                );
                $this->model->where('id',$id)->update($up);

                //扣除商家余额 和 增加发布订单的记录条数
                $orderinfo = $this->model->find($id);
                $total = ($orderinfo['broker']+$orderinfo['goods_repPrice']) * $orderinfo['order_num'];
                Db::name('admin')->where('id',$order['shop_id'])->setDec('money',$total);
                Db::name('admin')->where('id',$order['shop_id'])->setInc('total_order',$orderinfo['order_num']);
                //商家余额
                $admins = Admin::get($order['shop_id']);
                //增加一条 财务记录
                admin_record($order['shop_id'],'1','-'.$total,$admins->money,$admins->nickname);
                $this->model->commit();
                //存入redis一份,提供给抢单的人用
                $this->_redis->lPush('order_wait_list',$id);//存入redis后, TODO 抢单的时候查看此订单是否还有单子,如果没有了 就清除此id
                $this->success('任务发布成功','order/index');
            }else{
                $this->model->rollback();
                $this->error('发布失败');
            }
        }catch(Exception $exception){
            $this->model->rollback();
            $this->error($exception->getMessage());
        }catch(PDOException $PDOException){
            $this->error($PDOException->getMessage());
        }

    }

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //如果不是总管理员,那么只获取自己商户的订单
            $where1 = "";
            $admin_id = $this->auth->id;
            $group_id = $this->auth->getRoleid($admin_id)??'';//获取当前角色的管理组id
            if ($admin_id != 1){
                $where1 = "shop_id=$admin_id";
            }
            $total = $this->model
                    ->with(['admin','plat','json'])
                    ->where($where)
                    ->where($where1)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['admin','plat','json'])
                    ->where($where)
                    ->where($where1)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                if ($group_id == 1){
                    $row->isadmin = 1;
                }
                $row->getRelation('admin')->visible(['username','nickname']);
				$row->getRelation('plat')->visible(['name','brok']);
				$row->getRelation('json')->visible(['name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['shop_id'] = $this->auth->id;
                $params['order_no'] =  date('YmdHis').'_'.$this->auth->id;
                $params['ctime'] = time();
                if ( $params['goods_image'] ){
                    $this->addwater($params['goods_image']);//加水印
                }
                //计算单子的总金额(本金+佣金*单子数量),判断商家余额是否充足, 如果充足扣除发单商家的金额
                $total = ($params['broker']+$params['goods_repPrice']) * $params['order_num'];
                //查询当前商家余额
                $store = Admin::get($this->auth->id);
                if ($total > $store['money']){//如果单子总金额 大于 商家余额, 不能发单
                    $this->error('余额不足!请充值!');
                }

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /*
     * 商家撤单按钮
     * 任务状态:0=未发布,1=已发布待审核,2=审核通过已发布,3=平台拒发布,4=商家撤单
     * */
    public function feed_back()
    {
        //订单id
        $id = $this->request->param('ids');
        //1,如果订单状态是 0 1 3 说明刷手没有接单,直接算总金额返给商家就行
        $order = $this->model->find($id);
        if ($order['status'] == '4'){
            $this->success('此单已撤销,请勿重复撤单!');
        }
        $this->model->startTrans();
        if ( $order['status'] == '2' ){
            //2,如果状态是2 说明审核通过发布了扣商家钱了,先返回未被接单的子订单金额 (然后刷手已接单的单子,会在提交的时候打断提交,并返回给商户钱)
            $last_num = OrderItem::where(['order_id'=>$order['id'],'brush_id' => 0])->count();
            $total = ($order['broker'] + $order['goods_repPrice']) * $last_num??0;
            $res = Admin::where('id',$order['shop_id'])->setInc('money',$total);
            Admin::where('id',$order['shop_id'])->setDec('total_order',$last_num);
            //如果状态不=2, 说明平台没有审核通过并发布此订单,没有扣款不需要返款
        }else{
            $total = ($order['broker'] + $order['goods_repPrice']) * $order['order_num'];
        }
        $order->status = '4';
        $res = $order->save();
        $admins = Admin::get($order['shop_id']);
        //商家财务记录
        admin_record($order['shop_id'],'2','+'.$total,$admins->money,$admins->nickname);
        $this->model->commit();
        if ($res){
            //给管理员后台发送一个订单提醒
            $send = new sendMsg();
            $send->send('有新的商家撤单,请及时查看!');
            $this->success('撤单成功,金额已返');
        } else {
            $this->model->rollback();
            $this->error('失败');
        }
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
    private function addwater($imgsrc)
    {
        $imgsrc = ROOT_PATH.'/public/'.$imgsrc;
        $markimg= ROOT_PATH.'/public/uploads/thumb/wat.png';
        $upload = new Upload();
        $res  = $upload->setWater($imgsrc,$markimg,'','',5,'','img');
    }
    /*
     * ajax接口
     * */
    public function get_bro()
    {
        $price = input('price');
        $res = Db::name('bro_set')->where("'$price' BETWEEN `low` and `high`")->value('bro');
        $this->success('ok','',$res);
    }

}