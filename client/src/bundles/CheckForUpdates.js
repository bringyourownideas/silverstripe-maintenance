/* global window */

window.jQuery.entwine('ss', ($) => {
  // p tag that holds button
  $('#checkForUpdates').entwine({
    // Magically set by the magic get/set{thisMemberProperty} (see poll function below)
    PollTimeout: null,
    onclick() {
      this.setLoading();
    },
    onmatch() {
      // Poll the current job and update the front end status
      if (this.getButton(true).length) {
        this.setLoading();
      }
    },
    /**
     * Add warning message (set as data attribute on GridFieldRefreshButton) before
     * and has finished
     */
    setLoading() {
      const message = this.getButton().data('message');
      $('.ss-gridfield-buttonrow')
        .first()
        .prepend(`<p class="message warning">${message}</p>`);
      this.poll();
    },
    /**
     * Poll the provided "check" endpoint to determine whether the job has been processed
     * and has finished.
     */
    poll() {
      $.ajax({
        url: this.getButton().data('check'),
        async: true,
        success: (data) => {
          this.clearLoading(JSON.parse(data));
        },
        error: (error) => {
          if (typeof console !== 'undefined') {
            console.log(error);
          }
        }
      });
    },
    getButton(disabled) {
      let button = 'button';
      if (disabled) {
          button += ':disabled';
      }
      return this.children(button).first();
    },
    clearLoading(hasRunningJob) {
      if (hasRunningJob === false) {
        this.closest('fieldset.ss-gridfield').reload();
        return;
      }

      // Ensure the regular poll method is run
      // Kill any existing timeout
      clearTimeout(this.getPollTimeout());

      this.setPollTimeout(setTimeout(() => {
        $('#checkForUpdates').poll();
      }, 5000));
    }
  });
});
