<?php
function xmldb_block_my_external_backup_restore_courses_upgrade($oldversion=0) {
    global $DB;
    $newversion = 2019052302;
    if($oldversion< $newversion){
        $dbman = $DB->get_manager();

        $table = new xmldb_table('block_external_backuprestore');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
            $dbman->add_field($table,$field);
            $key = new xmldb_key('course', XMLDB_KEY_FOREIGN, array('courseid'),'course', array('id'));
            $dbman->add_key($table,$key);
         }
        upgrade_block_savepoint(true, $newversion, 'my_external_backup_restore_courses');
    }
    return true;
}