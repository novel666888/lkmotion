<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_charge_rule".
 *
 * @property int $id
 * @property string $city_code 城市编码
 * @property int $service_type_id 服务类型
 * @property int $channel_id 渠道名称
 * @property int $car_level_id 车辆级别
 * @property double $lowest_price 最低消费
 * @property double $base_price 基础价格
 * @property double $base_kilo 基础价格包含公里数
 * @property double $base_minutes 基础价格包含时长数(分钟)
 * @property double $per_kilo_price 超公里单价(每公里单价)
 * @property double $per_minute_price 超时间单价(每分钟单价)
 * @property double $beyond_start_kilo 远途起算公里
 * @property double $beyond_per_kilo_price 远途单价
 * @property string $night_start 夜间时间段开始
 * @property string $night_end 夜间时间段结束
 * @property double $night_per_kilo_price 夜间超公里加收单价
 * @property double $night_per_minute_price 夜间超时间加收单价
 * @property string $effective_time 生效时间
 * @property string $active_status 生效状态 0已失效 1未失效
 * @property int $is_unuse 是否不可用 0可用 1不可用
 * @property string $create_time create_time
 * @property string $update_time update_time
 */
class ChargeRule extends BaseModel
{

    const ACTIVE_STATUS_VALID = 1;//生效状态-为失效
    const ACTIVE_STATUS_INVALID = 0;//生效状态-已失效

    const IS_UNUSE_YES = 1;//是否不可用-可用
    const IS_UNUSE_NO = 0;//是否不可用-不可用

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_charge_rule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_type_id', 'channel_id', 'car_level_id', 'lowest_price', 'base_price', 'base_kilo', 'base_minutes', 'per_kilo_price', 'per_minute_price', 'beyond_start_kilo', 'beyond_per_kilo_price', 'operator_id'], 'required'],
            [['service_type_id', 'channel_id', 'car_level_id', 'active_status', 'is_unuse', 'creator_id', 'operator_id'], 'integer'],
            [['lowest_price', 'base_price', 'base_kilo', 'base_minutes', 'per_kilo_price', 'per_minute_price', 'beyond_start_kilo', 'beyond_per_kilo_price', 'night_per_kilo_price', 'night_per_minute_price'], 'number'],
            [['night_start', 'night_end', 'effective_time', 'create_time', 'update_time'], 'safe'],
            [['city_code'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'city_code' => 'City Code',
            'service_type_id' => 'Service Type ID',
            'channel_id' => 'Channel ID',
            'car_level_id' => 'Car Level ID',
            'lowest_price' => 'Lowest Price',
            'base_price' => 'Base Price',
            'base_kilo' => 'Base Kilo',
            'base_minutes' => 'Base Minutes',
            'per_kilo_price' => 'Per Kilo Price',
            'per_minute_price' => 'Per Minute Price',
            'beyond_start_kilo' => 'Beyond Start Kilo',
            'beyond_per_kilo_price' => 'Beyond Per Kilo Price',
            'night_start' => 'Night Start',
            'night_end' => 'Night End',
            'night_per_kilo_price' => 'Night Per Kilo Price',
            'night_per_minute_price' => 'Night Per Minute Price',
            'cancel_price' => 'Cancel Price',
            'effective_time' => 'Effective Time',
            'active_status' => 'Active Status',
            'is_unuse' => 'Is Unuse',
            'creator_id' => 'Creator Id',
            'operator_id' => 'Operator Id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * get_query --
     * @author JerryZhang
     * @param $params
     * @return \yii\db\ActiveQuery
     * @cache No
     */
    public static function get_query($params)
    {
        $query = self::find();

        if (!empty($params['id'])) {
            $query->andWhere(['id' => $params['id']]);
        }
        if (isset($params['is_unuse']) && $params['is_unuse'] > -1) {
            $query->andWhere(['is_unuse' => $params['is_unuse']]);
        }
        if (!empty($params['city_code'])) {
            $query->andWhere(['city_code' => $params['city_code']]);
        }
        if (!empty($params['service_type_id'])) {
            $query->andWhere(['service_type_id' => $params['service_type_id']]);
        }
        if (!empty($params['channel_id'])) {
            $query->andWhere(['channel_id' => $params['channel_id']]);
        }
        if (!empty($params['car_level_id'])) {
            $query->andWhere(['car_level_id' => $params['car_level_id']]);
        }
        if (isset($params['active_status'])) {
            $query->andWhere(['active_status' => $params['active_status']]);
        }
        if (isset($params['effective_time'])) {
            $query->andWhere(['<=', 'effective_time', $params['effective_time']]);
        }

        $query->orderBy(['effective_time' => SORT_DESC]);

        return $query;
    }

    public static function getCheckData($city_code, $service_type_id, $car_level_id, $channel_id, $effective_time = '', $id = 0)
    {

        $query = self::find();
        $query->select('*');
        $query->andWhere(['is_unuse' => self::IS_UNUSE_NO, 'city_code' => $city_code, 'service_type_id' => $service_type_id, 'channel_id' => $channel_id, 'car_level_id' => $car_level_id]);
        if (!empty($effective_time)) {
            $query->andWhere(['effective_time' => $effective_time]);
        }
        if (!empty($id)) {
            $query->andWhere(['<>', 'id', $id]);
        }

        return $query->asArray()->all();

    }

}
