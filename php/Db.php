<?php

namespace Helper;

require_once(__DIR__ . '/safemysql.php');

use Exception;
use Throwable;


class Db
{

    public static $db;

    public function __construct(
        $host, $db_name, $user, $password, $charset = 'utf8mb4'
    )
    {
        try {
            self::$db = new SafeMysql(
                array(
                    'user' => $user, 'pass' => $password, 'db' => $db_name,
                    'charset' => $charset
                )
            );
        } catch (Throwable $throwable) {
            return false;
        } catch (Exception $exception) {
            return false;
        }
        return false;
    }

    public static function getTables($db_name)
    {
        $query = 'SHOW TABLES FROM ?n';
        return self::$db->getCol($query, $db_name);
    }


    /**
     * Получить все записи из таблицы (расширенная)
     *
     * @param                  $table        string Название таблицы
     * @param int $limit Ограничение
     * @param int $offset Отступ
     * @param array|bool|mixed $search_array Список для поиска
     * @param                  $order
     *
     * @return array|bool|mixed Список записей
     */
    public static function getAllLimitAdvanced(
        $table, $limit = 0, $offset = 0, $search_array, $order
    )
    {
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
        return self::$db->getAll($query, $table);
    }


    /**
     * Получить запись по значению `id`
     *
     * @param $table string Исходная таблица
     * @param $id    string Значение `id`
     *
     * @return array|FALSE Запись
     */
    public static function getById($table, $id)
    {
        $id = (int)$id;
        $query = 'SELECT * FROM ?n WHERE `id`=?i';
        return self::$db->getRow($query, $table, $id);
    }


    /**
     * Получить отсортированные данные из таблицы
     *
     * @param $table  string Исходная таблица
     * @param $column string Столбец, по которому идёт сортировка
     *
     * @return array Отсортированный список записей
     */
    public static function getAllOrdered($table, $column)
    {
        $query = 'SELECT * FROM ?n ORDER BY ?n DESC';
        return self::$db->getAll($query, $table, $column);
    }

    /**
     * Получить запись по значению `column`
     *
     * @param $table string Исходная таблица
     * @param $id    string Значение `column`
     *
     * @return array|FALSE Запись
     */
    public static function getByColumn($table, $column, $value)
    {
        $query = 'SELECT * FROM ?n';

        if (!empty($column)) {
            $query .= " WHERE ?n=?s";
            return self::$db->getRow($query, $table, $column, $value);
        }
        return self::$db->getRow($query, $table, $column, $value);
    }

    public static function getByColAll($table, $column, $value)
    {
        $query = 'SELECT * FROM ?n WHERE ?n = ?s';
        return self::$db->getAll($query, $table, $column, $value);
    }


    public static function getAllByColLike($table, $column, $value)
    {
        $value = '%' . $value . '%';
        $query = 'SELECT * FROM ?n WHERE ?n LIKE ?s';
        return self::$db->getAll($query, $table, $column, $value);
    }

    /**
     * Получить записи, фильтруя по нескольким полям
     *
     * @param      $table         string Исходная таблица
     * @param      $array         array Массив вида [['column'=>'col_example', 'value'=>'value_example']] ИЛИ [['col_example', 'value_example']]
     * @param bool $is_one Получить только одну запись
     * @param null $needed_column Выбирать определённую колонку
     *
     * @return array|FALSE|string
     */
    public static function getByColumnAndArray(
        $table, $array, $is_one = true, $needed_column = null
    )
    {

        if (!empty($needed_column)) {
            $needed_column = "`$needed_column`";
        } else {
            $needed_column = '*';
        }

        $query = "SELECT $needed_column FROM $table WHERE";

        foreach ($array as $item) {
            if (!isset($item['column']) && count($item) == 2) {
                $item['column'] = @$item[0];
                $item['value'] = @$item[1];
            }

            $query .= ' ' . $item['column'] . '="' . $item['value'] . '" AND';
        }
        $query .= ' 1';

        if ($needed_column !== '*') {
            if ($is_one) {
                return self::$db->getOne($query);
            }
            return self::$db->getCol($query);

        }

        if ($is_one) {
            return self::$db->getRow($query);
        } else {
            return self::$db->getAll($query);
        }
    }

