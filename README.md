# yii2-api-paykeeper
Yii2 модуль для связи с API PayKeeper

#Практика использования
```php
$provider = new \iAvatar777\components\PayKeeperApi\PayKeeperApi([
    'url'       => '',
    'user'      => '',
    'password'  => '',
    'secret'    => '',
]);
$data = $provider->createPayment(['pay_amount' => 100]);
$url = $data['invoice_url'];
```
