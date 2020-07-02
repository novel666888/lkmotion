<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-24
 * Time: 下午5:03
 */

namespace common\jobs;

use yii\base\Component;

class TestJos extends Component
{
    // 测试用例
    public function test($event)
    {
        var_dump($event);
    }

}