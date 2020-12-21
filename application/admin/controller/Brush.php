<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;

/**
 * 刷手管理
 *
 * @icon fa fa-circle-o
 */
class Brush extends Backend
{
    
    /**
     * Brush模型对象
     * @var \app\admin\model\Brush
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Brush;
        $this->view->assign("genderList", $this->model->getGenderList());
        $this->view->assign("statusList", $this->model->getStatusList());
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
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where1 = "";
            $admin_id = $this->auth->id;
            $group_id = $this->auth->getRoleid($admin_id)??'';//获取当前角色的管理组id
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $row) {
                if ($group_id == 1){
                    $row->isadmin = 1;
                }
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function inc()
    {
        $id = input('ids');
        $this->assign('uid',$id);
        return $this->fetch('inc');
    }
    public function do_inc()
    {
        $brush_id = input('uid');
        $money = input('money');
        Db::startTrans();
        try{
            //1,增加刷手余额
            $a = Db::name('brush')->where('id',$brush_id)->setInc('money',$money);
            //2,添加一条记录
            $data = [
                'brush_id'=>$brush_id,
                'money' => $money,
                'status' => '1',
                'ctime' => time()
            ];
            $b = Db::name('feed_brush')->insert($data);
            if ($a && $b){
                Db::commit();
                $this->success('成功,请刷新页面查看');
            }
        }catch(Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
    }

    public function dec()
    {
        $id = input('ids');
        $this->assign('uid',$id);
        return $this->fetch('dec');
    }

    public function do_dec()
    {
        $brush_id = input('uid');
        $money = input('money');
        Db::startTrans();
        try{
            //1,增加刷手余额
            $a = Db::name('brush')->where('id',$brush_id)->setDec('money',$money);
            //2,添加一条记录
            $data = [
                'brush_id'=>$brush_id,
                'money' => $money,
                'status' => '2',
                'ctime' => time()
            ];
            $b = Db::name('feed_brush')->insert($data);
            if ($a && $b){
                Db::commit();
                $this->success('成功,请刷新页面查看');
            }
        }catch(Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
    }
}
