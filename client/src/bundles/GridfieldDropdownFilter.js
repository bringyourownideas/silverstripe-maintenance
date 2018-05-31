/* global window */

window.jQuery.entwine('ss', ($) => {
  $('.gridfield-dropdown-filter select').entwine({
    /**
     * Trigger the action when the select is changed. This clicks a
     * hidden button that is entwined by GridField.js . This is similar
     * to how GridFieldFilterHeader works.
     */
    onchange() {
      this.parent().find('.action').click();
    }
  });
});
