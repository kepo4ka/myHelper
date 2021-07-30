<?php


namespace Helper;

use DateTime;
use Exception;
use SimpleXMLElement;
use Throwable;

class Helper
{
    /**
     * Выполнить запрос
     *
     * @param      $url string Адрес, куда будет отправлен запрос
     * @param null $z   Дополнительные параметры запроса
     *
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

        $useragent = '';
        if (!empty($config['current_user_agent'])) {
            $useragent = $config['current_user_agent'];
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


        if (!empty($config['def_proxy_info']) && empty($z['no_proxy'])) {
            curl_setopt(
                $ch, CURLOPT_PROXYTYPE, $config['def_proxy_info']['type']
            );
            curl_setopt($ch, CURLOPT_PROXY, $config['def_proxy_info']['full']);
            curl_setopt(
                $ch, CURLOPT_PROXYUSERPWD, $config['def_proxy_info']['auth']
            );
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($z['json']));
        }


		if (!empty($useragent))
		{
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		}
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		if (!empty($cookiePath))
		{
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
		}
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (!empty($z['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $z['headers']);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }


    /**
     * Задать стандартный набор заголовков для запроса
     *
     * @param $ch mixed Объект curl
     *
     * @return mixed Обновлённый Объект curl
     */
    public static function setHeaders_Form($ch)
    {
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Type: application/x-www-form-urlencoded",
            "cache-control: no-cache"
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        return $ch;
    }

    /**
     * Задать заголовки для передачи данных в формате JSON
     *
     * @param $ch
     *
     * @return mixed
     */
    public static function setHeaders_JSON($ch)
    {
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

    /**
     * Считать случайную строку из файла
     *
     * @param $file string Название файла
     *
     * @return string Случайная строка
     */
    public
    static function getRandLineFromFile(
        $file
    ) {
        $line = '';
        try {
            $f_contents = @file($file);
            $line = @$f_contents[rand(0, count($f_contents) - 1)];
        } catch (Throwable $throwable) {

        } catch (Exception $exception) {

        }
        return $line;
    }

    /**
     * Получить случайный UserAgent из файла
     *
     * @param string $file Файл со списком агентов
     *
     * @return string Случайный UserAgent
     */
    public
    static function getRandUserAgent(
        $file = ''
    ) {
        if (empty($file)) {
            $file = __DIR__ . '/assets/useragents.txt';
        }

        $line = '';
        try {
            $f_contents = @file($file);
            $line = @$f_contents[rand(0, count($f_contents) - 1)];
        } catch (Throwable $throwable) {

        } catch (Exception $exception) {

        }
        return $line;
    }


    /**
     * Выделить Ip-адрес из строки
     *
     * @param $str string Исходная строка
     *
     * @return bool|mixed Ip-адрес
     */
    public
    static function getIpReg(
        $str
    ) {
        $matches = array();
        preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/m', $str, $matches);

        if (!empty($matches[0])) {
            return $matches[0];
        }
        return false;
    }


    /**
     * Очистить Cookie
     *
     * @return bool Результат операции
     */
    public
    static function clearCookie()
    {
        try {
            $cookiePath = self::getCookiePath();
            file_put_contents($cookiePath, '');
        } catch (Throwable $throwable) {

        } catch (Exception $exception) {
//            self::logDB($exception, 'clearCookie - error', 'error');
        }
        return true;
    }


    /**
     * Получить результат поиска регулярного выражения
     *
     * @param     $re    string Регулярное выражение
     * @param     $str   string Исходная строка
     * @param int $index Индекс группы совпадений
     *
     * @return mixed|string Совпадение
     */
    public
    static function checkRegular(
        $re, $str, $index = 1
    ) {
        $result = '';
        try {
            $matches = array();

            if (preg_match($re, $str, $matches)) {
                if (!empty($matches[$index])) {
                    $result = $matches[$index];
                }
            }
        } catch (Throwable $throwable) {

        } catch (Exception $exception) {

        }
        return $result;
    }


    /**
     * Проверка заполненности массива
     *
     * @param $array array Исходный массив
     *
     * @return bool Результут проверки
     */
    public
    static function checkArrayFilled(
        $array
    ) {
        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $key => $value) {
            if (empty($array[$key])) {
                return false;
            }
        }
        return true;
    }


    /**
     * Функция Random, аналогичная функции в JavaScript
     *
     * @return float|int Случайное значение
     */
    public
    static function jsRandom()
    {
        return mt_rand() / (mt_getrandmax() + 1);
    }


