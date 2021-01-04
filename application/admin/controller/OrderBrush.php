<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use app\admin\model\Brush;
use app\admin\model\Order;
use app\admin\model\Feed;
/**
 * 刷手订单
 *
 * @icon fa fa-circle-o
 */
class OrderBrush extends Backend
{
    
    /**
     * OrderBrush模型对象
     * @var \app\admin\model\OrderBrush
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderBrush;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("backList", $this->model->getBackList());
        $this->view->assign("typeList", $this->model->getTypeList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

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
                $where1 = ['admin_id'=>$admin_id];
            }

            $total = $this->model
                    ->with(['admin','brush','plat','orderb'])
                    ->where($where)
                ->where($where1)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['admin','brush','plat','orderb'])
                    ->where($where)
                ->where($where1)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $k=>$row) {
                if ($group_id == 1){
                    $row->isadmin = 1;
                }
                $row->visible(['id','order_no','isadmin','shop_name','status','ctime','broker','stime','ptime','confirmtime','gettime','donetime','back','images','else','type','act_account','act_money','pj_images','act_no']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['username','nickname']);
				$row->visible(['brush']);
				$row->getRelation('brush')->visible(['name','mobile','indent_name']);
				$row->visible(['plat']);
				$row->getRelation('plat')->visible(['name','image']);
				$row->visible(['orderb']);
				$row->getRelation('orderb')->visible(['goods_ame','goods_price','goods_repPrice','goods_sku','goods_link','goods_num','goods_image','shop_desc','status','broker']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /*
     * 商家确认发货
     * */
    public function send()
    {
        $order_brush_id = $this->request->param('ids');
        //1,修改刷手订单为已发货
        $res = $this->model->where('id',$order_brush_id)->setField('status','2');
        if ($res){
            $this->success('成功');
        }
        $this->success('异常');
    }

    /*
     * 返回给刷手本金
     * */
    public function feed_base($mulid = '')
    {
        if ($mulid){
            $oid = $mulid;
        }else{
            $oid = $this->request->param('ids');
        }
        $border = $this->model->where('id',$oid)->find();
        if ( $mulid && ($border['back'] == '2' || $border['back'] == '4')){
            return false;//如果是批量返本金 并且 订单状态为已返本金,直接返回
        }
        $this->model->startTrans();
        try{
            //1,修改刷手订单表状态
            //  先查询当前订单状态, 可能是两种状态: 1=本佣未返 3=本未返佣已返
            if ($border->back == '1'){
                $border->back = '2';//改成本返佣未返
                $border->save();
            }elseif ( $border->back == '3' ){
                $border->back = '4'; //改成本佣全返
                $border->status = '3';
                $border->save();
            }
            //2,走到这里, 订单类型肯定是垫付,返给刷手本金
            $base = Order::get($border->order_id);//查询此订单的垫付价格
            //返本金机制: 如果单子本金是100元, 刷手提交任务是 90元(小于单子金额), 那么返本金是90, 剩余10元还返给平台
            //如果刷手提交的金额大于100 , 那么返100
            $moy = 0;
            if ($border->act_money >= $base->goods_repPrice){
                $moy = $base->goods_repPrice;
            }else{
                //TODO 如果单子本金是100元, 刷手提交任务是 90元(小于单子金额), 那么返本金是90, 剩余10元还返给平台,并添加一条商户的财务记录
                $extra_money = $base->goods_repPrice - $border->act_money;
                Db::name('admin')->where('id',$border->admin_id)->setInc('money',$extra_money);
                $moy = $border->act_money;
            }
            Brush::where(['id'=>$border->brush_id])->setInc('money',$moy);
            Brush::where(['id'=>$border->brush_id])->setInc('total',$moy);//给刷手总额增加
            $this->model->where(['id'=>$oid])->setField('confirmtime',time());//更新 商家确认时间字段
            $this->model->where(['id'=>$oid])->setField('donetime',time());//更新 商家确认时间字段
            //3,插入一条返本金记录
            $data = [
                'brush_id' => $border->brush_id,
                'order_id' => $border->order_id,
                'admin_id' => $border->admin_id,
                'money' => $moy,
                'status' => '1',
                'ctime' => time(),
            ];
            Feed::create($data);
            $this->model->commit();
            if ($mulid){
                return true;
            }else{
                $this->success('成功');
            }

        } catch (PDOException $PDOException){
            $this->model->rollback();
            $this->error($PDOException->getMessage());
        } catch (Exception $exception){
            $this->model->rollback();
            $this->error($exception->getMessage());
        }
    }

