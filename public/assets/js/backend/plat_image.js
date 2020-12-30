define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'plat_image/index' + location.search,
                    add_url: 'plat_image/add',
                    edit_url: 'plat_image/edit',
                    del_url: 'plat_image/del',
                    multi_url: 'plat_image/multi',
                    table: 'plat_image',
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
                        {field: 'my_image', title: __('My_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'zl_image', title: __('Zl_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'tq_image', title: __('Tq_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'jx_image', title: __('Jx_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
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