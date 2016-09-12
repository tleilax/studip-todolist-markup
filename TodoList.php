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

        StudipAutoloader::addAutoloadPath(__DIR__ . '/models', 'TodoList');

        if (UpdateInformation::isCollecting()) {
            if (method_exists('UpdateInformation', 'hasData') && UpdateInformation::hasData('TodoList.update')) {
                $ids = UpdateInformation::getData('TodoList.update');
            } elseif (isset($_REQUEST['page_info']['TodoList'])) {
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
        $item = new TodoList\ListItem($id);
        $item->state = (int)$state;
        $item->store();

        $this->render_json(compact('state'));
    }

    private function update($ids)
    {
        $items = TodoList\ListItem::findMany($ids);

        $states = array();
        foreach ($items as $item) {
            $states[$item->id] = array(
                'checked' => (bool)$item->state,
                'info'    => $item->chinfo,
            );
        }

        return $states;
    }

    private function render_json($data)
    {
        header('Content-Type: text/json;charset=utf-8');
        echo json_encode($data);
    }

    public static function transformItem($markup, $matches, $contents)
    {
        $item = new TodoList\ListItem();
        $item->state = (int)(strtolower($matches[1]) === 'x');
        $item->store();

        return sprintf('[todo:%s]', $item->id);
    }

    public static function markup($markup, $matches, $contents)
    {
        $item = new TodoList\ListItem($matches[1]);

        $template = '<input id="todo-%1$s" type="checkbox" data-todoitem="%1$s"%2$s>'
                  . '<label for="todo-%1$s" title="%3$s"></label>';
        return sprintf($template,
                       $matches[1],
                       $item->state ? ' checked' : '',
                       $item->chinfo);
    }
}