    /**
     * Удалить апростроф
     *
     * @param $string
     *
     * @return bool|string
     */
    public
    static function delApostrof(
        $string
    ) {
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
     *
     * @param      $var     mixed Переменная
     * @param bool $no_exit Прерывать ли работу всего скрипта
     */
    public
    static function echoVarDumpPre(
        $var, $no_exit = false
    ) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        if (!$no_exit) {
            exit;
        }
    }


    /**
     * Вывод в виде JSON
     *
     * @param $var mixed Переменная для вывода
     */
    public
    static function echoBr(
        $var
    ) {
        echo json_encode($var, JSON_UNESCAPED_UNICODE);
        echo '<hr>';
    }


    /**
     * Получить путь до файлов Cookie
     *
     * @param bool $second Использовать обычный или обратный слэш, при генерации Пути
     *
     * @return bool|string Путь
     */
    public
    static function getCookiePath(
        $second = false
    ) {
        global $config;

        if (empty($config['proccess_id'])) {
            return false;
        }

		if (empty($config['project_dir']))
		{
			return false;
		}

        self::makeDir($config['project_dir'] . '/cookies');

        $full_path = $config['project_dir'] . '/cookies/'
            . $config['proccess_id'] . '.txt';
        if ($second) {
            $full_path = $config['project_dir'] . '/cookies/'
                . $config['proccess_id'] . '.txt';
        }

        return $full_path;
    }


    /**
     * Получить Адрес сайта
     *
     * @return string Адрес
     */
    public
    static function base_url()
    {

        return strtok(
            sprintf(
                "%s://%s%s",
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https'
                    : 'http',
                $_SERVER['SERVER_NAME'],
                $_SERVER['REQUEST_URI']
            ), '?'
        );
    }


    /**
     * Получить Домен сайта
     *
     * @return string Домен
     */
    public static function baseDomain()
    {
        $base = self::base_url();
        if (empty($base)) {
            return false;
        }

        $parsed = @parse_url($base);

        if (empty($parsed) || empty($parsed['scheme'])
            || empty($parsed['host'])
        ) {
            return false;
        }

        $domain = $parsed['scheme'] . '://' . $parsed['host'];

        return $domain;
    }


