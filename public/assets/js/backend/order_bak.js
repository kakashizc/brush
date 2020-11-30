define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'order_no', title: __('Order_no')},
                        {field: 'shop_id', title: __('Shop_id')},
                        {field: 'goods_ame', title: __('Goods_ame')},
                        {field: 'keywords', title: '关键字'},
                        {field: 'goods_price', title: __('Goods_price'), operate:'BETWEEN'},
                        {field: 'goods_repPrice', title: __('Goods_repprice'), operate:'BETWEEN'},
                        {field: 'goods_sku', title: __('Goods_sku')},
                        {field: 'goods_link', title: __('Goods_link')},
                        {field: 'goods_num', title: __('Goods_num')},
                        {field: 'goods_image', title: __('Goods_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'broker', title: __('Broker'), operate:'BETWEEN'},
                        {field: 'brush_str', title: __('Brush_str')},
                        {field: 'order_num', title: __('Order_num')},
                        {field: 'plat_id', title: __('Plat_id')},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'base_type', title: __('Base_type'), searchList: {"1":__('Base_type 1'),"2":__('Base_type 2'),"3":__('Base_type 3')}, formatter: Table.api.formatter.normal},
                        {field: 'bro_type', title: __('Bro_type'), searchList: {"1":__('Bro_type 1'),"2":__('Bro_type 2'),"3":__('Bro_type 3')}, formatter: Table.api.formatter.normal},
                        {field: 'json_id', title: __('Json_id')},
                        {field: 'admin.username', title: __('Admin.username')},
                        {field: 'admin.nickname', title: __('Admin.nickname')},
                        {field: 'plat.name', title: __('Plat.name')},
                        {field: 'plat.brok', title: __('Plat.brok'), operate:'BETWEEN'},
                        {field: 'json.name', title: __('Json.name')},
                        {field: 'operate', title: __('Operate'),
                            buttons:[
                                {
                                    name: 'detail',
                                    text: '提交任务',
                                    title: '提交审核任务',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'order/examine',
                                    confirm:'管理员审核通过后发布成功',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        //Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                        //如果需要阻止成功提示，则必须使用return false;
                                        return false;
                                    },
                                    error: function (data, ret) {

                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status == '0' && row.isadmin != 1){
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '确认发布',
                                    title: '发布任务',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'order/publish',
                                    confirm:'确认发布此刷单任务?',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.isadmin == 1 && row.status == '1'){
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                            ],
                            table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});