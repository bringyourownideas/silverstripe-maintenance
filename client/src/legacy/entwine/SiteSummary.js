/* global window */
import React from 'react';
import ReactDOM from 'react-dom';
import ModuleDetails from 'components/ModuleDetails/ModuleDetails';

window.jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .package-summary__details-container').entwine({
    onmatch() {
      const dataSchema = this.data('schema');

      ReactDOM.render(
        <ModuleDetails
          detailsId={this.attr('id')}
          dataSchema={dataSchema}
        />,
        this[0]
      );
    },

    onunmatch() {
      ReactDOM.unmountComponentAtNode(this[0]);
    }
  });

  $('.site-summary .ss-gridfield-item').entwine({
    /**
     * When you click on a table row (not a button in the table row), trigger the button
     * to be clicked.
     */
    onclick(e) {
      if ($(e.target).is('button')) {
        return;
      }

      if (this.data('popover-open')) {
        // Reset state for next click
        this.data('popover-open', false);
      } else {
        // Open the popover
        this.data('popover-open', true);
        this.find('.package-summary__module-info-trigger').click();
      }
    }
  });
});
