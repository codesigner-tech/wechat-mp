<?php

namespace Codesigner\WechatMp;

include_once "wechat/wxBizDataCrypt.php";

use \WXBizDataCrypt;
use \ErrorCode;
use Exception;

class WechatMp {

    /**
     * @var string
     */
    private $appId;
    private $secret;
    private $code2session_url;

    /**
     * constructor.
     * @param $config 小程序配置
     */
    function __construct($config = [])
    {
        $this->appId = $config ? $config['appid'] : config('wechatMp.default.appid', '');
        $this->secret = $config ? $config['secret'] : config('wechatMp.default.secret', '');
        $this->code2session_url = config('wechatMp.code2session_url', '');
    }

    /**
     * 根据 code 获取 openid、session_key 等相关信息
     * @param $code 小程序login获得的code
     * @return mixed
     */
    public function getLoginInfo($code){
        $code2session_url = sprintf($this->code2session_url,$this->appId,$this->secret,$code);
        $userInfo = $this->httpRequest($code2session_url);
        if(!isset($userInfo['session_key'])) {
            throw new Exception('获取 session_key 失败', ErrorCode::$FetchSessionKeyError);
        }
        return $userInfo;
    }

    /**
     * 解密用户信息
     * @param $encryptedData
     * @param $iv
     * @return Object
     * @throws \Exception
     */
    public function getUserInfo($encryptedData, $iv, $sessionKey){
        $pc = new WXBizDataCrypt($this->appId, $sessionKey);
        $decodeData = "";
        $errCode = $pc->decryptData($encryptedData, $iv, $decodeData);
        if ($errCode != 0 ) {
            throw new Exception('encryptedData 解密失败', $errCode);
        }
        return json_decode($decodeData);
    }


    /**
     * curl封装
     */
    private function httpRequest($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if($output === FALSE ){
            return false;
        }
        curl_close($curl);
        return json_decode($output, JSON_UNESCAPED_UNICODE);
    }
}
