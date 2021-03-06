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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $key = $rlValid->xSql($_GET['key']);

        if ($field == 'Default') {
            $parent = $rlDb->getOne('Parent_ID', '`ID`=' . $id, 'data_formats');

            $uncheckall = "UPDATE `{db_prefix}data_formats` SET `Default` = '0' ";
            $uncheckall .= "WHERE `Parent_ID`={$parent} AND `ID` !={$id}";
            $rlDb->query($uncheckall);

            $value = ($value == 'true') ? '1' : '0';
            $multi_format_key = $rlDb->getOne('Key', '`ID`=' . $parent, 'data_formats');
            $default_item_key = $rlDb->getOne('Key', '`ID`=' . $id, 'data_formats');

            $sql = "UPDATE `{db_prefix}multi_formats` ";
            $sql .= "SET `Default` = '{$default_item_key}' ";
            $sql .= "WHERE `Key` = '{$multi_format_key}'";
            $rlDb->query($sql);

        } elseif ($field == 'Status') {
        
            if (!$_GET['parent']) {
                $item_key = $rlDb->getOne('Key', '`ID`=' . $id, 'multi_formats');

                $sql = "UPDATE `{db_prefix}multi_formats` ";
                $sql .= "SET `Status` = '{$value}' ";
                $sql .= "WHERE `Key` LIKE '{$item_key}'";
                $rlDb->query($sql);
            } else {
                $item_key = $rlDb->getOne('Key', '`ID`=' . $id, 'data_formats');
            }

            $sql = "UPDATE `{db_prefix}data_formats` ";
            $sql .= "SET `Status` = '{$value}' ";
            $sql .= "WHERE `Key` LIKE '{$item_key}\_%'";
            $rlDb->query($sql);

            $sql = "UPDATE `{db_prefix}lang_keys` ";
            $sql .= "SET `Status` = '{$value}' ";
            $sql .= "WHERE `Key` LIKE 'data_formats+name+{$item_key}\_%'";
            $rlDb->query($sql);

            $sql = "UPDATE `{db_prefix}lang_keys` ";
            $sql .= "SET `Status` = '{$value}' ";
            $sql .= "WHERE `Key` = 'data_formats+name+{$item_key}'";
            $rlDb->query($sql);
        }

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtDataFormatsUpdate');

        $rlActions->updateOne($updateData, 'data_formats');

        $rlCache->updateDataFormats();
        $rlCache->updateForms();
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);
    $parent_key = $rlValid->xSql($_GET['parent']);

    $parent = $parent_key ? $rlDb->getOne('ID', "`Key` = '" . $parent_key . "'", 'data_formats') : 0;

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Value` as `name` ";

    if (!$parent) {
    
        $sql .= "FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('data_formats+name+',`T1`.`Key`) = `T2`.`Key` ";
        $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    } else {

        $sql .= ", `TLP`.`Value` AS `Parent_name`, `T1`.`Default` ";
        $sql .= "FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('data_formats+name+',`T1`.`Key`) = `T2`.`Key` ";
        $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "LEFT JOIN `{db_prefix}data_formats` AS `TP` ON `TP`.`ID` = `T1`.`Parent_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `TLP` ON `TLP`.`Key` = CONCAT('data_formats+name+', `TP`.`Key`) ";
        $sql .= "AND `TLP`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `T1`.`Status` <> 'trash' ";

        if ($_GET['action'] == 'search' && $_GET['Search_all_levels'] && $_GET['Name']) {
            $sql .= "AND `T2`.`Value` LIKE '" . $_GET['Name'] . "%' ";
        } else {
            $sql .= "AND `T1`.`Parent_ID` = {$parent} ";        

            if ($_GET['action'] == 'search' && $_GET['Name']) {
                $sql .= "AND `T2`.`Value` LIKE '" . $_GET['Name'] . "%' ";
            }
        }

        $sql .= "GROUP BY `T1`.`ID` ";
    }

    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    
    $sql .= "LIMIT {$start},{$limit}";

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
        $data[$key]['Default'] = (bool) $value['Default'];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $reefless->loadClass('Json');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo $rlJson->encode($output);
}
/* ext js action end */

