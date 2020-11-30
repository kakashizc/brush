<?php
require_once 'Redis2.php';
$redis2 = new \Redis2();
$res = $redis2->setex('122_55_delay',10, "It is no pay");
//sleep(3);
//$redis2->rename('122_55_delay','122_55_active');

var_dump($res);