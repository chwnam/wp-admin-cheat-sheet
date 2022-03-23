(function ($) {
    $(document).ready(function () {
        var edit = inlineEditPost.edit;

        inlineEditPost.edit = function (id) {
            var t = this,
                data,
                row;

            edit.apply(t, arguments);

            if ('object' === typeof id) {
                id = t.getId(id);
            }

            data = $('#inline_' + id);
            row = $('#edit-' + id);

            row.find('#quick_wacs_primary').val(data.find('.wacs_primary').text());
        }

        $('#skip-primary').on('change', function (e) {
            var t = $(e.target);

            if (t.is(':checked')) {
                $('#bulk_wacs_primary').attr('disabled', 'disabled');
            } else {
                $('#bulk_wacs_primary').removeAttr('disabled');
            }
        });
    });
})(jQuery);
