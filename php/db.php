<?php

namespace Helper;

// DB QUERIES using `safemysql.class.php`


use Exception;

class DB
{

    public static function getTables($db_name)
    {
        global $db;
        $query = 'SHOW TABLES FROM ?n';
        return $db->getCol($query, $db_name);
    }


    /**
     * Получить все записи из таблицы (расширенная)
     * @param $table string Название таблицы
     * @param int $limit Ограничение
     * @param int $offset Отступ
     * @param array|bool|mixed $search_array Список для поиска
     * @param $order
     * @return array|bool|mixed Список записей
     */
    public static function getAllLimitAdvanced($table, $limit = 0, $offset = 0, $search_array, $order)
    {
        global $db;

        if ($limit > 0) {
        } else {
            $limit = 1000;
        }

        $query = "SELECT * FROM ?n";

        if (!empty($search_array)) {

            $query .= ' WHERE';

            foreach ($search_array as $i => $iValue) {
                if (empty($iValue['value'])) {
                    $iValue['full'] = true;
                }

                $column = $iValue['column'];
                $value = $iValue['value'];

                if (empty($iValue['full'])) {
                    $query .= " `$column` LIKE'%$value%' AND";
                } else {
                    $query .= " `$column`='$value' AND";
                }
            }
            $query .= ' 1';
        }

        if (!empty($order)) {
            $column = $order['column'];
            $dir = $order['dir'];

            $query .= " ORDER BY `$column` $dir";
        }

        if ($limit > 0) {
            $query .= " LIMIT $limit";

            if ($offset > 0) {
                $query .= " OFFSET $offset";
            }
        }
        return $db->getAll($query, $table);
    }


    /**
     * Получить запись по значению `id`
     * @param $table string Исходная таблица
     * @param $id string Значение `id`
     * @return array|FALSE Запись
     */
    public static function getById($table, $id)
    {
        global $db;
        $query = 'SELECT * FROM ?n WHERE `id`=?i';
        return $db->getRow($query, $table, $id);
    }


    /**
     * Получить запись по значению `column`
     * @param $table string Исходная таблица
     * @param $id string Значение `column`
     * @return array|FALSE Запись
     */
    public static function getByColumn($table, $column, $value)
    {
        global $db;
        $query = 'SELECT * FROM ?n';

        if (!empty($column)) {
            $query .= "WHERE ?n=?s";
            return $db->getRow($query, $table, $column, $value);
        }
        return $db->getRow($query, $table, $column, $value);
    }

    public static function getByColAll($table, $column, $value)
    {
        global $db;
        $query = 'SELECT * FROM ?n WHERE ?n = ?s';
        return $db->getAll($query, $table, $column, $value);
    }


    public static function getAllByColLike($table, $column, $value)
    {
        global $db;
        $value = '%' . $value . '%';
        $query = 'SELECT * FROM ?n WHERE ?n LIKE ?s';
        return $db->getAll($query, $table, $column, $value);
    }


    public static function getByColumnAndArray($table, $array, $is_one = true, $needed_column = null)
    {
        global $db;

        if (!empty($needed_column)) {
            $needed_column = "`$needed_column`";
        } else {
            $needed_column = '*';
        }

        $query = "SELECT $needed_column FROM $table WHERE";

        foreach ($array as $item) {
            $query .= ' ' . $item['column'] . '="' . $item['value'] . '" AND';
        }
        $query .= ' 1';

        if ($needed_column !== '*') {
            if ($is_one) {
                return $db->getOne($query);
            }
            return $db->getCol($query);

        }

        if ($is_one) {
            return $db->getRow($query);
        } else {
            return $db->getAll($query);
        }
    }

    /**
     * Добавить запись в таблицу связей таблиц
     * @param $p_data array Данные для добавления
     * @param $table string Исходная таблица
     * @return bool|FALSE|resource Результат операции
     */
    public static function saveRelation($p_data, $table)
    {
        global $db;
        if (empty($p_data)) {
            return false;
        }

        $columns = self::getColumnNames($table);
        $data = $db->filterArray($p_data, $columns);

        $query = 'INSERT INTO ?n SET ?u';
        return $db->query($query, $table, $data);
    }


    /**
     * Получить все записи с указанных значением внешнего ключа
     * @param $table string Исходная таблица
     * @param $column string Название внешнего ключа
     * @param $value string Значение внешнего ключа
     * @param $needed_column string Название поля, которое нужно получить
     * @param int $limit Ограничение количества записей
     * @return array Результат выборки
     */
    public static function getOneToMany($table, $column, $value, $needed_column, $limit = 0)
    {
        global $db;
        $query = 'SELECT ?n FROM ?n WHERE ?n=?s';
        $limit = (int)$limit;

        if ($limit) {
            $query .= "LIMIT $limit";
        }

        return $db->getCol($query, $needed_column, $table, $column, $value);
    }


    /**
     * Добавление записи или обновление в случае существования
     * @param $p_data array Данные для добавления
     * @param $table string Исходная Таблица
     * @param string $primary Название первичного ключа
     * @return bool|FALSE|resource Результат операции
     */
    public static function save($p_data, $table, $primary = 'id')
    {
        global $db;

        if (empty($p_data)) {
            return false;
        }

        $columns = self::getColumnNames($table);
        $data = $db->filterArray($p_data, $columns);

        $exist = false;

        if (is_array($primary)) {
            $exist = self::getByColumnAndArray($table, $primary);
        } else {
            if (!isset($data[$primary])) {
                $exist = false;
            }
            else {
                $exist = self::checkExist($table, $primary, $data[$primary]);
            }
        }

        if (!$exist) {
            $query = 'INSERT INTO ?n SET ?u';
            return $db->query($query, $table, $data);
        } else {
            if (is_array($primary)) {
                $query = 'UPDATE ?n SET ?u WHERE';

                foreach ($primary as $item) {
                    $query .= ' ' . $item['column'] . '="' . $item['value'] . '" AND';
                }
                $query .= ' 1';

                return $db->query($query, $table, $data);
            } else {
                $query = 'UPDATE ?n SET ?u WHERE ?n=?s';
                return $db->query($query, $table, $data, $primary, $data[$primary]);
            }
        }
    }


