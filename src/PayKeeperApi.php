<?php

namespace HiLsAger\components\PayKeeperApi;

use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\helpers\Json;

/**
 * https://paykeeper.ru/
 * call-back - https://docs.paykeeper.ru/metody-integratsii/priyom-post-opoveshhenij/
 * test card - MasterCard	5100 4772 8001 3333	03 / 23	IVAN IVANOV	333
 */
class PayKeeperApi extends \yii\base\BaseObject
{
    public $module;
    public $url;
    public $user;
    public $password;
    public $secret;

    /** @var string название ключа в кеше */
    public $key = '\HiLsAger\components\PayKeeperApi\PayKeeperApi::$key';

    /** @var int Время хранения токена в сек */
    public $keySaveTime;

    public static function createObject($params)
    {
        return new self($params);
    }

    public function init()
    {
        parent::init();
        $this->module = Module::getInstance();
        if (
            empty($this->login)
            || empty($this->url)
            || empty($this->user)
            || empty($this->password)
            || empty($this->secret
            )) {
            throw new InvalidConfigException('Модуль настроен не правильно пожалуйсто прочтите документацию');
        }
    }


    /**
     * @param array $params
     *[
     * "pay_amount"   => 42.50,
     * "clientid"     => "Иванов Иван Иванович",
     * "orderid"      => "Заказ № 11",
     * "client_email" => "test@example.com",
     * "service_name" => "Услуга",
     * "client_phone" => "8 (910) 123-45-67"
     * ]
     * @return array
     */
    public function createPayment($params)
    {
        $data = $this->callToken('POST', '/change/invoice/preview/', $params);

        return [
            'invoice_id'  => $data['invoice_id'],
            'invoice_url' => $data['invoice_url'],
        ];
    }

    public function callToken($type, $path, $params)
    {
        $token = $this->getToken();

        $params['token'] = $token;
        $data = $this->call($type, $path, $params);

        return $data;
    }

    /**
     * Возвращает токен из кеша, если там нет то вызывает фугкцию получения токена
     *
     * @return string
     */
    public function getToken()
    {
        $key = [
            $this->key,
            $this->url,
            $this->user,
        ];
        $token = \Yii::$app->cache->get($key);
        if ($token === false) {
            $token = $this->_getToken();
            \Yii::$app->cache->set($key, $this->keySaveTime);
        }

        return $token;
    }

    /**
     * получает токен через API
     *
     * @return string
     */
    public function _getToken()
    {
        $data = $this->call('GET', '/info/settings/token/');

        return $data['token'];
    }

    public function call($type, $path, $params = [])
    {
        $client = new \yii\httpclient\Client(['baseUrl' => $this->url]);
        $user = $this->user;
        $password = $this->password;

        $base64 = base64_encode("$user:$password");

        if ($type == 'GET') {
            $request = $client->get($path, $params);
        } else {
            $request = $client->post($path, $params);
        }
        $request->headers
            ->add('Content-Type', 'application/x-www-form-urlencoded')
            ->add('Authorization', 'Basic ' . $base64);
        $response = $request->send();

        $data = Json::decode($response->content);

        return $data;
    }
}