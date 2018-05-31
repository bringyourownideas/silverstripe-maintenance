(function ($) {
    $.entwine('ss', function ($) {
        $('.gridfield-dropdown-filter select').entwine({
            onchange: function () {
                // Trigger the action when the select is changed. This clicks a hidden button that is entwined by
                // GridField.js . This is similar to how GridFieldFilterHeader works.
                this.parent().find('.action').click();
            }
        });
    });
})(jQuery);
