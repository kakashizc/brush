define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'brush/index' + location.search,
                    add_url: 'brush/add',
                    edit_url: 'brush/edit',
                    del_url: 'brush/del',
                    multi_url: 'brush/multi',
                    table: 'brush',
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
                        {field: 'name', title: __('Name')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'password', title: __('Password')},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'age', title: __('Age')},
                        {field: 'gender', title: __('Gender'), searchList: {"1":__('Gender 1'),"2":__('Gender 2')}, formatter: Table.api.formatter.normal},
                        {field: 'code', title: __('Code')},
                        {field: 'pid', title: __('Pid')},
                        {field: 'score', title: __('Score')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'onum', title: __('Onum')},
                        {field: 'qqwx', title: __('Qqwx')},
                        {field: 'indent_name', title: __('Indent_name')},
                        {field: 'indent_no', title: __('Indent_no')},
                        {field: 'front', title: __('Front')},
                        {field: 'back', title: __('Back')},
                        {field: 'keep', title: __('Keep')},
                        {field: 'ali', title: __('Ali')},
                        {field: 'status', title: __('Status'), searchList: {"0":"未提交实名认证","1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
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