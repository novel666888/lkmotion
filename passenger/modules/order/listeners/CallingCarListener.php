<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/9/4
 * Time: 21:33
 */
namespace passenger\modules\order\listeners;

use passenger\models\PassengerInfo;
use yii\base\Component;
use yii\base\Event;

class CallingCarListener extends Component
{
    /**
     * @param Event $event
     * @return bool
     */
    public static  function  updateLastCallingCarTime(Event $event)
    {
        $eventData= $event->data;
        \Yii::info($eventData);
        $passengerTable = PassengerInfo::findOne($eventData['userId']);
        if($passengerTable){
            $passengerTable->last_order_time = date('Y-m-d H:i:s');
            return $passengerTable->save();
        }
    }
}
