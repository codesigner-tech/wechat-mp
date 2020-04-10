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
    private $code2session_url = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code';
    private $access_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
    private $wxa_code_url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=%s';
    private $fileName;

    /**
     * constructor.
     * @param $config 小程序配置
     */
    function __construct($config = [])
    {
        $this->appId = $config ? $config['appid'] : config('wechatMp.default.appid', '');
        $this->secret = $config ? $config['secret'] : config('wechatMp.default.secret', '');
        $this->fileName = sprintf('access_tokens/%s.json', $this->appId);
        if(!file_exists('access_tokens')) {
            mkdir('access_tokens');
        }
        if(!file_exists($this->fileName)) {
          file_put_contents($this->fileName, '{}');
        }
    }

    /**
     * 根据 code 获取 openid、session_key 等相关信息
     * @param $code 小程序login获得的code
     * @return mixed
     */
    public function getLoginInfo($code){
        $code2session_url = sprintf($this->code2session_url, $this->appId, $this->secret,$code);
        $userInfo = $this->httpRequestJson($code2session_url);
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
     * 获取 access token
     */
    public function getAccessToken($forceRefresh = false) {
      $tokenInfo = json_decode(file_get_contents($this->fileName));
      if ($forceRefresh || empty($tokenInfo->access_token) || empty($tokenInfo->expire_time) || $tokenInfo->expire_time < time()) {
        $newToken = $this->getAccessTokenFromServer();
        $tokenInfo->access_token = $newToken['access_token'];
        $tokenInfo->expire_time = time() + $newToken['expires_in'] - 60 * 5;
        file_put_contents($this->fileName, json_encode($tokenInfo));
      }
      return $tokenInfo->access_token;
    }

    /**
     * 获取小程序二维码
     */
    public function getWxaCode($scene, $page, $width, $autoColor = false, $lineColor = null, $isHyaline = false){
      $accessToken = $this->getAccessToken();
      $url = sprintf($this->wxa_code_url, $accessToken);
      $data = [
        'scene' => $scene,
        'page' => $page,
        'width' => $width,
        'auto_color' => $autoColor,
        'line_color' => $lineColor,
        'is_hyaline' => $isHyaline,
      ];
      $result = $this->httpRequest($url, json_encode($data));
      $data = json_decode($result);
      if(isset($data) && isset($data->errcode)) {
        throw new Exception($data->errmsg, $data->errcode);
      } else {
        return $result;
      }
    }

    /**
     * 从微信服务器获取 access token
     */
    private function getAccessTokenFromServer(){
      $access_token_url = sprintf($this->access_token_url, $this->appId, $this->secret);
      $result = $this->httpRequestJson($access_token_url);
      if(empty($result->errcode)) {
        return $result;
      } else {
        throw new Exception($result->errmsg, $result->errcode);
      }
    }


    /**
     * curl封装
     */
    private function httpRequestJson($url, $data = null)
    {
        $data = $this->httpRequest($url, $data);
        return json_decode($data, JSON_UNESCAPED_UNICODE);
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
        return $output;
    }
}
