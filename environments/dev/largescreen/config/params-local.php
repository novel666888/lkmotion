<?php
return [
    'LargeScreenWhiteList'=>[
        'home/large-screen-home/jpush-regist',
        'home/large-screen-flight/flight-change',
        'home/large-screen-home/tv-update',
        'home/large-screen-home/app-manage',
    ],
    'LargeScreenSignCheckSwitch' => true,//大屏sign校验 false为关闭校验 true开启校验
    'umetripAppId'                  => 'ume_7cf8df38544445d1845def985290a563',
    'weatherKey'                    => '9fb2066ba4cfbf6f374e09a6b6c6ca0e',
    'api' => [
        'file' => [//java文件服务
            'serverName' =>  'http://192.8.19.103:8089',//'http://192.168.33.239:8089',
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
                'cancelSubscribeFlight' => 'UmeSDK/FlightStatus/CancelSubscribeFlight.do',//取消订阅航路信息
            ]
        ],
        'weather'=>[
            'serverName'=>'http://restapi.amap.com',
            'method'=>[
                'weatherInfo'=>'v3/weather/weatherInfo',
            ]
        ]
    ],
];