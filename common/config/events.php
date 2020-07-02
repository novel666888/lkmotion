<?php

return [
    // 抢单结果
    'common\events\OrderEvent.grabResult' => [
        ['common\services\EventPush','sendOrderGrapResult'],  //发信息给司机
        ['common\services\EventPush','sendOrderGrapToPassenger'], //发信息给乘客
    ],

    //去接乘客--3
    'common\events\OrderEvent.goPickup' => [
        ['common\services\EventPush','orderStatusChange'],
    ],

    //司机到达上车点--4
    'common\events\OrderEvent.arrived' => [
        ['common\services\EventPush','orderStatusChange'],
        ['common\listeners\DriverMessage','sendArrivedSms'], // 给他人叫车人发送短信提醒
    ],

    //接到乘客--5
    'common\events\OrderEvent.startOrder' => [
        ['common\services\EventPush','orderStatusChange'],
        ['common\services\EventPush','sendReceivePassenger'],//大屏
        ['common\listeners\ShareTrip','sendSms'],
    ],

    //结束行程--6
    'common\events\OrderEvent.endOrder' => [
        ['common\services\EventPush','orderStatusChange'],
        ['common\services\EventPush','sendPassengerGetOff'],//大屏
    ],

    //司机发起收款--7
    'common\events\OrderEvent.driverStartPay' => [
        ['common\services\EventPush','orderStatusChange'],
    ],

    //支付成功--8
    'common\events\OrderEvent.paySuccess' => [
        ['common\services\EventPush','orderStatusChange'],
        ['common\services\EventPush','orderPaySuccess'], //推司机
        ['common\services\EventPush','deviceProbing'], //设备校验
    ],

    // 司机解约
    'common\events\DriverEvent.unsigned' => [
        ['common\listeners\DriverMessage','sendAppMessage'], // app消息
//        ['common\listeners\DriverMessage','sendSmsMessage'], // 短信消息
    ],
    // 司机冻结
    'common\events\DriverEvent.unused' => [
        ['common\listeners\DriverMessage','sendAppMessage'],  // app消息
//        ['common\listeners\DriverMessage','sendSmsMessage'], // 短信消息
    ],

    'common\events\PassengerEvent.register' => [ // 乘客注册
        ['common\listeners\Activity', 'passengerRegister'],  // 注册活动分解
        ['common\listeners\PassengerMessage', 'sendRegCouponSms'], // 注册送优惠券短信
    ],
    'common\events\PassengerEvent.consumption' => [ // 乘客消费
        ['common\listeners\Activity', 'passengerConsumption'],  // 消费活动分解
    ],
    'common\events\PassengerEvent.charge' => [ // 乘客充值
        ['common\listeners\Activity', 'passengerCharge'],  // 活动状态标记
    ],

];