import './page/dtgs-gtm-services-list';
import './page/dtgs-gtm-services-detail';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('dtgs-gtm-services', {
    type: 'plugin',
    name: 'dtgs-gtm-services',
    title: 'dtgs-gtm-services.general.mainMenuItemGeneral',
    description: 'dtgs-gtm-services.general.descriptionTextModule',
    color: '#9AA8B5',
    icon: 'regular-cog',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        list: {
            component: 'dtgs-gtm-services-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index.plugins',
                privilege: 'system.system_config',
            },
        },
        detail: {
            component: 'dtgs-gtm-services-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'dtgs.gtm.services.list',
            },
            props: {
                default(route) {
                    return { serviceId: route.params.id };
                },
            },
        },
        create: {
            component: 'dtgs-gtm-services-detail',
            path: 'create',
            meta: {
                parentPath: 'dtgs.gtm.services.list',
            },
        },
    },

    settingsItem: [{
        group: 'plugins',
        to: 'dtgs.gtm.services.list',
        icon: 'regular-cog',
        label: 'dtgs-gtm-services.general.mainMenuItemGeneral',
        privilege: 'system.system_config',
    }]
});
