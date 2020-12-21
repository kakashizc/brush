<?php
/**
 * Created by 网吧大神
 * User: 网吧大神
 * Date: 2020/12/21
 * Time: 11:54
 */

namespace app\common\controller;


class sendMsg
{
    /*
     * @param @msg 发送的内容
     * */
    public function send($msg)
    {
        // 指明给谁推送，为空表示向所有在线用户推送
        $to_uid = "";
        // 推送的url地址，使用自己的服务器地址
        $push_api_url = "https://sd.hbwuganfu.com:2121/";
        $post_data = array(
            "type" => "publish",
            "content" => "$msg",
            "to" => $to_uid,
        );
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        //var_export($return);
    }
}