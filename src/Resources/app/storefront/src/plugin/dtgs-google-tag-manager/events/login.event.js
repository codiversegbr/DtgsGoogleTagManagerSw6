import AnalyticsEvent from 'src/plugin/google-analytics/analytics-event';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class GtmLoginEvent extends AnalyticsEvent
{
    supports() {
        return true;
    }

    execute() {
        const loginButton = DomAccessHelper.querySelector(document, '.login-submit button', false);
        if (loginButton) {
            loginButton.addEventListener('click', this._fireLoginEvent.bind(this));
        }
    }

    _fireLoginEvent() {
        if (!this.active) {
            return;
        }

        window.dataLayer.push({
            'event': 'login'
        });
    }
}
