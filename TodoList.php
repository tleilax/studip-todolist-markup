<?php

/**
 * TodoList - Plugin for Stud.IP
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @copyright   Stud.IP Core Group 2014
 * @license     GPL 2 or any later version
 */

class TodoList extends StudipPlugin implements SystemPlugin
{
    public function __construct()
    {
        parent::__construct();

        // transformation markup for voting element
        StudipTransformFormat::addStudipMarkup('todolist_item', '\[( |[xX])\]', NULL, 'TodoList::transformItem');

        // markup for voting element
        StudipFormat::addStudipMarkup('todolist', "\[todo:([0-9a-f]{32})\]", NULL, 'TodoList::markup');

        // Add url, css and js to page header
        PageLayout::addHeadElement('meta', array(
            'name'    => 'todolist-base-url',
            'content' => PluginEngine::getURL($this, array('cid' => null), ''),
        ));
        PageLayout::addScript($this->getPluginURL() . '/todolist.js');
        $this->addStylesheet('todolist.less');
    }

    public function toggle_action($id, $state) 
    {
        $query = "INSERT INTO todolist_items (item_id, state, user_id, mkdate, chdate)
                  VALUES (:id, :state, :user_id, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE state = VALUES(state),
                           user_id = VALUES(user_id),
                           chdate = UNIX_TIMESTAMP()";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':state', (int)$state);
        $statement->bindValue(':user_id', $GLOBALS['user']->id);
        $statement->execute();

        $this->render_json(compact('state'));
    }

    public function poll_action()
    {
        $ids = Request::optionArray('ids');
        
        $query = "SELECT item_id, state
                  FROM todolist_items
                  WHERE item_id IN (:ids)";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':ids', $ids, StudipPDO::PARAM_ARRAY);
        $statement->execute();
        $states = $statement->fetchGrouped(PDO::FETCH_COLUMN);
        
        $this->render_json(compact('states'));
    }
    
    private function render_json($data)
    {
        header('Content-Type: text/json;charset=utf-8');
        echo json_encode($data);
    }

    public static function transformItem($markup, $matches, $contents)
    {
        $id = md5(uniqid(__CLASS__, true));

        $query = "INSERT INTO todolist_items (item_id, state, user_id, mkdate, chdate)
                  VALUES (:id, :state, :user_id, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE state = VALUES(state),
                           user_id = VALUES(user_id),
                           chdate = UNIX_TIMESTAMP()";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':state', (int)(strtolower($matches[1]) === 'x'));
        $statement->bindValue(':user_id', $GLOBALS['user']->id);
        $statement->execute();

        return sprintf('[todo:%s]', $id);
    }

    public static function markup($markup, $matches, $contents)
    {
        $query = "SELECT state FROM todolist_items WHERE item_id = :id";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':id', $matches[1]);
        $statement->execute();
        $checked = $statement->fetchColumn() ?: false;
        
        $uniqid = 'todo-' . $matches[1];
        
        return sprintf('<input id="todo-%1$s" type="checkbox" data-todoitem="%1$s"%2$s><label for="todo-%1$s"></label>',
                       $matches[1], $checked ? ' checked' : '');
    }
}
