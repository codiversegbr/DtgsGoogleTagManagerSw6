import template from './dtgs-gtm-services-list.html.twig';

const { Component, Mixin } = Shopware;

Component.register('dtgs-gtm-services-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            repository: null,
            entityName: 'dtgs_gtm_custom_service',
            items: null,
            isLoading: false,
            sortBy: 'name',
            sortDirection: 'ASC',
            term: '',
        };
    },

    computed: {
        columns() {
            return [
                { property: 'name', dataIndex: 'name', label: this.$t('dtgs-gtm-services.list.columnName'), routerLink: 'dtgs.gtm.services.detail' },
                { property: 'eventName', dataIndex: 'eventName', label: this.$t('dtgs-gtm-services.list.columnEventName'), inlineEdit: 'string' },
                { property: 'category', dataIndex: 'category', label: this.$t('dtgs-gtm-services.list.columnCategory') },
                { property: 'active', dataIndex: 'active', label: this.$t('dtgs-gtm-services.list.columnActive'), align: 'center', inlineEdit: 'boolean'  },
            ];
        },

        serviceRepository() {
            return this.repositoryFactory.create('dtgs_gtm_custom_service');
        },
    },

    created() {
        this.getList();
    },

    methods: {
        getList() {
            this.isLoading = true;

            const criteria = new Shopware.Data.Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addSorting(Shopware.Data.Criteria.sort(this.sortBy, this.sortDirection));

            this.serviceRepository.search(criteria).then((result) => {
                this.total = result.total;
                this.items = result;
                this.isLoading = false;
            });
        },

        onDelete(id) {
            return this.serviceRepository.delete(id).then(() => {
                this.getList();
            });
        },

        onSearch(term) {
            this.term = term;
            this.getList();
        },

        onAddNew() {
            this.$router.push({ name: 'dtgs.gtm.services.create' });
        },

        onEdit(item) {
            this.$router.push({ name: 'dtgs.gtm.services.detail', params: { id: item.id } });
        },

        onChangeLanguage(languageId) {
            this.getList();
        },
    },
});