    /**
     * Получить количество записей в таблице
     * @param $table string Таблица, по которой идёт подсчёт
     * @param bool $col Название столбца, по которому идёт выбор (опционально)
     * @param bool $val Значение стоблца, по которому идёт выбор
     * @return int Количество записей
     */
    public static function counting($table, $col = false, $val = false)
    {
        global $db;
        $query = "SELECT COUNT(1) FROM ?n";

        if (!empty($col) && !empty($val)) {
            $query .= " WHERE `$col`='$val'";
        }
        $res = $db->getOne($query, $table);
        return $res ?: 0;
    }


    /**
     * Проверить существование записи с указанным значением одного поля
     * @param $table string Таблица для проверки
     * @param $column string Поле для проверки
     * @param $value string Контрольное Значение
     * @return FALSE|string Существует или нет
     */
    public static function checkExist($table, $column, $value)
    {
        global $db;
        $query = 'SELECT ?n FROM ?n WHERE ?n=?s';
        $is_exist = $db->getOne($query, $column, $table, $column, $value);
        return $is_exist;
    }


    /**
     * Получить столбцы таблицы
     * @param $table_name string Исходная таблица
     * @return array Список столбцов
     */
    public static function getColumnNames($table_name)
    {
        global $db;
        $columns = array();

        try {
            $sql = "SHOW COLUMNS FROM `$table_name`";
            $result = $db->query($sql);
            while ($row = $db->fetch($result)) {
                $columns[] = $row['Field'];
            }
        } catch (Exception $ex) {

        }
        return $columns;
    }


    public static function getAll($table)
    {
        global $db;
        $query = "SELECT * FROM " . $table;
        return $db->getAll($query);
    }


    public static function createLogTable($table = 'debug_log')
    {
        global $db;
        $query = "
CREATE TABLE `$table` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `title` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `data` text NOT NULL,
  `site` varchar(25) NOT NULL,
  `proccess_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 
";


        $query1 = "
ALTER TABLE `$table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`),
  ADD KEY `title` (`title`),
  ADD KEY `type` (`type`),
  ADD KEY `proccess_id` (`proccess_id`); 
ALTER TABLE `$table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

        try {
            return $db->query($query) && $db->query($query1);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Записать данные в Лог-базу
     * @param $data mixed Данные для записи
     * @param string $title Заголовок
     * @param string $type Тип
     * @return array|FALSE|resource
     */
    public static function logDB($data, $title = 'Info', $type = 'info', $table = 'debug_log')
    {
        global $config, $db;

        try {
            $count = self::counting($table);

            if ($count > 500) {
                $primary = 'id';
                $id = $db->getOne('SELECT ?n FROM ?n', $primary, $table);

                if (!empty($id)) {
                    $db->query('DELETE FROM ?n WHERE ?n=?i', $table, $primary, $id);
                }
            }

            if (!is_array($data)) {
                $data = [$data];
            }

            $element = array();
            $element['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $element['type'] = $type;
            $element['title'] = $title;
            @$element['site'] = @$config['site'];
            $element['proccess_id'] = @$config['proccess_id'];

            return $db->query('INSERT INTO ?n SET ?u', $table, $element);
        } catch (Exception $exception) {
            return ['error' => true, 'message' => $exception->getMessage()];
        }
    }

    /**
     * Получить записи Лог-базы
     * @return array Список логирования
     */
    public static function getlogDB($site = false, $table = 'debug_log')
    {
        global $db;
        $query = 'SELECT * FROM ?n';
        if (!empty($site)) {
            $query .= ' WHERE `site`="' . $site . '"';
        }
        $query .= ' ORDER BY `id` DESC LIMIT 50';

        return $db->getAll($query, $table);
    }


    public static function clearlogDB($table = 'debug_log')
    {
        global $db;
        $table = 'debug_log';
        return $db->query('DELETE FROM ?n', $table);
    }

    /**
     * Удаляет текущую директорию и все файлы и папки в ней, включая скрытые файлы (.extension)...
     * @param string $folder_path Путь до папки которую нужно удалить
     */
    public static function delete_folder($folder_path, $delete_self = true)
    {

        $glod = glob("$folder_path/{,.}[!.,!..]*", GLOB_BRACE);
        foreach ($glod as $file) {
            if (is_dir($file))
                call_user_func(__FUNCTION__, $file);
            else
                unlink($file);
        }

        if ($delete_self)
            rmdir($folder_path);
    }


    public static function getColumnComment($db_name, $table, $column)
    {
        global $info_db;
        $query = "SELECT `COLUMN_COMMENT` FROM `COLUMNS` WHERE `TABLE_SCHEMA`=?s AND `TABLE_NAME`=?s AND `COLUMN_NAME`=?s";
        return $info_db->getOne($query, $db_name, $table, $column);
    }


    public static function getColumnDefaultValue($db_name, $table, $column)
    {
        global $info_db;
        $query = "SELECT `COLUMN_DEFAULT` FROM `COLUMNS` WHERE `TABLE_SCHEMA`=?s AND `TABLE_NAME`=?s AND `COLUMN_NAME`=?s";
        return $info_db->getOne($query, $db_name, $table, $column);
    }


}

?>