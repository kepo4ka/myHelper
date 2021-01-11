<?php

namespace Helper;

use Helper\Helper as Helper;

class CurlClient
{
    public $config;

    function __construct($p_config = [])
    {
        $config = [];
        Helper::setVarField($config, $p_config, 'cookiePath', __DIR__ . '/cookie.txt');
        Helper::setVarField($config, $p_config, 'current_user_agent', 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36');

        Helper::setVarField($config, $p_config, 'def_proxy_info', []);
        Helper::setVarField($config, $p_config, 'proxy_list', []);
        Helper::setVarField($config, $p_config, 'query_count', 0);
        Helper::setVarField($config, $p_config, 'sleep_mode', false);
        Helper::setVarField($config, $p_config, 'delay_min', 2);
        Helper::setVarField($config, $p_config, 'delay_max', 5);
        Helper::setVarField($config, $p_config, 'max_try', 2);
        Helper::setVarField($config, $p_config, 'rucaptcha_key', '2d0b9ef88d61355b18a369063e8d60ef');
        Helper::setVarField($config, $p_config, 'enable_db', false);
        Helper::setVarField($config, $p_config, 'project_dir', __DIR__);

        $config['proccess_id'] = substr(md5(microtime()), 0, 5);
        $this->config = $config;
    }

    /**
     * Получить список прокси из string
     * @param $str string string, не разделённый на строки
     * @param string $type Указываем тип прокси насильно
     * @return mixed Список прокси
     */
    public function getProxyListFromStr($str, $type = 'http')
    {
        $this->config['proxy_list'] = [];
        $lines = preg_split('/\n/m', trim($str));

        $proxy_info = array();

        foreach ($lines as $line) {
            if (preg_match('/@/m', $line)) {
                $split = explode('@', $line);
                $auth = trim($split[0]);

                $proxy_part = trim($split[1]);
                $split = explode(' ', $proxy_part);
                $proxy = trim($split[0]);

                if (!empty($split[1])) {
                    $type = trim($split[1]);
                }

            } else {
                $proxy = trim($line);
                $auth = '';
            }
            $proxy_info['full'] = $proxy;
            $proxy_info['auth'] = $auth;
            $proxy_info['type'] = $type;
            $this->config['proxy_list'][] = $proxy_info;
        }
        return $this->config['proxy_list'];
    }

    /**
     * Получить список прокси из файла
     */
    public function getProxyListFile($file = null)
    {
        if (empty($file)) {
            return [];
        }

        $str = file_get_contents($file);

        if (empty($str)) {
            return [];
        }

        return $this->getProxyListFromStr($str);
    }


    /**
     * Задать список прокси
     * @param array $list Массив прокси
     */
    public function updateProxyList($list = [])
    {
        $proxy_list = [];

        foreach ($list as $item) {
            if (!empty($item)) {
                $proxy_list[] = $item;
            }
        }
        $this->config['proxy_list'] = $proxy_list;
    }


    /**
     * Получить свой IP
     * !ВНИМАНИЕ. Функция зависима от работоспособности сайта, который определяет IP
     * @return bool|mixed
     */
    public function getMyIp()
    {
        $url = 'https://api.myip.com/';

        $data = $this->fetch($url);
        if (empty(Helper::checkJson($data))) {
            return false;
        }
        return Helper::json_decode($data);
    }

    /*
     * Проверка работоспособности Google
     */
    public function getGoogle()
    {
        $url = 'https://google.ru/';
        return $this->fetch($url);
    }

    /**
     * Удалить прокси из списка
     * @param $proxy string Ip значение прокси xxx.xxx.xxx.xxx
     * @return bool
     */
    public function deleteProxy($proxy)
    {
        $del_proxy = $proxy;

        if (!empty($proxy['full'])) {
            $del_proxy = $proxy['full'];
        }

        for ($i = 0; $i < count($this->config['proxy_list']); $i++) {
            if ($this->config['proxy_list'][$i]['full'] == $del_proxy) {
                array_splice($this->config['proxy_list'], $i, 1);
                return true;
            }
        }
        return false;
    }

    /**
     * Получить случайное прокси из списка
     * @return bool|mixed
     */
    public function getRandomProxy()
    {
        if (!empty($this->config['proxy_list'])) {
            $index = rand(0, count($this->config['proxy_list']) - 1);
            return $this->config['proxy_list'][$index];
        }
        return false;
    }

    /**
     * Обновить текущий Прокси, который используется
     * @param null $proxy Задать его вручную
     * @return bool
     */
    public function update($proxy = null)
    {
        if (!empty($proxy)) {
            $this->config['def_proxy_info'] = $proxy;
        } else {
            $this->config['def_proxy_info'] = $this->getRandomProxy();
        }

        if (empty($this->config['def_proxy_info'])) {
            return false;
        }

        if (!empty($this->config['user_agents'])) {
            $index = rand(0, count($this->config['user_agents']) - 1);
            $this->config['current_user_agent'] = $this->config['user_agents'][$index];
        }
        @unlink($this->getCookiePath());

        return true;
    }

    /**
     * Получить путь до папки, где хранятся Cookie
     * @return bool|string
     */
    public
    function getCookiePath()
    {
        if (empty($this->config['proccess_id'])) {
            return false;
        }
        Helper::makeDir($this->config['project_dir'] . '/cookies');
        $full_path = $this->config['project_dir'] . '/cookies/' . $this->config['proccess_id'] . '.txt';
        return $full_path;
    }


    /**
     * Выполнить запрос
     * @param $url string Адрес, куда будет отправлен запрос
     * @param null $z Дополнительные параметры запроса
     * @return mixed Полученный ответ
     */
    public function fetch($url, $z = null)
    {

        $ch = curl_init();
        $cookiePath = $this->getCookiePath();

        if (!empty($z['params'])) {
            $url .= '?' . http_build_query($z['params']);
        }

        $useragent = '';
        if (!empty($this->config['current_user_agent'])) {
            $useragent = $this->config['current_user_agent'];
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


        if (!empty($this->config['def_proxy_info'])) {

            curl_setopt($ch, CURLOPT_PROXYTYPE, $this->config['def_proxy_info']['type']);
            curl_setopt($ch, CURLOPT_PROXY, $this->config['def_proxy_info']['full']);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['def_proxy_info']['auth']);
        }

        if (isset($z['refer'])) {
            curl_setopt($ch, CURLOPT_REFERER, $z['refer']);
        }

        if (!empty($z['post']) || !empty($z['json'])) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if (!empty($z['post'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($z['post']));
        }

        if (!empty($z['json'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, Helper::json_encode($z['json']));
        }

        if (!empty($z['app_form'])) {
            $headers = [
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Type: application/x-www-form-urlencoded",
                "cache-control: no-cache"
            ];

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($z['app_json'])) {
            $headers = [
                "application/json, text/plain, */*",
                "Accept-Encoding: gzip, deflate, br",
                "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Type: application/json;charset=UTF-8",
                "cache-control: no-cache",
                "Sec-Fetch-Site: same-origin",
                "Sec-Fetch-Mode: cors",
            ];

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            return $ch;
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (!empty($z['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $z['headers']);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }


    /**
     * Проверить работоспособность прокси
     * @param $proxy array Информация о прокси
     * @return null
     */
    public static function checkProxy($proxy)
    {
        $client = new CurlClient();

        $client->update($proxy);

        $ip_info = $client->getMyIp();

        if (!empty($ip_info['ip'])) {
            return $proxy;
        }
        return null;
    }


    /**
     * Функция `fetch` для использования в асинхронных вызовах
     *
     * @param      $url
     * @param null $proxy Задать прокси для запроса
     * @param null $z
     *
     * @return mixed Ответ от сервера
     */
    public static function fetchAsync($url, $proxy = null, $z = null)
    {
        $client = new CurlClient();
        if (!empty($proxy)) {
            $client->update($proxy);
        }
        return $client->fetch($url, $z);
    }

}


?>