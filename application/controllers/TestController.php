<?php

namespace application\controllers;

use common\logic\CouponTrait;
use common\logic\Sign;
use common\models\Decrypt;
use Dompdf\Dompdf;
use Dompdf\Options;
use Faker\Provider\Uuid;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\web\Controller;

class TestController extends Controller
{
    use CouponTrait;

    public function actionSign()
    {
        $info = self::getOrderMaxCoupon(961,1396);

        $data = [
            'ts' => microtime(1),
            'a' => rand(),
            'b' => Uuid::uuid(),
            'z_list' => [
                'godden' => 'is hero',
                'harry' => '是菜鸟',
            ],
            'map' => [
                2,3,3
            ]
        ];
        $token = Decrypt::createBossToken(3);
        $signModel = new Sign();
        $secret = $signModel->genKey(strval($token));
        var_dump(json_encode($data,256));
        echo '<br>';
        echo 'secret: ', $secret, "<br>";
        $sign = $signModel->sign($data, $secret);
        echo '对singStr_sing进行md5结果: ', $sign, "<br>";
        $data['sign'] = $sign;
        //$data['a'] = rand();
        var_dump($signModel->checkSign($data,$secret));
    }

    /**
     * 下载excel-使用yii的sendFile
     *
     * @return \yii\console\Response|\yii\web\Response
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDownloadExcel()
    {
        // 获取sheet
        $spreadsheet = $this->createSpreadsheet();
        // 将sheet页填充到excel表
        $writer = new Xlsx($spreadsheet);
        // 保存excel
        $filename = '文件名乱码测试_' . time() . '.xlsx';
        $writer->save($filename);
        // 清除输出缓存
        ob_clean();
        // 输出文件
        return \Yii::$app->response->sendFile($filename);
    }

    /**
     * 下载excel-使用PHP原生header
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDlExcel()
    {
        $spreadsheet = $this->createSpreadsheet();
        $writer = new Xlsx($spreadsheet);
        $filename = '文件名乱码测试_' . time() . '.xlsx';
        // 清除输出缓存
        ob_clean();
        // redirect output to client browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // xlsx
        //header('Content-Type: application/vnd.ms-excel'); // xls
        header('Content-Disposition: attachment;filename="' . $filename . '"'); // 文件名
        header('Cache-Control: max-age=0'); // 不缓存
        // 输出文件
        $writer->save('php://output');
        // 输出完成后及时结束
        exit;
    }

    public function actionDlPdf()
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $domPdf = new Dompdf($options);
        $str = '果酸按照分子结构的不同可区分为：甘醇酸、乳酸、苹果酸、酒石酸、柠檬酸、杏仁酸等37种，然而在医学美容界中，最常被应用到的成分为甘醇酸及乳酸。甘醇酸，又称为甘蔗酸、乙二醇酸，最早由甘蔗中萃取而得，是果酸产品中应用最广的一员。甘醇酸具有果酸中最小的分子量(76)，因此最容易渗透皮肤的表层，吸收的效果也最明显，是最常被用在换肤使用的果酸。乳酸，有果酸中的第二小的分子量(90)，因为保湿度强、天然成分不会刺激人体皮肤，所以广泛被用在改善肌肤干燥及角化现象。高浓度时，使皮肤松解脱皮最快的则是酒石酸，其次是甘醇酸和乳酸。至于促进细胞更新，则以乳酸效果最好，其次是甘醇酸。
酸碱值编辑
果酸是一种弱酸，除了浓度之外，PH值决定了它的功效。酸性环境下有利于果酸的作用及保存，在偏碱性环境下，果酸会被解离而失去作用。譬如是高达15%浓度的AHA，但是在PH值大于5的溶液中，大多数的果酸分子都已经解离，当然失去果酸的活性。
在果酸产品中，调和酸碱度的缓衡溶液系统，稳定平衡比果酸的浓度还重要。根据研究，PH在2.5-3的酸性范围，果酸的效果最佳，但刺激性也增大。目前化妆品专柜的保养品，果酸的浓度都在5%以下，PH值都在3以上，功效只能着重在去角质及保湿作用，对于除皱美白没有明显疗效。
自由酸的概念：所谓的自由酸就是在溶液中未解离的酸，就果酸而言，也就是真正能作用的果酸。所有的果酸都是弱酸，有部分会解离在水溶液中，真正有作用的是未解离的自由酸。';

        $domPdf->loadHtml(($str));
        // Render the HTML as PDF
        $domPdf->render();

        // Output the generated PDF to Browser
        $domPdf->stream();

        /*
                $spreadsheet = $this->createSpreadsheet();///
                $writer = new Mpdf($spreadsheet);
                $writer->setOrientation('autoScriptToLang');
                // 保存excel
                $filename = '文件名乱码测试_'.time().'.pdf';
                $writer->save($filename);
                return 'OK';
        */
    }

    /**
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function createSpreadsheet()
    {
        // 新建sheet页
        $spreadsheet = new Spreadsheet();
        // 生成数据
        $arrayData = [
            [NULL, 2010, 2011, 2012],
            ['Q1', 12, 15, 21],
            ['Q2', 56, 73, 86],
            ['Q3', 52, 61, 69],
            ['Q4', 30, 32, 12],
            ['公式和中文乱测试',
                '=SUM(B2:B5)',
                '=MAX(C2:C5)',
                '=MIN(D2:D5)',
            ]
        ];
        // 填充sheet
        $spreadsheet->getActiveSheet()->fromArray($arrayData);
        // 返回sheet
        return $spreadsheet;
    }

}
