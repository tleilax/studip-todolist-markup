<?php
namespace TodoList;

use SimpleORMap;

class ListItem extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'todolist_items';

        $config['has_one']['author'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id',
            'assoc_foreign_key' => 'user_id',
        );

        $config['default_values']['user_id'] = $GLOBALS['user']->id;

        $config['additional_fields']['chinfo'] = array(
            'get' => function (ListItem $item) {
                if ($item['chdate'] === $item['mkdate']) {
                    return '';
                }

                return sprintf(_('Letzte Änderung %s von %s'),
                               reltime($item['chdate']),
                               $item->author->getFullName());
            },
            'set' => false,
        );

        parent::configure($config);
    }
}