    /**
     * Добавить запись в таблицу связей таблиц
     *
     * @param $p_data array Данные для добавления
     * @param $table  string Исходная таблица
     *
     * @return bool|FALSE|resource Результат операции
     */
    public static function saveRelation($p_data, $table)
    {
        if (empty($p_data)) {
            return false;
        }

        $columns = self::getColumnNames($table);
        $data = self::$db->filterArray($p_data, $columns);

        $query = 'INSERT INTO ?n SET ?u';
        return self::$db->query($query, $table, $data);
    }


    /**
     * Получить все записи с указанных значением внешнего ключа
     *
     * @param     $table         string Исходная таблица
     * @param     $column        string Название внешнего ключа
     * @param     $value         string Значение внешнего ключа
     * @param     $needed_column string Название поля, которое нужно получить
     * @param int $limit Ограничение количества записей
     *
     * @return array Результат выборки
     */
    public static function getOneToMany(
        $table, $column, $value, $needed_column, $limit = 0
    )
    {
        $query = 'SELECT ?n FROM ?n WHERE ?n=?s';
        $limit = (int)$limit;

        if ($limit) {
            $query .= "LIMIT $limit";
        }

        return self::$db->getCol(
            $query, $needed_column, $table, $column, $value
        );
    }


    /**
     * Добавление записи или обновление в случае существования
     *
     * @param              $p_data  array Данные для добавления
     * @param              $table   string Исходная Таблица
     * @param string|array $primary Название первичного ключа
     *
     * @return bool|FALSE|resource Результат операции
     */
    public static function save($p_data, $table, $primary = 'id')
    {
        global $meDoo;
        try {
            if (empty($p_data)) {
                return false;
            }

            $columns = self::getColumnNames($table);
            $data = self::$db->filterArray($p_data, $columns);

            $exist = false;

            if (is_array($primary)) {
                $exist = self::checkExist($table, $primary, $data);
            } else {
                if (!isset($data[$primary])) {
                    $exist = false;
                } else {
                    $exist = self::checkExist(
                        $table, $primary, $data[$primary]
                    );
                }
            }

            if (!$exist) {
                return $meDoo->insert($table, $data);
            } else {
                if (is_array($primary)) {

                    $filter = [];

                    foreach ($primary as $item) {

                        switch (true) {
                            case is_string($item):
                                $temp = $item;
                                $item = [];
                                $item['column'] = $temp;
                                $item['value'] = $data[$temp];
                                break;
                            case empty($item['column']):
                                $item['column'] = @$item[0];
                                $item['value'] = @$item[1];
                                break;
                        }
                        $filter[$item['column']] = $item['value'];
                    }
                } else {
                    $filter = [$primary => $data[$primary]];
                }

                $res = $meDoo->update($table, $data, $filter);
                return $res;
            }
        } catch (Throwable $exception) {
            $message = @'error: ' . $_SERVER['DOCUMENT_ROOT'] . ': MYSQL: '
                . $exception->getMessage();
            Helper::sendTGMessage($message);
            return false;
        }
    }


    /**
     * Добавление записи или обновление в случае существования
     *
     * @param              $p_data  array Данные для добавления
     * @param              $table   string Исходная Таблица
     * @param string|array $primary Название первичного ключа
     *
     * @return bool|FALSE|resource Результат операции
     */
    public static function update($p_data, $table, $primary = 'id')
    {
        global $meDoo;
        try {
            if (empty($p_data)) {
                return false;
            }

            $columns = self::getColumnNames($table);
            $data = self::$db->filterArray($p_data, $columns);

            $exist = false;

            if (is_array($primary)) {
                $exist = self::checkExist($table, $primary, $data);
            } else {
                if (!isset($data[$primary])) {
                    $exist = false;
                } else {
                    $exist = self::checkExist(
                        $table, $primary, $data[$primary]
                    );
                }
            }

            if (!$exist) {
                return false;
            }

            if (is_array($primary)) {
                $filter = [];

                foreach ($primary as $item) {

                    switch (true) {
                        case is_string($item):
                            $temp = $item;
                            $item = [];
                            $item['column'] = $temp;
                            $item['value'] = $data[$temp];
                            break;
                        case empty($item['column']):
                            $item['column'] = @$item[0];
                            $item['value'] = @$item[1];
                            break;
                    }
                    $filter[$item['column']] = $item['value'];
                }
            } else {
                $filter = [$primary => $data[$primary]];
            }

            $res = $meDoo->update($table, $data, $filter);
            return $res;

        } catch
        (Throwable $exception) {
            $message = @'error: ' . $_SERVER['DOCUMENT_ROOT'] . ':UPDATE MYSQL: '
                . $exception->getMessage();
            Helper::sendTGMessage($message);
            return false;
        }
    }


