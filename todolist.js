(function ($) {

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

    // Polling mechanism, actively watch for changes on server
    var polling     = false,
        should_poll = true;
    function poll() {
        if (!should_poll || polling) {
            return;
        }
        polling = true;
        
        var url  = $('meta[name="todolist-base-url"]').attr('content'),
            temp = $(':checkbox[data-todoitem]').map(function () {
                return $(this).data().todoitem;
            }),
            ids  = $.makeArray(temp);

        $.getJSON(url + 'poll', {ids: ids}, function (response) {
            $(':checkbox[data-todoitem]').each(function () {
                var id      = $(this).data().todoitem,
                    checked = false,
                    info    = '';
                if (response.states[id]) {
                    checked = response.states[id].checked;
                    info    = response.states[id].info;
                }
                $(this).prop('checked', checked);
                $(this).next('label').attr('title', info);
            });
        }).always(function () {
            polling = false;
        });
    }

    $(window).blur(function () {
        console.log('no polling!');
        should_poll = false;
    }).focus(function () {
        console.log('polling!');
        should_poll = true;
    })

    $(document).ready(function () {
        setInterval(poll, 30 * 1000);
    });

    
}(jQuery));