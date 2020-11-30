<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/11/4
 * Time: 15:04
 */

namespace app\api\controller;


use app\common\controller\Api;

class Mission extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    
}