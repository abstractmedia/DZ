<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.8.0
 *  LICENSE: RU9018RLF896 - https://www.flynax.ru/user-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: domozhil.ru
 *  FILE: INDEX.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2020 | All copyrights reserved.
 *  
 *  https://www.flynax.ru
 ******************************************************************************/

class rlFields extends reefless
{
    /**
     * @var new fields ID
     **/
    public $addID;

    /**
     * @var fields data table name to work with
     **/
    public $table = 'listing_fields';

    /**
     * @var source data table name to work with
     **/
    public $source_table = 'listings';

    /**
     * @var submit field details for add/edit field methods
     **/
    public $submit_data = array();

    /**
     * create new listing field
     *
     * @param string $type - field type
     * @param array $key - field key
     * @param array $langs - available system languages
     *
     * @return bool
     **/
    public function createField($type = false, $key = false, &$langs)
    {
        if (!$type || !$key) {
            return false;
        }

        // insert field information
        $info = array(
            'Key'      => $key,
            'Default'  => '',
            'Type'     => $type,
            'Required' => (int) $_POST['required'],
            'Map'      => (int) $_POST['map'],
            'Status'   => $_POST['status'],
            'Contact'  => $_POST['contact'] ? 1 : 0,
        );

        $info['Add_page'] = empty($_POST['add_page']) ? '0' : '1';
        $info['Details_page'] = empty($_POST['details_page']) ? '0' : '1';

        foreach ($langs as $lang_item) {
            $lang_keys[] = array(
                'Code'   => $lang_item['Code'],
                'Module' => 'common',
                'Status' => 'active',
                'Key'    => $this->table . '+name+' . $key,
                'Value'  => $_POST['name'][$lang_item['Code']],
            );

            if (!empty($_POST['description'][$lang_item['Code']])) {
                $lang_keys[] = array(
                    'Code'   => $lang_item['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => $this->table . '+description+' . $key,
                    'Value'  => $_POST['description'][$lang_item['Code']],
                );
            }
        }

        // generate lang keys and type's additional information
        switch ($type) {
            case 'text':
                if ($_POST[$type]['maxlength'] > 255) {
                    $info['Values'] = 255;
                } elseif ($_POST['text']['maxlength'] < 1) {
                    $info['Values'] = 50;
                } else {
                    $info['Values'] = (int) $_POST['text']['maxlength'];
                }

                $info['Multilingual'] = $_POST['text']['multilingual'];
                $info['Condition'] = $_POST['text']['condition'];

                foreach ($langs as $lang_item) {
                    if (!empty($_POST['text']['default'][$lang_item['Code']])) {
                        $info['Default'] = 1;

                        $lang_keys[] = array(
                            'Code'   => $lang_item['Code'],
                            'Module' => 'common',
                            'Status' => 'active',
                            'Key'    => $this->table . '+default+' . $key,
                            'Value'  => $GLOBALS['rlValid']->xSql($_POST['text']['default'][$lang_item['Code']]),
                        );
                    }
                }

                /* change field type to MEDIUMTEXT */
                if ($info['Multilingual']) {
                    $new_length = ($info['Values'] + 13) * count($langs);
                    if ($new_length > 255) {
                        $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                    } else {
                        $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR({$new_length}) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                    }
                }
                /* set default field type/length */
                else {
                    $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR({$info['Values']}) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                }
                break;

            case 'textarea':
                $info['Condition'] = $_POST[$type]['html'] ? 'html' : '';
                $info['Values'] = (int) $_POST[$type]['maxlength'] < 3000
                ? ((int) $_POST[$type]['maxlength'] ?: 500)
                : 3000;
                $info['Multilingual'] = (int) $_POST[$type]['multilingual'];

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                break;

            case 'number':
                $info['Values'] = (int) $_POST[$type]['max_length'] ?: '';
                $info['Opt1'] = $_POST[$type]['format'] ? 1 : 0;
                $info['Opt2'] = $_POST[$type]['thousands_sep'];

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` DOUBLE NOT NULL";
                break;

            case 'phone':
                $info['Condition'] = $_POST['phone']['condition']; //phone data format

                $info['Default'] = (int) $_POST['phone']['area_length']; //area length
                $info['Values'] = (int) $_POST['phone']['phone_length']; //phone length
                $info['Opt1'] = $_POST['phone']['code'] ? 1 : 0; //code
                $info['Opt2'] = $_POST['phone']['ext'] ? 1 : 0; //ext

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR( 35 ) NOT NULL";
                break;

            case 'date':
                $info['Default'] = $_POST['date']['mode'];

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` DATE NOT NULL";
                if ($_POST['date']['mode'] == 'multi') {
                    $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}_multi` DATE NOT NULL";
                }
                break;

            case 'price':
            case 'unit':
                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR( 100 ) NOT NULL";
                break;

            case 'bool':
                $info['Default'] = (int) $_POST['bool']['default'];

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` ENUM( '0', '1' ) DEFAULT '{$info['Default']}' NOT NULL";
                break;

            case 'mixed':
                $info['Condition'] = $_POST['mixed_data_format'];
                $info['Opt1'] = $_POST[$type]['format'] ? 1 : 0;
                $info['Opt2'] = $_POST[$type]['thousands_sep'];

                if (!$info['Condition']) {
                    $info['Default'] = (int) $_POST[$type]['default'];
                    unset($_POST[$type]['default'], $_POST[$type]['format'], $_POST[$type]['thousands_sep']);

                    foreach ($_POST[$type] as $sKey => $sVal) {
                        foreach ($langs as $lang_item) {
                            $lang_keys[] = array(
                                'Code'   => $lang_item['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => $this->table . '+name+' . $key . '_' . $sKey,
                                'Value'  => $sVal[$lang_item['Code']],
                            );
                        }
                        // build multivalues field
                        $info['Values'] = $info['Values'] ? $info['Values'] . ',' . $sKey : $sKey;
                    }
                }

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR(255) NOT NULL";
                break;

            case 'checkbox':
                $info['Opt1'] = $_POST['show_tils'] ? 1 : 0;
                $info['Opt2'] = (int) $_POST['column_number'];
            case 'select':
            case 'radio':
                $info['Condition'] = !empty($_POST['data_format']) ? $_POST['data_format'] : '';
                if (!$info['Condition']) {
                    if ($type == 'checkbox') {
                        $info['Default'] = implode(",", $_POST[$type]['default']);
                    } else {
                        $info['Default'] = (int) $_POST[$type]['default'];
                    }
                    unset($_POST[$type]['default']);

                    foreach ($_POST[$type] as $sKey => $sVal) {
                        foreach ($langs as $lang_item) {
                            $lang_keys[] = array(
                                'Code'   => $lang_item['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => $this->table . '+name+' . $key . '_' . $sKey,
                                'Value'  => $sVal[$lang_item['Code']],
                            );
                        }
                        // build multivalues field
                        $mValues .= $sKey . ',';
                    }

                    $info['Values'] = substr($mValues, 0, -1);
                }

                // alter table field
                if ($type == 'checkbox' && $info['Condition']) {
                    $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                } else {
                    $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR( 255 ) NOT NULL";
                }
                break;

            case 'image':
                $info['Default'] = $_POST['image']['resize_type'];

                if ($_POST['image']['resize_type'] == 'C') {
                    $info['Values'] = (int) $_POST['image']['width'] . '|' . (int) $_POST['image']['height'];
                } elseif ($_POST['image']['resize_type'] == 'W') {
                    $info['Values'] = (int) $_POST['image']['width'];
                } elseif ($_POST['image']['resize_type'] == 'H') {
                    $info['Values'] = (int) $_POST['image']['height'];
                }

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR( 255 ) NOT NULL";
                break;

            case 'file':
                $info['Default'] = $_POST['file']['type'];

                // alter table field
                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` VARCHAR( 255 ) NOT NULL";
                break;

            case 'accept':
                $info['Default'] = $_POST['accept_page'];
                $info['Values']  = implode(',', $_POST['atypes']);
                $info['Opt1']    = $_POST['first_step'] == '1' ? 1 : 0;

                $alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}` ENUM('0', '1') DEFAULT '0' NOT NULL";
                break;
        };

        if ($this->query($alter)) {
            // run additional alter query
            if (!empty($additional_alter)) {
                if (!$this->query($additional_alter)) {
                    $GLOBALS['rlDebug']->logger("Can not create additional {$this->source_table} field (MYSQL ALTER QUERY FAIL)");
                }
            }

            // insert new fiels information
            $GLOBALS['rlActions']->insertOne($info, $this->table);
            $this->addID = $this->insertID();

            // insert new fiels language's keys
            $GLOBALS['rlActions']->insert($lang_keys, 'lang_keys');

            return true;
        } else {
            $GLOBALS['rlDebug']->logger("Can not create new {$this->source_table} field (MYSQL ALTER QUERY FAIL)");
        }

        return false;
    }

    /**
     * edit field
     *
     * @param string $type - field type
     * @param array $key - edit field key
     * @param array $langs - available system languages
     *
     * @return bool
     **/
    public function editField($type = false, $key = false, &$langs)
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$type || !$key) {
            return false;
        }

        $lang_rewrite = true;

        // edit field information
        $info['where'] = array(
            'Key' => $key,
        );

        $info['fields'] = array(
            'Required' => (int) $_POST['required'],
            'Map'      => (int) $_POST['map'],
            'Status'   => $_POST['status'],
            'Contact'  => $_POST['contact'] ? 1 : 0,
        );

        $info['fields']['Add_page'] = empty($_POST['add_page']) ? '0' : '1';
        $info['fields']['Details_page'] = empty($_POST['details_page']) ? '0' : '1';

        foreach ($langs as $lang_item) {
            if ($this->getOne('ID', "`Key` = '{$this->table}+name+{$key}' AND `Code` = '{$lang_item['Code']}'", 'lang_keys')) {
                // edit names
                $update_phrases = array(
                    'fields' => array(
                        'Value' => $_POST['name'][$lang_item['Code']],
                    ),
                    'where'  => array(
                        'Code' => $lang_item['Code'],
                        'Key'  => $this->table . '+name+' . $key,
                    ),
                );

                // update
                $GLOBALS['rlActions']->updateOne($update_phrases, 'lang_keys');
            } else {
                // insert names
                $insert_phrases = array(
                    'Code'   => $lang_item['Code'],
                    'Module' => 'common',
                    'Key'    => $this->table . '+name+' . $key,
                    'Value'  => $_POST['name'][$lang_item['Code']],
                );

                // insert
                $GLOBALS['rlActions']->insertOne($insert_phrases, 'lang_keys');
            }

            $exist_description = $this->getOne('ID', "`Key` = '{$this->table}+description+{$key}' AND `Code` = '{$lang_item['Code']}'", 'lang_keys');
            if ($exist_description) {
                // update fields description
                $lang_keys_desc['where'] = array(
                    'Code' => $lang_item['Code'],
                    'Key'  => $this->table . '+description+' . $key,
                );

                $lang_keys_desc['fields'] = array(
                    'Value' => $_POST['description'][$lang_item['Code']],
                );
                $GLOBALS['rlActions']->updateOne($lang_keys_desc, 'lang_keys');
            } else {
                // insert new description
                if (!empty($_POST['description'][$lang_item['Code']])) {
                    $field_description = array(
                        'Code'   => $lang_item['Code'],
                        'Module' => 'common',
                        'Status' => 'active',
                        'Key'    => $this->table . '+description+' . $key,
                        'Value'  => $_POST['description'][$lang_item['Code']],
                    );
                    $GLOBALS['rlActions']->insertOne($field_description, 'lang_keys');
                }
            }
        }

        // generate lang keys and types for additional information
        switch ($type) {
            case 'text':
                $info['fields']['Condition'] = $_POST['text']['condition'];
                $info['fields']['Multilingual'] = $_POST['text']['multilingual'];
                $info['fields']['Default'] = '';

                if ($_POST['text']['maxlength'] > 255) {
                    $info['fields']['Values'] = 255;
                } elseif ($_POST['text']['maxlength'] < 1) {
                    $info['fields']['Values'] = 50;
                } else {
                    $info['fields']['Values'] = (int) $_POST['text']['maxlength'];
                }

                if ($key != 'keyword_search') {
                    /* change field type to MEDIUMTEXT */
                    if ($info['fields']['Multilingual']) {
                        $new_length = ($info['fields']['Values'] + 13) * count($langs);
                        if ($new_length > 255) {
                            $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` CHANGE `{$key}` `{$key}` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                        } else {
                            $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` CHANGE `{$key}` `{$key}` VARCHAR({$new_length}) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                        }
                    }
                    /* change field type to VARCHAR */
                    else {
                        $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` CHANGE `{$key}` `{$key}` VARCHAR({$info['fields']['Values']}) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";

                        /* remove tags from the listings */
                        //$custom_sql = "UPDATE `". RL_DBPREFIX ."{$this -> source_table}` SET `{$key}` = IF (LOCATE('{|/', `{$key}`) > 0, IF (LOCATE('{|{$config['lang']}|}', `{$key}`) > 0, SUBSTRING(`{$key}` FROM LOCATE('{|{$config['lang']}|}', `{$key}`)+6 FOR LOCATE('{|/{$config['lang']}|}', `{$key}`) - LOCATE('{|{$config['lang']}|}', `{$key}`)-6), SUBSTRING(`{$key}` FROM 7 FOR LOCATE('{|/', `{$key}`)-7)), `{$key}`) WHERE `{$key}` IS NOT NULL";
                        //$this -> query($custom_sql);
                        // Let's keep the tags and will just handle output properly
                    }
                }

                foreach ($langs as $lang_item) {
                    if (!empty($_POST['text']['default'][$lang_item['Code']])) {
                        $info['fields']['Default'] = 1;

                        $lang_keys[] = array(
                            'Code'   => $lang_item['Code'],
                            'Module' => 'common',
                            'Status' => 'active',
                            'Key'    => $this->table . '+default+' . $key,
                            'Value'  => $_POST['text']['default'][$lang_item['Code']],
                        );
                    }
                }

                break;

            case 'textarea':
                $info['fields']['Condition'] = $_POST[$type]['html'] ? 'html' : '';
                $info['fields']['Values'] = (int) $_POST[$type]['maxlength'] < 3000
                ? ((int) $_POST[$type]['maxlength'] ?: 500)
                : 3000;
                $info['fields']['Multilingual'] = (int) $_POST[$type]['multilingual'];

                if ($this->getOne('Multilingual', "`Key` = '{$key}'", $this->table) != $info['fields']['Multilingual'] && !$info['fields']['Multilingual']) {
                    /* remove tags from the listings */
                    //$custom_sql = "UPDATE `". RL_DBPREFIX ."{$this -> source_table}` SET `{$key}` = IF (LOCATE('{|{$config['lang']}|}', `{$key}`) > 0, SUBSTRING(`{$key}` FROM LOCATE('{|{$config['lang']}|}', `{$key}`)+6 FOR LOCATE('{|/{$config['lang']}|}', `{$key}`) - LOCATE('{|{$config['lang']}|}', `{$key}`)-6), SUBSTRING(`{$key}` FROM 7 FOR LOCATE('{|/', `{$key}`)-7)) WHERE `{$key}` IS NOT NULL";
                    //$this -> query($custom_sql);
                    // Let's keep the tags and will just handle output properly
                }
                break;

            case 'number':
                $info['fields']['Values'] = (int) $_POST[$type]['max_length'] ?: '';
                $info['fields']['Opt1'] = $_POST[$type]['format'] ? 1 : 0;
                $info['fields']['Opt2'] = $_POST[$type]['thousands_sep'];

                $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ";
                $additional_alter .= "CHANGE `{$key}` `{$key}` DOUBLE NOT NULL";
                break;

            case 'phone':
                $info['fields']['Condition'] = $_POST['phone']['condition']; //phone data format

                $info['fields']['Default'] = (int) $_POST['phone']['area_length']; //area length
                $info['fields']['Values'] = (int) $_POST['phone']['phone_length']; //phone length
                $info['fields']['Opt1'] = $_POST['phone']['code'] ? 1 : 0; //code
                $info['fields']['Opt2'] = $_POST['phone']['ext'] ? 1 : 0; //ext
                break;

            case 'date':
                $info['fields']['Default'] = $_POST['date']['mode'];

                if ($_POST['date']['mode'] == 'multi') {
                    if (!$this->getRow("SHOW FIELDS FROM `{db_prefix}{$this->source_table}` WHERE `Field` LIKE '{$key}_multi'")) {
                        $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` ADD `{$key}_multi` DATE NOT NULL AFTER `{$key}`";
                    }
                } elseif ($_POST['date']['mode'] == 'single') {
                    if ($this->getRow("SHOW FIELDS FROM `{db_prefix}{$this->source_table}` LIKE '{$key}_multi'")) {
                        $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` DROP `{$key}_multi`";
                    }
                }
                break;

            case 'bool':
                $info['fields']['Default'] = (int) $_POST['bool']['default'];
                break;

            case 'mixed':
                $info['fields']['Condition'] = $_POST['mixed_data_format'];
                $info['fields']['Opt1'] = $_POST[$type]['format'] ? 1 : 0;
                $info['fields']['Opt2'] = $_POST[$type]['thousands_sep'];

                if (!$_POST['mixed_data_format']) {
                    $info['fields']['Default'] = (int) $_POST[$type]['default'];
                    unset($_POST[$type]['default'], $_POST[$type]['format'], $_POST[$type]['thousands_sep']);

                    foreach ($_POST[$type] as $sKey => $sVal) {
                        foreach ($langs as $lang_item) {
                            $lang_keys[] = array(
                                'Code'   => $lang_item['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => $this->table . '+name+' . $key . '_' . $sKey,
                                'Value'  => $sVal[$lang_item['Code']],
                            );
                        }
                        // build multivalues field
                        $info['fields']['Values'] = $info['fields']['Values']
                        ? $info['fields']['Values'] . ',' . $sKey : $sKey;
                    }
                }

                break;

            case 'checkbox':
                $info['fields']['Opt1'] = $_POST['show_tils'] ? '1' : '0';
                $info['fields']['Opt2'] = (int) $_POST['column_number'];

                $struct_info = $this->getRow("SHOW FIELDS FROM `{db_prefix}listings` WHERE `Field` = '{$key}'");
                if ($_POST['data_format'] && strtolower($struct_info['Type']) != 'mediumtext') {
                    $additional_alter = "ALTER TABLE `{db_prefix}{$this->source_table}` CHANGE `{$key}` `{$key}` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
                }
            case 'select':
            case 'radio':
                $info['fields']['Condition'] = !empty($_POST['data_format']) ? $_POST['data_format'] : '';

                if (!$_POST['data_format']) {
                    if ($type == 'checkbox') {
                        $info['fields']['Default'] = implode(",", $_POST[$type]['default']);
                    } else {
                        $info['fields']['Default'] = $_POST[$type]['default'];
                    }

                    unset($_POST[$type]['default']);

                    foreach ($_POST[$type] as $sKey => $sVal) {
                        foreach ($langs as $lang_item) {
                            $lang_keys[] = array(
                                'Code'   => $lang_item['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => $this->table . '+name+' . $key . '_' . $sKey,
                                'Value'  => $sVal[$lang_item['Code']],
                            );
                        }
                        // build multivalues field
                        $mValues .= $sKey . ',';
                    }
                    $info['fields']['Values'] = substr($mValues, 0, -1);
                }

                break;

            case 'image':
                $info['fields']['Default'] = $_POST['image']['resize_type'];

                if ($_POST['image']['resize_type'] == 'C') {
                    $info['fields']['Values'] = (int) $_POST['image']['width'] . '|' . (int) $_POST['image']['height'];
                } elseif ($_POST['image']['resize_type'] == 'W') {
                    $info['fields']['Values'] = (int) $_POST['image']['width'];
                } elseif ($_POST['image']['resize_type'] == 'H') {
                    $info['fields']['Values'] = (int) $_POST['image']['height'];
                }
                break;

            case 'file':
                $info['fields']['Default'] = $_POST['file']['type'];
                break;

            case 'accept':
                $info['fields']['Default'] = $_POST['accept_page'];
                $info['fields']['Values']  = implode(',', $_POST['atypes']);
                $info['fields']['Opt1']    = $_POST['first_step'] == '1' ? 1 : 0;

                if ($_POST['first_step'] == '1') {
                    $field_id = $rlDb->getOne('ID', "`Key` = '{$key}'", 'account_fields');

                    // remove field from the registration form builder
                    $rlDb->delete(array('Field_ID' => $field_id), 'account_submit_form');
                }

                break;
        };

        if (!empty($info)) {
            // run additional alter query
            if (!empty($additional_alter)) {
                if (!$this->query($additional_alter)) {
                    $GLOBALS['rlDebug']->logger("Can not create additional {$this->source_table} field (MYSQL ALTER QUERY FAIL)");
                }
            }

            // insert new fiels information
            $GLOBALS['rlActions']->updateOne($info, $this->table);
        }

        if (!empty($lang_keys) && $lang_rewrite === true) {
            // delete languages phrases by current field
            $lSql = "DELETE FROM `{db_prefix}lang_keys` WHERE (`Key` REGEXP '^{$this->table}\\\\+([a-zA-Z]*)\\\\+{$key}_([0-9]*)$' OR `Key` = '{$this->table}+default+{$key}') AND `Key` <> '{$this->table}+name+{$key}'";
            $this->query($lSql);

            // insert new fiels language's keys
            $GLOBALS['rlActions']->insert($lang_keys, 'lang_keys');
        }

        /* update arrange data */
        if ($this->table == 'listing_fields') {
            $rlListingTypes->editArrangeField($key, $type, $info['fields']['Values']);
        }

        return true;
    }

    /**
     * delete listing field
     *
     * @package ajax
     *
     * @param string $key - field key
     *
     **/
    public function ajaxDeleteLField($key = false)
    {
        global $_response, $lang, $rlCache, $rlListingTypes, $rlActions, $allLangs, $type_info, $f_key, $fields, $field;

        $GLOBALS['rlValid']->sql($key);

        // get field info
        $field = $this->fetch(
            array('ID', 'Readonly', 'Values', 'Type', 'Condition'),
            array('Key' => $key),
            null,
            1,
            'listing_fields',
            'row'
        );
       
        if (!$key || !$field['ID']) {
            trigger_error("Can not delete listing field, field with requested key does not exist", E_WARNING);
            $GLOBALS['rlDebug']->logger("Can not delete listing field, field with requested key does not exist");

            return $_response;
        }

        if ($field['Readonly']) {
            $error = str_replace('{field}', $lang['listing_fields+name+' . $key], $lang['field_protected']);
            $_response->script("printMessage('error', '{$error}')");

            return $_response;
        }

        // DROP field from the lsitings table
        $sql = "ALTER TABLE `{db_prefix}listings` DROP `{$key}` ";

        if ($this->query($sql)) {
            $GLOBALS['rlHook']->load('apPhpFieldsAjaxDeleteField');

            // delete information from listing_fields table
            $sql = "DELETE FROM `{db_prefix}listing_fields` WHERE `Key` = '{$key}'";
            $this->query($sql);

            // delete languages phrases by current field
            $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'listing_fields+name+{$key}' ";
            $sql .= "OR `Key` = 'listing_fields+default+{$key}' OR `Key` = 'listing_fields+description+{$key}'";

            // Delete field value names
            if (!$field['Condition'] && $field['Values'] && ($field['Type'] == 'checkbox' || $field['Type'] == 'radio')) {
                foreach (explode(',', $field['Values']) as $value) {
                    $sql .=" OR `Key` = 'listing_fields+name+{$key}_{$value}'";
                }
            }
            $this->query($sql);

            // delete field relations from submit forms
            $field_rel = $this->fetch(array('ID', 'Fields'), null, "WHERE FIND_IN_SET('{$field['ID']}', `Fields`) > 0", null, 'listing_relations');
            foreach ($field_rel as $field_item) {
                $c_fields = explode(',', trim($field_item['Fields'], ','));
                $poss = array_search($field['ID'], $c_fields);

                unset($c_fields[$poss]);

                if (!empty($c_fields)) {
                    $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = '" . implode(',', $c_fields) . ",' WHERE `ID` = '{$field_item['ID']}'";
                } else {
                    $sql = "DELETE FROM `{db_prefix}listing_relations` WHERE `ID` = '{$field_item['ID']}'";
                }
                $this->query($sql);
            }

            // delete field relations from search forms
            $search_rel = $this->fetch(array('ID', 'Fields'), null, "WHERE FIND_IN_SET('{$field['ID']}', `Fields`) > 0", null, 'search_forms_relations');
            foreach ($search_rel as $search_item) {
                $c_fields = explode(',', trim($search_item['Fields'], ','));
                $poss = array_search($field['ID'], $c_fields);

                unset($c_fields[$poss]);

                if (!empty($c_fields)) {
                    $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = '" . implode(',', $c_fields) . ",' WHERE `ID` = '{$search_item['ID']}'";
                } else {
                    $sql = "DELETE FROM `{db_prefix}search_forms_relations` WHERE `ID` = '{$search_item['ID']}'";
                }
                $this->query($sql);
            }

            // delete field relations from short form
            $sql = "DELETE FROM `{db_prefix}short_forms` WHERE `Field_ID` = '{$field['ID']}'";
            $this->query($sql);

            // delete field relations from listing title form
            $sql = "DELETE FROM `{db_prefix}listing_titles` WHERE `Field_ID` = '{$field['ID']}'";
            $this->query($sql);

            // delete field relations from featured form
            $sql = "DELETE FROM `{db_prefix}featured_form` WHERE `Field_ID` = '{$field['ID']}'";
            $this->query($sql);

            // delete arrange relations
            /* prepare arrange fields */
            foreach ($rlListingTypes->types as $lt) {
                if ($lt['Arrange_field']) {
                    $arrange_keys[$lt['Arrange_field']] = $lt['Key'];
                }
            }

            if (isset($arrange_keys[$key])) {
                /* symulate data */
                $type_info = $rlListingTypes->types[$arrange_keys[$key]];
                $f_key = $arrange_keys[$key];
                $fields[$key]['Values'] = $field['Values'];

                // remove all related modules
                if ($type_info['Arrange_search']) {
                    $rlListingTypes->arrange_search_remove($type_info['Arrange_field']);
                }

                if ($type_info['Arrange_featured']) {
                    $rlListingTypes->arrange_featured_remove($type_info['Arrange_field']);
                }

                if ($type_info['Arrange_stats']) {
                    $rlListingTypes->arrange_statistics_remove($type_info['Arrange_field']);
                }

                /* remove arrange data from type */
                $sql = "UPDATE `{db_prefix}listing_types` SET `Arrange_field` = '', `Arrange_values` = '', `Arrange_search` = '0', `Arrange_featured` = '0', `Arrange_stats` = '0' WHERE `Key` = '{$arrange_keys[$key]}' LIMIT 1";
                $this->query($sql);

                /* remove data from type globals */
                $rlListingTypes->types[$arrange_keys[$key]]['Arrange_field'] = '';
                $rlListingTypes->types[$arrange_keys[$key]]['Arrange_values'] = '';
                $rlListingTypes->types[$arrange_keys[$key]]['Arrange_search'] = 0;
                $rlListingTypes->types[$arrange_keys[$key]]['Arrange_featured'] = 0;
                $rlListingTypes->types[$arrange_keys[$key]]['Arrange_stats'] = 0;
            }

            /* update cache */
            $rlCache->updateForms();
            $rlCache->updateListingStatistics();

            $_response->script("
                listingFieldsGrid.reload();
                printMessage('notice', '{$lang['field_deleted']}');
            ");
        }

        return $_response;
    }

    /**
     * delete account field
     *
     * @package ajax
     *
     * @param string $key - field key
     *
     **/
    public function ajaxDeleteAField($key)
    {
        global $_response, $lang, $id;

        $GLOBALS['rlValid']->sql($key);

        // get field info
        $field = $this->fetch(array('ID', 'Readonly', 'Values'), array('Key' => $key), null, 1, 'account_fields', 'row');

        if (!$key || !$field['ID']) {
            trigger_error("Can not delete listing field, field with requested key does not exist", E_WARNING);
            $GLOBALS['rlDebug']->logger("Can not delete listing field, field with requested key does not exist");

            return $_response;
        }

        if ($field['Readonly']) {
            $error = str_replace('{field}', $lang['listing_fields+name+' . $key], $lang['field_protected']);
            $_response->script("printMessage('error', '{$error}')");

            return $_response;
        }

        // DROP field from the lsitings table
        $sql = "ALTER TABLE `{db_prefix}accounts` DROP `{$key}` ";

        if ($this->query($sql)) {
            $id = (int) $this->getOne('ID', "`Key` = '{$key}'", 'account_fields');

            $GLOBALS['rlHook']->load('apPhpFieldsAjaxDeleteAField');

            // delete information from listing_fields table
            $sql = "DELETE FROM `{db_prefix}account_fields` WHERE `Key` = '{$key}' LIMIT 1";
            $this->query($sql);

            // delete languages phrases by current field
            $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'account_fields+name+{$key}%' OR `Key` LIKE 'account_fields+default+{$key}'";

            /* remove field relations */
            $sql = "DELETE FROM `{db_prefix}account_search_relations` WHERE `Field_ID` = '{$id}'";
            $this->query($sql);

            $sql = "DELETE FROM `{db_prefix}account_short_form` WHERE `Field_ID` = '{$id}'";
            $this->query($sql);

            $sql = "DELETE FROM `{db_prefix}account_submit_form` WHERE `Field_ID` = '{$id}'";
            $this->query($sql);

            $_response->script("
                accountFieldsGrid.reload();
                printMessage('notice', '{$lang['field_deleted']}');
            ");
        }

        return $_response;
    }

    /**
     * symulate post form data
     *
     * @param string $key - field key
     * @param array $field_info - field information array
     *
     **/
    public function simulatePost(&$key, &$field_info)
    {
        $_POST['key'] = $key;
        $_POST['required'] = $field_info['Required'];
        $_POST['map'] = $field_info['Map'];
        $_POST['status'] = $field_info['Status'];
        $_POST['type'] = $field_info['Type'];
        $_POST['contact'] = $field_info['Contact'];

        if ($field_info['Add_page']) {
            $_POST['add_page'] = 'on';
        }

        if ($field_info['Details_page']) {
            $_POST['details_page'] = 'on';
        }

        // get names
        $names = $this->fetch(array('Code', 'Value'), array('Key' => $this->table . '+name+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
        foreach ($names as $name) {
            $_POST['name'][$name['Code']] = $name['Value'];
        }

        // get description
        $descriptions = $this->fetch(array('Code', 'Value'), array('Key' => $this->table . '+description+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
        foreach ($descriptions as $description) {
            $_POST['description'][$description['Code']] = $description['Value'];
        }

        // additional fields
        switch ($field_info['Type']) {
            case 'text':
                $e_default = $this->fetch(array('Code', 'Value'), array('Key' => $this->table . '+default+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');

                foreach ($e_default as $nKey => $nVal) {
                    $_POST['text']['default'][$e_default[$nKey]['Code']] = $e_default[$nKey]['Value'];
                }

                $_POST['text']['condition'] = $field_info['Condition'];
                $_POST['text']['multilingual'] = $field_info['Multilingual'];
                $_POST['text']['maxlength'] = $field_info['Values'];
                break;

            case 'textarea':
                $_POST['textarea']['maxlength'] = $field_info['Values'];
                $_POST['textarea']['multilingual'] = $field_info['Multilingual'];
                $_POST['textarea']['html'] = $field_info['Condition'] == 'html' ? 1 : 0;
                break;

            case 'number':
                $_POST['number']['max_length'] = $field_info['Values'];
                $_POST['number']['format'] = $field_info['Opt1'];
                $_POST['number']['thousands_sep'] = $field_info['Opt2'];
                break;

            case 'phone':
                $_POST['phone']['condition'] = $field_info['Condition'];

                $_POST['phone']['area_length'] = $field_info['Default'];
                $_POST['phone']['phone_length'] = $field_info['Values'];
                $_POST['phone']['code'] = $field_info['Opt1'];
                $_POST['phone']['ext'] = $field_info['Opt2'];
                break;

            case 'date':
                $_POST['date']['mode'] = $field_info['Default'];
                break;

            case 'bool':
                $_POST['bool']['default'] = $field_info['Default'];
                break;

            case 'mixed':
                $_POST['mixed_data_format'] = $field_info['Condition'];
                $_POST['format'] = $field_info['Opt1'];
                $_POST['thousands_sep'] = $field_info['Opt2'];
            case 'select':
                $_POST['data_format'] = $field_info['Condition'];

                $s_items = $this->fetch(array('Code', 'Key', 'Value'), null, "WHERE `Key` REGEXP '^{$this->table}\\\+name\\\+{$key}\\\_([0-9]*)$' AND `Key` <> '{$this->table}+name+" . $key . "' AND `Status` <> 'trash' ORDER BY `ID`", null, 'lang_keys');

                foreach ($s_items as $nKey => $nVal) {
                    $s_item = explode('_', $s_items[$nKey]['Key']);
                    $s_item = array_reverse($s_item);

                    $_POST[$field_info['Type']][$s_item[0]][$s_items[$nKey]['Code']] = $s_items[$nKey]['Value'];
                }

                // set default items
                if (!empty($field_info['Default'])) {
                    $_POST[$field_info['Type']]['default'] = $field_info['Default'];
                }
                break;

            case 'radio':
                $_POST['data_format'] = $field_info['Condition'];

                $s_default = $this->fetch(array('Code', 'Key', 'Value'), null, "WHERE `Key` REGEXP '^{$this->table}\\\+name\\\+{$key}\\\_([0-9a-zA-Z]*)$' AND `Key` <> '{$this->table}+name+" . $key . "' AND `Status` <> 'trash' ORDER BY `Key`", null, 'lang_keys');

                foreach ($s_default as $nKey => $nVal) {
                    $s_item = explode('_', $s_default[$nKey]['Key']);
                    $s_item = array_reverse($s_item);
                    $_POST[$field_info['Type']][$s_item[0]][$s_default[$nKey]['Code']] = $s_default[$nKey]['Value'];
                }

                // set default items
                if (!empty($field_info['Default'])) {
                    $_POST[$field_info['Type']]['default'] = $field_info['Default'];
                }
                break;

            case 'checkbox':
                $_POST['data_format'] = $field_info['Condition'];
                $_POST['column_number'] = $field_info['Opt2'];
                $_POST['show_tils'] = $field_info['Opt1'];

                $s_items = $this->fetch(array('Code', 'Key', 'Value'), null, "WHERE `Key` REGEXP '^{$this->table}\\\+name\\\+{$key}\\\_([0-9]*)$' AND `Key` <> '{$this->table}+name+" . $key . "' AND `Status` <> 'trash' ORDER BY `Key`", null, 'lang_keys');

                foreach ($s_items as $nKey => $nVal) {
                    $s_item = explode('_', $s_items[$nKey]['Key']);
                    $s_item = array_reverse($s_item);

                    $_POST[$field_info['Type']][$s_item[0]][$s_items[$nKey]['Code']] = $s_items[$nKey]['Value'];
                }

                // set default items
                if (!empty($field_info['Default'])) {
                    $ch_def = explode(',', $field_info['Default']);
                    foreach ($ch_def as $cdItem) {
                        $_POST[$field_info['Type']][$cdItem]['default'] = $cdItem;
                    }
                }
                break;

            case 'image':
                $_POST['image']['resize_type'] = $field_info['Default'];

                if ($field_info['Default'] == 'C') {
                    $resolution = explode('|', $field_info['Values']);
                    $_POST['image']['width'] = $resolution[0];
                    $_POST['image']['height'] = $resolution[1];
                } elseif ($field_info['Default'] == 'W') {
                    $_POST['image']['width'] = $field_info['Values'];
                } elseif ($field_info['Default'] == 'H') {
                    $_POST['image']['height'] = $field_info['Values'];
                }
                break;

            case 'file':
                $_POST['file']['type'] = $field_info['Default'];
                break;

            case 'accept':
                $_POST['accept_page'] = $field_info['Default'];
                $_POST['atypes']      = $field_info['Values'] ? explode(',', $field_info['Values']) : array();
                $_POST['first_step']  = $field_info['Opt1'];
                break;
        };
    }
}
