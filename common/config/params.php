<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'cs@yesincar.com',
    'inBoxEmail' => 'driver_inbox@lkmotion.com',
    'umetripAppId' => 'ume_7cf8df38544445d1845def985290a563',
    'weatherKey' => '9fb2066ba4cfbf6f374e09a6b6c6ca0e',
    'pdfBin' => '@vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64',
    'user.passwordResetTokenExpire' => 3600,
    'serviceManFixedPhone' => '0571-56030631',//客服坐机电话
    'driverServicePhone' => '0571-8690-809',//司机管理电话
    'doReportData' => false, // 是否上报数据
    'cancelOrderReason' => [
        1 => '重复订单',
        2 => '出行计划有变',
        3 => '司机态度差',
        4 => '司机要求取消',
        5 => '其他原因'
    ],
    //乘客端 - 意见反馈 - 类型
    'passengerFeedback' => [
        1   =>  [
            'name' => '乘客端问题',
            'data' => [1=>'计价规则',2=>'定位/地图/导航',3=>'闪退/卡顿/界面问题',4=>'其他问题'],
        ],
        2   =>  [
            'name' => '智能大屏问题',
            'data' => [1=>'操作/体验问题',2=>'闪退/卡顿/死机问题',3=>'其他问题'],
        ],
        3   =>  [
            'name' => '司机服务问题',
            'data' => [1=>'服务态度不好',2=>'故意绕路',3=>'提前结束订单',4=>'其他问题'],
        ],
    ],
    //乘客端 - 乘客评价司机 - 标签
    'PassengerCommentDriver' => [
        1=>'车内整洁',
        2=>'活地图',
        3=>'驾驶平稳',
        4=>'态度好服务好',
        5=>'提拿行李',
        6=>'主动开门',
    ],
    //司机端 - 意见反馈 - 类型
    'driverFeedback' => [
        1=>'听单、抢单',
        2=>'接单服务中地图问题',
        3=>'派单、改派',
        4=>'预估公里数、预估价格',
        5=>'联系乘客、等待乘客',
        6=>'升级、闪退、页面问题',
        7=>'其他',
    ],
    //司机端 - 司机评价乘客 - 星级对应标签
    'driverCommentPassenger' => [
        1 => [
            1=>'车内就餐',
            2=>'迟到上车',
            3=>'衣着不得体',
            4=>'过于吵闹',
            5=>'言语粗鲁',
            6=>'辱骂司机',
        ],
        2 => [
            1=>'车内就餐',
            2=>'迟到上车',
            3=>'衣着不得体',
            4=>'过于吵闹',
            5=>'言语粗鲁',
            6=>'辱骂司机',
        ],
        3 => [
            1=>'车内就餐',
            2=>'迟到上车',
            3=>'衣着不得体',
            4=>'过于吵闹',
            5=>'言语粗鲁',
            6=>'辱骂司机',
        ],
        4 => [
            1=>'安静看大屏',
            2=>'文明礼貌',
            3=>'爱聊天',
            4=>'幽默风趣',
            5=>'准时上车',
        ],
        5 => [
            1=>'安静看大屏',
            2=>'文明礼貌',
            3=>'爱聊天',
            4=>'幽默风趣',
            5=>'准时上车',
        ]
    ],
    'client' => [
        'passenger' => [
            'index' => 1,
            'desc' => '乘客端'
        ],
        'driver' => [
            'index' => 2,
            'desc' => '司机端'
        ],
        'car_screen' => [
            'index' => 3,
            'desc' => '车机端'
        ],
        'large_screen' => [
            'index' => 4,
            'desc' => '大屏端'
        ]
    ],
    'events' => require 'events.php',// 事件订阅配置
    'whiteList' => [
        'activities/invite/register', // h5邀请注册
        'auth/auth/login',//boss端登陆
        'auth/auth/send-sms-code',//boss端登陆发送验证码
        'ucenter/auth/login',//司机端登陆
        'ucenter/auth/send-sms',//司机端发送登录验证码
        'basic/app-version/latest',//司机端检测更新
        'basic/list/jpush-alias', // 获取极光别名
        'order/work/notify',//司机端消息轮询
        'service/user/login',//乘客端登陆注册
        'service/auth/send-sms',//乘客端发送登录验证码
        'service/auth/check-version',//乘客端版本升级
        'service/info/timestamp',//乘客端获取服务器时间
        'service/ad/get',//乘客端获取广告
        'service/info/city-list',//获取城市列表
        'order/place-order/get-trip-detail',//乘客端行程分享页面
        'order/place-order/refresh-location',//乘客端行程分享轨迹刷新
        'coupon/coupon-task/run',//临时
        'order/init/get-pricing-rule',
    ],
];
