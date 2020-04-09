<?php

namespace Codesigner\WechatMp;

use \Illuminate\Support\Facades\Facade;

class WechatMpFacade extends Facade {

    protected static function getFacadeAccessor() {
        return 'wechatMp';
    }
}