<?php


namespace Helper;

use Exception;

class Helper
{


    /**
     * Выполнить запрос
     * @param $url string Адрес, куда будет отправлен запрос
     * @param null $z Дополнительные параметры запроса
     * @return mixed Полученный ответ
     */
    public static function fetch($url, $z = null)
    {
        global $config;

        $ch = curl_init();
        $cookiePath = self::getCookiePath(1);

        if (!empty($z['params'])) {
            $url .= '?' . http_build_query($z['params']);
        }


        $useragent = $config['current_user_agent'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
//    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));

        if (!empty($config['def_proxy_info'])) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, $config['def_proxy_info']['type']);
            curl_setopt($ch, CURLOPT_PROXY, $config['def_proxy_info']['full']);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $config['def_proxy_info']['auth']);
        }

        if (isset($z['refer'])) {
            curl_setopt($ch, CURLOPT_REFERER, $z['refer']);
        }


        if (!empty($z['is_post'])) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if (!empty($z['post'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $z['post']);
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
     * Получить список аккаунтов из файла
     * @return array Список аккаунтов для авторизации
     */
    public static function getAccounts()
    {
        global $config;
        $config['accounts_list'] = array();

        $url = 'http://localhost/accounts.txt';

        $data = self::fetchNoProxy($url);

        $lines = preg_split('/\n/m', trim($data));

        $info = array();

        foreach ($lines as $line) {
            $split = explode(':', $line);
            $info['login'] = trim($split[1]);
            $info['password'] = trim($split[2]);

            $config['accounts_list'][] = $info;
        }

        return $config['accounts_list'];
    }


    /**
     * Выделить Ip-адрес из строки
     * @param $str string Исходная строка
     * @return bool|mixed Ip-адрес
     */
    public static function getIpReg($str)
    {
        $matches = array();
        preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/m', $str, $matches);

        if (!empty($matches[0])) {
            return $matches[0];
        }
        return false;
    }


    /**
     * Очистить Cookie
     * @return bool Результат операции
     */
    public static function clearCookie()
    {
        try {
            $cookiePath = self::getCookiePath();
            file_put_contents($cookiePath, '');
        } catch (Exception $exception) {
//            self::logDB($exception, 'clearCookie - error', 'error');
        }
        return true;
    }


    /**
     * Получить результат поиска регулярного выражения
     * @param $re string Регулярное выражение
     * @param $str string Исходная строка
     * @param int $index Индекс группы совпадений
     * @return mixed|string Совпадение
     */
    public static function checkRegular($re, $str, $index = 1)
    {
        $result = '';
        $matches = array();

        if (preg_match($re, $str, $matches)) {
            if (!empty($matches[$index])) {
                $result = $matches[$index];
            }
        }
        return $result;
    }


    /**
     * Проверка заполненности массива
     * @param $array array Исходный массив
     * @return bool Результут проверки
     */
    public static function checkArrayFilled($array)
    {
        foreach ($array as $key => $value) {
            if (empty($array[$key])) {
                return false;
            }
        }
        return true;
    }


    /**
     * Функция Random, аналогичная функции в JavaScript
     * @return float|int Случайное значение
     */
    public static function jsRandom()
    {
        return mt_rand() / (mt_getrandmax() + 1);
    }


    /**
     * Удалить апростроф
     * @param $string
     * @return bool|string
     */
    public static function delApostrof($string)
    {
        $bad_symbol = '"';
        $count = substr_count($string, $bad_symbol);
        $last_symbol = substr($string, -1);


        if ($count % 2 == 1 && $last_symbol == $bad_symbol) {
            $string = substr($string, 0, -1);
        }
        return $string;
    }


    /**
     * Вывод значения для отладки
     * @param $var mixed Переменная
     * @param bool $no_exit Прерывать ли работу всего скрипта
     */
    public static function echoVarDumpPre($var, $no_exit = false)
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        if (!$no_exit) {
            exit;
        }
    }


    /**
     * Вывод в виде JSON
     * @param $var mixed Переменная для вывода
     */
    public static function echoBr($var)
    {
        echo json_encode($var, JSON_UNESCAPED_UNICODE);
        echo '<hr>';
    }


    /**
     * Получить путь до файлов Cookie
     * @param bool $second Использовать обычный или обратный слэш, при генерации Пути
     * @return bool|string Путь
     */
    public static function getCookiePath($second = false)
    {
        global $config;

        if (empty($config['proccess_id'])) {
            return false;
        }

       self::makeDir(dirname(__FILE__) . '\cookies');

        $full_path = dirname(__FILE__) . '\cookies/' . $config['proccess_id'] . '.txt';
        if ($second) {
            $full_path = dirname(__FILE__) . '\cookies\\' . $config['proccess_id'] . '.txt';
        }
        return $full_path;
    }


    /**
     * Получить Адрес сайта
     * @return string Адрес
     */
    public static function base_url()
    {
        return strtok(sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            $_SERVER['REQUEST_URI']
        ), '?');
    }


    /**
     * Получить последнюю часть Url
     * @param $url string Url
     * @param string $separator Разделитель
     * @return bool|string Часть Url
     */
    public static function urlLastPart($url, $separator = '/')
    {
        if (empty($url)) {
            return false;
        }

        $split = explode($separator, $url);

        if (empty($split)) {
            return false;
        }
        $part = $split[count($split) - 1];
        return $part;
    }


    /**
     * Создать папку в случае её отсутствия
     * @param $path string Путь до папки
     * @return bool Результат операции
     */
    public static function makeDir($path)
    {
        return is_dir($path) || mkdir($path);
    }


    public static function inputFilter($var)
    {
        $var = preg_replace('/[^\w]/m', '', $var);
        return $var;
    }


    public static function resize_image($file, $w, $h, $type = 'jpeg', $crop = FALSE)
    {
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        if ($crop) {
            if ($width > $height) {
                $width = ceil($width - ($width * abs($r - $w / $h)));
            } else {
                $height = ceil($height - ($height * abs($r - $w / $h)));
            }
            $newwidth = $w;
            $newheight = $h;
        } else {
//        $newwidth = $w;
//        $newheight = $h;
            if ($w / $h > $r) {
                $newwidth = $h * $r;
                $newheight = $h;
            } else {
                $newheight = $w / $r;
                $newwidth = $w;
            }
        }
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($file);
                break;
            case 'png':
                $src = imagecreatefrompng($file);
                break;
            case 'bmp':
                $src = imagecreatefrombmp($file);
                break;
            default:
                return false;
        }

        $dst = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

        return $dst;
    }


    /**
     * Получить список изображений в папке
     * @param string $directory Путь к папке
     * @return array Список изображений
     */
    public static function getDirectoryImages($directory = '../assets/images/')
    {
        $images_array = array();
        $allowed_types = array("jpg", "jpeg", "bmp", "png", "gif", "ico", "svg");  //разрешеные типы изображений

        //пробуем открыть папку
        $dir_handle = @opendir($directory) or die("Ошибка при открытии папки !!!");

        while ($file = readdir($dir_handle))    //поиск по файлам
        {
            if ($file == "." || $file == "..") {
                continue;
            }

            $type = explode('.', $file);

            if (count($type) !== 2) {
                continue;
            }
            $type = $type[1];

            if (!in_array($type, $allowed_types)) {
                continue;
            }

            $image = array();
            $image['path'] = $directory . $file;
            $image['abs_path'] = substr($image['path'], 3);
            $image['abs_path1'] = substr($image['abs_path'], 3);
            $image['title'] = $file;
            $images_array[] = $image;
        }

        closedir($dir_handle);  //закрыть папку
        return $images_array;
    }


    /**
     * Транслитерация
     * @param $string - Строка на кирилице
     * @return string - Строка для латинице
     */
    public static function rus2translit($string)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '\'', 'ы' => 'y', 'ъ' => '\'',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }

    /**
     * Подготовка строки для возможности отображения в адресной строке
     * @param $str - исходная строка
     * @return mixed|string строка для url
     */
    public static function str2url($str)
    {
        // переводим в транслит
        $str = self:: rus2translit($str);
        // в нижний регистр
        $str = strtolower($str);
        // заменям все ненужное нам на "-"
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        // удаляем начальные и конечные '-'
        $str = trim($str, "-");
        return $str;
    }


    public static function json_encodeKirilica($val)
    {
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    }


    /**
     * Прочитать файл в родительской папке, зная только относительно расположение относительно текущего файла скрипта
     * @param $file_name string Имя файла с расширением
     * @param int $level На сколько уровней выше находится выше
     * @param string $protocol
     * @return bool|string Содержимое файла
     */
    public static function readFileOverDir($file_name, $level = 1, $protocol = 'http')
    {
        $dir_name = dirname($_SERVER['SCRIPT_NAME']);

        $dir =self:: recDirName($_SERVER['SCRIPT_NAME'], 0);


        if ($dir_name == '\\') {
            $dir_name = '/';
        }

        $dir = "$protocol://" . $_SERVER['SERVER_NAME'] . $dir_name . '/' . $file_name;

        $dir = preg_replace('/\/\//m', '/', $dir);
        $dir = preg_replace("/$protocol:\//m", "$protocol://", $dir);

        $data = file_get_contents($dir);

        return $data;
    }

    public static function recDirName($dir_name, $cur_level, $level = 1)
    {
        if ($cur_level >= $level || $dir_name == '\\') {
            return $dir_name;
        } else {
            $cur_level++;
            $dir_name = dirname($dir_name);
            return self::recDirName($dir_name, $cur_level);
        }
    }


    public static function myReadFile($file_name)
    {
        $content = [];

        try {
            if ($file = fopen($file_name, "r")) {
                while (!feof($file)) {
                    $line = trim(fgets($file));
                    $content[] = $line;
                }
                fclose($file);
            }
        } catch (Exception $exception) {

        }
        return $content;
    }

}