import DtgsGoogleTagManagerPlugin from './plugin/dtgs-google-tag-manager/dtgs-google-tag-manager.plugin';

window.PluginManager.register('GoogleTagManager', DtgsGoogleTagManagerPlugin);

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}