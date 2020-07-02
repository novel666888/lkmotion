<?php
return [
    'api' => [
        'order' => [//java订单服务
            'serverName' => 'http://192.8.19.103:8083',
            'method' => [
                'forecastFee' => 'order/forecast',
                'callingCar' => 'order/callCar',
                'updateOrder' => 'order/updateOrder', // 订单状态更新
                'grabbing' => '/order/grab', // 抢单
                'reassign' => 'order/reassignment',
            ]
        ],
        'dispatch' => [//java派单服务
            'serverName' => 'http://192.8.19.103:8082',
            'method' => [
                'dispatchOrder' => 'dispatch/dispatchOrder',
            ]
        ],
        'account' => [//java用户中心
            'serverName' => 'http://192.8.19.121:8081',
            'method' => [
                'createEncrypt' => 'phone/createEncrypt',
                'getPhoneList' => 'phone/getPhoneList',
                'checkOut' => 'auth/checkOut',
                'regist' => 'passenger/regist',
                'driverWorkStatus' => 'driver/changeWorkStatus', // 司机变更工作状态
            ]
        ],
        'message' => [//java消息服务
            'serverName' => 'http://192.168.33.239:8085',
            'method' => [
                'jpush' => 'push/message', //极光推送
                'sendMessage' => 'sms/send'
            ]
        ],
        'map' => [//java地图服务
            'serverName' => 'http://192.168.33.239:8084',
            'method' => [
                'fenceSearch' => 'fence/search', //查询围栏
                'vehicleDispatch' => 'vehicleDispatch',//调度服务
                'addPoints' => 'vehicle', // 轨迹服务
            ]
        ],
        'valuation' => [//java计价服务
            'serverName' => 'http://192.168.33.239:8088',
            'method' => [

            ]
        ],
        'pay' => [//java支付服务
            'serverName' => 'http://192.168.33.239:8087',
            'method' => [
                'pay' => 'consume/pay', //订单支付
                'alipay' => 'alipay/pretreatment',//支付宝支付
                'weixinPay' => 'weixinPay/pretreatment',//微信支付
            ]
        ],
        'file' => [//java文件服务
            'serverName' => 'http://192.8.19.103:8089',//'http://192.168.33.239:8089',
            'method' => [
                'securityToken' => 'sts/authorization',
            ]
        ],
        'flight' => [//航旅
            'serverName' => 'http://comp.umetrip.com',
            'method' => [
                'flightToken' => 'UmeSDK/Token/getToken.do',//获取航旅token
                'getFlightInfo' => 'UmeSDK/FlightStatus/GetFlightStatusByFlightNo.do',//按照航班号航班日期搜索航旅信息
                'subscribeFlight' => 'UmeSDK/FlightStatus/SubcribeFlight.do',//订阅航路信息
            ]
        ],
        'weather' => [
            'serverName' => 'http://restapi.amap.com',
            'method' => [
                'weatherInfo' => 'v3/weather/weatherInfo',
            ]
        ]
    ],
];
