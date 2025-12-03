import DtgsGoogleTagManagerPlugin from './plugin/dtgs-google-tag-manager/dtgs-google-tag-manager.plugin';
import DtgsGtmServicesPlugin from './plugin/dtgs-gtm-services/dtgs-gtm-services.plugin';

window.PluginManager.register('GoogleTagManager', DtgsGoogleTagManagerPlugin);
window.PluginManager.register('DtgsGtmServicesPlugin', DtgsGtmServicesPlugin);


// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
