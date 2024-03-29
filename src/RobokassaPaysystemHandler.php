<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\shop\robokassa;

use skeeks\cms\helpers\StringHelper;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopOrder;
use skeeks\cms\shop\paysystem\PaysystemHandler;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @property string $baseUrl
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class RobokassaPaysystemHandler extends PaysystemHandler
{

    public $isLive = true; //https://auth.robokassa.ru/Merchant/Index.aspx
    public $sMerchantLogin = '';
    public $sMerchantPass1 = '';
    public $sMerchantPass2 = '';

    /**
     * @var bool Отправлять данные по чекам?
     */
    public $is_receipt = false;


    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('skeeks/shop/app', 'Robokassa'),
        ]);
    }


    public function getBaseUrl()
    {
        return "https://auth.robokassa.ru/Merchant/Index.aspx";
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['sMerchantLogin'], 'string'],
            [['sMerchantPass1'], 'string'],
            [['sMerchantPass2'], 'string'],
            [['isLive'], 'boolean'],
            [['is_receipt'], 'boolean'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'isLive'         => 'Боевой режим?',
            'sMerchantLogin' => 'sMerchantLogin',
            'sMerchantPass1' => 'sMerchantPass1',
            'sMerchantPass2' => 'sMerchantPass2',
            'is_receipt'     => "Отправлять данные для формирования чеков?",
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'isLive'     => 'Боевой режим использует адрес: https://auth.robokassa.ru/Merchant/Index.aspx, не боевой — http://test.robokassa.ru/Index.aspx',
            'is_receipt' => "Необходимо передавать, если вы отправляете данные для формирования чеков по одному из сценариев: Платеж и чек одновременно или Сначала чек, потом платеж.",
        ]);
    }




    /**
     * @param ShopOrder $shopOrder
     * @return $this
     */
    /*public function paymentResponse(ShopOrder $shopOrder)
    {
        return $this->getMerchant()->payment($shopOrder->price, $shopOrder->id, \Yii::t('skeeks/shop/app', 'Payment order'), null, $shopOrder->user->email);
    }*/

    /**
     * @param ShopOrder $shopOrder
     * @return $this
     */
    public function actionPaymentResponse(ShopBill $shopBill)
    {
        return $this->payment($shopBill->money->amount, $shopBill->id, \Yii::t('skeeks/shop/app', 'Payment order'), null, $shopBill->shopOrder->email);
    }

    /**
     * @param ShopOrder $shopOrder
     * @return bool
     */
    public function actionPayOrder(ShopOrder $shopOrder)
    {
        $shopBill = $this->getShopBill($shopOrder);
        return $this->payment($shopBill->money->amount, $shopBill->id, \Yii::t('skeeks/shop/app', 'Payment order'), null, $shopBill->shopOrder->email);
    }


    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        $successUrl = Url::to(['/robokassa/robokassa/success'], true);
        $resultUrl = Url::to(['/robokassa/robokassa/result'], true);
        $failUrl = Url::to(['/robokassa/robokassa/fail'], true);

        $text = <<<HTML
