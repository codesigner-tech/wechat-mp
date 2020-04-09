<?php

return [
    'default' => [
        'appid' => 'your AppID',
        'secret' => 'your AppSecret',
    ],

    'mp1' => [
        'appid' => 'your another AppSecret',
        'secret' => 'your another AppSecret',
    ],
    
    'code2session_url' => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",
];