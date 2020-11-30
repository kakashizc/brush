define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'comp/index' + location.search,
                    add_url: 'comp/add',
                    edit_url: 'comp/edit',
                    del_url: 'comp/del',
                    multi_url: 'comp/multi',
                    table: 'comp',
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
                        {field: 'admin_id', title: __('Admin_id')},
                        {field: 'orderbrush_id', title: __('Orderbrush_id')},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'brush_id', title: __('Brush_id')},
                        {field: 'complain_id', title: __('Complain_id')},
                        {field: 'say', title: __('Say')},
                        {field: 'images', title: __('Images'), events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'feed', title: __('Feed')},
                        {field: 'admin.username', title: __('Admin.username')},
                        {field: 'admin.nickname', title: __('Admin.nickname')},
                        {field: 'brush.name', title: __('Brush.name')},
                        {field: 'brush.mobile', title: __('Brush.mobile')},
                        {field: 'orderbrush.id', title: __('Orderbrush.id')},
                        {field: 'orderbrush.order_no', title: __('Orderbrush.order_no')},
                        {field: 'orderbrush.shop_name', title: __('Orderbrush.shop_name')},
                        {field: 'complain.title', title: __('Complain.title')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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