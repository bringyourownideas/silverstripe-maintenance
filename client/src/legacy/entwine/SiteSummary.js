/* global window */
import React from 'react';
import { createRoot } from 'react-dom/client';
import ModuleDetails from 'components/ModuleDetails/ModuleDetails';

window.jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .package-summary__details-container').entwine({
    ReactRoot: null,

    onmatch() {
      const dataSchema = this.data('schema');

      let root = this.getReactRoot();
      if (!root) {
        root = createRoot(this[0]);
        this.setReactRoot(root);
      }
      root.render(
        <ModuleDetails
          detailsId={this.attr('id')}
          dataSchema={dataSchema}
        />
      );
    },

    onunmatch() {
      const root = this.getReactRoot();
      if (root) {
        root.unmount();
        this.setReactRoot(null);
      }
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
