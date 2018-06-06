/* global window */

window.jQuery.entwine('ss', ($) => {
  /**
   * p tag that holds button
   */
  $('#checkForUpdates').entwine({
    /**
     * Magically set by the magic get/set{thisMemberProperty} (see poll function below)
     */
    PollTimeout: null,

    /**
     * Start the loading process
     */
    onclick() {
      this.setLoading();
    },

    /**
     * Poll the current job and update the frontend status
     */
    onmatch() {
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
        .prepend(`<p class="alert alert-info">${message}</p>`);
      this.poll();
    },

    /**
     * Poll the provided "check" endpoint to determine whether the job has been processed
     * and has finished.
     */
    poll() {
      const self = this;
      $.ajax({
        url: self.getButton().data('check'),
        async: true,
        success: (data) => {
          self.clearLoading(JSON.parse(data));
        }
      });
    },

    /**
     * Returns the "Check for updates" button
     *
     * @param {boolean} disabled
     */
    getButton(disabled) {
      let button = 'button';
      if (disabled) {
        button += ':disabled';
      }
      return this.children(button).first();
    },

    /**
     * Cleanup timers and reload the GridField
     * @param {String|boolean} checkResult
     */
    clearLoading(checkResult) {
      if (checkResult !== true) {
        // Reload the report
        this.closest('fieldset.ss-gridfield').reload();
        return;
      }

      // Ensure the regular poll method is run
      // Kill any existing timeout
      clearTimeout(this.getPollTimeout());

      this.setPollTimeout(setTimeout(() => {
        $('#checkForUpdates').poll();
      }, 5000));
    },
  });
});
