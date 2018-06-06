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
  toggle() {
    this.setState({
      popoverOpen: !this.state.popoverOpen,
    });
  }

  render() {
    const { description, detailsId, link, linkTitle } = this.props;

    const popoverId = `${detailsId}-popover`;
    const triggerId = `${detailsId}-trigger`;

    return (
      <div className="package-summary__details">
        <span
          id={triggerId}
          className="package-summary__module-info-trigger"
          onClick={this.toggle}
          tabIndex={0}
          role="button"
        >
          {i18n._t('ModuleDetails.MODULE_INFO', 'Module info')}
        </span>

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
              className="btn btn-sm btn-secondary font-icon-info-circled"
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