    public static function setDefaultColumnValue($db_name, $table)
    {
        $columns = self::getColumnNames($table);

        $types = [];

        foreach ($columns as $column) {
            $query
                = "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ?s AND table_name = ?s AND COLUMN_NAME = ?s limit 1";

            $type_info = self::$db->getRow($query, $db_name, $table, $column);

            $type = @$type_info['DATA_TYPE'];

            switch ($type) {
                case 'varchar':
                    $default = self::getColumnDefaultValue(
                        $db_name, $table, $column
                    );
                    if (empty($default)) {
                        $query = "ALTER TABLE ?n ALTER COLUMN ?n SET DEFAULT ''";
                        self::$db->query($query, $table, $column);
                    }
                    break;
                case 'int':
                    $default = self::getColumnDefaultValue(
                        $db_name, $table, $column
                    );
                    if (!is_int($default)) {
                        $query = "ALTER TABLE ?n ALTER COLUMN ?n SET DEFAULT 0";
                        self::$db->query($query, $table, $column);
                    }
                    break;
            }
        }
        return $types;

    }

    /**
     * Удалить запись
     *
     * @param $table  string Исходная таблица
     * @param $column string Столбец, по которому идёт фильрация
     * @param $value  string Значение стоблца
     *
     * @return FALSE|\mysqli|resource Удалось ли удалить запись
     */
    public static function deleteRow($table, $column, $value)
    {
        $query = 'DELETE FROM ?n WHERE ?n=?s';
        return self::$db->query($query, $table, $column, $value);
    }


    /**
     * Получить количество записей в таблице
     *
     * @param      $table string Таблица, по которой идёт подсчёт
     * @param bool $col Название столбца, по которому идёт выбор (опционально)
     * @param bool $val Значение стоблца, по которому идёт выбор
     *
     * @return int Количество записей
     */
    public static function counting($table, $col = false, $val = false)
    {
        $query = "SELECT COUNT(1) FROM ?n";

        if (!empty($col) && !empty($val)) {
            $query .= " WHERE `$col`='$val'";
        }
        $res = self::$db->getOne($query, $table);
        return $res ?: 0;
    }


    public static function lastId()
    {
        return self::$db->insertId();
    }


    /**
     * Проверить существование записи с указанным значением одного поля
     *
     * @param $table  string Таблица для проверки
     * @param $column string Поле для проверки
     * @param $value  string|array Контрольное Значение
     *
     * @return FALSE|string Существует или нет
     */
    public static function checkExist($table, $column, $value)
    {
        if (is_array($column) && Helper::isAssoc($value)) {
            $new_filter = [];

            foreach ($column as $item) {
                if (is_string($item)) {
                    $new_filter[$item] = $value[$item];
                }

                switch (true) {
                    case is_string($item):
                        $new_filter[$item] = $value[$item];
                        break;
                    case !empty($item['column']):
                        $new_filter[$item['column']] = $item['value'];
                        break;
                    case !empty($item[0]):
                        $new_filter[$item[0]] = $item[1];
                        break;
                }
            }

            $query = 'SELECT ?n FROM ?n WHERE ?x';
            return self::$db->getOne($query, $column, $table, $new_filter);
        } else {
            $query = 'SELECT ?n FROM ?n WHERE ?n=?s';
            return self::$db->getOne($query, $column, $table, $column, $value);
        }
    }