    /**
     * Получить последнюю часть Url
     *
     * @param        $url       string Url
     * @param string $separator Разделитель
     *
     * @return bool|string Часть Url
     */
    public
    static function urlLastPart(
        $url, $separator = '/'
    ) {
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
     *
     * @param $path string Путь до папки
     *
     * @return bool Результат операции
     */
    public
    static function makeDir(
        $path
    ) {
        return is_dir($path) || mkdir($path);
    }


    /**
     * Валидация значения
     *
     * @param      $input string Исходное значение
     * @param null $link  MYSQLI LINK
     *
     * @return mixed|string "Очищенное" значение
     */
    public
    static function inputFilter(
        $input, $type = null
    ) {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');
        $input = html_entity_decode($input);

        $input = strip_tags($input);

        $input = self::regexpFilter($input);
        $input = self::scriptFilter($input);

        if (!empty($type) && is_string($type)) {
            $array = str_split($type, 1);

            $regex = "/[^";

            foreach ($array as $item) {
                $regex .= '\\' . $item;
            }
            $regex .= ']/u';
            $input = preg_replace($regex, '', $input);
        }

        return $input;
    }


    /**
     * Очистка от js тэга
     *
     * @param $input string Исходное значение
     *
     * @return mixed "Очищенное" значение
     */
    public
    static function scriptFilter(
        $input
    ) {
        return str_replace('script', '', $input);
    }


    /**
     * Очистка строки от "плохих" символов
     *
     * @param $input string Исходная строка
     *
     * @return string "Очищенная" строка
     */
    public
    static function regexpFilter(
        $input
    ) {
        $input = preg_replace(
            '/[^_\w\s\-+*\/=,.@#$&(){}\[\]!:\\\]/u', '', $input
        );
        return preg_replace('/0x/u', '', $input);
    }


    /**
     * Форматирование строки перед выводом
     *
     * @param $text string Исходная строка
     *
     * @return mixed|string Отформатированная строка
     */
    public static function readableText($text)
    {
        $formatted_text = preg_replace('/[_-]/', ' ', $text);
        $formatted_text = ucwords($formatted_text);
        return $formatted_text;
    }


    /**
     * UTF8 encode
     *
     * @param $d
     *
     * @return array|string
     */
    public
    static function utf8ize(
        $d
    ) {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = self::utf8ize($v);
            }
        } else if (is_string($d)) {
            return utf8_encode($d);
        }
        return $d;
    }

    /**
     * Получить максимальное значение по ключу в ассоциативном массиве
     *
     * @param $array    array Исходный массив
     * @param $key_name string Ключ
     *
     * @return mixed|string Максимум
     */
    public
    static function max_array_value(
        $array, $key_name
    ) {
        $max = '';
        foreach ($array as $key => $value) {
            $make_array[] = $value[$key_name];
            $max = max($make_array);
        }
        return $max;
    }


    /**
     * Получить минимальное значение по ключу в ассоциативном массиве
     *
     * @param $array    array Исходный массив
     * @param $key_name string Ключ
     *
     * @return mixed|string Минимум
     */
    public
    static function min_array_value(
        $array, $key_name
    ) {
        $max = '';
        foreach ($array as $key => $value) {
            $make_array[] = $value[$key_name];
            $max = min($make_array);
        }
        return $max;
    }

    /**
     * Проверить наличие элемента в ассоциативном массиве
     *
     * @param $list     array Массив
     * @param $list_key string Ключ, по которому идёт проверка
     * @param $value    mixed Контрольное значение
     *
     * @return bool Присутствует ли элемент в массиве
     */
    public
    static function checkListItemExist(
        $list, $list_key, $value
    ) {
        foreach ($list as $item) {
            if ($item[$list_key] == $value) {
                return $item;
            }
        }
        return false;
    }


    /**
     * Перенаправление с помощью JS
     *
     * @param $url string Ссылка, на которую идёт перенаправление
     */
    public
    static function locationJs(
        $url
    ) {
        ?>
        <script>
            location.href = '<?=$url?>';
        </script>
        <?php
    }


    /**
     * Изменить размер картинки
     *
     * @param        $file string Название файла
     * @param        $w    integer|double Требуемая ширина
     * @param        $h    integer|double Требуемая высота
     * @param string $type Расширение файла
     * @param bool   $crop Нужно ли делать обрезание
     *
     * @return bool|resource Результат обработки
     */
    public
    static function resize_image(
        $file, $w, $h, $type = 'jpeg', $crop = false
    ) {
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
        imagecopyresampled(
            $dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height
        );

        return $dst;
    }


    /**
     * Получить список изображений в папке
     *
     * @param string $directory Путь к папке
     *
     * @return array Список изображений
     */
    public
    static function getDirectoryImages(
        $directory = '../assets/images/'
    ) {
        $images_array = array();
        $allowed_types = array(
            "jpg", "jpeg", "bmp", "png", "gif", "ico", "svg"
        );  //разрешеные типы изображений

        //пробуем открыть папку
        $dir_handle = @opendir($directory)
        or die("Ошибка при открытии папки !!!");

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
     * Получить список файлов в папке
     *
     * @param string $directory Путь к папке
     *
     * @return array Список изображений
     */
    public
    static function getFiles(
        $directory
    ) {
        $files = [];

        //пробуем открыть папку
        $dir_handle = @opendir($directory)
        or die("Ошибка при открытии папки ${directory}!!!");

        while ($file = readdir($dir_handle))    //поиск по файлам
        {
            if ($file == "." || $file == "..") {
                continue;
            }

            $type = explode('.', $file);

            if (count($type) !== 2) {
                continue;
            }


            $item = array();
            $item['path'] = $directory . $file;
            $item['abs_path'] = @substr($item['path'], 3);
            $item['abs_path1'] = @substr($item['abs_path'], 3);
            $item['title'] = $file;
            $item['info'] = $type;

            $files[] = $item;
        }
        closedir($dir_handle);  //закрыть папку

        return $files;
    }

    /**
     * Проверить выполнен ли запрос с помощью AJAX (внимание: заголовки могут быть подменены пользователем, поэтому не всегда достоверно)
     *
     * @return bool Выполнено с помощью ajax
     */
    public static function isAjax()
    {
        $with = '';

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $with = $_SERVER['HTTP_X_REQUESTED_WITH'];
        }

        $is_ajax = 'xmlhttprequest' == strtolower($with);
        return $is_ajax;
    }

    /**
     * Транслитерация
     *
     * @param $string - Строка на кирилице
     *
     * @return string - Строка для латинице
     */
    public
    static function rus2translit(
        $string
    ) {
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
     *
     * @param $str - исходная строка
     *
     * @return mixed|string строка для url
     */
    public
    static function str2url(
        $str
    ) {
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

    /**
     * Закодировать в Json с использованием Unicode
     *
     * @param $val
     *
     * @return string
     */
    public static function json_encode($val)
    {
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Декодировать Json в ассоциативный массив
     *
     * @param $val string Исходный Json
     *
     * @return mixed Массив
     */
    public static function json_decode($val)
    {
        return json_decode($val, true);
    }

    // Дублирование функции `json_encode`
    // Сохранение функции для обратной совместимости
    public
    static function json_encodeKirilica(
        $val
    ) {
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    }


    /**
     * Прочитать файл в родительской папке, зная только относительно расположение относительно текущего файла скрипта
     *
     * @param        $file_name string Имя файла с расширением
     * @param int    $level     На сколько уровней выше находится выше
     * @param string $protocol
     *
     * @return bool|string Содержимое файла
     */
    public
    static function readFileOverDir(
        $file_name, $level = 1, $protocol = 'http'
    ) {
        $dir_name = dirname($_SERVER['SCRIPT_NAME']);

        $dir = self:: recDirName($_SERVER['SCRIPT_NAME'], 0);


        if ($dir_name == '\\') {
            $dir_name = '/';
        }

        $dir = "$protocol://" . $_SERVER['SERVER_NAME'] . $dir_name . '/'
            . $file_name;

        $dir = preg_replace('/\/\//m', '/', $dir);
        $dir = preg_replace("/$protocol:\//m", "$protocol://", $dir);

        $data = file_get_contents($dir);

        return $data;
    }


    public
    static function recDirName(
        $dir_name, $cur_level, $level = 1
    ) {
        if ($cur_level >= $level || $dir_name == '\\') {
            return $dir_name;
        } else {
            $cur_level++;
            $dir_name = dirname($dir_name);
            return self::recDirName($dir_name, $cur_level);
        }
    }

    /**
     * Прочитать файл в виде массива строк
     *
     * @param $file_name string Название файла
     *
     * @return array Массив строк
     */
    public
    static function myReadFile(
        $file_name
    ) {
        $content = [];

        try {
            if ($file = fopen($file_name, "r")) {
                while (!feof($file)) {
                    $line = trim(fgets($file));
                    $content[] = $line;
                }
                fclose($file);
            }
        } catch (Throwable $throwable) {

        } catch (Exception $exception) {

        }
        return $content;
    }

    /**
     * Получить часть зашифрованных в md5 данных
     *
     * @param     $mixed  mixed Данные
     * @param int $length Длина полученной строки
     *
     * @return bool|string Краткий md5
     */
    public
    static function getShortMd5(
        $mixed, $length = 5
    ) {
        return substr(md5(json_encode($mixed)), 0, $length);
    }

    public
    static function e(
        $str
    ) {
        return htmlspecialchars($str, ENT_QUOTES, 'utf-8');
    }


    /**
     * Получить результут функции var_dump в виде переменной
     *
     * @param $variable mixed Наблюдаемая переменная
     *
     * @return string Дамп переменной
     */
    public
    static function getvarDumpPre(
        $variable
    ) {
        ob_start();
        echo "<pre>";
        var_dump($variable);
        echo "</pre>";

        return ob_get_clean();

    }

    /**
     * Сохранить в сессии данные $_POST
     */
    public static function setPostToSession($true__start_fields = null)
    {
        if (!empty($_POST)) {
            foreach ($_POST as $key => $item) {
                if (empty($item)) {
                    continue;
                }

                if (!empty($true__start_fields)) {
                    if (!in_array($key, $true__start_fields)) {
                        continue;
                    }
                }

                if (strlen($item) > 1000) {
                    continue;
                }

                $_SESSION[$key] = self::inputFilter($item);
            }
        }
    }


    /**
     * Получить из сесси сохранённые данные $_POST
     *
     * @return array|mixed Данные $_POST
     */
    public static function getPostFromSession()
    {
        $data = [];
        if (empty($_SESSION)) {
            return $data;
        }

        foreach ($_SESSION as $key => $item) {
            $data[$key] = $item;
        }
        return $data;
    }

    /**
     * Очистить сессию
     */
    public static function clearSession()
    {
        session_unset();
        session_destroy();
    }


    /**
     * Записать данные в Log файл
     *
     * @param        $data  mixed Данные для записи
     * @param string $title Заголовок
     * @param string $type  Тип
     */
    public static function logFile(
        $data, $title = 'Info', $type = 'info', $path = ''
    ) {
        global $config;

        if (empty($path)) {
            $path = __DIR__;
        }

        $log_file = "$path/log.json";

        if (empty($type)) {
            $type = 'info';
        }

        @$old = json_decode(file_get_contents($log_file), true);

        if (!empty($old) && count($old) > 20) {
            array_pop($old);
        }

        $element = array();
        $element['date'] = date('Y-m-d H:i:s');
        $element['content'] = print_r($data, true);

        if (!is_string($data)) {
            $element['json'] = json_encode($data);
        }

        $element['type'] = $type;
        $element['title'] = $title;

        if (!empty($config['proccess_id'])) {
            $element['proccess'] = $config['proccess_id'];
        }

        if (empty($old)) {
            $old[] = $element;
        } else {
            array_unshift($old, $element);
        }

        file_put_contents(
            $log_file, json_encode($old, JSON_UNESCAPED_UNICODE), LOCK_EX
        );
    }

    /**
     * Удалить директорию вместо с содержимым
     *
     * @param $dirPath string Путь до директории
     *
     * @return bool Удалось ли удалить директорию
     * @throws Exception Ошибка при удалении
     */
    public static function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new Exception("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '\\') {
            $dirPath .= '\\';
        }


        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
        return true;
    }

    /**
     * Получить списко файлов в директории
     *
     * @param $dir string Путь к директории
     *
     * @return array Список файлов
     */
    public static
    function scanDirFiltered(
        $dir
    ) {
        $filtered_files = [];
        $files = scandir($dir);
        foreach ($files as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $filtered_files[] = $item;
        }
        return $filtered_files;
    }


    /**
     * Валидация поля
     *
     * @param        $field    string Название поля
     * @param        $required bool Является ли Поле обязательным для заполнения
     * @param string $type     Предполагаемый тип Поля
     *
     * @return array Прошло ли Поле проверку
     */
    public static function checkField(
        $info
        = [
            'field' => '', 'required' => true, 'form_name' => 'formdata',
            'type'  => '', 'method' => 'post', 'regex' => '[^\w\-\.\,\s]+'
        ]
    ) {

        $form_name = $info['form_name'];
        $field = $info['field'];
        $type = $info['type'];
        $method = $info['method'];
        $regex = "/" . $info['regex'] . "/u";
        $required = $info['required'];

        $request = $_REQUEST;
        switch ($method) {
        case 'post':
            $request = $_POST;
            break;
        case 'get':
            $request = $_GET;
            break;
        }


        if (isset($type)) {
            switch ($type) {
            case 'email':
                $regex = "/[^\w\-@\.]+/u";
                break;
            case 'number':
                $regex = "/[^+\d-()\s]/";
                break;
            case "hash":
                $regex = "/[^\w\$\.\/\-]+/";
                break;
            }
        }

        $res = array();
        $res['type'] = true;
        $res['value'] = '';

        if (!isset($request[$form_name][$field])
            || empty($request[$form_name][$field])
        ) {
            if ($required) {
                $res['type'] = false;
            }
        } else {
            $val = trim($request[$form_name][$field]);

            $match = preg_match($regex, $val, $matches);

            if ($match) {
                $res['type'] = false;

            } else {
                $res['type'] = true;
                $res['value'] = $val;
            }
        }

        return $res;
    }


    /**
     * Проверить валидность Капчи
     *
     * @return bool Валидна ли Капча
     */
    public static function checkRecaptcha($captcha, $secretKey)
    {
        // post request to server
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret='
            . urlencode($secretKey) . '&response=' . urlencode($captcha);
        $recaptcha_response = file_get_contents($url);

        $responseKeys = json_decode($recaptcha_response, true);

        if (!$responseKeys['success']) {
            return false;
        }
        return true;
    }

    /**
     * Попытаться декодировать из json
     *
     * @param $str string Строка Json
     *
     * @return bool Удалось ли
     */
    public static function checkJson($str)
    {
        $json = json_decode($str);
        return $json && $str != $json;
    }

    /**
     * Получить IP пользователя
     *
     * @return mixed Полученный IP
     */
    public static function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Вспомогательная функция
     * Заполнить xml элементами массива
     *
     * @param SimpleXMLElement $object
     * @param array            $data
     */
    private function _array_to_xml(SimpleXMLElement $object, array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_integer($key)) {
                    $key = 'item';
                }

                $new_object = $object->addChild($key);
                self::_array_to_xml($new_object, $value);
            } else {
//            if ($key == (int)$key) {
//                $key = "$key";
//            }

                $object->addChild($key, $value);
            }
        }
    }

    /**
     * Вспомогательная функция
     * Конвертация из xml в массив
     *
     * @param string $xml
     *
     * @return mixed
     */
    private function _xml_to_array($xml)
    {
        $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);
        return $array;
    }

    /**
     * Вспомогательная функция
     * При конвертации внутри массива сохдаётся лишняя `прослойка` в виде ещё одного уровня в массиве
     * Функция удаляет её
     *
     * @param        $array array Исходный массив
     * @param string $name  Название
     *
     * @return array
     */
    private function _clear_xml_array(&$array, $name = 'item')
    {
        foreach ($array as $key => $value) {
            if ($key == $name) {
                if (!empty($value[0])) {
                    $array = $value;
                } else {
                    $array = [$value];
                }
                break;
            }
        }

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = self::_clear_xml_array($value);
            }
        }
        return $array;
    }


    public function arrayToXml(array $array)
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document></Document>'
        );
        self::_array_to_xml($xml, $array);

        return $xml->asXML();
    }

    public function XmlToArray($xml, $name = 'item', $clear = true)
    {
        $array = self::_xml_to_array($xml);
        if (!empty($clear)) {
            $array = self::_clear_xml_array($array, $name);
        }

        return $array;
    }

    /**
     * Расчитать отношений двух величин
     *
     * @param $new mixed Первая величина
     * @param $old mixed Вторая величина
     *
     * @return float|int
     */
    public static function getRatio($new, $old)
    {
        $new = (float)$new;
        $old = (float)$old;
        if ($old == 0) {
            if ($new == 0) {
                return 0;
            }
            return 100;
        }

        $new_value = (($new / $old) - 1) * 100;

        return round($new_value, 1);
    }

    /**
     * Сгенерировать массив, заполненный случайными числами
     *
     * @param $count int Длина массива
     * @param $min   int Минимально возможное значение элемента массива
     * @param $max   int Максимально возможное значение элемента массива
     *
     * @return array Сгенерированный массив
     */
    public static function generateRandomArray($count, $min, $max)
    {
        $array = [];

        while (count($array) < $count) {
            $number = rand($min, $max);
            $is_exist = false;
            for ($i = 0, $iMax = count($array); $i < $iMax; $i++) {
                if ($number === $array[$i]) {
                    $is_exist = true;
                    break;
                }
            }
            if (!$is_exist) {
                $array[] = $number;
            }
        }

        return $array;
    }

    /**
     * Проверка является ли массив Ассоциативным
     * https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
     *
     * @param array $arr
     *
     * @return bool
     */
    public static function isAssoc(array $arr)
    {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }


    public static function setVar(&$var, $value, $other = null)
    {
        if (!empty($value)) {
            $var = $value;
        } elseif (!empty($other)) {
            $var = $other;
        }
        return $var;
    }


    public static function setVarField(&$var, $p_var, $field, $other = null)
    {
        if (!empty($p_var[$field])) {
            $var[$field] = $p_var[$field];
        } elseif (!empty($other)) {
            $var[$field] = $other;
        }
        return $var;
    }

    public static function formatDate($date)
    {
        return date('Y-m-d H:i:s', strtotime($date));
    }
	
	 public static function formatDateFromSeconds($seconds)
    {
        return date('Y-m-d H:i:s', $seconds);
    }

    public static function getUrlLastPart()
    {
        $matches = array();
        $url = preg_match(
            "/\/([\w\-]+)\.php/u", $_SERVER['REQUEST_URI'], $matches
        );
        if (!empty($matches[1])) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Получить сообщение из Сессии
     *
     * @return string
     */
    public static function getMessageFromSession()
    {
        $message = '';

        if (!empty($_SESSION['message_full_name'])) {
            $message = $_SESSION['message_full_name'];
            $_SESSION['message_full_name'] = '';
        }

        return $message;
    }


    /**
     * Сохранить сообщение в Сессии
     *
     * @param $message string Название сообщения
     *
     * @return mixed
     */
    public static function setMessageToSession($message, $type = 'error')
    {

        @$_SESSION['message_full_name'] = $message;
        @$_SESSION['message_type'] = $type;
        return $_SESSION['message_full_name'];
    }

    /**
     * Отменить Транзакцию БД
     *
     * @param        $message string Текст ошибки
     * @param string $url     URL для редиректа
     */
    public static function badEnd(
        $message, $url = '', $error = null, $rollback = false
    ) {
        if (!empty($rollback)) {
            DB::transactionRollback();
        }

        if (!empty($error)) {
//            self::errorLog($error);
        }

        if (empty($url)) {
            echo $message;
        } else {
            self::setMessageToSession($message);
            header('location: ' . $url);
        }
        exit;
    }


    public static function errorLog($error, $table = 'error_log')
    {
        try {
            $count = DB::counting('error_log');

            if ($count > 5000) {
                DB::clearTable('error_log');
            }

            if (is_string($error)) {
                $error_data['data'] = $error;
            } else {
                $error_data['data'] = Helper::json_encode($error);
            }
            $error_data['ip'] = Helper::getIp();

            return DB::save($error_data, 'error_log');
        } catch (Throwable $throwable) {
            return false;
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * Получить разницу между сегодняшним днём и заданной датой
     *
     * @param $date string Дата
     *
     * @return int Разница в днях. (0 - если дата - это сегодняшний день)
     */
    public static function diffDays($date)
    {
        $timestamp = $date;

        $today = new DateTime(); // This object represents current date/time
        $today->setTime(
            0, 0, 0
        ); // reset time part, to prevent partial comparison

        $match_date = DateTime::createFromFormat('Y-m-d', $timestamp);
        $match_date->setTime(
            0, 0, 0
        ); // reset time part, to prevent partial comparison

        $diff = $today->diff($match_date);
        $diffDays = (integer)$diff->format(
            "%R%a"
        ); // Extract days count in interval

        //    switch( $diffDays ) {
        //        case 0:
        //            echo "//Today";
        //            break;
        //        case -1:
        //            echo "//Yesterday";
        //            break;
        //        case +1:
        //            echo "//Tomorrow";
        //            break;
        //        default:
        //            break;
        //    }
        return $diffDays;
    }

    /*
     * Находится ли число в указанном интервале (включая границы)
     */
    public static function checkNumberInInterval($number, $min, $max)
    {
        return $number >= $min && $number <= $max;
    }

    /**
     * Replace &, ", <, > to HTML entity
     * Offical document: https://core.telegram.org/bots/api#html-style
     *
     * @param string $str
     *
     * @return string $str Encoded string
     */
    public static function enHTML($str = '')
    {
        $search = Array('&', '"', '<', '>');
        $replace = Array('&amp;', '&quot;', '&lt;', '&gt');
        $str = str_replace($search, $replace, $str);
        return $str;
    }


    public static function serverProccessingAuto()
    {

        $draw = 0;
        $limit = 10;
        $offset = 0;
        $order = [];

        $search = '';
        $search_array = [];
        $table = '';
        $get_user_id = '';


        if (!empty($_GET['table'])) {
            $table = Helper::inputFilter($_GET['table']);
        } else {
            exit;
        }


// Данные, которые передаёт библиотека DataTables
        if (!empty($_REQUEST['draw'])) {
            $draw = (int)$_REQUEST['draw'];
            $offset = (int)$_REQUEST['start'];

            $limit = (int)$_REQUEST['length'];

            if (!empty($_REQUEST['order'][0]['dir'])
                && in_array(
                    $_REQUEST['order'][0]['dir'], array('desc', 'asc')
                )
            ) {
                $order['dir'] = strtoupper($_REQUEST['order'][0]['dir']);

                $index = (int)$_REQUEST['order'][0]['column'];
                if ($index == 0) {
                    $index = 1;
                }
                $order['column'] = $_REQUEST['columns'][$index]['data'];
            }

            $search = [];

            $get_column = '';


            if (empty($_REQUEST['search']['value'])) {
                if (!empty($_REQUEST['get_column'])
                    && !empty($_REQUEST['get_value'])
                ) {
                    $search['column'] = Helper::inputFilter(
                        $_GET['get_column']
                    );
                    $search['value'] = Helper::inputFilter($_GET['get_value']);
                    $search['full'] = true;
                    $search_array[] = $search;

                    $get_column = $search['column'];
                }

                foreach ($_REQUEST['columns'] as $column) {
                    if ($column['search']['value'] == '0'
                        || (!empty($column['search']['value']))
                        && $column['data'] !== $get_column
                    ) {
                        $temp = trim($column['data']);
                        $temp = preg_replace('/[^\w\/.,-@\s)]/u', '', $temp);
                        $search['column'] = $temp;

                        $temp = trim($column['search']['value']);
                        $temp = preg_replace('/[^\w\/.,-@\s)]/u', '', $temp);
                        $search['value'] = $temp;

                        $search['full'] = false;
                        $search_array[] = $search;
                    }
                }

                $results = DB::getAllLimitAdvanced(
                    $table, $limit, $offset, $search_array, $order
                );
                $full_count = DB::countingAdvanced($table, $search_array);
            } else {
                // Медленный поиск по всем полям
                $search = Helper::inputFilter($_REQUEST['search']['value']);

                $columns = DB::getColumnNames($table);

                $rows = [];
                foreach ($columns as $column) {
                    $find = DB::getAllByColLike($table, $column, $search);
                    $rows = array_merge($rows, $find);
                }
                $rows = array_map(
                    "unserialize", array_unique(array_map("serialize", $rows))
                );
                $full_count = count($rows);
                $results = $rows;
            }
        }


//if (!empty($search_array[0]['value'])) {
//    $params['table'] = $table;
//    $params['column'] = $search_array[0]['column'];
//    $params['text'] = $search_array[0]['value'];
//    $results = fuzzySearchDB($params);
//}


// данные, необходимые для библиотеки DataTables
        $response['draw'] = $draw;
        $response['recordsTotal'] = $full_count;
        $response['recordsFiltered'] = $full_count;


        $bolean_columns = DB::filterEnumColumns($table);

//        $select_info = DB::checkTableHavingSelectBox($table);


        foreach ($results as &$item) {
            $item['action'] = Generator::genActionButtons(
                $table, $table, $item, $table
            );



            foreach ($item as $key => $value) {
                // Проверка на возможность сгенерировать checkbox
                if (in_array($key, $bolean_columns)) {
                    $item[$key] = Generator::genTableCheckbox(
                        $item, $key, $value
                    );

                    continue;
                }


                try {
                    $field_info = DB::getByColumn('table_fields', 'field', $key);




                    if (!empty($field_info)) {
                        if ($field_info['description'] == 'img') {
                            $item[$key] = Generator::genTableImageBox(
                                $field_info['full_name'], $value
                            );
                            continue;
                        }
                    }

                } catch (Throwable $exception) {
                }

                $relation = DB::getTableRelationsOneToMany($table, $key);

                // Проверка на возможность сгенерировать SelectBox
                if (!empty($relation)) {

                    switch (true) {
                    case !empty($relation['is_small']):
                        $item[$key] = Generator::genTableSelectBox(
                            $relation, $value, $item['id']
                        );
                        break;

                    default:
                        $item[$key] = Generator::simpleFilter(
                            $value, $relation
                        );
                        break;
                    }


                    continue;
                }


                if (strlen($item[$key]) < 100) {
                    continue;
                }

                $column_type = DB::getColumnType($table, $key);
                if (preg_match('/text|varchar/ui', $column_type)) {
                    $item[$key] = mb_strcut($item[$key], 0, 100);
                    $symbol_index = strrpos($item[$key], '.');

                    if ($symbol_index === false) {
                        $symbol_index = strrpos($item[$key], ' ');
                    }
                    $item[$key] = mb_strcut($item[$key], 0, $symbol_index + 1)
                        . ' ...';
                }
            }
        }

        unset($item);

        $response['data'] = $results;
        $json = Helper::json_encode($response);

        /*$log['get'] = $_GET;
        $log['sent'] = $response;
        mylog($log);*/

        return $json;
    }


	   public static function sendTGMessage($message, $token = null, $chatID = null
    ) {
        if (!empty(@MY_TELEGRAM_TOKEN)) {
            $token = @MY_TELEGRAM_TOKEN;
            $chatID = @MY_TELEGRAM_CHAT;
        }

        if ( empty(TELEGRAM_ACTIVE)) {
            return false;
        }

       if (empty($token) || empty($chatID) || empty($message)) {
            return false;
        }

        if (!is_string($message)) {
            $message = Helper::json_encode($message);
        }

        // max size 4096
        if (strlen($message) > 4000) {
            $message = substr($message, 0, 4000);
        }
		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']
        );
        curl_setopt(
            $ch, CURLOPT_URL,
            'https://api.telegram.org/bot' . $token . '/sendMessage'
        );
        $postFields = array(
            'chat_id'                  => '-' . $chatID,
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => false,
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}