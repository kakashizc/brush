<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/11/11
 * Time: 17:10
 */

namespace app\admin\controller;


use app\common\controller\Backend;

class Finance extends Backend
{
    /**
     * Recharge模型对象
     * @var \app\admin\model\Recharge
     */
    protected $model = null;
    protected $noNeedRight = ['index'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');
    }
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
            //如果不是总管理员,那么只获取自己商户的订单
            $where1 = "";
            $admin_id = $this->auth->id;
            $group_id = $this->auth->getRoleid($admin_id)??'';//获取当前角色的管理组id
            if ($group_id != 1){
                $where1 = "id=$admin_id";
            }
            $total = $this->model
                ->where($where)
                ->where($where1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($where1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}