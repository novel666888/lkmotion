<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/20
 * Time: 9:25
 */

namespace common\services;

use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\httpclient\Client;
use yii\base\Component;

class YesinCarHttpClient extends Component
{
    public $serverURI;
    /**
     * @var \yii\httpclient\Client $client
     */
    public $client;

    private $i18nCategory = 'sys_error';

    /**
     * YesinCarHttpClient constructor.
     * @param array $config
     * @throws InvalidConfigException
     */

    public function __construct(array $config = [])
    {
        if (!isset($config['serverURI'])) {
            throw new InvalidConfigException('cannot find parameters `serverURI`');
        }
        parent::__construct($config);
    }

    /**
     *
     */

    public function init()
    {
        parent::init();
        $this->client = new Client();
    }

    /**
     * @param $methodPath
     * @param array $data
     * @param string $format
     * @param int $timeout
     * @return array|mixed
     * @throws UserException
     */
    /**
     * @param $methodPath
     * @param array $data
     * @param string $format
     * @param array $options
     * @return array|mixed
     * @throws UserException
     */

    public function post($methodPath,array $data=[],$format = Client::FORMAT_JSON, $options = ['timeout'=>10])
    {
        if(empty($methodPath)){
            \Yii::info(\Yii::t($this->i18nCategory, CConstant::ERROR_CODE_SERVICE_API_METHOD_NOT_EXIST), 'process');
            throw new UserException(\Yii::t($this->i18nCategory, CConstant::ERROR_CODE_SERVICE_API_METHOD_NOT_EXIST), CConstant::ERROR_CODE_SERVICE_API_METHOD_NOT_EXIST);
        }
        $methodPath = trim($methodPath,'/');
        $request = $this->client->createRequest()
            ->setOptions($options)
            ->setFormat($format)
            ->setMethod('post')
            ->setUrl($this->serverURI.'/'.$methodPath)
            ->setData($data);
        $response = $request->send();
        /**@var \yii\httpclient\Response $response */
       if(!$response->getIsOk()){
           throw new \RuntimeException('Service API error!');
       }
       $data = $response->getData();
       if(isset($data['message'])){
           \Yii::info($data, 'process');
       }
       return $data;
    }

    /**
     * @param $methodPath
     * @param array $data
     * @param string $format
     * @return array|mixed|string
     * @throws UserException
     */

    public function get($methodPath,array $data=[],$reponseType,$format = Client::FORMAT_URLENCODED)
    {
        $methodPath = ltrim($methodPath,'/');

        $request = $this->client->createRequest()
            ->setFormat($format)
            ->setMethod('get')
            ->setUrl($this->serverURI.'/'.$methodPath)
            ->setData($data);

        $response = $request->send();


        /**@var \yii\httpclient\Response $response */
        if(!$response->getIsOk()){
            throw new \RuntimeException('service API error!');
        }

        if($reponseType==1){
            $data = $response->content;
        }else{
            $data = $response->getData();
        }
        return $data;
    }


}



