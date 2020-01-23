/**
 * Получить все записи из таблицы (расширенная)
 * @param $table string Название таблицы
 * @param int $limit Ограничение
 * @param int $offset Отступ
 * @param array|bool|mixed $search_array Список для поиска
 * @param $order
 * @return array|bool|mixed Список записей
 */
function getAllLimitAdvanced($table, $limit = 0, $offset = 0, $search_array, $order)
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
//    echovarDumpPre($query);


    return $db->getAll($query, $table);
}


/**
 * Получить запись по значению `id`
 * @param $table string Исходная таблица
 * @param $id string Значение `id`
 * @return array|FALSE Запись
 */
function getById($table, $id)
{
    global $db;
    $query = 'SELECT * FROM ?n WHERE `id`=?i';
    return $db->getRow($query, $table, $id);
}


/**
 * Добавить запись в таблицу связей таблиц
 * @param $p_data array Данные для добавления
 * @param $table string Исходная таблица
 * @return bool|FALSE|mysqli|resource Результат операции
 */
function saveRelation($p_data, $table)
{
    global $db;
    if (empty($p_data)) {
        return false;
    }

    $columns = getColumnNames($table);
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
function getOneToMany($table, $column, $value, $needed_column, $limit = 0)
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
 * @return bool|FALSE|mysqli|resource Результат операции
 */
function save($p_data, $table, $primary = 'id')
{
    global $db;
    if (empty($p_data)) {
        return false;
    }

    $columns = getColumnNames($table);
    $data = $db->filterArray($p_data, $columns);


    if (!checkExist($table, $primary, $data[$primary])) {
        $query = 'INSERT INTO ?n SET ?u';
        return $db->query($query, $table, $data);
    } elseif (!empty($data[$primary])) {
        $query = 'UPDATE ?n SET ?u WHERE ?n=?s';
        return $db->query($query, $table, $data, $primary, $data[$primary]);
    }
    return true;
}



/**
 * Получить количество записей в таблице
 * @param $table string Таблица, по которой идёт подсчёт
 * @param bool $col Название столбца, по которому идёт выбор (опционально)
 * @param bool $val Значение стоблца, по которому идёт выбор
 * @return int Количество записей
 */
function counting($table, $col = false, $val = false)
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
 * Проверить существование записи, подходящей под указанные фильтры
 * @param $table string Таблица для проверки
 * @param $filter string Ассоциативный Массив условий
 * @return FALSE|string Существует или нет
 */
function checkExistMulti($table, $filter)
{
    global $db;
    $query = 'SELECT `id` FROM ?n WHERE ?x LIMIT 1';
    $is_exist = $db->getOne($query, $table, $filter);
    return $is_exist;
}


/**
 * Проверить существование записи с указанным значением одного поля
 * @param $table string Таблица для проверки
 * @param $column string Поле для проверки
 * @param $value string Контрольное Значение
 * @return FALSE|string Существует или нет
 */
function checkExist($table, $column, $value)
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
function getColumnNames($table_name)
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


function getAll($table)
{
    global $db;
    $query = "SELECT * FROM " . $table;
    return $db->getAll($query);
}

function json_encodeKirilica($val)
{
    return json_encode($val, JSON_UNESCAPED_UNICODE);
}
