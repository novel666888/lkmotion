<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
use common\models\PassengerInfo;
use yii\helpers\ArrayHelper;
use common\util\Common;
use PHPExcel;
use PHPExcel_IOFactory;
use common\logic\LogicTrait;


/**
 * This is the model class for table "tbl_feedback".
 *
 * @property string $id
 * @property string $user_id 乘客ID
 * @property string $driver_id 司机ID
 * @property string $user_name 反馈人姓名
 * @property string $phone 反馈人手机号码
 * @property string $terminal 终端类型：1乘客、2司机、3车机、4大屏
 * @property string $large_class 问题分类大类
 * @property string $category 问题类型
 * @property string $content 反馈内容 
 * @property string $advice_image 反馈图片
 * @property int $status 状态：1待处理，2跟进中，3已解决
 * @property string $solution 解决方案
 * @property string $operator_id 操作人ID
 * @property string $create_time
 * @property string $update_time
 */
class Feedback extends \common\models\BaseModel
{
    use ModelTrait;
    use LogicTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_feedback';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'driver_id', 'status', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['user_name'], 'string', 'max' => 100],
            [['content'], 'string', 'max' => 1280],
            [['phone'], 'string', 'max' => 64],
            [['terminal', 'large_class', 'category', 'advice_image', 'solution'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'driver_id' => 'Driver ID',
            'user_name' => 'User Name',
            'phone' => 'Phone',
            'terminal' => 'Terminal',
            'large_class' => 'Large Class',
            'category' => 'Category',
            'content' => '反馈内容',
            'advice_image' => 'Advice Image',
            'status' => 'Status',
            'solution' => 'Solution',
            'operator_id' => 'Operator ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 获取反馈信息列表
     * @param  array $condition
     * @param  array $field
     * @param $r
     * @return array
     */
    public static function list($condition, $field=['*'], $r=true){

        $model = self::find()->select($field);
        if(!empty($condition['queryInfo']['beginTime'])){
            $model->andFilterWhere(['>=', 'create_time', $condition['queryInfo']['beginTime']]);
        }
        if(!empty($condition['queryInfo']['endTime'])){
            $model->andFilterWhere(['<=', 'create_time', $condition['queryInfo']['endTime']]);
        }
        if(!empty($condition['queryInfo']['terminal']) && is_numeric($condition['queryInfo']['terminal'])){
            \Yii::info($condition['queryInfo']['terminal'], 'terminal');
            $model->andFilterWhere(['terminal' => intval($condition['queryInfo']['terminal'])]);
        }
        if(!empty($condition['queryInfo']['phone'])){
            $model->andFilterWhere(['phone' => $condition['queryInfo']['phone']]);
        }
        if(!empty($condition['queryInfo']['status'])){
            $model->andFilterWhere(['status' => $condition['queryInfo']['status']]);
        }
        //添加判断条件
        //...
        if($r){
            $data = self::getPagingData($model, ["type"=>"desc", "field"=>"update_time"]);
            \Yii::info($data, 'list');
        }else{
            $data = $model->orderBy("update_time desc")->asArray()->all();
            \Yii::info($data, 'list');
            $tmp['data']['list'] = $data;
            $data = $tmp;
        }

        if(isset($data['data']['list']) && !empty($data['data']['list'])){
            $cipher = ArrayHelper::getColumn($data['data']['list'], 'phone');
            $cipher = Common::decryptCipherText($cipher);
            $passengerFeedback = \Yii::$app->params['passengerFeedback'];
            $driverFeedback = \Yii::$app->params['driverFeedback'];
            foreach ($data['data']['list'] as $key => &$value) {
                if(isset($cipher[$value['phone']])){
                    $value['phone'] = $cipher[$value['phone']];
                }
                if(!empty($value['advice_image'])){
                    $value['advice_image'] = explode(",", $value['advice_image']);
                    foreach ($value['advice_image'] as $kk => $vv){
                        $value['advice_image'][$kk] = \Yii::$app->params['ossFileUrl'].$vv;
                    }
                }
                if($value['terminal']==1){
                    if(!empty($value['large_class']) && isset($passengerFeedback[$value['large_class']])){
                        $value['category']    = $passengerFeedback[$value['large_class']]['data'][$value['category']];
                        $value['large_class'] = $passengerFeedback[$value['large_class']]['name'];
                    }else{
                        $value['category']    = "";
                        $value['large_class'] = "";
                    }
                }
                if($value['terminal']==2){
                    if(!empty($value['category']) && isset($driverFeedback[$value['category']])){
                        $value['category'] = $driverFeedback[$value['category']];
                    }else{
                        $value['category'] = "";
                    }
                }
            }
        }
        LogicTrait::fillUserInfo($data['data']['list']);
        \Yii::info($data['data'], 'listArr');
        return $data['data'];
    }

    /**
     * 写入一条反馈信息
     * @param array $upData 要更新的数组信息
     * @return array
     */
    public static function add($upData){
        $rs = PassengerInfo::find()->select("id,phone,passenger_name")->where(["id"=>$upData['user_id']])->asArray()->one();
        if(!isset($rs['id']) || empty($rs['id'])){
            return ['code'=>-1, 'message'=>'用户不存在'];
            //return false;
        }
        $upData['user_name'] = $rs['passenger_name'];
        $upData['phone'] = $rs['phone'];
        $model = new Feedback();
        $model->load($upData, '');
        if (!$model->validate()){
            //echo $model->getFirstError();
            //exit;
            \Yii::info($model->getFirstError(), "feedback add 1");
            //return ['code'=>-2, 'message'=>$model->getFirstError()];
            return ['code'=>-2, 'message'=>'添加失败'];
        }else{
            if($model->save()){
                return ['code'=>0, 'data'=>''];
                //return true;
            }else{
                //return Json::message($model->getErrors());
                \Yii::info($model->getErrors(), "feedback add 2");
                //return ['code'=>-3, 'message'=>$model->getErrors()];
                return ['code'=>-3, 'message'=>'添加失败'];
            }
        }
    }



    /*
     *导出
     * */

    public static function outPutExcel($data){
        if(!$data) return false;

        ini_set("memory_limit", "2048M");
        set_time_limit(0);
        $objectPHPExcel = new PHPExcel();

        //设置表格头的输出
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('A1', '序号');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('B1', '乘客ID');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('C1', '司机ID');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('D1', '用户名');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('E1', '用户手机');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('F1', '端类型');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('G1', '问题大类');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('H1', '问题类型');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('I1', '反馈内容');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('J1', '操作状态');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('K1', '解决方案');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('L1', '创建时间');
        $objectPHPExcel->setActiveSheetIndex()->setCellValue('M1', '更新时间');
        if(!empty($data)){
            $n = 2;
            foreach ($data as $v){
                $objectPHPExcel->getActiveSheet()->setCellValue('A'.($n) ,$v['id']);
                $objectPHPExcel->getActiveSheet()->setCellValue('B'.($n) ,$v['user_id']);
                $objectPHPExcel->getActiveSheet()->setCellValue('C'.($n) ,$v['driver_id']);
                $objectPHPExcel->getActiveSheet()->setCellValue('D'.($n) ,$v['user_name'].' ');
                $objectPHPExcel->getActiveSheet()->setCellValue('E'.($n) ,$v['phone']);
                if($v['terminal']== 1) $terminal = "乘客端";
                if($v['terminal']== 2) $terminal = "司机端";
                if($v['terminal']== 3) $terminal = "车机端";
                if($v['terminal']== 4) $terminal = "大屏端";
                $objectPHPExcel->getActiveSheet()->setCellValue('F'.($n) ,$terminal);
                $objectPHPExcel->getActiveSheet()->setCellValue('G'.($n) ,$v['large_class']);
                $objectPHPExcel->getActiveSheet()->setCellValue('H'.($n) ,$v['category']);
                $objectPHPExcel->getActiveSheet()->setCellValue('I'.($n) ,$v['content']);
                if($v['status']==1) $status="待处理";
                if($v['status']==2) $status="跟进中";
                if($v['status']==3) $status="已解决";
                $objectPHPExcel->getActiveSheet()->setCellValue('J'.($n) ,$status);
                $objectPHPExcel->getActiveSheet()->setCellValue('K'.($n) ,$v['solution']);
                $objectPHPExcel->getActiveSheet()->setCellValue('L'.($n) ,$v['create_time']);
                $objectPHPExcel->getActiveSheet()->setCellValue('M'.($n) ,$v['update_time']);
                $n = $n +1;
            }
        }

        ob_end_clean();
        ob_start();
        header('Content-Type:application/vnd.ms-excel');//!!!不能有空格

        //设置输出文件名及格式
        header('Content-Disposition:attachment;filename="用户反馈信息列表'.date("YmdHis").'.xls"');

        //导出.xls格式的话使用Excel5,若是想导出.xlsx需要使用Excel2007
        $objWriter= PHPExcel_IOFactory::createWriter($objectPHPExcel,'Excel5');

        $objWriter->save('php://output');

        ob_end_flush();

        //清空数据缓存
        unset($data);
    }
}
