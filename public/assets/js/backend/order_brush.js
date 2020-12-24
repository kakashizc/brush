define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order_brush/index' + location.search,
                    multi_url: 'order_brush/multi_edit',
                    table: 'order_brush',
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
                        {field: 'shop_name', title: __('Shop_name')},
                        {field: 'act_account', title: '刷手平台账号'},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5'),}, formatter: Table.api.formatter.status},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'broker', title: __('Broker'), operate:'BETWEEN'},
                        {field: 'stime', title: __('Stime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'ptime', title: __('Ptime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'confirmtime', title: __('Confirmtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'gettime', title: __('Gettime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'donetime', title: __('Donetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'back', title: __('Back'), searchList: {"1":__('Back 1'),"2":__('Back 2'),"3":__('Back 3'),"4":__('Back 4')}, formatter: Table.api.formatter.normal},
                        {field: 'images', title: __('Images'), events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'else', title: '其他核对问题', operate:'BETWEEN'},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'admin.username', title: __('Admin.username')},
                        {field: 'admin.nickname', title: __('Admin.nickname')},
                        {field: 'brush.name', title: __('Brush.name')},
                        {field: 'brush.mobile', title: __('Brush.mobile')},
                        {field: 'plat.name', title: __('Plat.name')},
                        {field: 'plat.image', title: __('Plat.image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'orderb.goods_ame', title: __('Order.goods_ame')},
                        {field: 'orderb.goods_price', title: __('Order.goods_price'), operate:'BETWEEN'},
                        {field: 'orderb.goods_repPrice', title: __('Order.goods_repprice'), operate:'BETWEEN'},
                        {field: 'orderb.goods_sku', title: __('Order.goods_sku')},
                        {field: 'orderb.goods_link', title: __('Order.goods_link')},
                        {field: 'orderb.goods_num', title: __('Order.goods_num')},
                        {field: 'orderb.goods_image', title: __('Order.goods_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'order.status', title: __('Order.status'), formatter: Table.api.formatter.status},
                        //{field: 'orderb.act_bro', title: __('Order.broker'), operate:'BETWEEN'},
                        {field: 'operate', title: __('Operate'),
                            buttons:[
                                {
                                    name: 'detail',
                                    text: '发货',
                                    title: '发货',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'order_brush/send',
                                    confirm:'确定发货',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if ( row.type == '1' && row.status== '1'){// type = 1 垫付任务
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '返本金',
                                    title: '返本金',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'order_brush/feed_base',
                                    confirm:'返本金?',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.type == '2'){ //如果订单类型是 浏览订单,说明没有下本金不需要返本金
                                            return false;
                                        }
                                        if ( row.back == '1' || row.back == '3'){// 如果 本佣未返 或者 本未返佣已返
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }else if(row.back == '2' || row.back == '4'){
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '返佣金',
                                    title: '返佣金',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'order_brush/feed_bro',
                                    confirm:'返佣金?',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if ( row.back == '1' || row.back == '2' ){// 如果 本佣未返 或者 本返佣未返
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