/*jslint browser: true */
/*global jQuery, STUDIP */
(function ($, STUDIP) {
    'use strict';

    // Change handler, triggered if checkbox is manipulated
    // Sends the new state to the server
    $(document).on('change', ':checkbox[data-todoitem]', function () {
        var $that = $(this),
            id    = $that.data().todoitem,
            url   = $('meta[name="todolist-base-url"]').attr('content'),
            state = $that.prop('checked') ? '1' : '0';

        $that.prop('disabled', true).addClass('ajaxing');

        $.ajax({
            type: 'POST',
            url: url + 'toggle/' + id + '/' + state,
            dataType: 'json'
        }).done(function (response) {
            $that.prop('checked', response.state === '1');
        }).always(function () {
            $that.prop('disabled', false).removeClass('ajaxing');
        });
    });

    // Attach polling mechanism to global js updater
    var todolist = {};
    todolist.update = function (states) {
        $(':checkbox[data-todoitem]').each(function () {
            var id      = $(this).data().todoitem,
                checked = false,
                info    = '';
            if (states[id]) {
                checked = states[id].checked;
                info    = states[id].info;
            }
            $(this).prop('checked', checked);
            $(this).next('label').attr('title', info);
        });
    };

    // Check for any todolist items present on document ready
    $(document).ready(function () {
        var temp, ids;
        temp = $(':checkbox[data-todoitem]').map(function () {
            return $(this).data().todoitem;
        });
        ids = $.makeArray(temp);

        // If no items are present, just leave
        if (ids.length === 0) {
            return;
        }

        if (STUDIP.JSUpdater.hasOwnProperty('register')) {
            STUDIP.JSUpdater.register('TodoList.update', todolist.update, ids);
        } else {
            todolist.periodicalPushData = function () {
                return ids;
            };
            STUDIP.TodoList = todolist;
        }
    });

}(jQuery, STUDIP));