<div class="col-12" style="margin-top: 20px;">
<div class="alert alert-default">
<p>В личном кабинете <a href="https://partner.robokassa.ru/" target="_blank">https://partner.robokassa.ru/</a>, выбирите нужный магазин и пропишите настройки:</p> 
<p>Result Url: <b>{$resultUrl}</b></p> 
<p>Success Url: <b>{$successUrl}</b></p> 
<p>Fail Url: <b>{$failUrl}</b></p> 
</div>
</div>
HTML;
        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Основные',
                'fields' => [
                    'text' => [
                        'class'   => HtmlBlock::class,
                        'content' => $text,
                    ],

                    'isLive'     => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'is_receipt' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'sMerchantLogin',
                    'sMerchantPass1',
                    'sMerchantPass2',

                ],
            ],

        ];
    }


    public function renderConfigForm(ActiveForm $activeForm)
    {
        $successUrl = Url::to(['/robokassa/robokassa/success'], true);
        $resultUrl = Url::to(['/robokassa/robokassa/result'], true);
        $failUrl = Url::to(['/robokassa/robokassa/fail'], true);

        echo Alert::widget([
            'closeButton' => false,
            'options'     => [
                'class' => 'alert-info',
            ],

            'body' => <<<HTML
<p>В личном кабинете <a href="https://partner.robokassa.ru/" target="_blank">https://partner.robokassa.ru/</a>, выбирите нужный магазин и пропишите настройки:</p> 
<p>Result Url: <b>{$resultUrl}</b></p> 
<p>Success Url: <b>{$successUrl}</b></p> 
<p>Fail Url: <b>{$failUrl}</b></p> 
HTML
            ,
        ]);
        echo $activeForm->field($this, 'isLive')->checkbox();
        echo $activeForm->field($this, 'sMerchantLogin')->textInput();
        echo $activeForm->field($this, 'sMerchantPass1')->textInput();
        echo $activeForm->field($this, 'sMerchantPass2')->textInput();
    }


    public function payment(
        $nOutSum,
        $nInvId,
        $sInvDesc = null,
        $sIncCurrLabel = null,
        $sEmail = null,
        $sCulture = null,
        $shp = []
    ) {
        $url = $this->baseUrl;

        $shopBill = ShopBill::findOne($nInvId);
        if (!$shopBill) {
            throw new Exception("Счет не найден: ".$nInvId);
        }

        if (!$shopBill->shopOrder) {
            throw new Exception("Заказ не найден: ".$shopBill->shopOrder->id);
        }
        
        if (!$sEmail) {
            $sEmail = $shopBill->shopOrder->contact_email;
        }
        
        $sInvDesc = "Оплата заказа №" . $shopBill->shopOrder->id . " по счету №" . $shopBill->id;

        if ($this->is_receipt) {



            $receipt = [];
            
            foreach ($shopBill->shopOrder->shopOrderItems as $shopOrderItem) {
                $itemData = [];

                $itemData['name'] = StringHelper::substr($shopOrderItem->name, 0, 128);
                $itemData['quantity'] = (float)$shopOrderItem->quantity;
                //$itemData['cost'] = $shopOrderItem->money->amount;
                $itemData['sum'] = $shopOrderItem->money->amount * $shopOrderItem->quantity;
                $itemData['payment_method'] = 'full_payment';
                $itemData['payment_object'] = 'commodity';
                $itemData['tax'] = 'none';

                $receipt['items'][] = $itemData;
            }

            /**
             * Стоимость доставки так же нужно добавить
             */
            if ((float)$shopBill->shopOrder->moneyDelivery->amount > 0) {
                $itemData = [];
                $itemData['name'] = StringHelper::substr($shopOrder->shopDelivery->name, 0, 128);
                $itemData['quantity'] = 1;
                $itemData['tax'] = 'none'; //todo: доработать этот момент
                $itemData['sum'] = $shopOrder->moneyDelivery->amount;
                $itemData['payment_method'] = 'full_payment';
                $itemData['payment_object'] = 'service';

                $receipt['items'][] = $itemData;
            }

            $totalCalcAmount = 0;
            foreach ($receipt['items'] as $itemData) {
                $totalCalcAmount = $totalCalcAmount + ($itemData['sum']);
            }

            $discount = 0;
            if ($totalCalcAmount > (float)$nOutSum) {
                $discount = abs((float)$nOutSum - $totalCalcAmount);
            }

            /**
             * Стоимость скидки
             */
            //todo: тут можно еще подумать, это временное решение
            if ($discount > 0) {
                $discountValue = $discount;
                foreach ($receipt['items'] as $key => $item) {
                    if ($discountValue == 0) {
                        break;
                    }
                    if ($item['sum']) {
                        if ($item['sum'] >= $discountValue) {
                            $item['sum'] = $item['sum'] - $discountValue;
                            $discountValue = 0;
                        } else {
                            $item['sum'] = 0;
                            $discountValue = $discountValue - $item['sum'];
                        }
                    }

                    $receipt['items'][$key] = $item;
                }

                //print_r($receipt);die;
                //$receipt['items'][] = $itemData;
            }


            $arr_encode = json_encode($receipt); // Преобразовываем JSON в строку

            $receipt = urlencode($arr_encode);
            $receipt_urlencode = urlencode($receipt);

            $signatureData = [
                $this->sMerchantLogin,
                $nOutSum,
                $nInvId,
                $receipt,
                $this->sMerchantPass1,
            ];

        } else {
            $signatureData = [
                $this->sMerchantLogin,
                $nOutSum,
                $nInvId,
                $this->sMerchantPass1,
            ];
        }

        //$signature = "{$this->sMerchantLogin}:{$nOutSum}:{$nInvId}:{$this->sMerchantPass1}";
        $signature = implode(":", $signatureData);

        if (!empty($shp)) {
            $signature .= ':'.$this->implodeShp($shp);
        }

        $sSignatureValue = md5($signature);

        $data = [
            'MerchantLogin'  => $this->sMerchantLogin,
            'OutSum'         => $nOutSum,
            'InvId'          => $nInvId,
            'Description'    => $sInvDesc,
            'SignatureValue' => $sSignatureValue,
            'IncCurrLabel'   => $sIncCurrLabel,
            'Email'          => $sEmail,
            'Culture'        => $sCulture,
        ];
        $recipientHtml = '';
        if ($this->is_receipt) {
            $data['Receipt'] = $receipt_urlencode;
            $recipientHtml = '<input type="hidden" name="Receipt" value="' . $receipt_urlencode . '">';
        }

        if (!$this->isLive) {
            $data['isTest'] = 1;
        }

        $url .= '?'.http_build_query($data);

        if (!empty($shp) && ($query = http_build_query($shp)) !== '') {
            $url .= '&'.$query;
        }

        $baseUrl = $this->baseUrl;
        $form = <<<HTML
<html>

<form id="sxform" action='{$baseUrl}' method=POST>
    <input type="hidden" name="MerchantLogin" value="{$this->sMerchantLogin}">
    <input type="hidden" name="OutSum" value="{$nOutSum}">
    <input type="hidden" name="InvId" value="{$nInvId}">
    <input type="hidden" name="Description" value='{$sInvDesc}'>
    <input type="hidden" name="SignatureValue" value="{$sSignatureValue}">
    <input type="hidden" name="IncCurrLabel" value="{$sIncCurrLabel}">
    <input type="hidden" name="Culture" value="{$sCulture}">
    {$recipientHtml}
    <input type="submit" value='Pay'>
</form>
<script type="text/javascript">
    document.getElementById('sxform').submit();
</script>
</html>
HTML
;
        
        return $form;    
        /*\Yii::$app->user->setReturnUrl(\Yii::$app->request->getUrl());
        return \Yii::$app->response->redirect($url);*/
    }

    private function implodeShp($shp)
    {
        ksort($shp);
        foreach ($shp as $key => $value) {
            $shp[$key] = $key.'='.$value;
        }

        return implode(':', $shp);
    }

    public function checkSignature($sSignatureValue, $nOutSum, $nInvId, $sMerchantPass, $shp)
    {
        $signature = "{$nOutSum}:{$nInvId}:{$sMerchantPass}";
        if (!empty($shp)) {
            $signature .= ':'.$this->implodeShp($shp);
        }
        return strtolower(md5($signature)) === strtolower($sSignatureValue);

    }

}