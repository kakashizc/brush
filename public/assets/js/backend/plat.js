define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            var a =  JSON.parse(window.localStorage.getItem('lastlogin'));
            if (a.id == 1){
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'plat/index' + location.search,
                    add_url: 'plat/add',
                    edit_url: 'plat/edit',
                    del_url: 'plat/del',
                    multi_url: 'plat/multi',
                    table: 'plat',
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
                        {field: 'brok', title: __('Brok'), operate:'BETWEEN'},
                        {field: 'image', title: __('Image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

                // 为表格绑定事件
                Table.api.bindevent(table);
            }
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