<?php
namespace common\logic\order;

//use common\services\traits\ModelTrait;
use common\models\Order;
use common\models\OrderRulePrice;
use common\models\OrderRulePriceDetail;
use common\models\OrderUseCoupon;
use common\models\OrderPayment;
use common\models\OrderAdjustRecord;
use common\models\OrderRulePriceTag;
/**
 *
 * 返回订单费用规则相关计算
 * 
 */
trait OrderBillingRulesTrait
{
	/**
	 * 返回订单的费用详情
     * 当订单状态为0-6时，读取category=0
     * 当订单状态为7-8时，读取category=1
     *
	 * @return [type] [description]
	 */
	public function details($orderId, $passengerId){
        $_one = Order::find()->select(["id","status"])->where(["id"=>$orderId, "passenger_info_id"=>$passengerId])->asArray()->one();
        if(empty($_one)){
            \Yii::info([$orderId,$passengerId],"Non owning order");
            return ['code'=>-2, 'message'=>'Non owning order'];
        }
        $category=0;
        if($_one['status']>=0 && $_one['status']<=6){
            $category=0;
        }
        if($_one['status']>=7 && $_one['status']<=8){
            $category=1;
        }
        if($_one['status']==9){
            $category=0;
        }

		$select=[
			'order_id',
            'base_price',//起步价
            'lowest_price',//基础价格
			'total_price',//总价
			'path',//总距离（公里）
			'path_price',//总距离（费用）
			'duration',//总时间（分钟）
			'duration_price',////总时间（费用）
			'supplement_price',//基础费补足
			'beyond_distance',//远途费公里数
			'beyond_price',//远途费
			'night_time',//夜间服务费，总时间
			'night_distance',//夜间服务费，总公里
			'night_price',//夜间服务费
			'parking_price',//停车费
			'road_price',//过路费
            'other_price',//其他费用
			'rest_distance',//其他时段距离（公里）
			'rest_distance_price',//其他时段距离（费用）
			'rest_duration',//其他时段时间（分钟）
			'rest_duration_price',//其他时段时间（费用）
            'dynamic_discount_rate',//动态调价的折扣率(0-1 两小数)
            'city_code'
		];
		$RulePrice = OrderRulePrice::find()->select($select)->andFilterWhere(["order_id"=>$orderId, "category"=>$category])->asArray()->one();
		if(empty($RulePrice) || !is_array($RulePrice)){
            \Yii::info([$orderId,$category],"no RulePrice");
		    return ['code'=>-3, 'message'=>'no RulePrice'];
		}
		$select2=[
			"start_hour",//时间开始
			"end_hour",//时间结束
			"duration",//时间
			"time_price",//时间价格
			"distance",//距离
			"distance_price",//距离价格

		];
		$RulePriceDetail = OrderRulePriceDetail::find()->select($select2)->andFilterWhere(["order_id"=>$orderId, "category"=>$category])->asArray()->all();
		$licheng = [];//里程分段记录
		$shijian = [];//时长分段记录
		if(is_array($RulePriceDetail)){
			foreach($RulePriceDetail as $k => $v){
				$licheng[]=[
					'startHour' 		=> $v['start_hour'],
					'endHour'	 		=> $v['end_hour'],
					'distance'	 		=> $v['distance'],
					'distancePrice'	    => $v['distance_price'],
				];
				$shijian[]=[
					'startHour' 		=> $v['start_hour'],
					'endHour'	 		=> $v['end_hour'],
					'duration'	 		=> $v['duration'],
					'timePrice'		    => $v['time_price'],
				];
			}
		}
		$RulePrice['distance_detail']   = $licheng;
		$RulePrice['duration_detail']   = $shijian;
        $RulePrice['order_status']      = "0";
        $RulePrice['coupon_money']      = "0";
        $RulePrice['paid_price']        = "0";
        $RulePrice['remain_price']      = "0";
        $RulePrice['adjust_price']      = "0";
        $RulePrice['adjust_rate']       = "0";//调账率

        //女性，儿童
        $OrderRulePriceTag = OrderRulePriceTag::find()->select(['id','tag_name','tag_price'])->andFilterWhere(["order_id"=>$orderId, "category"=>$category])->asArray()->all();
        if(!empty($OrderRulePriceTag)){
            $RulePrice['tagRule'] = $OrderRulePriceTag;
        }else{
            $RulePrice['tagRule'] = [];
        }

        if($category == 0){
            $result = OrderUseCoupon::find()->select(['coupon_money'])->andFilterWhere(['order_id'=>$orderId, 'category'=>$category])->asArray()->one();
            if(isset($result['coupon_money'])){
                $RulePrice['coupon_money'] = (string)$result['coupon_money'];
            }
        }
        if($category == 1 || $_one['status']==9){
            $result = OrderPayment::find()->select(['final_price', 'total_price', 'coupon_reduce_price', 'paid_price', 'remain_price'])->andFilterWhere(['order_id'=>$orderId])->asArray()->one();
            if(!empty($result)){
                $RulePrice['coupon_money']      = $result['coupon_reduce_price'];
                $RulePrice['paid_price']        = $result['paid_price'];
                $RulePrice['remain_price']      = $result['remain_price'];
                if($RulePrice['total_price'] != $result['total_price']){
                    //取消订单后，订单status=9，rule_price=1的没有生成，所以之前读取=0的total_price是预估的大的值，所以要修正为pay_ment。
                    //正常订单，调账后
                    $RulePrice['total_price']   = $result['total_price'];
                }
                /**
                $Denominator = sprintf("%.2f", ($result['total_price']-$result['coupon_reduce_price']));
                if($Denominator!=0 && $result['final_price']!=0){
                    $RulePrice['adjust_rate']   =   sprintf("%.2f", ($result['final_price']/$Denominator));
                }*/
            }
            $result = OrderAdjustRecord::find()->select(['old_cost','new_cost'])->andFilterWhere(['order_id'=>$orderId])->asArray()->one();
            if(!empty($result)){
                $jg = sprintf("%.2f", ($result['new_cost'] - $result['old_cost']));
                if($jg>0){
                    $jg = '+'.$jg;
                }
                $RulePrice['adjust_price']      = (string)$jg;
            }
        }

        $RulePrice['cancel_price'] = (string)$this->getOrderCancelPrice($orderId);
		return ['code'=>0, 'data'=>$RulePrice];
	}


