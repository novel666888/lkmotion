<?php

namespace common\models;

use Yii;
use common\util\Common;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
use common\models\CarInfo;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tbl_car_insurance".
 *
 * @property string $id
 * @property string $company_id 公司标识
 * @property string $plate_number 车辆号牌
 * @property string $insurance_company 保险公司名称
 * @property string $insurance_number 保险号
 * @property string $insurance_type 保险类型
 * @property double $insurance_count 保险金额，单位：元
 * @property string $insurance_eff 保险生效时间
 * @property string $insurance_exp 保险到期时间
 * @property string $insurance_photo 保单扫描照片
 * @property string $other_photo 其他照片
 * @property string $operator_id 操作人id
 * @property string $create_time
 * @property string $update_time
 */
class CarInsurance extends \common\models\BaseModel
{
    use ModelTrait;
    use LogicTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_car_insurance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['insurance_count', 'insurance_eff', 'insurance_exp', 'operator_id'], 'required'],
            [['insurance_count'], 'number'],
            [['insurance_eff', 'insurance_exp', 'create_time', 'update_time'], 'safe'],
            [['operator_id'], 'integer'],
            [['company_id', 'insurance_type'], 'string', 'max' => 32],
            [['plate_number'], 'string', 'max' => 16],
            [['insurance_company', 'insurance_number'], 'string', 'max' => 64],
            [['insurance_photo', 'other_photo', 'other_photo_2'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_id' => 'Company ID',
            'plate_number' => 'Plate Number',
            'insurance_company' => 'Insurance Company',
            'insurance_number' => 'Insurance Number',
            'insurance_type' => 'Insurance Type',
            'insurance_count' => 'Insurance Count',
            'insurance_eff' => 'Insurance Eff',
            'insurance_exp' => 'Insurance Exp',
            'insurance_photo' => 'Insurance Photo',
            'other_photo' => 'Other Photo',
            'other_photo_2' => 'Other Photo 2',
            'operator_id' => 'Operator ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 获取车辆保险详情
     * @param int $id 车辆保险主键id
     */
    public static function getDetail($id, $field=['*']){
        if(empty($id)){
            return [];
        }
        $one = self::find()->select($field)->where(['id'=>$id])->asArray()->one();
        if(!empty($one)){
            /**
            if(!empty($one['insurance_photo'])){
                $arr = explode(",", $one['insurance_photo']);
                //foreach($arr as $kk => $vv){
                //    $arr[$kk] = \Yii::$app->params['ossFileUrl'].$vv;
                //}
                $one['insurance_photo'] = $arr;
            }else{
                $one['insurance_photo'] = [];
            }
            if(!empty($one['other_photo'])){
                $arr = explode(",", $one['other_photo']);
                //foreach($arr as $kk => $vv){
                //    $arr[$kk] = \Yii::$app->params['ossFileUrl'].$vv;
                //}
                $one['other_photo'] = $arr;
            }else{
                $one['other_photo'] = [];
            }
            if(!empty($one['other_photo_2'])){
                $arr = explode(",", $one['other_photo_2']);
                //foreach($arr as $kk => $vv){
                //    $arr[$kk] = \Yii::$app->params['ossFileUrl'].$vv;
                //}
                $one['other_photo_2'] = $arr;
            }else{
                $one['other_photo_2'] = [];
            }*/
            $one['oss_file_url'] = \Yii::$app->params['ossFileUrl'] ?? "";
            return $one;
        }
        return [];
    }

    /**
     * 获取车辆保险列表
     */
    public static function getList($condition){
        $model = self::find();
        if(!empty($condition['plateNumber'])){
            $model->andFilterWhere(['plate_number'=>$condition['plateNumber']]);
        }
        if ($condition['startTime'] !== '') {
            $model->andFilterWhere(['>=', 'create_time', date('Y-m-d', strtotime($condition['startTime']))]);
        }
        if ($condition['endTime'] !== '') {
            $model->andFilterWhere(['<', 'create_time', date('Y-m-d', strtotime($condition['endTime']) + 86400)]);
        }
        $data = self::getPagingData($model, ['type'=>'desc','field'=>'create_time']);
        if(!empty($data['data']['list'])){
            $plate_number = ArrayHelper::getColumn($data['data']['list'], 'plate_number');
            $CarInfo = CarInfo::find()->select(['id', 'plate_number'])->filterWhere(['plate_number'=>$plate_number])->indexBy('plate_number')->asArray()->all();
            foreach($data['data']['list'] as $k => $v){
                if(isset($CarInfo[$v['plate_number']])){
                    $data['data']['list'][$k]['car_id'] = $CarInfo[$v['plate_number']]['id'];
                }else{
                    $data['data']['list'][$k]['car_id'] = 0;
                } 
                /**
                if(!empty($v['insurance_photo'])){
                    $arr = explode(",", $v['insurance_photo']);
                    foreach($arr as $kk => $vv){
                        $arr[$kk] = \Yii::$app->params['ossFileUrl'].$vv;
                    }
                    $data['data']['list'][$k]['insurance_photo'] = $arr;
                }else{
                    $data['data']['list'][$k]['insurance_photo'] = [];
                }
                if(!empty($v['other_photo'])){
                    $arr = explode(",", $v['other_photo']);
                    foreach($arr as $kk => $vv){
                        $arr[$kk] = \Yii::$app->params['ossFileUrl'].$vv;
                    }
                    $data['data']['list'][$k]['other_photo'] = $arr;
                }else{
                    $data['data']['list'][$k]['other_photo'] = [];
                }
                if(!empty($v['other_photo_2'])){
                    $arr = explode(",", $v['other_photo_2']);
                    foreach($arr as $kk => $vv){
                        $arr[$kk] = \Yii::$app->params['ossFileUrl'].$vv;
                    }
                    $data['data']['list'][$k]['other_photo_2'] = $arr;
                }else{
                    $data['data']['list'][$k]['other_photo_2'] = [];
                }*/
                $data['data']['list'][$k]['oss_file_url'] = \Yii::$app->params['ossFileUrl'] ?? "";
            }
            LogicTrait::fillUserInfo($data['data']['list']);
        }
        return $data['data'];
    }

    /**
     * 判断车牌号是否已经添加保险
     * @return bool true已添加保险，false未添加保险
     */
    public static function checkIfInsurance($plate_number=null){
        if(!$plate_number){
            return false;
        }
        $rs = self::find()->where(['plate_number'=>$plate_number])->one();
        if($rs){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 编辑车辆保险
     */
    public static function edit($condition, $data){
        $model = self::find();
        if(!empty($condition['id'])){
            $model->andFilterWhere(['id'=>$condition['id']]);
        }
        $model = $model->one();
        if($model){
            $model->load($data, '');
            if (!$model->validate()){
                \Yii::info($model->getFirstError(), "add edit 1");
                return ['code'=>-1, 'message'=>$model->getFirstError()];
                //return false;
            }else{
                if($model->save()){
                    return ['code'=>0];
                }else{
                    \Yii::info($model->getFirstError(), "add edit 2");
                    return ['code'=>-2, 'message'=>'保存失败'];
                }
            }
        }else{
            return ['code'=>-1, 'message'=>'no data'];
        }
    }

    /**
     * 添加车辆车辆保险
     * @param $data array
     * @return array
     */
    public static function add($data){
        $model = new CarInsurance();
        $model->load($data, '');
        if (!$model->validate()){
            \Yii::info($model->getFirstError(), "add CarInsurance 1");
            return ['code'=>-1, 'message'=>$model->getFirstError()];
            //return false;
        }else{
            if($model->save()){
                return ['code'=>0];
            }else{
                \Yii::info($model->getFirstError(), "add CarInsurance 2");
                return ['code'=>-2, 'message'=>'保存失败'];
            }
        }
    }

}