    /**
     * Получить столбцы таблицы
     *
     * @param $table_name string Исходная таблица
     *
     * @return array Список столбцов
     */
    public static function getColumnNames($table_name)
    {
        $columns = array();

        try {
            $sql = "SHOW COLUMNS FROM `$table_name`";
            $result = self::$db->query($sql);
            while ($row = self::$db->fetch($result)) {
                $columns[] = $row['Field'];
            }
        } catch (Throwable $throwable) {

        } catch (Exception $ex) {

        }
        return $columns;
    }


    public static function getAll($table)
    {
        $query = "SELECT * FROM " . $table;
        return self::$db->getAll($query);
    }


    public static function createLogTable($table = 'debug_log')
    {
        $query
            = "
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


        $query1
            = "
ALTER TABLE `$table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`),
  ADD KEY `title` (`title`),
  ADD KEY `type` (`type`),
  ADD KEY `proccess_id` (`proccess_id`); 
ALTER TABLE `$table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

        try {
            return self::$db->query($query) && self::$db->query($query1);
        } catch (Throwable $throwable) {
            return false;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Записать данные в Лог-базу
     *
     * @param        $data  mixed Данные для записи
     * @param string $title Заголовок
     * @param string $type Тип
     *
     * @return array|FALSE|resource
     */
    public static function logDB(
        $data, $title = 'Info', $type = 'info', $table = 'debug_log', $limit = 50000
    )
    {

        try {
            $count = self::counting($table);

            if ($count > $limit) {
                $primary = 'id';
                $id = self::$db->getOne('SELECT ?n FROM ?n', $primary, $table);

                if (!empty($id)) {
                    self::$db->query(
                        'DELETE FROM ?n WHERE ?n=?i', $table, $primary, $id
                    );
                }
            }

            if (!is_array($data)) {
                $data = [$data];
            }

            $element = array();
            $element['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $element['type'] = $type;
            $element['title'] = $title;
            @$element['site'] = @$element['site'] ?: '';
            $element['proccess_id'] = @$element['proccess_id'] ?: '';

            return self::$db->query('INSERT INTO ?n SET ?u', $table, $element);
        } catch (Throwable $throwable) {
            return ['error' => true, 'message' => $throwable->getMessage()];
        } catch (Exception $exception) {
            return ['error' => true, 'message' => $exception->getMessage()];
        }
    }

    /**
     * Получить записи Лог-базы
     *
     * @return array Список логирования
     */
    public static function getlogDB($site = false, $table = 'debug_log')
    {
        $query = 'SELECT * FROM ?n';
        if (!empty($site)) {
            $query .= ' WHERE `site`="' . $site . '"';
        }
        $query .= ' ORDER BY `id` DESC LIMIT 50';

        return self::$db->getAll($query, $table);
    }


    public static function clearlogDB($table = 'debug_log')
    {
        $table = 'debug_log';
        return self::$db->query('DELETE FROM ?n', $table);
    }

    /**
     * Удаляет текущую директорию и все файлы и папки в ней, включая скрытые файлы (.extension)...
     *
     * @param string $folder_path Путь до папки которую нужно удалить
     */
    public static function delete_folder($folder_path, $delete_self = true)
    {

        $glod = glob("$folder_path/{,.}[!.,!..]*", GLOB_BRACE);
        foreach ($glod as $file) {
            if (is_dir($file)) {
                call_user_func(__FUNCTION__, $file);
            } else {
                unlink($file);
            }
        }

        if ($delete_self) {
            rmdir($folder_path);
        }
    }


    public static function getColumnComment($db_name, $table, $column)
    {
        $query
            = "SELECT `COLUMN_COMMENT` FROM INFORMATION_SCHEMA.COLUMNS WHERE `TABLE_SCHEMA`=?s AND `TABLE_NAME`=?s AND `COLUMN_NAME`=?s";
        return self::$db->getOne($query, $db_name, $table, $column);
    }


    public static function getColumnDefaultValue($db_name, $table, $column)
    {
        $query
            = "SELECT `COLUMN_DEFAULT` FROM INFORMATION_SCHEMA.COLUMNS WHERE `TABLE_SCHEMA`=?s AND `TABLE_NAME`=?s AND `COLUMN_NAME`=?s";
        return self::$db->getOne($query, $db_name, $table, $column);
    }

    /**
     * @param $results
     * @param $keys_for_formatting
     *
     * @return mixed
     */
    public static function formatDataForTableShowing(
        $results, $keys_for_formatting
    )
    {
        foreach ($results as &$item) {
            foreach ($item as $key => &$obj) {
                if (in_array($key, $keys_for_formatting)) {
                    $obj = Helper::inputFilter($obj);
                    if (strlen($obj) > 100) {
                        $obj = mb_strcut($obj, 0, 200);
                        $symbol_index = strrpos($obj, '.');

                        if ($symbol_index === false) {
                            $symbol_index = strrpos($obj, ' ');
                        }
                        $obj = mb_strcut($obj, 0, $symbol_index + 1) . ' ...';
                    }
                }
            }
            unset($obj);
        }
        unset($item);

        return $results;
    }


    /**
     *  Отформатировать столбцы таблицы
     *
     * @param $table string Исходная таблица
     *
     * @return mixed Массив списков столбцов
     */
    public static function getColumnsReadable($table, $user_showed = true)
    {
        $columns = self::getColumnNames($table);

        $data_columns = array('action');
        $max_columns = array();

        try {
            $columns_props = DB::getByColAll(
                'table_fields', 'table_name', $table
            );
        } catch (Throwable $exception) {
            $columns_props = [];
        }


        foreach ($columns as $column) {
            $showed = true;

            $full_name = '';
            if (!empty($columns_props)) {
                foreach ($columns_props as $columnsProp) {
                    if ($columnsProp['field'] == $column) {
                        $full_name = $columnsProp['full_name'];
                        $showed = $columnsProp['showed'];
                        break;
                    }
                }
                if (empty($showed) && !empty($user_showed)) {
                    continue;
                }
            }

            if (empty($full_name)) {
                $full_name = self::readableText($column);
            }

            $data_columns[] = $column;
            $max_columns[] = $full_name;
        }

        $result['data_columns'] = $data_columns;
        $result['columns'] = $max_columns;
        return $result;
    }


    /**
     * Форматирование строки перед выводом
     *
     * @param $text string Исходная строка
     *
     * @return mixed|string Отформатированная строка
     */
    private static function readableText($text)
    {
        $formatted_text = preg_replace('/[_-]/', ' ', $text);
        $formatted_text = ucwords($formatted_text);
        return $formatted_text;
    }

    /**
     * Получить типы столбцов в таблице
     *
     * @param $table string Исходная таблица
     *
     * @return array Список типов
     */
    public static function getTableTypes($table)
    {
        $columns = array();

        $q = self::$db->query("DESCRIBE `$table`");
        while ($row = self::$db->fetch($q)) {
            $temp['type'] = $row['Type'];
            $temp['name'] = $row['Field'];
            $temp['value'] = '';
            $columns[] = $temp;
        }
        return $columns;
    }

    /**
     * Получить тип столбца
     *
     * @param $table  string Исходная таблицы
     * @param $column string Исходный столбец
     *
     * @return FALSE|string Тип столбца
     */
    public static function getColumnType($table, $column)
    {
        $query
            = "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
      WHERE `table_name` = ?s AND COLUMN_NAME = ?s";
        $res = self::$db->getOne($query, $table, $column);
        return $res;
    }

    /**
     * Получить длину столбца
     *
     * @param $table  string Исходная таблицы
     * @param $column string Исходный столбец
     *
     * @return FALSE|string Тип столбца
     */
    public static function getColumnLength($table, $column)
    {
        $query
            = "SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS 
          WHERE `table_name` = ?s AND COLUMN_NAME = ?s";
        $res = self::$db->getOne($query, $table, $column);
        return $res;
    }


    /**
     * Получить столбцы таблицы, которые принимают только два значения: "Y" или "N"
     *
     * @param $table string Исходная таблицы
     *
     * @return array Список столбцов, подходящих под критерий
     */
    public static function filterEnumColumns($table)
    {
        $list = self::getTableTypes($table);

        $bolean_columns = array();
        foreach ($list as $item) {
            if ($item['type'] === "tinyint(1)") {
                $bolean_columns[] = $item['name'];
            }
        }
        return $bolean_columns;
    }

    /**
     * Получить данные для генерации формы
     *
     * @param $table string Исходная таблица
     * @param $id    int Id, по которой идёт выборка
     *
     * @return array Данные для генерации
     */
    public static function rowWithTableTypes($table, $id)
    {
        $empty_row = self::getTableTypes($table);

        if (!$id) {
            return $empty_row;
        }

        $row = self::getById($table, $id);

        if (empty($row)) {
            return $empty_row;
        }


        foreach ($empty_row as &$item) {
            foreach ($row as $key => $value) {
                if ($item['name'] == $key) {
                    $item['value'] = $value;
                    break;
                }
            }
        }
        return $empty_row;
    }


    /**
     * Проверить имеет ли таблица возможность использования SelectBox
     *
     * @param $table string Исходная таблица
     *
     * @return array Список связей
     */
    public static function checkTableHavingSelectBox($table)
    {
        $query = 'SELECT inner_column FROM relations WHERE table_name=?s';
        return self::$db->getAll($query, $table);
    }


    /**
     * Получить список связей "один ко многим"
     *
     * @param $table string Исходная таблица
     * @param $key   string Первичный ключ
     *
     * @return array|FALSE|string
     */
    public static function getTableRelationsOneToMany($table, $key)
    {
        $array = [
            [
                'column' => 'table_name',
                'value' => $table
            ],
            [
                'column' => 'inner_column',
                'value' => $key
            ]
        ];

        $relation = self::getByColumnAndArray('relations', $array);
        return $relation;
    }


    public static function getTableRelationsManyToOne($table)
    {
        $array = [
            [
                'column' => 'foreign_table',
                'value' => $table
            ]
        ];

        $relations = self::getByColumnAndArray('relations', $array, false);

        $result_array = [];
        foreach ($relations as $relation) {
            if ($relation['table_name'] !== $relation['foreign_table']) {

                $filter = [
                    [
                        'column' => 'name',
                        'value' => $relation['table_name']
                    ]
                ];

                $relation['foreign_table_name'] = DB::getByColumnAndArray(
                    'tables', $filter, true, 'full_name'
                );

                $result_array[] = $relation;

            }
        }

        $relations = $result_array;


        return $relations;
    }


    public static function getForeignKeys($db_name, $table, $column)
    {
        $query
            = "select REFERENCED_TABLE_NAME as ref_table, REFERENCED_COLUMN_NAME as ref_column from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where  REFERENCED_COLUMN_NAME<>'' AND TABLE_SCHEMA=?s AND TABLE_NAME = ?s AND COLUMN_NAME=?s";
        return self::$db->getRow($query, $db_name, $table, $column);
    }


    /**
     * Получить количество записей с учётом выборки по массиву
     *
     * @param $table string Таблица, по которой идёт подсчёт
     * @param $cols  array Массив для фильтрации
     *
     * @return string
     */
    public static function countingAdvanced($table, $cols)
    {
        $query = "SELECT COUNT(1) FROM ?n";

        $query .= ' WHERE';

        foreach ($cols as $item) {


            $col = $item['column'];
            $val = $item['value'];

            if ($item['full']) {
                $query .= " `$col`='$val' AND";
            } else {
                $query .= " `$col` LIKE '%$val%' AND";
            }
        }

        $query .= ' 1';

        $res = self::$db->getOne($query, $table);
        return $res ?: 0;
    }


    /**
     * Создать таблицу для контроля других таблиц
     *
     * @param        $db_name            string Исходная база
     * @param string $control_table_name Название контрольной таблицы
     *
     * @return array|FALSE Создана ли база
     */
    public static function createControlTable(
        $db_name, $control_table_name = 'tables'
    )
    {

        $query = 'DROP TABLE IF EXISTS ?n.?n';

        self::$db->query($query, $db_name, $control_table_name);


        $query
            = "CREATE TABLE ?n.?n (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `full_name` varchar(150) NOT NULL,
          `edit` tinyint(1) NOT NULL,
          `position` int(11) NOT NULL,
          `info` text NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `name` (`name`),
          KEY `edit` (`edit`),
          KEY `position` (`position`)
        ) ENGINE=InnoDB";

        self::$db->query($query, $db_name, $control_table_name);

        $relations_table = 'relations';

        $query
            = 'CREATE TABLE ?n.?n (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `table_name` varchar(130) NOT NULL,
      `inner_column` varchar(130) NOT NULL,
      `foreign_table` varchar(130) NOT NULL,
      `foreign_column` varchar(130) NOT NULL,
      PRIMARY KEY (`id`)
    )';
        self::$db->query($query, $db_name, $relations_table);


        $tables = self::getTables($db_name);


        foreach ($tables as $table) {
            $info = [];
            $info['name'] = $table;
            $info['full_name'] = Helper::readableText($info['name']);
            $info['position'] = 1;
            $info['edit'] = 'Y';
            self::save($info, $control_table_name);
        }


        return $tables;
    }


    public static function createAdminTables(
        $db_name, $admin_table = 'admin_users',
        $admin_attemps = 'admin_auth_attempts'
    )
    {
        $query
            = 'CREATE TABLE ?n.?n (
          `id` int(11) NOT NULL,
          `login` varchar(100) NOT NULL,
          `password` varchar(100) NOT NULL,
          `role` varchar(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        DB::$db->query($query, $db_name, $admin_table);


        $query
            = 'ALTER TABLE ?n.?n
          ADD PRIMARY KEY (`id`),
          ADD UNIQUE KEY `login` (`login`);';

        DB::$db->query($query, $db_name, $admin_table);


        $query
            = 'CREATE TABLE ?n.?n (
          `id` int(11) NOT NULL,
          `ip` varchar(30) NOT NULL,
          `count` int(11) NOT NULL DEFAULT 0,
          `last_attempt_time` varchar(30) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        DB::$db->query($query, $db_name, $admin_attemps);


        $query
            = 'ALTER TABLE ?n.?n
          ADD PRIMARY KEY (`id`),
          ADD UNIQUE KEY `ip` (`ip`),
          ADD KEY `last_attempt_time` (`last_attempt_time`);';

        DB::$db->query($query, $db_name, $admin_attemps);
        return true;
    }


    public static function qSELECT($query, $is_one = false)
    {
        if (preg_match('/select/iu', $query)) {
            $result = self::$db->getAll($query);
        } else {
            $result = self::$db->query($query);
            return $result;
        }


        if ($is_one) {
            return @$result[0];
        }

        return $result;
    }


    /**
     * Получить все записи из таблицы (расширенная)
     *
     * @param      $table  string Название таблицы
     * @param int $limit Ограничение
     * @param int $offset Отступ
     * @param bool $search Выражение для поиска
     *
     * @return array|bool|mixed Список записей
     */
    public static function getAllLimit(
        $table, $order_column = 'id', $limit = 0, $offset = 0, $search = false
    )
    {
        if ($limit > 0) {
        } else {
            $limit = 1000;
        }

        $query = "SELECT * FROM `$table`";

        if (!empty($search)) {
            if (empty($search['value'])) {
                return array();
            }
            $column = $search['column'];
            $value = $search['value'];

            $query .= " WHERE $column LIKE '%$value%'";
        }

        if (!empty($order_column)) {
            $query .= " ORDER BY `$order_column` DESC";
        }

        if ($limit > 0) {
            $query .= " LIMIT $limit";

            if ($offset > 0) {
                $query .= " OFFSET $offset";
            }
        }
        return self::qSELECT($query);
    }

    public static function getLastRows($table, $column = 'id', $limit = 5)
    {
        $query = "SELECT * FROM `$table` ORDER BY `$column` DESC LIMIT $limit";
        return self::qSELECT($query);
    }

    public static function transactionStart()
    {
        global $meDoo;
        return $meDoo->pdo->beginTransaction();
    }

    public static function transactionRollback()
    {
        global $meDoo;
        return $meDoo->pdo->rollBack();
    }

    public static function transactionCommit()
    {
        global $meDoo;
        return $meDoo->pdo->commit();
    }


    /**
     * Очистить таблицу
     *
     * @param $table string Название очищаемой таблицы
     */
    public static function clearTable($table)
    {
        $query = 'TRUNCATE TABLE ?n';
        return self::$db->query($query, $table);
    }

}

?>