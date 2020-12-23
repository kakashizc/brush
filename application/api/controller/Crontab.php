<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/12/23
 * Time: 9:14
 */

namespace app\api\controller;
use app\admin\model\Brush;
use app\admin\model\Feed;
use app\admin\model\Order;
use app\common\controller\Api;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class Crontab extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $model = null;
    protected function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderBrush;
    }

    /*
     * crontab -e
     * crontab -l
     * 定时执行:  未审核的单子48小时后无操作自动划拨本金(后台)
     * */
    public function feed()
    {
        //1,查找超过48小时 还未审核的订单
        $now = time();
        $before = $now - 3600*48;
        $ids = Db::name('order_brush')
            ->where("ctime < $before")
            ->whereIn('back',[1,3])
            ->column('id');
        //2,循环数据,调用baseback方法->返给本金
        foreach ($ids as $k => $v){
            $this->baseback($v);
        }
    }
    /*
     * 返回给刷手本金
     * */
    private function baseback($oid = '')
    {
        $border = $this->model->where('id',$oid)->find();

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
            //返本金机制: 如果单子本金是100元, 刷手提交任务是 90元(小于单子金额), 那么返本金是90
            //如果刷手提交的金额大于100 , 那么返100
            $moy = 0;
            if ($border->act_money >= $base->goods_repPrice){
                $moy = $base->goods_repPrice;
            }else{
                $moy = $border->act_money;
            }
            Brush::where(['id'=>$border->brush_id])->setInc('money',$moy);
            Brush::where(['id'=>$border->brush_id])->setInc('total',$moy);//给刷手总额增加
            $this->model->where(['id'=>$oid])->setField('confirmtime',time());//更新 商家确认时间字段
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

        } catch (PDOException $PDOException){
            $this->model->rollback();

        } catch (Exception $exception){
            $this->model->rollback();

        }
    }
}