    /**
     * 获取订单取消费
     * @param $orderId
     * @return 取消费
     */
    public function getOrderCancelPrice($orderId){
        if(empty($orderId)){
            return 0;
        }
        $_one = Order::find()->select(["id","status","service_type"])->where(["id"=>$orderId])->asArray()->one();
        if(empty($_one)){
            return 0;
        }
        if($_one['status']>=0 && $_one['status']<=5){
            return 0;
        }
        $category=0;
        if($_one['status']==6){
            $category=0;
        }
        if($_one['status']>=7 && $_one['status']<=8){
            $category=1;
        }
        if($_one['status']==9){
            $category=1;
        }

        if($category==0){
            //tbl_order_rule_price
            $OrderRulePrice = OrderRulePrice::find()->select(['base_price','lowest_price'])->where(["order_id"=>$orderId, "category"=>0])->asArray()->one();
            if(empty($OrderRulePrice)){
                return 0;
            }
            if($_one['service_type']==1){//实时单
                return $OrderRulePrice['base_price'];
            }
            if($_one['service_type']==2){//预约单
                return $OrderRulePrice['lowest_price'];
            }
        }
        if($category==1){
            //tbl_order_payment
            $OrderPayment = OrderPayment::find()->select(['total_price'])->where(["order_id"=>$orderId])->asArray()->one();
            if(empty($OrderPayment)){
                return 0;
            }
            return $OrderPayment['total_price'];
        }
        return 0;
    }


}