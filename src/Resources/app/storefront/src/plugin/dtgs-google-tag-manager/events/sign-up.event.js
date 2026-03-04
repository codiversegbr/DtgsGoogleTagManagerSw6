import AnalyticsEvent from 'src/plugin/google-analytics/analytics-event';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class GtmSignUpEvent extends AnalyticsEvent
{
    supports() {
        return true;
    }

    execute() {
        const signUpButton = DomAccessHelper.querySelector(document, '.register-submit button', false);
        if (signUpButton) {
            signUpButton.addEventListener('click', this._fireSignUpEvent.bind(this));
        }
    }

    _fireSignUpEvent() {
        if (!this.active) {
            return;
        }

        window.dataLayer.push({
            'event': 'sign_up'
        });
    }
}