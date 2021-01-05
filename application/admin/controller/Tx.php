<?php

namespace app\admin\controller;

use app\admin\model\Brush;
use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Tx extends Backend
{
    
    /**
     * Tx模型对象
     * @var \app\admin\model\Tx
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Tx;
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function pass()
    {
        $id = $this->request->param('ids');
        $res = $this->model->where('id',$id)->setField('status','1');
        if ($res){
            $tx = $this->model->find($id);
            //增加一条刷手财务记录
            $brush = Brush::get($tx['brush_id']);
            //TODO 审核通过后的刷手余额 $tx['money'] ,而不是申请提现时候的刷手余额, 这里有冲突,后期需要改进
            brush_record($brush->id,'4','-'.$tx['money'],$brush->money,$brush->indent_name,$brush->mobile);
            $this->success('已通过');
        }else{
            $this->error('失败');
        }
    }

    public function negative()
    {
        $id = $this->request->param('ids');
        $res = $this->model->where('id',$id)->setField('status','2');
        if ($res){
            //同时返回给商户金额
            $money = $this->model->find($id);
            Db::name('brush')->where('id',$money->brush_id)->setInc('money',$money->money);
            $this->success('已拒绝');
        }else{
            $this->error('失败');
        }
    }

}
