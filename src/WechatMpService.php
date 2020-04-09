<?php

namespace Codesigner\WechatMp;

class WechatMpService {
    private $wechatMp;

    public function __construct($configName = 'default') {
        $this->wechatMp = new WechatMp(config("wxxcx.$configName"));
    }

    public function get() {
        return $this->wechatMp;
    }
}
