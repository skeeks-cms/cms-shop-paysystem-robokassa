<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.09.2015
 */

namespace skeeks\cms\shop\robokassa\controllers;

use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopOrder;
use skeeks\cms\shop\models\ShopPayment;
use skeeks\cms\shop\paySystems\robokassa\Merchant;
use skeeks\cms\shop\paySystems\RobokassaPaySystem;
use skeeks\cms\shop\robokassa\RobokassaPaysystemHandler;
use yii\base\Exception;
use yii\web\BadRequestHttpException;

/**
 * Class RobocassaController
 * @package skeeks\cms\shop\controllers
 */
class RobokassaController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * Используется в случае успешного проведения платежа.
     *
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     */
    public function actionSuccess()
    {
        \Yii::info("success request", static::class);
        \Yii::info(print_r($_REQUEST, true), static::class);

        if (!isset($_REQUEST['OutSum'], $_REQUEST['InvId'], $_REQUEST['SignatureValue'])) {
            \Yii::error("Not found params", static::class);
            throw new BadRequestHttpException('Not found params');
        }

        $bill = $this->loadModel($_REQUEST['InvId']);
        $merchant = $this->getPaySystem($bill->shopOrder);
        $shp = $this->getShp();

        if ($merchant->checkSignature($_REQUEST['SignatureValue'], $_REQUEST['OutSum'], $_REQUEST['InvId'],
            $merchant->sMerchantPass1, $shp)
        ) {

            /*$order->ps_status = "STATUS_ACCEPTED";
            $order->save();*/
            return $this->redirect($bill->shopOrder->url);
            //return $this->redirect(Url::to(['/shop/order/view', 'id' => $order->id]));
        }

        \Yii::error("bad signature", static::class);
        throw new BadRequestHttpException('bad signature');
    }
    /**
     * Загрузка заказа
     *
     * @param integer $id
     * @return ShopBill
     * @throws \yii\web\BadRequestHttpException
     */
    protected function loadModel($id)
    {
        $model = ShopBill::findOne($id);
        if ($model === null) {
            throw new BadRequestHttpException("Order: {$id} not found");
        }
        return $model;
    }


    /**
     * @param ShopOrder $order
     * @return RobokassaPaySystem
     * @throws BadRequestHttpException
     */
    protected function getPaySystem(ShopOrder $order)
    {
        $paySystemHandler = $order->shopPaySystem->paySystemHandler;
        if (!$paySystemHandler || !$paySystemHandler instanceof RobokassaPaysystemHandler) {
            \Yii::error("Not found pay system", static::class);
            throw new BadRequestHttpException('Not found pay system');
        }

        return $paySystemHandler;
    }

    /**
     * @return array
     */
    public function getShp()
    {
        $shp = [];
        foreach ($_REQUEST as $key => $param) {
            if (strpos(strtolower($key), 'shp') === 0) {
                $shp[$key] = $param;
            }
        }

        return $shp;
    }

    /**
     * Используется для оповещения о платеже
     *
     * @return string
     * @throws BadRequestHttpException
     */
    public function actionResult()
    {
        \Yii::info("result request", static::class);
        \Yii::info(print_r($_REQUEST, true), static::class);

        if (!isset($_REQUEST['OutSum'], $_REQUEST['InvId'], $_REQUEST['SignatureValue'])) {
            \Yii::error("Not found params", static::class);
            throw new BadRequestHttpException('Not found params');
        }

        $bill = $this->loadModel($_REQUEST['InvId']);
        $merchant = $this->getPaySystem($bill->shopOrder);
        $shp = $this->getShp();

        if ($merchant->checkSignature($_REQUEST['SignatureValue'], $_REQUEST['OutSum'], $_REQUEST['InvId'],
            $merchant->sMerchantPass2, $shp)
        ) {

            \Yii::info("result signature OK", static::class);


            $transaction = \Yii::$app->db->beginTransaction();

            try {

                $payment = new ShopPayment();
                $payment->shop_buyer_id = $bill->shop_buyer_id;
                $payment->shop_pay_system_id = $bill->shop_pay_system_id;
                $payment->shop_order_id = $bill->shop_order_id;
                $payment->amount = $bill->amount;
                $payment->currency_code = $bill->currency_code;
                $payment->comment = "Оплата по счету №{$bill->id} от ".\Yii::$app->formatter->asDate($bill->created_at);
                $payment->external_data = $response;

                if (!$payment->save()) {
                    throw new Exception("Не сохранился платеж: ".print_r($payment->errors, true));
                }

                $bill->isNotifyUpdate = false;
                $bill->paid_at = time();
                $bill->shop_payment_id = $payment->id;

                if (!$bill->save()) {
                    throw new Exception("Не обновился счет: ".print_r($payment->errors, true));
                }

                $bill->shopOrder->paid_at = time();
                $bill->shopOrder->save();

                $transaction->commit();

                //return $this->redirect($bill->shopOrder->url);

            } catch (\Exception $e) {
                $transaction->rollBack();
                \Yii::error($e->getMessage(), self::class);
                throw $e;
            }

            return "OK\n";
        }

        \Yii::error("bad signature", static::class);

        throw new BadRequestHttpException;
    }


    /**
     * @return string|\yii\web\Response
     * @throws BadRequestHttpException
     */
    public function actionFail()
    {
        \Yii::info("fail request", static::class);

        if (!isset($_REQUEST['OutSum'], $_REQUEST['InvId'])) {
            \Yii::error("Not found params", static::class);
            throw new BadRequestHttpException;
        }

        $bill = $this->loadModel($_REQUEST['InvId']);
        $merchant = $this->getPaySystem($bill->shopOrder);
        $shp = $this->getShp();

        /*$order->ps_status = "STATUS_FAIL";
        $order->save();*/
        return $this->redirect($bill->shopOrder->url);
        //$this->loadModel($nInvId)->updateAttributes(['status' => Invoice::STATUS_SUCCESS]);
        return 'Ok';
    }

}