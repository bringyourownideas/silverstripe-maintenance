import React, { Component, PropTypes } from 'react';
import i18n from 'i18n';
import { Popover, PopoverHeader, PopoverBody } from 'reactstrap';

/**
 * Module details are a link and popover, attached to each report row. The popover
 * will contain a description, link to more information, ratings, etc
 */
class ModuleDetails extends Component {
  constructor(props) {
    super(props);

    this.toggle = this.toggle.bind(this);
    this.state = {
      popoverOpen: false,
    };
  }

  /**
   * Toggle the popover with module details being open or not
   */
  toggle(event) {
    this.setState({
      popoverOpen: !this.state.popoverOpen,
    });

    event.preventDefault();
    return false;
  }

  render() {
    const { description, detailsId, link, linkTitle } = this.props;

    const popoverId = `${detailsId}-popover`;
    const triggerId = `${detailsId}-trigger`;

    return (
      <div className="package-summary__details">
        <button
          id={triggerId}
          // NB: the "edit-link" class is a hack to let GridField.js set pointer cursors
          className="package-summary__module-info-trigger btn btn-link edit-link"
          onClick={this.toggle}
        >
          {i18n._t('ModuleDetails.MODULE_INFO', 'Module info')}
        </button>

        <Popover
          id={popoverId}
          target={triggerId}
          placement="bottom"
          className="package-summary__details-popover"
          isOpen={this.state.popoverOpen}
          toggle={this.toggle}
        >
          <PopoverHeader className="package-summary__details-header">
            {i18n._t('ModuleDetails.MODULE_INFO', 'Module info')}
          </PopoverHeader>

          <PopoverBody>
            <p>{description}</p>

            <a
              href={link}
              title={linkTitle}
              target="blank"
              rel="noopener"
              className="btn btn-secondary font-icon-info-circled"
            >
              {i18n._t('ModuleDetails.MORE_INFO', 'More info')}
            </a>
          </PopoverBody>
        </Popover>
      </div>
    );
  }
}

ModuleDetails.propTypes = {
  description: PropTypes.string,
  detailsId: PropTypes.string.isRequired,
  link: PropTypes.string,
  linkTitle: PropTypes.string,
};

export default ModuleDetails;
