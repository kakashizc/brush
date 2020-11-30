define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'brush_plat/index' + location.search,
                    add_url: 'brush_plat/add',
                    edit_url: 'brush_plat/edit',
                    del_url: 'brush_plat/del',
                    multi_url: 'brush_plat/multi',
                    table: 'brush_plat',
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
                        {field: 'account', title: __('Account')},
                        {field: 'recive', title: __('Recive')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'gender', title: __('Gender'), searchList: {"1":__('Gender 1'),"2":__('Gender 2')}, formatter: Table.api.formatter.normal},
                        {field: 'recive_city', title: __('Recive_city')},
                        {field: 'recive_address', title: __('Recive_address')},
                        {field: 'my_image', title: __('My_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'myinfo_image', title: __('Myinfo_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        // {field: 'plat_id', title: __('Plat_id')},
                        {field: 'brush_id', title: __('Brush_id')},
                        {field: 'plat.name', title: __('Plat.name')},
                        {field: 'brush.name', title: __('Brush.name')},
                        {field: 'brush.mobile', title: __('Brush.mobile')},
                        {field: 'brush.indent_name', title: __('Brush.indent_name')},
                        {field: 'brush.indent_no', title: __('Brush.indent_no')},
                        {field: 'brush.front', title: __('Brush.front')},
                        {field: 'brush.back', title: __('Brush.back')},
                        {field: 'brush.keep', title: __('Brush.keep')},
                        {field: 'brush.ali', title: __('Brush.ali')},
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