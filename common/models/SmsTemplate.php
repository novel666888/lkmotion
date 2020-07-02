<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;

/**
 * This is the model class for table "{{%sms_template}}".
 *
 * @property int $id
 * @property string $template_id 短信模板ID
 * @property string $template_type 模板类型（1：营销；2：通知；3：订单）
 * @property string $content 模板内容
 */
class SmsTemplate extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sms_template}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id','template_type'], 'integer'],
            [['template_id'], 'string', 'max' => 16],
            [['content'], 'string', 'max' => 512],
            [['template_id'], 'unique'],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_id' => 'Template ID',
            'template_type' => 'Template Type',
            'content' => 'Content',
        ];
    }
    
    /**
     * 短信模板列表
     * 
     * @param string $templateName
     * @return array
     */
    public static function getTemplateList($templateName){
        $query = self::find();
        $query->select('*')->where(['template_type'=>[1,2],'source'=>1]);
        $query->andFilterWhere(['LIKE', 'template_name', $templateName]);
        $templateList = static::getPagingData($query, ['type'=>'desc', 'field'=>'create_time'], true);
        return $templateList['data'];
    }
    
    
    
    
    
    
    
    
    
    
    
}
