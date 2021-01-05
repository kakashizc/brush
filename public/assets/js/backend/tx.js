define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'tx/index' + location.search,
                    
                    table: 'tx',
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
                        {field: 'brush_id', title: __('Brush_id')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'checktime', title: __('Checktime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'date', title: __('Date')},
                        {field: 'bank_name', title: __('Bank_name')},
                        {field: 'bank_no', title: __('Bank_no')},
                        {field: 'indent_name', title: __('Indent_name')},
                        {field: 'indent_no', title: __('Indent_no')},
                        {field: 'mobile', title: '提现人手机号'},
                        {field: 'ali', title: '支付宝收款码'},
                        {field: 'operate', title: __('Operate'), 
                        buttons:[
                                {
                                    name: 'detail',
                                    text: '通过',
                                    title: '审核通过',
                                    classname: 'btn btn-xs btn-primary btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'tx/pass',
                                    confirm:'确认通过?',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status == '0'){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '拒绝',
                                    title: '审核拒绝通过',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'tx/negative',
                                    confirm:'确认拒绝通过?',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status == '0'){
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