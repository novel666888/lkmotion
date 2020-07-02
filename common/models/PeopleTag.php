<?php

namespace common\models;

/**
 * This is the model class for table "{{%people_tag}}".
 *
 * @property int $id
 * @property string $tag_no 标签编号
 * @property string $tag_name 人群标签名称
 * @property int $tag_type 目标类别 (1,passenger, 2,driver)
 * @property string $tag_conditions 筛选条件
 * @property int $tag_number 标签人数(约数)
 * @property int $link_number 引用次数
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property int $operator_id 更新人id
 */
class PeopleTag extends BaseModel
{
    public $tagTypes = [
        '1' => '用户',
        '2' => '司机',
    ];

    public $conditionTags = [
        '1' => ['regTime', 'totalAmount', 'sex'],
        '2' => ['carNo', 'orderNum', 'totalAmount', 'sex'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%people_tag}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tag_type', 'tag_number', 'link_number', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['tag_no'], 'string', 'max' => 15],
            [['tag_name'], 'string', 'max' => 60],
            [['tag_conditions'], 'string', 'max' => 2000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag_no' => 'Tag No',
            'tag_name' => 'Tag Name',
            'tag_type' => 'Tag Type',
            'tag_conditions' => 'Tag Conditions',
            'tag_number' => 'Tag Number',
            'link_number' => 'Link Number',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator ID',
        ];
    }

    /**
     * 详情
     *
     * @param int $id
     * @return array
     */
    public static function getPeopleTagDetail($id)
    {
        $query = self::find();
        $peopleTagDetail = $query->where(['id' => $id])->asArray()->one();
        return $peopleTagDetail;

    }

    /**
     * @param $id
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     */
    public function getTagPhonesByTagId($id)
    {
        $tag = self::getPeopleTagDetail($id);
        if (!$tag) {
            return [];
        }
        // 查找乘客手机号
        if ($tag['tag_type'] == 1) {
            return $this->getPassengerPhones($tag['tag_conditions']);
        }
        // 查找司机手机号
        if ($tag['tag_type'] == 2) {
            return $this->getDriverPhones($tag['tag_conditions']);
        }
        return [];
    }

    /**
     * @param $conditions
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     */
    private function getPassengerPhones($conditions)
    {
        $query = PassengerInfo::find();
        $conditions = json_decode($conditions);
        if (isset($conditions->regStart)) {
            $query->andWhere(['>=', 'register_time', date('Y-m-d H:i:s', strtotime($conditions->regStart))]);
        }
        if (isset($conditions->regEnd)) {
            $query->andWhere(['<', 'register_time', date('Y-m-d H:i:s', strtotime($conditions->regEnd) + 86400)]);
        }
        $userIds = $query->select('id')->asArray()->all();
        $userIds = array_column($userIds, 'id');
        $listArray = new ListArray();
        $phoneMap = $listArray->getPassengerPhoneNumberByIds($userIds);

        return $phoneMap;
    }

    /**
     * @param $conditions
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     */
    private function getDriverPhones($conditions)
    {
        $query = DriverInfo::find();
        $conditions = json_decode($conditions);
        if (isset($conditions->plates)) {
            $resource = CarInfo::find()->where(['plate_number' => $conditions->plates])->select('id')->asArray()->all();
            $carIds = array_column($resource, 'id');
            $query->where(['car_id' => $carIds]);
        }
        $userIds = $query->select('id')->all();
        $userIds = array_column($userIds, 'id');
        $listArray = new ListArray();
        $phoneMap = $listArray->getDriverPhoneNumberByIds($userIds);

        return $phoneMap;
    }

}
