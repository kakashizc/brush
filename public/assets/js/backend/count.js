define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Template, Echarts) {

    var Controller = {
        index: function () {

            console.log(Echarts.version);
            let datas;
            Fast.api.ajax({
                async: false,
                url:'count/getData',
            }, function (data) { //success
                datas = data
                return false;
            });
            console.log(datas)
            var barChart = Echarts.init(document.getElementById('bar-chart'), 'walden');
            option = {
                legend: {},
                tooltip: {},
                dataset: {
                    source: datas
                },
                xAxis: {type: 'category'},
                yAxis: {},
                series: [
                    {type: 'bar'},
                    {type: 'bar'},
                    {type: 'bar'},
                    {type: 'bar'},
                ]
            };
            // 使用刚指定的配置项和数据显示图表。
            barChart.setOption(option);
        }
    };
    return Controller;
});