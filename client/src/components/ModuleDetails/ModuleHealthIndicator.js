import React, { Component, PropTypes } from 'react';
import classnames from 'classnames';

class ModuleHealthIndicator extends Component {
  /**
   * Based on health rating score, determine health indicator colour and styling
   *
   * @returns {String}
   */
  getSymbolClasses() {
    const { rating } = this.props;

    let modifier = 'health-indicator__symbol--grey';
    if (rating >= 40) {
      modifier = 'health-indicator__symbol--green';
    }

    return classnames('health-indicator__symbol', modifier);
  }

  /**
   * Helper method to add additional div for module indicator symbol
   *
   * @returns {String|null}
   */
  renderHalfSymbol() {
    const { rating } = this.props;

    if (rating >= 40 && rating < 70) {
      return <div className="health-indicator__symbol--half" />;
    }
    return null;
  }

  render() {
    const { rating, link } = this.props;

    return (
      <a
        href={`${link}#rating-breakdown`}
        className="health-indicator"
        target="_blank"
        rel="noopener"
      >
        <div className={this.getSymbolClasses()}>
          {this.renderHalfSymbol()}
        </div>
        <p>{rating}/100</p>
      </a>
    );
  }
}

ModuleHealthIndicator.propTypes = {
  link: PropTypes.string,
  rating: PropTypes.number,
};

ModuleHealthIndicator.defaultProps = {
  rating: 0,
};

export default ModuleHealthIndicator;
