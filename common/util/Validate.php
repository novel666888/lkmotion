<?php
namespace common\util;

use yii\base\DynamicModel;

class Validate extends DynamicModel
{
    public function __get($name)
    {
        try {
            $value = parent::__get($name);

        } catch (\yii\base\UnknownPropertyException $e) {
            $value = null;
        }

        return $value;
    }

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (\yii\base\UnknownPropertyException $e) {
            $this->$name = $value;
        }

        return ;
    }

    public function addRules($rules)
    {
        foreach ($rules as $_k => $_v) {
            $this->addRule($_v[0], $_v[1], array_slice($_v, 2));
        }
    }

    public function getFirstError($attribute=null)
    {
        return current(parent::getFirstErrors());
    }
}