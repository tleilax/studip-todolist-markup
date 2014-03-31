(function ($) {

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

    var polling = false;
    function poll()
    {
        if (polling) {
            return;
        }
        polling = true;
        
        var url  = $('meta[name="todolist-base-url"]').attr('content'),
            temp = $(':checkbox[data-todoitem]').map(function () {
                return $(this).data().todoitem;
            }),
            ids  = $.makeArray(temp);

        $.ajax({
            type: 'POST',
            url: url + 'poll',
            data: {ids: ids},
            dataType: 'json'
        }).done(function (response) {
            $(':checkbox[data-todoitem]').each(function () {
                var id   = $(this).data().todoitem,
                    state = (response.states[id] || '0') === '1';
                $(this).prop('checked', state);
            });
        }).always(function () {
            polling = false;
        });
    }

    $(document).ready(function () {
        setInterval(poll, 5000);
    });

    
}(jQuery));