<?php
return [
    'umetripAppId'                  => 'ume_7cf8df38544445d1845def985290a563',
    'ossFileUrl'                  => 'https://cdn.yesincarapi.com/',
    'shareTripLogoUrl'=>'https://h5.yesincarapi.com/assets/images/passenger/logo_app_yeeping@3x.bd93a1ad.png',
    'shareTripH5Url'=>'https://h5.yesincarapi.com/pages/share_itinerary.html',
    'serviceManPhone' => '17810643063',//接收短信客服手机号码
    'api' => [
        'order' => [//java订单服务
            'serverName' => 'https://order.yesincarapi.com',
            'method' => [
                'forecastFee' => 'order/forecast',
                'callingCar' => 'order/callCar',
                'updateOrder' => 'order/updateOrder', // 订单状态更新
                'grabbing' => '/order/grab', // 抢单
                'reassign' => 'order/reassignment',
                'otherPay' => 'order/otherPrice', // 请求支付
                'updateBatchOrder' => 'order/batchUpdate', //批量修改订单发票状态
            ]
        ],
        'dispatch' => [//java派单服务
            'serverName' => 'https://dispatch.yesincarapi.com',
            'method' => [
                'dispatchOrder' => 'dispatch/dispatchOrder',
                'queryCar' => 'dispatch/vehicleDispatch',
            ]
        ],
        'account' => [//java用户中心
            'serverName' => 'https://account.yesincarapi.com',
            'method' => [
                'createEncrypt' => 'phone/createEncrypt',
                'getPhoneList' => 'phone/getPhoneList',
                'checkOut' => 'auth/checkOut',
                'regist' => 'passenger/regist',
                'driverTokenGen' => '/driver/regist', // 司机token生成
                'checkToken' => 'auth/checkToken', // 验证token有效性
                'driverWorkStatus' => 'driver/changeWorkStatus', // 司机变更工作状态
                'passengerInfo' => 'passenger/passengerInfo',
                'updatePassengerInfo' => 'passenger/updatePassengerInfo',
                'driverStore' => 'driver/driver', // 司机添加/编辑
                'driverUpdate' => 'driver/changeDriver', // 司机编辑
                'driverStatus' => 'driver/updateDriverStatus', // 司机状态
                'driverDetails' => 'driver/driverInfo', // 司机详情
                'carStore' => 'car/car', // 车辆信息添加
                'carUpdate' => 'car/updateCar', // 车辆信息编辑
                'carStatus' => 'car/updateCarInfo', // 车辆状态
                'generateToken' => '/auth/createToken', // 生成token通用
                'decryptPhone'=>'phone/getPhoneByEncryptList',
                'updateDriverInfo' => 'driver/updateDriverAddress', //修改司机信息
                'addressDecryption'=>'driver/addressDecryption', //司机地址解密
                'passengerExt'=>'passenger/ext', //乘客修改额外信息
            ]
        ],
        'message' => [//java消息服务
            'serverName' => 'https://message.yesincarapi.com',
            'method' => [
                'notice' => 'push/notice',//极光推送（通知）
                'jpush' => 'push/message', //极光推送（透传）
                'sendMessage' => 'sms/send',//短信发送（阿里）
                'hxSendMessage' => 'sms/hx_send', //发送短信（华信）
                'insertLoop' => 'loop/message' //插入轮询
            ]
        ],
        'map' => [//java地图服务
            'serverName' => 'https://map.yesincarapi.com',
            'method' => [
                'fenceSearch' => 'fence/search', //查询围栏
                'fenceMeta' => 'fence/meta',    // 新增/编辑围栏
                'fenceSearchByGids' => 'fence/searchByGids',    // 根据gid list 查询围栏数据
                'fenceChangeStatus' => 'fence/changeStatus',    // 更改围栏状态
                'fenceDelete' => 'fence/delete',    // 删除围栏
                'vehicleDispatch' => 'vehicleDispatch',//调度服务
                'addPoints' => 'vehicle', // 轨迹服务
                'batchPoints' => 'vehicles', // 批量上传点,
                'getCity' => 'geo/cityCode', // 根据坐标获取城市码
                'queryPoints'=>'route/points', //查询轨迹点
                'computeDistance'=>'distance',//计算两点间距离
                'inFence' => 'fence/isInFence',//检查点是否在围栏中
                'getPoints' => '/route/points', // 获取时间段内某车辆的轨迹
                'getSid' => '/config/sid', // 获取地图配置SID
            ]
        ],
        'valuation' => [//java计价服务
            'serverName' => 'https://valuation.yesincarapi.com',
            'method' => [
                'settlement' => 'valuation/settlement', // 订单结算
                'fixForecastCost'=>'valuation/forecast/done',//将预估费用写入数据库,
                'currentPrice' => 'valuation/current_price', // 获取订单实时价格
            ]
        ],
        'pay' => [//java支付服务
            'serverName' => 'https://pay.yesincarapi.com',
            'method' => [
                'pay' => 'consume/pay', //订单支付
                'alipay' => 'alipay/pretreatment',//支付宝支付
                'weixinPay' => 'weixinPay/pretreatment',//微信支付
                'orderPay' => 'consume/pay', // 订单支付
                'freeze'=>'consume/freeze',
                'unfreeze'=>'consume/unFreeze',
                'weixinPayResult' => 'weixinPay/payResult',//微信支付结果查询
                'refund'=>'refund/order', //定单退款
                'recharge'=>'recharge/boss', //BOSS充值
            ]
        ],
        'statistics' => [ //java监控统计服务
            'serverName' => 'https://monitor.yesincarapi.com',
            'method' => [
                'userStatistics' => 'user/user_statistics',
                'orderStatistics' => 'order/order_statistics',
                'counponStatistics' => 'discount_coupons/statistics',
                'carStatistics' => 'car/car_amount'
            ]
        ],
        'file' => [//java文件服务
            'serverName' => 'https://file.yesincarapi.com',//'http://192.168.33.239:8089',
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
        'weather' => [
            'serverName' => 'http://restapi.amap.com',
            'method' => [
                'weatherInfo' => 'v3/weather/weatherInfo',
            ]
        ],
        'base' => [//网约车数据上报java地址
            'serverName' => 'http://192.168.68.123:8086',
            'method' => [
                'company' => 'baseInfo/company',
                'companyPay' => 'baseInfo/companyPay',
                'companyService' => 'baseInfo/companyService',
                'companyPermit' => 'baseInfo/companyPermit',
		'cancelOrder'=> 'order/cancel',
                'ratedPassenger' => 'ratedPassenger',
                'ratedPassengerComplaint' => 'ratedPassengerComplaint',
            ]
        ]
    ],
    'env' => 'prod', // 环境
    'phoneWhiteList' => [
        'verifyCode' => '978361',
        'phoneNum' =>['13466785081']
    ]
];
