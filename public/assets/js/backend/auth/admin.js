define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'auth/admin/index',
                    add_url: 'auth/admin/add',
                    edit_url: 'auth/admin/edit',
                    del_url: 'auth/admin/del',
                    multi_url: 'auth/admin/multi',
                }
            });

            var table = $("#table");

            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function () {
                    if (parseInt($("td:eq(1)", this).text()) == Config.admin.id) {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
                });
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'state', checkbox: true, },
                        {field: 'id', title: 'ID'},
                        {field: 'username', title: __('Username')},
                        {field: 'nickname', title: __('Nickname')},
                        {field: 'groups_text', title: __('Group'), operate:false, formatter: Table.api.formatter.label},
                        // {field: 'email', title: __('Email')},
                        {field: 'status', title: __("Status"), formatter: Table.api.formatter.status},
                        {field: 'money', title: '商家余额'},
                        {field: 'total_order', title: '已放单总量'},
                        {field: 'total_money', title: '已放单总金额'},
                        {field: 'guarantee', title: '保证金'},
                        {field: 'logintime', title: __('Login time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'),
                            buttons:[
                                {
                                    name: 'detail',
                                    text: '扣款',
                                    title: '扣款',
                                    classname: 'btn btn-xs btn-warning test',
                                    icon: 'fa fa-address-book-o',

                                }
                            ],
                            table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                                if(row.id == Config.admin.id){
                                    return '';
                                }
                                return Table.api.formatter.operate.call(this, value, row, index);
                            }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(document).on("click", ".test", function () {
                var options = table.bootstrapTable('getSelections');
                var ids = options[0].ids
                return Layer.alert('请输入扣款金额', {
                    content: Template("logintpl", {}),
                    zIndex: 99,
                    area: ['430px', '180px'],
                    resize: false,
                    title: '确定?',
                    btn: ['确定扣款','取消'],
                    yes: function (index, layero) {
                        Fast.api.ajax({
                            url: 'auth/admin/do_kou',
                            // dataType: 'jsonp',
                            data: {
                                act_bro: $("#inputAccount", layero).val(),
                                id: ids
                            }
                        }, function (data, ret) {
                            Layer.alert(ret.msg);
                            $(".btn-refresh").trigger('click')
                        }, function (data, ret) {
                            Layer.closeAll();
                            Layer.alert(ret.msg);
                            return false;
                        });

                    },
                    btn2: function (index) {
                        layer.close(index);
                    }
                });
            });
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});