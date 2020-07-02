<?php
namespace common\logic\blacklist;

use common\models\OrderPayment;
use common\models\PassengerBlacklist;
use common\models\DeviceBlacklist;
use common\models\Order;
use common\util\Common;
use yii\helpers\ArrayHelper;
/**
 * 黑名单规则判断以及记录缓存
 */
class BlacklistDashboard 
{
	
	const TEMPORARY_TIME = 3600;
	const TEMPORARY_MAX  = 3;
	const TEMPORARY_LOCK_TIME = 3600;
	const PERMANENT_TIME = 86400;
	const PERMANENT_MAX  = 10;
	const PERMANENT_LOCK_TIME = 0;
	const DEVICE_NOPAY_MAX = 2;

	const PRENAME = "blacklist";//用户黑操作缓存前缀
    const PRENAME_DB = "blackDB";
    const PRENAME_DB_RECORD = "blackDBRecord";


	/**
	 * 用户
	 * 1000 正常
	 * 1001 进入临时黑名单
	 * 1002 进入永久黑名单
	 * 1003 进入临时黑名单-保存失败
	 * 1004 进入永久黑名单-保存失败
	 * 1009 入参不全
	 * 设备
	 * 1000 正常
	 * 1002 进入永久黑名单（获取内容：$deviceMes）
	 * 1004 进入永久黑名单-保存失败
	 */
	public static $deviceMes = [];//设备黑名单反馈信息

	/**
	 * 乘客黑名单校验入口
	 * @param  str/int $phone 用户手机号
	 * @param  int $act 动作（1:取消订单）
	 * @return [type]        [description]
	 */
	public static function checkBlacklist($phone){  //手机黑名单
	    \Yii::info($phone, "checkBlacklist 1");
		if(empty($phone)){
			return 1009;
		}

		$jg = self::rlist($phone);
        if($jg===false){
            return 1000;
        }
        if($jg==1){
            return 1001;
        }
        if($jg==2){
            return 1002;
        }
        return 1000;
        /**
		$rs = Common::phoneEncrypt([$phone]);
        \Yii::info([$phone, $rs], "checkBlacklist 2");
		if(empty($rs)){
			return 1000;
		}
		$phoneEncrypt = $rs;
		$data = [
            'phone'=>$phoneEncrypt,
            'is_release'=>0
        ];
		$rs = PassengerBlacklist::query($data);
		//echo "<pre>";
		//print_r($rs);
		//exit;
		if($rs){
			if(isset($rs['category']) && $rs['category']==1){
				return 1001;
			}
			if(isset($rs['category']) && $rs['category']==2){
				return 1002;
			}
		}
		return 1000;
        */
	}


    /**
	 * 记录一条乘客黑名单黑动作
	 * @param  str/int $phone 用户手机号
	 * @param  int $act 动作（1:取消订单）
	 * @return [type]        [description]
	 */
	public static function addBlacklist($phone, $act=1){
        \Yii::info($phone, "addBlacklist");
	    $check_c = self::checkBlacklist($phone);
		if($check_c!=1000){
			return $check_c;
		}

		if(empty($phone) || empty($act)){
			return 1009;
		}
		$time=time();
		$r=rand(100,999);
		$k=self::PRENAME."_".$phone."_".$act."_".$time."_".$r;
		$v=$phone."_".$act."_".$time;
		$redis = \Yii::$app->redis;
		$redis->set($k,$v);
		$redis->expire($k, self::PERMANENT_TIME);

		return self::_check($phone, [1]);
	}

    /**
     * [内]验证设备是否应该加入黑名单
     * @param string $deviceCode
     * @return array 1进入黑名单/0不需要进入黑名单
     */
    public static function checkEquipmentBlacklist($deviceCode){
        $model = Order::find()->select(["id", "passenger_phone", "order_start_time"])
            ->andFilterWhere(['device_code'=>$deviceCode])
            //->andFilterWhere(['is_paid'=>0])//0未支付
            ->andFilterWhere(['in', 'status', [6,7,8,9]])//6未支付7发起收款
            ->asArray()->all();
        $count = 0;
        if(!empty($model)){
            $ids = ArrayHelper::getColumn($model, 'id');
            $OrderPayment = OrderPayment::find()->select(['remain_price','order_id'])->where(['order_id' => $ids])->asArray()->all();
            $OrderPayment = !empty($OrderPayment) ? $OrderPayment : [];
            foreach($model as $k1 => $v1){
                $model[$k1]['remain_price'] = 0;
                foreach ($OrderPayment as $k2 => $v2){
                    if($v1['id']==$v2['order_id']){
                        $model[$k1]['remain_price'] = $v2['remain_price'];
                        break;
                    }
                }
                if($model[$k1]['remain_price'] <= 0){
                    unset($model[$k1]);
                }
            }
        }
        $js = [];
        if(!empty($model)){
            foreach ($model as $k => $v){
                $js[$v['passenger_phone']] = 1;
            }
        }
        $count = count($js);
        \Yii::info([$count,$model],'checkDeviceBlacklist 2');
        if($count >= self::DEVICE_NOPAY_MAX){
            return [1,$model];
        }else{
            return [0,$model];
        }
    }

