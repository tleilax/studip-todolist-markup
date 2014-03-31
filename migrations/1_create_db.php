<?php
class CreateDb extends DBMigration
{
    public function get_description()
    {
        return _('Setzt die Datenbank für die Todo-Listen auf.');
    }
    
    public function up()
    {
        $query = "CREATE TABLE IF NOT EXISTS `todolist_items` (
                    `item_id` char(32) NOT NULL DEFAULT '',
                    `state` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `user_id` char(32) NOT NULL,
                    `mkdate` int(11) unsigned NOT NULL,
                    `chdate` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`item_id`)
                  )";
        DBManager::get()->exec($query);
    }
    
    public function down()
    {
        $query = "DROP TABLE `todolist_items`";
        DBManager::get()->exec($query);
    }
}