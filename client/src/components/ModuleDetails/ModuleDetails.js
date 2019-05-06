import React, { Component } from 'react';
import PropTypes from 'prop-types';
import i18n from 'i18n';
import { Popover, PopoverHeader, PopoverBody } from 'reactstrap';
import ModuleHealthIndicator from 'components/ModuleDetails/ModuleHealthIndicator';

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

  /**
   * Renders a list of any security alerts that may be known for the given module
   *
   * @returns {DOMElement}
   */
  renderSecurityAlerts() {
    const { securityAlerts } = this.props.dataSchema;

    if (!securityAlerts || !securityAlerts.length) {
      return null;
    }

    return (
      <div className="package-summary__security-alerts alert alert-warning">
        <h4 className="h5">{i18n._t('ModuleDetails.SECURITY_ALERTS', 'Security alerts')}</h4>
        <ul>
          {
            securityAlerts.map((alert) => (
              <li key={alert.Identifier}>
                <a href={alert.ExternalLink} target="_blank" rel="noopener">
                  {alert.Identifier}
                </a>
              </li>
            ))
          }
        </ul>
      </div>
    );
  }

  render() {
    const { dataSchema: { description, link, linkTitle, rating }, detailsId } = this.props;
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
            <span className="package-summary__details-header-text">
              {i18n._t('ModuleDetails.MODULE_INFO', 'Module info')}
            </span>

            <ModuleHealthIndicator link={link} rating={rating} />
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
              <span className="btn__title">
                {i18n._t('ModuleDetails.MORE_INFO', 'More info')}
              </span>
            </a>

            {this.renderSecurityAlerts()}
          </PopoverBody>
        </Popover>
      </div>
    );
  }
}

ModuleDetails.propTypes = {
  detailsId: PropTypes.string.isRequired,
  dataSchema: PropTypes.shape({
    description: PropTypes.string,
    link: PropTypes.string,
    linkTitle: PropTypes.string,
    securityAlerts: PropTypes.array,
    rating: PropTypes.number
  }),
};

export default ModuleDetails;
