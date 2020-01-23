<?php


namespace Helper;

use Exception;

class Rucaptcha
{
    public static function getRucaptchaResolve($apiKey, $captcha_id)
    {
        $result = file_get_contents("https://rucaptcha.com/res.php?key=" . $apiKey . "&action=get&id=" . $captcha_id);

        return Helper::checkRegular('/OK\|(.+)?/', $result);
    }

    public static function getRucaptchaResult($apiKey, $google_key, $auth_url)
    {
        if (empty($apiKey) || empty($google_key) || empty($auth_url)) {
            return false;
        }

        $retrieve = file_get_contents("http://rucaptcha.com/in.php?key=" . $apiKey . "&method=userrecaptcha&googlekey=" . $google_key . "&pageurl=" . $auth_url);

        if (!Helper::checkRegular('/OK/', $retrieve, 0)) {
            Helper::echoBr('Не получен id капчи');
            Helper::echoVarDumpPre($retrieve);
        }

        $captcha_id = Helper::checkRegular('/OK\|(.+)?/', $retrieve);

        sleep(30);
        $captcha_result = self::getRucaptchaResolve($apiKey, $captcha_id);

        $k = 0;
        while (empty($captcha_result) || $k < 3) {
            sleep(5);
            $captcha_result = self::getRucaptchaResolve($apiKey, $captcha_id);
            $k++;
        }
        return $captcha_result;
    }

}