<?php
namespace application\modules\crm\controllers;

use common\util\Json;

use common\models\DeviceBlacklist;
use common\util\Common;
use application\controllers\BossBaseController;
/**
 * 设备管理控制器
 */
class DeviceController extends BossBaseController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
    }
    
    /**
     * 输出设备黑名单列表
     * @return [type] [description]
     */
    public function actionBlacklist(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['phone'] = isset($requestData['phone']) ? trim($requestData['phone']) : '';
        if(!empty($requestData['phone'])){
            if(!is_numeric($requestData['phone']) || strlen($requestData['phone'])<8){
                return Json::message("phone format error");
            }
        }
        $phoneEncrypt = $requestData['phone'];//memo中保存的是明文
        $condition=[];
        $condition['phone']     = $phoneEncrypt;
        $field=["id", "device_type AS deviceType", "device_code AS deviceCode", "last_login_time AS lastLoginTime", "memo", "is_release AS isRelease"];
        $data = DeviceBlacklist::getBlacklist($condition, $field);
        return Json::success($data);
    }


}
