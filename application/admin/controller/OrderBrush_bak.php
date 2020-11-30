<?php

namespace app\admin\controller;

use app\common\controller\Backend;

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
                $where1 = "shop_id=$admin_id";
            }
            $total = $this->model
                    ->with(['admin','brush','plat'])
                    ->where($where)
                    ->where($where1)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['admin','brush','plat'])
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
				$row->getRelation('brush')->visible(['name','mobile']);
				$row->getRelation('plat')->visible(['name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