    /**
     * 支付成功后解禁设备黑名单
     * $orderId
     */
    public static function relieveDevice($orderId){
        $Order = Order::find()->select(["id", "device_code", "source"])
            ->FilterWhere(['id'=>$orderId])
            ->asArray()->one();
        if(empty($Order)){
            return false;
        }
        //先判断设备是否正在黑名单中
        $_result = self::checkEquipmentBlacklist($Order['device_code']);
        if($_result[0]==0){
            $model = DeviceBlacklist::find();
            $model = $model->FilterWhere(['device_code'=>$Order['device_code']])
                ->andFilterWhere(['is_release'=>0])
                ->asArray()->all();
            if(!empty($model)){
                $DeviceBlacklist = new DeviceBlacklist();
                $DeviceBlacklist->updateAll(['is_release'=>1], ["device_code"=>$Order['device_code']]);
            }
        }
    }

	/**
	 * 验证设备是否满足进入黑名单
	 * @return [str] $deviceCode 设备code 
	 */
	public static function checkDeviceBlacklist($deviceCode, $deviceType){
        \Yii::info([$deviceCode,$deviceType], "checkDeviceBlacklist");
        if(empty($deviceCode) || empty($deviceType)){
            return 1009;
        }
        //先判断设备是否正在黑名单中
		$model = DeviceBlacklist::find()->select(['phones']);
        $model = $model->andFilterWhere(['device_code'=>$deviceCode])
        	//->andFilterWhere(['device_type'=>$deviceType])
        	->andFilterWhere(['is_release'=>0])
        	->asArray()->one();
        if(!empty($model)){
        	self::$deviceMes = explode(",", $model['phones']);
        	return 1002;
        }

        $_result = self::checkEquipmentBlacklist($deviceCode);
        $model = $_result[1];
		if($_result[0]==1){//满足进入黑名单，入库

			$cipher = ArrayHelper::getColumn($model, 'passenger_phone');
			$cipher = Common::decryptCipherText($cipher);
			foreach ($model as $key => &$value) {
				if(isset($cipher[$value['passenger_phone']])){
					$value['passenger_phone'] = $cipher[$value['passenger_phone']];
				}
			}

			$memo="";
			$phones=[];
			foreach($model as $k => $v){
				$memo .= $v['passenger_phone']."（欠款）".$v['order_start_time']."。";
                $phones[] = $v['passenger_phone'];
			}
			$time = date("Y-m-d H:i:s", time());
            $phones = array_unique($phones);
			$data = [
				"device_type"=>(string)$deviceType,
				"device_code"=>(string)$deviceCode,
				"last_login_time"=>$time,
				"is_release"=>0,
				"memo"=>$memo,
                "phones"=>implode(",", $phones)
			];
			$DeviceBlacklist = new DeviceBlacklist();
	        $DeviceBlacklist->load($data, "");
	        if (!$DeviceBlacklist->validate()){
	            //echo $DeviceBlacklist->getFirstError();
                \Yii::info($DeviceBlacklist->getFirstError(), "DeviceBlacklist_1");
	            //exit;
	            return 1004;
	        }else{
	            if($DeviceBlacklist->save()){
	                self::$deviceMes = $phones;
	                return 1002;
	            }else{
	                //return Json::message($model->getErrors());
                    \Yii::info($DeviceBlacklist->getErrors(), "DeviceBlacklist_2");
	                return 1004;
	            }
	        }
		}
		return 1000;
	}




	public static function test($phone, $act=1){
		exit;
		$redis = \Yii::$app->redis;
		$rs = $redis->keys("*");
		echo "<pre>";
		print_r($rs);
		exit;
	}


