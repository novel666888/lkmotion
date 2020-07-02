<?php

namespace common\models;

use yii\base\Model;

class ValidModel extends Model
{
    private $_rules = array();
    public $_attributes = array();
    private $_scenarios = array();

    public function __get($name)
    {
        return isset($this->_attributes[$name]) ? $this->_attributes[$name] : NULL;
    }

    public function __set($name, $value)
    {
        $this->_attributes[$name] = $value;
    }

    public function scenarios()
    {
        $scenarios = Parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = $this->_scenarios;
        return $scenarios;
    }

    public function setDefaultScenarios($scenarios = [])
    {
        $this->_scenarios = $scenarios;
    }

    public function setRules($rules)
    {
        $this->_rules = $rules;
    }

    public function rules()
    {
        return $this->_rules;
    }
}