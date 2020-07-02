<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-24
 * Time: 下午2:26
 */

namespace common\events;

use yii\base\Component;

class DriverEvent extends Component
{
    const EVENT_UNSIGNED = 'unsigned'; // 解约
    const EVENT_UNUSED = 'unused';  // 冻结

    public function unsigned($driverId)
    {
        $event = $this->packData($driverId,'unsigned');
        $this->trigger(self::EVENT_UNSIGNED, $event);
    }

    public function unused($driverId)
    {
        $event = $this->packData($driverId,'unused');
        $this->trigger(self::EVENT_UNUSED, $event);
    }

    private function packData($driverId,$evt)
    {
        $dataPack = new DataPack();
        $dataPack->identity = $driverId;
        $dataPack->extInfo = $evt;

        return $dataPack;
    }
}