    /*
     * 返回给刷手佣金
     * */
    public function feed_bro($mulid = '')
    {
        if ($mulid){
            $oid = $mulid;
        }else{
            $oid = $this->request->param('ids');
        }
        $border = $this->model->get($oid);
        if ( $mulid && ($border['back'] == '3' || $border['back'] == '4')){
            return false;//如果是批量返佣金 并且 订单状态为已返佣金,直接返回
        }
        $this->model->startTrans();
        try{
            
            if ($border->type == '2'){//如果是浏览订单,返回佣金,就算订单完成了
                $border->status = '3';
                 $border->back = '4';
                $border->save();
            }else{
                 //  先查询当前订单状态, 可能是两种状态: 1=本佣未返 2=本返佣未返
                if ($border->back == '1'){
                    $border->back = '3';//改成本未返佣已返
                    $border->save();
                }elseif ( $border->back == '2' ){
                    $border->back = '4'; //改成本佣全返
                    $border->status = '3';
                    $border->save();
                }
            }
            //2,给刷手增加佣金
            Brush::where(['id'=>$border->brush_id])->setInc('money',$border->broker);
            Brush::where(['id'=>$border->brush_id])->setInc('total',$border->broker); //2,给刷手增加历史总金额
            //3,添加一条佣金记录
            $data = [
                'brush_id' => $border->brush_id,
                'order_id' => $border->order_id,
                'admin_id' => $border->admin_id,
                'money' => $border->broker,
                'status' => '2',
                'ctime' => time(),
            ];
            Feed::create($data);
            //4,给刷手上级分佣,根据订单佣金的百分比得出的金额,返给上级,这个钱平台出
            $brush = Brush::get($border->brush_id);//查询当前刷手
            $parent = Brush::get($brush->pid);//刷手的上级
            Brush::where('id',$brush->pid)->setInc('money',$border->broker);//给上级增加余额
            Brush::where('id',$brush->pid)->setInc('total',$border->broker);//给上级增加总额
            
            //查找佣金比例
            $bl = Db::name('bro')->find();
            
            //插入一条上级获取佣金的记录
            $data2 = [
                'brush_id' => $parent->id,
                'order_id' => $border->order_id,
                'admin_id' => $border->admin_id,
                'money' => $border->broker*$bl['bro']??0,//先按照0.1计算, 这个比例是活的后期再改
                'status' => '3',
                'ctime' => time(),
            ];
            Feed::create($data2);
            $this->model->commit();
            if ($mulid){
                return true;
            }else{
                $this->success('成功');
            }
        } catch (PDOException $PDOException){
            $this->model->rollback();
            $this->error($PDOException->getMessage());
        } catch (Exception $exception){
            $this->model->rollback();
            $this->error($exception->getMessage());
        }
    }

    /**
     * 批量更新
     */
    public function multi_edit()
    {
        $params = $this->request->param();
        $idarr = explode(',',$params['ids']);
        if($params['params'] == 'ben'){
            //批量返本金
            foreach ($idarr as $k=>$v){
                $ret = $this->feed_base($v);
                if ($ret == false){
                    continue;
                }
            }
        }else{
            //批量返佣金
            foreach ($idarr as $k=>$v){
                $ret = $this->feed_bro($v);
                if ($ret == false){
                    continue;
                }
            }
        }
        $this->success('成功');
    }

}
