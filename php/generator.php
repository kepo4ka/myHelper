<?php


namespace Helper;


class Generator
{

    /**
     * Форматированный вывод элемента списка, который является внешним ключом для $item
     * Formatted output of a list item that is a foreign key for `$item`
     * @param $item mixed Объект, в котором есть внешний ключ
     * @param $list mixed Список, по которому идёт поиск элемента
     * @param $column string Название Столбца внешнего ключа
     * @param $list_column string Название Столбца элемента, который будет выведен
     * @param $name mixed Условное название элемента, в случае, если он не был найден в списке
     */
    public static function listFilterOnlyValue(&$item, $list, $column, $list_column, $name)
    {
        $is_exist = false;
        foreach ($list as $list_item) {
            if ($list_item['id'] == $item[$column]) {
                $is_exist = true;
                $item[$column] = $list_item[$list_column] . ' [' . $list_item['id'] . ']';
                break;
            }
        }

        if (!$is_exist) {
            $item[$column] = "<strong class='text-center'>[" . $item[$column] . "] <span class='text-danger'> $name not found </span> </strong>";
        }
    }


    /**
     * Вывод всего списка, ассоциируемого с элементом
     * List the entire list associated with an item
     * @param $item
     * @param $list
     * @param $column
     * @param $list_column
     */
    public static function listFilter(&$item, $list, $column, $list_column)
    {
        $item_column = $item[$column];
        $item_id = $item['id'];

        $select_html = "<select name='$column' data-id='$item_id' class='nice-select-table table_select_handler selectpicker' data-live-search='true'>";

        if (!Helper::checkListItemExist($list, 'id', $item_column)) {

            $select_html .= "<option value='NONE' selected>
            NONE
        </option>";
        }


        foreach ($list as $list_item) {
            $list_item_id = $list_item['id'];

            $list_item_column = $list_item[$list_column];

            $selected = $item_column == $list_item_id ? 'selected' : '';

            $select_html .= "
        <option value='$list_item_id' $selected>
            $list_item_column
        </option>";

        }
        $select_html .= "</select>";

        $item[$column] = $select_html;
    }


