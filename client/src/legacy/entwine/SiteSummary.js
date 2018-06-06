/* global window */
import React from 'react';
import ReactDOM from 'react-dom';
import ModuleDetails from 'components/ModuleDetails/ModuleDetails';

window.jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .package-summary__details').entwine({
    onmatch() {
      ReactDOM.render(
        <ModuleDetails
          description={this.data('description')}
          detailsId={this.attr('id')}
        />,
        this[0]
      );
    }
  });
});