else {
    $reefless->loadClass('MultiFieldAP', null, 'multiField');

    unset($_SESSION['mf_import']);

    /* additional bread crumb step */
    switch ($_GET['action']) {
        case 'add':
            $bcAStep = $lang['mf_add_item'];
            break;
        case 'edit':
            $bcAStep = $lang['mf_edit_item'] . " " . $lang['data_formats+name+' . $_GET['item']];
            break;
    }

    $rlSmarty->assign_by_ref('domain_info', $domain_info);

    if ($_GET['parent'] && !$_GET['action']) {
        $parent_id = $rlDb->getOne("ID", "`Key` ='{$_GET['parent']}'", 'data_formats');

        $head = $rlMultiFieldAP->getHead($parent_id);
        $rlSmarty->assign('head_level', $head);

        $gf = $rlDb->getOne("Geo_filter", "`Key` ='{$head}'", 'multi_formats');
        $rlSmarty->assign('geo_filter', $gf);

        $rlSmarty->assign_by_ref('parent_path', $rlDb->getOne("Path", "`ID` ={$parent_id}", 'data_formats'));

        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        $item_bread_crumbs = $rlMultiFieldAP->getBreadCrumbs($parent_id);
        $item_bread_crumbs = array_reverse($item_bread_crumbs);

        if (!empty($item_bread_crumbs)) {
            foreach ($item_bread_crumbs as $bKey => $bVal) {
                $item_bread_crumbs[$bKey]['name'] = $item_bread_crumbs[$bKey]['name'];
                $item_bread_crumbs[$bKey]['Controller'] = 'multi_formats';
                $item_bread_crumbs[$bKey]['Vars'] = 'parent=' . $item_bread_crumbs[$bKey]['Key'];
            }
            $bcAStep = $item_bread_crumbs;
        }
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        $data_entries = $rlDb->fetch(array('Key'), array('Parent_ID' => '0', 'Status' => 'active'), "AND NOT FIND_IN_SET(`Key`,'years,currency,unit' ) ORDER BY `Key`", null, 'data_formats');
        $data_entries = $rlLang->replaceLangKeys($data_entries, 'data_formats', 'name', RL_LANG_CODE, 'admin');
        $rlSmarty->assign_by_ref('data_entries', $data_entries);

        $f_key = $rlValid->xSql($_GET['item']);
        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $item_info = $rlDb->fetch("*", array('Key' => $f_key), "AND `Status` <> 'trash'", null, 'data_formats', 'row');

            $_POST['key'] = $item_info['Key'];
            $_POST['status'] = $rlDb->getOne("Status", "`Key`  = '{$f_key}'", "multi_formats");
            $_POST['order_type'] = $item_info['Order_type'];
            $_POST['geo_filter'] = $rlDb->getOne("Geo_filter", "`Key`  = '{$f_key}'", "multi_formats");

            $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'data_formats+name+' . $f_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($names as $nKey => $nVal) {
                $_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
            }
        }

        $ex_key = $rlDb->getOne("Key", "`Geo_filter`  = '1'", "multi_formats");

        if ($ex_key && $ex_key != $f_key) {
            $rlSmarty->assign("geo_disabled", true);
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            if ($_GET['action'] == 'add') {
                $f_df = $_POST['data_entry'];

                $f_key = $_POST['key'];
                $f_key = $rlValid->str2key($f_key);

                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                /* check key exist (in add mode only) */
                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                    $error_fields[] = 'key';
                }

                $exist_key = $rlDb->fetch(array('Key'), array('Key' => $f_key), null, null, 'data_formats');
                if (!empty($exist_key)) {
                    $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_key_exist']);
                    $error_fields[] = 'key';
                }

                /* check names */
                $f_name = $_POST['name'];

                foreach ($allLangs as $lkey => $lval) {
                    if (empty($f_name[$allLangs[$lkey]['Code']])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                        $error_fields[] = "name[{$lval['Code']}]";
                    }
                }
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    $data = array(
                        'Key' => $f_key,
                        'Status' => $_POST['status'],
                        'Geo_filter' => $_POST['geo_filter'],
                        'Position' => $rlDb->getOne("Position", "1 ORDER BY `Position` DESC", "data_formats") + 1,
                    );

                    if ($action = $rlActions->insertOne($data, 'multi_formats')) {
                        $parent_id = $rlDb->insertID();

                        $format_insert['Key'] = $f_key;
                        $format_insert['Parent_ID'] = 0;
                        $format_insert['Position'] = $rlDb->getOne("Position", "`Parent_ID` = 0 ORDER BY `Position` DESC", "data_formats");
                        $format_insert['Status'] = 'active';
                        $format_insert['Order_type'] = $_POST['order_type'];

                        if ($rlActions->insertOne($format_insert, 'data_formats')) {
                            foreach ($allLangs as $key => $value) {
                                $lang_keys[] = array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key' => 'data_formats+name+' . $f_key,
                                    'Value' => $f_name[$allLangs[$key]['Code']],
                                );
                            }
                            $rlActions->insert($lang_keys, 'lang_keys');
                        }

                        $message = $lang['notice_item_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new data format (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new data format (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_data = array('fields' => array(
                        'Status' => $_POST['status'],
                        'Geo_filter' => $_POST['geo_filter'],
                    ),
                        'where' => array('Key' => $f_key),
                    );

                    $action = $GLOBALS['rlActions']->updateOne($update_data, 'multi_formats');

                    $update_data = array('fields' => array(
                        'Order_type' => $_POST['order_type'],
                    ),
                        'where' => array('Key' => $f_key),
                    );

                    $GLOBALS['rlActions']->updateOne($update_data, 'data_formats');

                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'data_formats+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit name's values
                            $update_names = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$allLangs[$key]['Code']],
                                ),
                                'where' => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key' => 'data_formats+name+' . $f_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_names, 'lang_keys');
                        } else {
                            // insert names
                            $insert_names = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key' => 'data_formats+name+' . $f_key,
                                'Value' => $_POST['name'][$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_names, 'lang_keys');
                        }
                    }

                    $message = $lang['notice_item_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                } else {
                    trigger_error("Can't edit datafomats (MYSQL problems)", E_WARNING);
                    $rlDebug->logger("Can't edit datafomats (MYSQL problems)");
                }
            }
        }
    }

    $rlXajax->registerFunction(array('addItem', $rlMultiFieldAP, 'ajaxAddItem'));
    $rlXajax->registerFunction(array('editItem', $rlMultiFieldAP, 'ajaxEditItem'));
    $rlXajax->registerFunction(array('prepareEdit', $rlMultiFieldAP, 'ajaxPrepareEdit'));
    $rlXajax->registerFunction(array('deleteItem', $rlMultiFieldAP, 'ajaxDeleteItem'));
    $rlXajax->registerFunction(array('deleteFormat', $rlMultiFieldAP, 'ajaxDeleteFormat'));
    $rlXajax->registerFunction(array('dfItemsMassActions', $rlMultiFieldAP, 'ajaxDfItemsMassActions'));

    $rlXajax->registerFunction(array('listSources', $rlMultiFieldAP, 'ajaxListSources'));
    $rlXajax->registerFunction(array('expandSource', $rlMultiFieldAP, 'ajaxExpandSource'));
    $rlXajax->registerFunction(array('importSource', $rlMultiFieldAP, 'ajaxImportSource'));

    $parent_info = $rlDb->fetch("*", array('Key' => $_GET['parent']), null, null, 'data_formats', "row");
    $parent_id = $parent_info['ID'];

    $rlSmarty->assign('parent_default', $parent_info['Default']);
    $level = $rlMultiFieldAP->getLevel($parent_id);

    $multi_format = $rlMultiFieldAP->getHead($parent_id);
    $rlSmarty->assign('level', $level);

    $order_type = $rlDb->getOne('Order_type', "`Key` = '{$multi_format}'", 'data_formats');
    $rlSmarty->assign('order_type', $order_type);

    if ($level) {
        $sql = "SELECT * FROM `{db_prefix}listing_fields` WHERE `Condition` = '{$multi_format}' AND `Key` REGEXP 'level{$level}'";
        $related_listing_fields = $rlDb->getAll($sql);
        $related_listing_fields = $rlLang->replaceLangKeys($related_listing_fields, 'listing_fields', array('name'));
        $rlSmarty->assign('related_listing_fields', $related_listing_fields);

        $sql = "SELECT * FROM `{db_prefix}account_fields` WHERE `Condition` = '{$multi_format}' AND `Key` REGEXP 'level{$level}'";
        $related_account_fields = $rlDb->getAll($sql);
        $related_account_fields = $rlLang->replaceLangKeys($related_account_fields, 'account_fields', array('name'));
        $rlSmarty->assign('related_account_fields', $related_account_fields);
    } else {
        $sql = "SELECT * FROM `{db_prefix}listing_fields` WHERE `Condition` = '{$multi_format}' AND `Key` NOT REGEXP 'level[0-9]'";
        $related_listing_fields = $rlDb->getAll($sql);
        $related_listing_fields = $rlLang->replaceLangKeys($related_listing_fields, 'listing_fields', array('name'));
        $rlSmarty->assign('related_listing_fields', $related_listing_fields);

        $sql = "SELECT * FROM `{db_prefix}account_fields` WHERE `Condition` = '{$multi_format}' AND `Key` NOT REGEXP 'level[0-9]'";
        $related_account_fields = $rlDb->getAll($sql);
        $related_account_fields = $rlLang->replaceLangKeys($related_account_fields, 'account_fields', array('name'));
        $rlSmarty->assign('related_account_fields', $related_account_fields);
    }
}
