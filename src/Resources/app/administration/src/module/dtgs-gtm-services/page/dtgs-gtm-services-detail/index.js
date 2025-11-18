import template from './dtgs-gtm-services-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('dtgs-gtm-services-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    props: {
        serviceId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            isLoading: false,
            entityName: 'dtgs_gtm_custom_service',
            item: null,
            isNew: true,
            categories: [
                { value: 'statistic', label: this.$t('dtgs-gtm-services.detail.category.statistic') },
                { value: 'marketing', label: this.$t('dtgs-gtm-services.detail.category.marketing') },
            ],
        };
    },

    watch: {
        serviceId() {
            this.loadEntity();
        },
    },

    computed: {
        repository() {
            return this.repositoryFactory.create(this.entityName);
        },

        pageTitle() {
            return this.isNew
                ? this.$t('dtgs-gtm-services.detail.createTitle')
                : this.$t('dtgs-gtm-services.detail.editTitle');
        },

        gtmServiceIsLoading() {
            return this.isLoading || this.item == null;
        },

        isDisableSaveButton() {
            if (!this.item){
                return true;
            }

            if (!this.item.name
                || !this.item.eventName
                || !this.item.category
            ) {
                return true;
            }

            return false;
        }
    },

    created() {
        this.loadEntity();
    },

    methods: {
        async loadEntity() {
            this.isLoading = true;
            if (this.serviceId) {
                this.isNew = false;
                const entity = await this.repository.get(this.serviceId, Shopware.Context.api);

                this.item = entity;
                this.isLoading = false;
            } else {
                // set language to system language
                if (!Shopware.Store.get('context').isSystemDefaultLanguage) {
                    Shopware.Store.get('context').resetLanguageToDefault();
                }

                this.item = await this.repository.create(Shopware.Context.api);
                this.item.active = true;
                this.isNew = true;
                this.isLoading = false;
            }
        },

        onSave() {
            this.isLoading = true;
            this.repository.save(this.item).then(() => {
                this.isLoading = false;
                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('dtgs-gtm-services.detail.saveSuccess'),
                });
                if (this.isNew) {
                    this.$router.push({ name: 'dtgs.gtm.services.detail', params: { id: this.item.id } });
                }
            }).catch(() => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('dtgs-gtm-services.detail.saveError'),
                });
            });
        },

        onCancel() {
            this.$router.push({ name: 'dtgs.gtm.services.list' });
        },

        abortOnLanguageChange() {
            return this.repository.hasChanges(this.item);
        },

        saveOnLanguageChange() {
            return this.onSave();
        },

        onChangeLanguage() {
            this.loadEntity();
        },
    }
});
