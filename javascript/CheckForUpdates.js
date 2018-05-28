(function ($) {
    $.entwine('ss', function ($) {
        // p tag that holds button
        $('#checkForUpdates').entwine({
            // Magically set by the magic get/set{thisMemberProperty} (see poll function below)
            PollTimeout: null,
            onclick: function () {
                this.setLoading();
            },
            onmatch: function () {
                // Poll the current job and update the front end status
                if (this.getButton(true).length) {
                    this.setLoading();
                }
            },
            setLoading: function () {
                // Add warning message (set as data attribute on GridFieldRefreshButton) before
                // first button row
                $('.ss-gridfield-buttonrow').first().prepend(
                    '<p class="message warning">' +
                    this.getButton().data('message') +
                    '</p>'
                );
                this.poll();
            },
            poll: function () {
                var self = this;
                $.ajax({
                    url: self.getButton().data('check'),
                    async: true,
                    success: function (data) {
                        self.clearLoading(JSON.parse(data));
                    },
                    error: function (error) {
                        if (typeof console !== 'undefined') {
                            console.log(error);
                        }
                    }
                });
            },
            getButton: function (disabled) {
                let button = 'button';
                if (disabled) {
                    button += ':disabled';
                }
                return this.children(button).first();
            },
            clearLoading: function (hasRunningJob) {

                if (hasRunningJob === false) {
                    this.closest('fieldset.ss-gridfield').reload();
                    return;
                }

                // Ensure the regular poll method is run
                // Kill any existing timeout
                clearTimeout(this.getPollTimeout());

                this.setPollTimeout(setTimeout(
                    function () {
                        $('#checkForUpdates').poll();
                    },
                    5000
                ));
            }
        });
    });
}(jQuery));
