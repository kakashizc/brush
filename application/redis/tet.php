#! /www/server/php/72/bin/php
<?php

ini_set('default_socket_timeout', -1);
require_once 'Redis2.php';
$redis = new Redis2();

// 解决Redis客户端订阅时候超时情况
$redis->setOption();
/*这里订阅了db 0的所有key的过期事件,并监听
          ↓                         */
$redis->psubscribe(array('__keyevent@0__:expired'), function ($redis, $pattern, $chan, $msg){
    echo $msg;
    $arr = explode('_',$msg);
    print_r($arr);
    if ( sizeof($arr) < 3  ){
        if( $arr[2] != 'delay' || $arr[2] !='active' ){
            echo '不属于订单系统的key';
        }
        echo '键的数量不正确';
    }else{
        //订单到期,回收订单,调用回收订单接口
        if ( $arr[2] == 'delay' ){
            
            try{
                $db = new PDO('mysql:host=localhost;dbname=bill', 'root', 'e2c87cd122d2d53c');
                $res = $db->query("update `fa_order_item` set `brush_id`=0 where id=$arr[0] and brush_id=$arr[1]");
                if ($res){
                    echo '订单已被回收,刷手id'.$arr[1].'-----order_item id = '.$arr[0];
                    
                }else{
                    echo 2;
                }
                $db = null;//关闭数据库连接
            }catch(Exception $e){
                $db = null;//关闭数据库连接
                echo $e->getMessage();
            }
        }
        //刷手主动提交订单,处理订单逻辑
        //这里不用处理了,已经删除对应的key了,其他业务逻辑在接口中处理了
    }
});