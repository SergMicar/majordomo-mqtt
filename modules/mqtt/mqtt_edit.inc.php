<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name == 'panel') {
    $out['CONTROLPANEL'] = 1;
}
$table_name = 'mqtt';
$rec = SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
if ($this->mode == 'update') {
    $ok = 1;
    //updating 'TITLE' (varchar, required)
    /*
    global $title;
    $rec['TITLE'] = $title;
    if ($rec['TITLE'] == '') {
        $out['ERR_TITLE'] = 1;
        $ok = 0;
    }
    */
    //updating 'LOCATION_ID' (select)
    if (IsSet($this->location_id)) {
        $rec['LOCATION_ID'] = $this->location_id;
    } else {
        global $location_id;
        if ($location_id != "")
            $rec['LOCATION_ID'] = $location_id;
    }
    //updating 'PATH' (varchar, required)
    global $path;
    $rec['PATH'] = $path;
    if ($rec['PATH'] == '') {
        $out['ERR_PATH'] = 1;
        $ok = 0;
    }
    $rec['TITLE']=$rec['PATH'];

    global $path_write;
    $rec['PATH_WRITE'] = trim($path_write);

    global $disp_flag;
    $rec['DISP_FLAG'] = (int)$disp_flag;

    global $qos;
    $rec['QOS'] = (int)$qos;

    global $retain;
    $rec['RETAIN'] = (int)$retain;

    $rec['REPLACE_LIST'] = trim(gr('replace_list'));

    $old_linked_object = $rec['LINKED_OBJECT'];
    $old_linked_property = $rec['LINKED_PROPERTY'];
    $old_linked_method = $rec['LINKED_METHOD'];


    //updating 'LINKED_OBJECT' (varchar)
    global $linked_object;
    if (is_null ($linked_object)) { $rec['LINKED_OBJECT'] = ''; }
    else { $rec['LINKED_OBJECT'] = $linked_object; }
    //updating 'LINKED_PROPERTY' (varchar)
    global $linked_property;
    if (is_null ($linked_property)) { $rec['LINKED_PROPERTY'] = ''; }
    else { $rec['LINKED_PROPERTY'] = $linked_property; }
    //updating 'LINKED_METHOD' (varchar)
    global $linked_method;
    if (is_null ($linked_method)) { $rec['LINKED_METHOD'] = ''; }
    else { $rec['LINKED_METHOD'] = $linked_method; }

    $rec['READONLY']=gr('readonly','int');
    
    $rec['ONLY_NEW_VALUE']=gr('only_new_value','int');
    $rec['WRITE_TYPE']=gr('write_type','int');

    //UPDATING RECORD
    if ($ok) {
        if ($rec['ID']) {
            SQLUpdate($table_name, $rec); // update
        } else {
            $new_rec = 1;
            $rec['ID'] = SQLInsert($table_name, $rec); // adding new record
        }

        if ($rec['LINKED_OBJECT'] && $rec['LINKED_PROPERTY']) {
            addLinkedProperty($rec['LINKED_OBJECT'], $rec['LINKED_PROPERTY'], $this->name);
        }
        if ($old_linked_object && $old_linked_object != $rec['LINKED_OBJECT'] && $old_linked_property && $old_linked_property != $rec['LINKED_PROPERTY']) {
            removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
        }

        $out['OK'] = 1;
    } else {
        $out['ERR'] = 1;
    }

    global $new_value;
    global $set_new_value;
    if ((int)$set_new_value) {
        $rec['VALUE'] = $new_value;
        SQLUpdate('mqtt', $rec);
        $this->setProperty($rec['ID'], $new_value, 1);
    }

}
//options for 'LOCATION_ID' (select)
$tmp = SQLSelect("SELECT ID, TITLE FROM locations ORDER BY TITLE");
$locations_total = count($tmp);
for ($locations_i = 0; $locations_i < $locations_total; $locations_i++) {
    $location_id_opt[$tmp[$locations_i]['ID']] = $tmp[$locations_i]['TITLE'];
}
for ($i = 0; $i < count($tmp); $i++) {
    if ($rec['LOCATION_ID'] == $tmp[$i]['ID']) $tmp[$i]['SELECTED'] = 1;
}
$out['LOCATION_ID_OPTIONS'] = $tmp;
if (is_array($rec)) {
    foreach ($rec as $k => $v) {
        if (!is_array($v)) {
            $rec[$k] = htmlspecialchars($v);
        }
    }
}
outHash($rec, $out);

if ($rec['ID'] && $rec['PATH']) {

    $path = $rec['PATH'];
    $tmp = explode('/', $path);
    $list_path = '';
    $parents = array();
    foreach ($tmp as $word) {
        if ($word == '') continue;
        $list_path .= '/' . $word;
        $parent_rec['TITLE'] = $word;
        $parent_tmp = SQLSelectOne("SELECT ID FROM mqtt WHERE PATH='" . DBSafe($list_path) . "'");
        if ($parent_tmp['ID']) {
            $parent_rec['ID'] = $parent_tmp['ID'];
        }
        $parents[] = $parent_rec;
    }
    $out['PARENTS'] = $parents;

    $childs = SQLSelect("SELECT * FROM mqtt WHERE PATH LIKE '" . DBSafe($rec['PATH']) . "%' AND ID!={$rec['ID']} ORDER BY PATH");
    $out['CHILDS'] = $childs;
}