    /**
     * Вывод ссылки на редактирование записи
     * Link to edit post
     * @param $item mixed Объект, который содержит в себе внешний ключ
     * @param $user
     * @param $column
     * @param $link
     * @param $name
     */
     public static function simpleFilter($id, $info)
    {
        $table = $info['foreign_table'];
        $res_key = 'id';
        $res_name = $info['foreign_column'];
        $key = $info['inner_column'];

        $row = DB::getByColumn($table, $res_key, $id);

        if (!empty($id) && !empty($table) && !empty($row)) {
            $foreign_column = @$row[$res_name];
            $str = "<a target='_blank' class='btn btn-link' href = 'edit.php?cat=$table&act=edit&id=$id'>
                   [#$id] $foreign_column</a>";
        } else {
            $str = "<span class=''> $id </span>";
        }
        return $str;
    }


    /**
     * Сгенерировать поле Input без особенностей
     * Generate Input field without features
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genSimpleInput($key, $value)
    {
        ?>
        <div class="col-12 col-md-4 mb-3">
            <label>
                <?= Helper::readableText($key) ?>
            </label>
            <input type="text" class="form-control" name="<?= $key ?>" value='<?= $value ?>'>
        </div>
        <?php
        return true;
    }

    /**
     * Сгенерировать поле, предполагающее значение типа Int
     * Generate Input field without features...
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genIntInput($key, $value)
    {
        if (empty($value)) {
            $value = 0;
        }
        ?>
        <div class="col-12 col-md-4 mb-3">
            <label>
                <?= Helper::readableText($key) ?>
            </label>
            <input type="text" class="form-control" name="<?= $key ?>" value="<?= $value ?>">
        </div>
        <?php
        return true;
    }


    /**
     * Сгенерировать поле, предполагающее значение типa Float|Double
     * Generate a field suggesting a value of type Float | Double
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genFloatInput($key, $value)
    {
        if (empty($value)) {
            $value = 0.00;
        }
        ?>
        <div class="col-12 col-md-4 mb-3">
            <label>
                <?= Helper::readableText($key) ?>
            </label>
            <input type="text" class="form-control" name="<?= $key ?>" value="<?= $value ?>">
        </div>
        <?php
        return true;
    }

    /**
     * Сгенерировать поле с возможностью выбора Даты
     * Generate a field with a choice of Dates
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genDateInput($key, $value, $with_time = false)
    {
        // класс `datepicker` используется самой библиотекой, поэтому даём другое название
        $class = 'onlydatepicker';

        if ($with_time) {
            $class = 'datetimepicker';
        }

        ?>
        <div class="col-12 col-md-4 mb-3">
            <label>
                <?= Helper::readableText($key) ?>
            </label>
            <input type="text" class="form-control <?= $class ?>" name="<?= $key ?>" value="<?= $value ?>">
        </div>
        <?php
        return true;
    }


    /**
     * Сгенерировать поле с аттрибутом Disabled
     * Generate a field with attribute Disabled
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genDisabledInput($key, $value)
    {
        ?>
        <input type="hidden" class="form-control disabled" disabled name="<?= $key ?>" value='<?= $value ?>'>
        <?php
        return true;
    }


    /**
     * Сгенерировать поле, с доступом только для чтения
     * Generate read-only field
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genReadonlyInput($key, $value)
    {
        ?>
        <div class="col-12 col-md-4 mb-3">
            <label>
                <?= Helper::readableText($key) ?>
            </label>
            <input type="text" class="form-control" readonly name="<?= $key ?>" value='<?= $value ?>'>
        </div>
        <?php
        return true;
    }

    /**
     * Сгенерировать TextArea
     * Generate TextArea
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @param bool $with_editor Использовать ли JS редактор для поля
     * @return bool
     */
    public static function genTextarea($key, $value, $with_editor = false)
    {
        $editor_class = '';

        if ($with_editor) {
            $editor_class = 'ckeditor';
        }

        ?>
        <div class="col-12 mb-3">
            <label>
                <?= Helper::readableText($key) ?>
            </label>
            <textarea type="text" cols="10" rows="5" class="form-control <?= $editor_class ?>"
                      name="<?= $key ?>"><?= $value ?></textarea>
        </div>
        <?php
        return true;
    }


    /**
     * Сгенерировать TextArea
     * Generate TextArea
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genJsonEditor($db_name, $table, $input_key, $input_value, $is_bad = false)
    {
        if (DB::getColumnComment($db_name, $table, $input_key) == 'json') {
            $is_json_input = true;
        }

        ?>


        <div class="col-12 mb-5">
            <label>
                <?= Helper::readableText($input_key) ?> (<b class='text-warning font-weight-bold'>JSON</b>)
            </label>

            <?php if ($is_bad) {
                ?>
                <small for="" class="text-danger">
                    Значение поля имело неверный формат, поэтому было сброшено!
                </small>
                <?php
            } ?>

            <div class="tab-content" id="json_container_<?= $input_key ?>Content">

                <?php if ($is_json_input) {
                    ?>
                    <div class="tab-pane fade show active"
                         id="json_editor_json_viewer_<?= $input_key ?>" role="tabpanel"
                         aria-labelledby="json_editor_content<?= $input_key ?>-tab">

                        <div id="jsoneditor_<?= $input_key ?>" style=" height: 400px;"></div>
                        <script>

                            // create the editor
                            const container_<?= $input_key ?> = document.getElementById("jsoneditor_<?= $input_key ?>");
                            const options_<?= $input_key ?> = {

                                onChangeText: function (jsonString) {
                                    $('#json_textarea<?= $input_key ?>').text(jsonString);
                                }
                            };
                            const editor_<?= $input_key ?> = new JSONEditor(container_<?= $input_key ?>, options_<?= $input_key ?>);

                            let val_<?= $input_key ?> = <?= $input_value ?: '[1]' ?>;
                            editor_<?= $input_key ?>.set(val_<?= $input_key ?>);

                        </script>
                    </div>
                    <div class="tab-pane fade" id="json_editor_text_viewer_<?= $input_key ?>" role="tabpanel"
                         aria-labelledby="json_editor_text_viewer_<?= $input_key ?>-tab">

                <textarea type="text" id="json_textarea<?= $input_key ?>" cols="10" rows="10"
                          class="form-control border-radius-0"
                          name="<?= $input_key ?>"
                ><?= stripslashes($input_value) ?></textarea>

                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
        return true;
    }


    /**
     * Сгенерировать CheckBox
     * Generate CheckBox
     * @param $key string Ключ поля
     * @param $value string Значение поля
     * @return bool
     */
    public static function genCheckbox($key, $value)
    {
        ?>
        <div class="col-12 col-md-4 mb-3 ">
            <label for="">
                <?= Helper::readableText($key) ?>
            </label>
            <div class="checkbox form-control border-0">

                <label class="d-flex align-items-center">
                    <input type="hidden" name="<?= $key ?>" value="0">
                    <input type="checkbox" name="<?= $key ?>" <?= @$value ? 'checked' : '' ?>
                           value="1">
                    <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                    <?= Helper::readableText($key) ?>
                </label>
            </div>
        </div>
        <?php
        return true;
    }


    /**
     * Сгеренировать поле Checkbox для записи DataTable
     * Generate Checkbox field for DataTable record
     * @param $item
     * @param $key
     * @param $value
     * @return string
     */
    public static function genTableCheckbox(&$item, $key, $value)
    {
        $text = Helper::readableText($key);

        $checked = $value ? 'checked' : '';
        $item_id = '';

        $disabled = '';

        if (empty($item['id'])) {
            $disabled = 'disabled';
        } else {
            $item_id = $item['id'];
        }

        $check_html = "            
            <div class='checkbox form-control d-flex align-items-center border-0 table_checkbox_container'>        
                <label class='d-flex align-items-center mb-0'>
                    <input type = 'hidden' name = '$key' value='0'>
                    <input type = 'checkbox' data-id='$item_id' $disabled class='table_checkbox_handler $disabled' name='$key' $checked>       
                    <span class='cr'><i class='cr-icon fa fa-check'></i></span>
                   $text
                </label>    
            </div>
        ";

        return $check_html;
    }


    /**
     * Сгенерировать SelectBox для формы
     * Generate SelectBox for the form
     * @param $table string Таблица, из которой берутся элементы
     * @param $key string Ключ поля
     * @param $value string Текущее значение поля
     * @param $res_key string Ключ, по которому идёт проверка списка, полученного из таблицы
     * @param $res_name string Столбец, значение которого будет отображаться в элементе option
     * @return bool
     */
    public static function genSelectBox($table, $key, $value, $res_key, $res_name)
    {
        $res_key = 'id';
        $results = DB::getAllOrdered($table, $res_key);


        $url = $table;

        if (empty($results)) {
            return false;
        }
        ?>
        <div class="col-12 col-md-4">
            <label>
                <?= Helper::readableText($key) ?>
            </label>
            <br>
            <select name="<?= $key ?>" class="nice-select selectpicker col-12 px-0" data-live-search='true'
                    data-url="<?= $url ?>">
                <option <?= empty($value) ? 'selected' : '' ?>
                        value="">
                    0
                </option>

                <?php foreach ($results as $item) {

                    $item[$res_name] = '[' . @$item[$res_key] . '] ' . $item[$res_name];

                    ?>
                    <option <?= $item[$res_key] == @$value ? 'selected' : '' ?>
                            value="<?= @$item[$res_key] ?>">
                        <?= Helper::readableText($item[$res_name]) ?>
                    </option>
                    <?php
                }
                ?>
            </select>
        </div>
        <?php

        return true;
    }


    /**
     * Сгенерировать SelectBox для таблицы
     * Generate SelectBox for the form
     * @param $table string Таблица, из которой берутся элементы
     * @param $key string Ключ поля
     * @param $value string Текущее значение поля
     * @param $res_key string Ключ, по которому идёт проверка списка, полученного из таблицы
     * @param $res_name string Столбец, значение которого будет отображаться в элементе option
     * @return bool
     */
    public static function genTableSelectBox($info, $value, $id)
    {
        $table = $info['foreign_table'];
        $res_key = 'id';
        $res_name = $info['foreign_column'];
        $key = $info['inner_column'];

        $results = DB::getAllOrdered($table, $res_name);

        if (empty($results)) {
            return false;
        }


        ob_start();
        ?>
        <div class="col-12">
            <select name="<?= $key ?>" data-id="<?= $id ?>"
                    class="nice-select-table nice-select table_select_handler selectpicker" data-live-search='true'>

                <option <?= empty($value) ? 'selected' : '' ?>
                        value="">
                    0
                </option>

                <?php foreach ($results as $item) {
                    $item[$res_name] = '[' . @$item[$res_key] . '] ' . $item[$res_name];
                    ?>
                    <option <?= $item[$res_key] == @$value ? 'selected' : '' ?>
                            value="<?= $item[$res_key] ?>">
                        <?= $item[$res_name] ?>
                    </option>
                    <?php
                }
                ?>
            </select>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }


    /**
     * Сгенерировать форму
     * Generate form
     * @param $row mixed Данные для формы
     * @param array $info Дополнительные данные
     */
    public static function generateForm($db_name, $table, $row, $info = [])
    {
        if (empty($info['primary'])) {
            $info['primary'] = 'id';
        }

        foreach ($row as $key => $value) {

            $type = 'simple';
            $relations = DB::getTableRelationsOneToMany($table, $value['name']);
            $comment = DB::getColumnComment($db_name, $table, $value['name']);

            if ($value['name'] == 'id' && preg_match('/int\(/', $value['type'])) {
                $type = 'disabled';
            } else if ($comment == 'json') {
                if (!Helper::checkJson($value['value'])) {
                    $type = 'bad_json';
                } else {
                    $type = 'json';
                }
            } else if ($comment == 'editor') {
                $type = 'blob';
            } else if (!empty($relations)) {
                $type = 'selectbox';
            } else if (false !== strpos($value['type'], 'tinyint(1)')) {
                $type = 'checkbox';
            } else if (false !== strpos($value['type'], 'tinyint(1)')) {
                $type = 'checkbox';
            } else if (preg_match('/int\(/', $value['type'])) {
                $type = 'int';
            } else if (false !== strpos($value['type'], 'float')) {
                $type = 'float';
            } else if (false !== strpos($value['type'], 'double')) {
                $type = 'float';
            } else if (false !== strpos($value['type'], 'datetime')) {
                $type = 'datetime';
                $type = 'float';
            } else if (false !== strpos($value['type'], 'date')) {
                $type = 'date';
            } else if (false !== strpos($value['type'], 'blob')) {
                $type = 'blob';
            } else if (false !== strpos($value['type'], 'text')) {
                $type = 'text';
            }


//        switch ($value['name']) {
//            case $info['primary'];
//                $type = 'disabled';
//                break;
//        }
            self::generateFormInput($db_name, $table, $value['name'], $value['value'], $type);
        }
    }


    /**
     * Сгенерировать поле формы
     * Generate form field
     * @param $key string Название поля
     * @param $value string значение поля
     * @param $type string Тип поля
     */
    public static function generateFormInput($db_name, $table, $key, $value, $type)
    {
        $act = 'add';

        if (!empty($_GET['act'] && $_GET['act'] === 'edit')) {
            $act = 'edit';
        }


        switch ($type) {
            case 'simple':
                self::genSimpleInput($key, $value);
                break;

            case 'readonly':
                self::genReadonlyInput($key, $value);
                break;

            case 'blob':
                self::genTextarea($key, $value, true);
                break;
            case 'text':
                self::genTextarea($key, $value);
                break;

            case 'int':
                self::genIntInput($key, $value);
                break;

            case 'float':
            case 'double':
                self::genFloatInput($key, $value);
                break;
            case 'disabled':
                self::genDisabledInput($key, $value);
                break;

            case 'checkbox':
                self::genCheckbox($key, $value);
                break;

            case 'datetime':
                self::genDateInput($key, $value, true);
                break;
            case 'date':
                self::genDateInput($key, $value);
                break;

            case 'json':
                self::genJsonEditor($db_name, $table, $key, $value);
                break;

            case 'bad_json':
                self::genJsonEditor($db_name, $table, $key, $value, true);
                break;

            case 'selectbox':
                $relation = DB::getTableRelationsOneToMany($table, $key);

                if (!empty($relation) && !empty($relation['is_small'])) {
                    if ($relation['inner_column'] == $key) {
                        self::genSelectBox(
                            $relation['foreign_table'],
                            $relation['inner_column'], $value,
                            $relation['foreign_column'],
                            $relation['foreign_column']
                        );
                        break;
                    }
                } else {
                    self::genSimpleInput($key, $value);
                }

                break;
        }
    }


    /**
     * Сгенерировать CRUD кнопки для записи
     * Generate CRUD buttons for recording
     * @param array $info Информация, необходимая для генерации
     * @return bool|string Сгенерированный html код
     */
    public static function getActionButtons(array $info = array())
    {

        if (empty($info) || empty($info['page_name'])
            || empty($info['table'])
            || empty($info['data_type'])) {
            return false;
        }

        $html = "<div class='btn-group-vertical'>";
        $page_name = $info['page_name'];
        $id = $info['id'];


        if (!empty($info['edit'])) {
            $html .= " <a class='btn btn-primary' href='edit.php?cat=$page_name&act=edit&id=$id'>
                <i class='fa fa-edit'></i>
                     Редактировать
            </a>";
        }

        if (!empty($info['relations'])) {
            $html .= '<div class="btn-group" role="group">
    <button  type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      <i class="fa fa-boxes"></i>
      Связи
    </button>
    <div class="dropdown-menu">';
            foreach ($info['relations'] as $relation_info) {
                $table_name = $relation_info['table_name'];
                $table_full_name = !empty($relation_info['foreign_table_name']) ? $relation_info['foreign_table_name'] : Helper::readableText($table_name);
                $get_column = $relation_info['inner_column'];
                $get_value = $id;

                $html .= "
            <a class='dropdown-item' href='list.php?cat=$table_name&get_column=$get_column&get_value=$get_value'>";
                $html .= $table_full_name;
                $html .= "</a>";
            }

            $html .= '</div></div>';
        }

        if (!empty($info['copy'])) {
            $html .= "
            <a class='btn btn-dark' href='edit.php?cat=$page_name&act=add&id=$id'>
                <i class='far fa-copy'></i>
                    Копировать 
            </a>";
        }


        if (!empty($info['delete'])) {
            $html .= " 
            <a class='btn btn-danger delete_row_handler' href = 'save.php?act=delete&id=$id&cat=$page_name&method=ajax'>
                <i class='fa fa-trash'></i> 
                Удалить
            </a>";
        }

        $html .= "</div";

        return $html;
    }


    /**
     * Сгенерировать стандартную страницу с Таблицей, содержащей список записей (источник передаётся через global)
     * Generate a standard page with a Table containing a list of records (the source is transmitted through global)
     * Например, список новостей
     */
    public static function generateStandartTablePage($table)
    {
        if (empty($table)) {
            return false;
        }

        $table_info = DB::getByColumn('tables', 'name', $table);

        $columns_array = DB::getColumnsReadable($table);

        $data_columns = $columns_array['data_columns'];
        $columns = $columns_array['columns'];
        ?>

        <input type="hidden" class="table_columns" value='<?= json_encode($data_columns) ?>'>

        <div class="d-flex justify-content-between">
            <div>
                <a class="btn btn-primary" href="edit.php?cat=<?= $table ?>&act=add">
                    <i class="fa fa-plus-circle"></i>
                    Добавить запись
                </a>
            </div>

            <div>

                <?php if (!empty($_GET['get_column'])) {
                    ?>
                    <a class="btn btn-primary" href="list.php?cat=<?= $table ?>">
                        <i class="fa fa-list"></i>
                        Все записи <?= $table_info['name'] ?>
                    </a>
                    <?php
                } ?>

                <a class="btn btn-danger" href="save.php?cat=<?= $table ?>&act=clear"
                   onclick="return navConfirm(this.href, 'The Table will be cleared! Continue?');">
                    <i class="fa fa-ban"></i>
                    Очистить таблицу <?= $table_info['name'] ?>
                </a>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-6">
                <h1>
                    <?= $table_info['full_name'] ?>
                </h1>
                <p>
                    <span>
                        Количество записей:
                    </span>
                    <span class="badge badge-pill badge-success">
                        <?= DB::counting($table) ?>
                    </span>
                </p>
            </div>


            <div class="col-12">
                <?php if (!empty($_GET['status']) && !empty($_GET['message'])) {
                    $class = '';
                    $message = '';

                    if ($_GET['status'] === 'success') {
                        $class = 'alert-success';

                        if ($_GET['message'] == 'add') {
                            $message = 'Запись успешно добавлена!';
                        } else {
                            $message = 'Запись успешно обновлена!';
                        }
                    } else {
                        $class = 'alert-error';

                        if ($_GET['message'] === 'add') {
                            $message = 'Row Not Added!';
                        } else {
                            $message = 'Row Not Updated!';
                        }
                    }
                    ?>

                    <div class="alert <?= $class ?>" role="alert">
                        <?= $message ?>
                    </div>
                    <?php
                } ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12 ">
                <table id="serverside" class="table table-striped table-bordered display " style="width:100%">
                    <thead>
                    <tr>
                        <th class="no-sort">
                            Действие
                        </th>
                        <?php foreach ($columns as $column) {
                            ?>
                            <th>
                                <?= $column ?>
                            </th>
                            <?php
                        } ?>
                    </tr>
                    </thead>

                    <tfoot>
                    <tr>
                        <th>Поиск <br><span class='text-danger'>(не менее 3-5 символов)</span></th>
                        <?php foreach ($columns as $column) {
                            ?>
                            <th>
                                <?= $column ?>
                            </th>
                            <?php
                        } ?>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php
        return true;
    }


    /**
     * Сгенерировать страницу для Редактирования информации о Записи в таблицы
     * Generate a page for editing table entry information
     * @param bool $read_user_id
     */
    public static function generateStandartEditPage($db_name, $table)
    {

        $act = $_GET['act'];
        @$id = (int)$_GET['id'];
        $row = DB::rowWithTableTypes($table, $id);

        $new_row = [];

        foreach ($row as $item) {
            $key = $item['name'];
            if (!empty($_GET[$key]) && $key !== 'id') {
                $item['value'] = $_GET[$key];
            }
            if (!empty($_POST[$key]) && $key !== 'id') {
                $item['value'] = $_POST[$key];
            }
            $new_row[] = $item;
        }
        $row = $new_row;


        ?>

        <a class="btn btn-outline-secondary" href="list.php?cat=<?= $table ?>"
           onclick="return navConfirm(this.href, 'Изменения не будут сохранены. Продолжить?');">
            <i class="fa fa-arrow-circle-left"></i>
            Вернуться в список <?= Helper::readableText($table) ?>
        </a>
        <hr>

        <form method="post" action="save.php" enctype='multipart/form-data'>

            <fieldset>

                <input name="cat" type="hidden" value="<?= @$table ?>">
                <input name="id" type="hidden" value="<?= @$id ?>">
                <input name="act" type="hidden" value="<?= @$act ?>">

                <legend class="hidden-first mb-0">
                    <?php if ($act == "edit") { ?>
                        Редактировать запись #<?= $id ?>
                    <?php } else { ?>
                        Добавить запись
                    <?php } ?>
                </legend>
                <hr class="mb-3">

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="custom_content_slot border">

                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <?php
                    self::generateForm($db_name, $table, $row);
                    ?>
                </div>

                <div class="row">
                    <div class="col-12">
                        <!--                    <input type="submit" value=" Save " class="btn btn-success">-->

                        <?php if ($act == "edit") { ?>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i>
                                Обновить
                            </button>
                        <?php } else { ?>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-plus"></i>
                                Сохранить
                            </button>
                        <?php } ?>
                    </div>
                </div>
        </form>
        <?php
    }


    public static function generateAnotherKeyEditPage($table, $cat, $list_name, $key_name)
    {
        $act = $_GET['act'];
        @$id = (int)$_GET['id'];

        $empty_row = DB::getTableTypes($table);

        if (!empty($id)) {
            $search_array = array();
            $search_item['column'] = $key_name;
            $search_item['value'] = $id;
            $search_item['full'] = true;
            $search_array[] = $search_item;

            $order['column'] = $search_item['column'];
            $order['dir'] = 'desc';

            $row = DB::getAllLimitAdvanced($table, 1, 0, $search_array, $order);

            if (empty($row[0])) {
                return false;
            }
            $row = $row[0];

            foreach ($empty_row as &$item) {
                foreach ($row as $key => $value) {
                    if ($item['name'] == $key) {
                        $item['value'] = $value;
                        break;
                    }
                }
            }
            unset($item);
        }

        $row = $empty_row;

        ?>

        <a class="btn btn-outline-secondary" href="<?= $cat ?>.php"
           onclick="return navConfirm(this.href, 'Изменения не будут сохранены. Продолжить?');">
            <i class="fa fa-arrow-circle-left"></i>
            Вернуться в общий список <?= $list_name ?> С
        </a>
        <hr>

        <form method="post" action="save.php" enctype='multipart/form-data'>
            <fieldset>

                <input name="cat" type="hidden" value="<?= @$cat ?>">
                <input name="id" type="hidden" value="<?= @$id ?>">
                <input name="act" type="hidden" value="<?= @$act ?>">

                <legend class="hidden-first">
                    <?php if ($act == "edit") { ?>
                        Редактировать запись #<?= $id ?>
                    <?php } else { ?>
                        Добавить запись
                    <?php } ?>
                </legend>

                <div class="row mb-3">
                    <?php
                    generateForm($row);
                    ?>
                </div>

                <div class="row">
                    <div class="col-12">
                        <input type="submit" value=" Сохранить " class="btn btn-success">
                    </div>
                </div>
        </form>
        <?php
        return true;
    }


    public static function uniqueColumnUpDownButtons($item, $column, $min_value, $max_value, $page_name)
    {
        $id = $item['id'];

        $up_position = $item[$column] + 1;
        $down_position = $item[$column] - 1;


        $temp =
            "<div class='btn-group-vertical'>";


        if ($item[$column] > $min_value) {
            $temp .= "<a href = 'save.php?act=edit&id=$id&cat=$page_name&$column=$down_position' class='btn btn-lg btn-outline-info w-min-content' >
                    <i class='fa fa-arrow-up mr-0 py-1' ></i >
                </a >";
        }

        $temp .= "<div  class='btn btn-lg font-weight-bold w-min-content'>"
            . $item[$column] .
            "</div>";


        if ($item[$column] < $max_value && $item[$column] >= 1) {
            $temp .= "<a href='save.php?act=edit&id=$id&cat=$page_name&$column=$up_position' class='btn btn-lg btn-outline-info w-min-content'>
                    <i class='fa fa-arrow-down mr-0 py-1'></i>
                </a>";
        }

        $temp .= "</div > ";

        return $temp;

    }


    /**
     * Сгенерировать Кнопки для строки DataTable
     * Generate Action buttons for Datatable row
     * @param $page_name
     * @param $table string Source table
     * @param $item mixed Table row
     * @param $type string page url
     * @return bool|string generated html
     */
    public static function genActionButtons($page_name, $table, &$item, $type)
    {
        if (empty($item['id'])) {
            return '';
        }

        $id = $item['id'];

        $info['table'] = $table;
        $info['page_name'] = $page_name;

        $info['id'] = $id;

        $info['data_type'] = $type;
        $info['edit'] = true;
        $info['copy'] = false;
        $info['delete'] = true;

        $relations = DB::getTableRelationsManyToOne($table);

        if (!empty($relations)) {
            $info['relations'] = $relations;
        }

        return self::getActionButtons($info);
    }
}