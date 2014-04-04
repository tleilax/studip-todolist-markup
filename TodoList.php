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

        if (UpdateInformation::isCollecting()) {
            if (method_exists('UpdateInformation', 'hasData') && UpdateInformation::hasData('TodoList.update')) {
                $ids = UpdateInformation::getData('TodoList.update');
            } elseif (isset($_REQUEST['page_info']['TodoList']))) {
                $ids = (array)$_REQUEST['page_info']['TodoList'];
            }
            UpdateInformation::setInformation('TodoList.update', $this->update($ids));
        }

        StudipTransformFormat::addStudipMarkup('todolist_item', '\[( |[xX])\]', NULL, 'TodoList::transformItem');
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

    private function update($ids)
    {
        $query = "SELECT item_id, state, chdate, mkdate, user_id
                  FROM todolist_items
                  WHERE item_id IN (:ids)";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':ids', $ids, StudipPDO::PARAM_ARRAY);
        $statement->execute();
        $temp = $statement->fetchAll(PDO::FETCH_ASSOC);

        $states = array();
        foreach ($temp as $row) {
            $states[$row['item_id']] = array(
                'checked' => (bool)$row['state'],
                'info'    => self::get_item_info($row),
            );
        }

        return $states;
    }

    private function get_item_info($row)
    {
        if ($row['chdate'] === $row['mkdate']) {
            return '';
        }

        return sprintf(_('Letzte Änderung %s von %s'),
                         reltime($row['chdate']),
                         User::find($row['user_id'])->getFullName());
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
        $query = "SELECT state, user_id, chdate, mkdate FROM todolist_items WHERE item_id = :id";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':id', $matches[1]);
        $statement->execute();
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        $checked   = (bool)$temp['state'];
        $user_info = self::get_item_info($temp);

        $template = '<input id="todo-%1$s" type="checkbox" data-todoitem="%1$s"%2$s>'
                  . '<label for="todo-%1$s" title="%3$s"></label>';
        return sprintf($template,
                       $matches[1],
                       $checked ? ' checked' : '',
                       $user_info);
    }
}