	/**
	 * 验证用户是否需要进入黑名单（永久/临时）
	 * @param  str/int  $phone 用户手机号
	 * @param  array $acts   动作（1:取消订单）数组支持多个动作
	 * @return [type]         [description]
	 */
	private static function _check($phone, $acts=[1]){
		$_k = self::PRENAME."_".$phone."_".$acts[0]."_*";
		$redis = \Yii::$app->redis;
		$rs = $redis->keys($_k);
		if(!empty($rs) && is_array($rs)){
			foreach ($rs as $kk => &$vv){
				$vv = explode("_", $vv);
				if(!isset($vv[3]) || empty($vv[3])){
					unset($rs[$kk]);
				}
			}
		}else{
			return 1000;
		}

		$y=count($rs);
		if($y>=self::PERMANENT_MAX){
			//进入永久黑名单
			$jg = self::_insertRecord($phone,2,2,0);
			if($jg){
				return 1002;
			}else{
				return 1004;
			}
		}

		$time=time();
		$l=0;
		if(!empty($rs) && is_array($rs)){
			foreach ($rs as $kk => &$vv) {
				 if(intval(sprintf("%d", ($time-$vv[3]))) <= self::TEMPORARY_TIME){
				 	 $l++;
				 }
			}
			if($l>=self::TEMPORARY_MAX){
				//进入临时黑名单
				$release_time = $time+self::TEMPORARY_LOCK_TIME;
				$release_time = date("Y-m-d H:i:s", $release_time);
				$jg = self::_insertRecord($phone,1,1,$release_time);
				if($jg){
					return 1001;
				}else{
					return 1003;
				}
			}
		}
		
		return 1000;
	}


	/**
	 * 删除某个手机号和某个动作下的所有redis不良记录
	 * @return
	 */
	public static function delCacheRecord($phone, $acts=[1]){
		$_k = self::PRENAME."_".$phone."_".$acts[0]."_*";
		$redis = \Yii::$app->redis;
		$rs = $redis->keys($_k);
		if(!empty($rs)){
			foreach ($rs as $vv){
				$redis->del($vv);
			}
		}
	}

    /**
     * 从redis列表缓存，返回指定的phone信息
     * @param $phone 明文
     */
    public static function rlist($phone){
        $redis = \Yii::$app->redis;
        $blackDBRecord = $redis->get("blackDBRecord");
        if($blackDBRecord!='start'){
            self::ulist();
        }
        $_k = self::PRENAME_DB."_".$phone;
        $rs = $redis->keys($_k);
        if(!empty($rs)){
            return $redis->get($_k);
        }else{
            return false;
        }
    }

    /**
     * 更新redis列表缓存
     * @param $all Array
     */
    public static function ulist(){
        $redis = \Yii::$app->redis;
        $redis->set("blackDBRecord", 'start');

        $_k = self::PRENAME_DB."_*";
        $rs = $redis->keys($_k);
        if(!empty($rs)){
            foreach ($rs as $vv){
                $redis->del($vv);
            }
        }

        $a = PassengerBlacklist::find()->select(['id','phone','is_release','category'])->where(["is_release"=>0])->asArray()->all();
        if(empty($a)){
            return false;
        }
        $cipher = ArrayHelper::getColumn($a, 'phone');
        $cipher = Common::decryptCipherText($cipher);
        \Yii::info([$a,$cipher], 'ulist');
        if(!empty($cipher) && is_array($cipher)){//数组
            foreach($a as $k => &$v){
                $v['phone'] = $cipher[$v['phone']];
            }
        }elseif(!empty($cipher)){//字符串
            foreach($a as $k => &$v){
                $v['phone'] = $cipher;
            }
        }else{
            return false;
        }

        if(!empty($a)){
            foreach ($a as $vv){
                $_k = self::PRENAME_DB."_".$vv['phone'];
                $_v = $vv['category'];
                $redis->set($_k,$_v);
            }
            return true;
        }
        return false;
    }





	/**
	 * 记录数据库乘客 黑 操作行为
	 * @param  [type] $phone        [description]
	 * @param  [type] $reason       [description]
	 * @param  [type] $category     [description]
	 * @param  [type] $release_time [description]
	 * @return [type]               [description]
	 */
	private static function _insertRecord($phone, $reason, $category, $release_time){
		$rs = Common::phoneEncrypt([$phone]);
		if(empty($rs)){
			return false;
		}
		$phoneEncrypt = $rs;
		return PassengerBlacklist::add($phoneEncrypt, $reason, $category, $release_time);
	}




}