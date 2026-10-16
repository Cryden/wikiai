export default {
    props: {
        initialData: {
            type: Object,
            default: () => ({})
        }
    },
    data() {
        const init = this.initialData || {};
        const tpl = init.template || {};

        return {
            template: {
                id: tpl.id || null,
                name: tpl.name || '',
                code: tpl.code || '',
                type: tpl.type || 'article',
                parent_id: tpl.parent_id !== undefined ? tpl.parent_id : null,
                slug: tpl.slug || '',
                is_mandatory: !!tpl.is_mandatory,
                is_auto_create: !!tpl.is_auto_create,
                is_nested: !!tpl.is_nested,
                display_fields: Array.isArray(tpl.display_fields) ? [...tpl.display_fields] : [],
                description: tpl.description || ''
            },
            fields: Array.isArray(init.fields) ? JSON.parse(JSON.stringify(init.fields)) : [],
            parentTemplates: Array.isArray(init.parentTemplates) ? init.parentTemplates : [],
            fieldTypes: Array.isArray(init.fieldTypes) ? init.fieldTypes : [],
            activeTab: 'settings',

            // Field modal
            fieldModalOpen: false,
            editingFieldIndex: -1,
            editingField: {
                id: null,
                label: '',
                name: '',
                type: 'text',
                is_mandatory: false,
                is_global: false,
                depends_on_field: null,
                depends_on_value: null,
                config: {}
            }
        };
    },
    computed: {
        templateType() {
            return this.template.type || 'article';
        },
        parentLabel() {
            return this.template.type === 'variant' ? 'Базовый шаблон' : 'Родительский шаблон';
        },
        parentComment() {
            return this.template.type === 'variant'
                ? 'Шаблон, от которого наследуются поля (Вариант)'
                : 'Шаблон более высокого уровня в дереве базы знаний';
        },
        availableFieldTypes() {
            return this.fieldTypes;
        }
    },
    mounted() {
        // Observe changes to template type input from header dropdown
        const typeInput = document.getElementById('template-type-input');
        if (typeInput) {
            this.template.type = typeInput.value || this.template.type;
            const observer = new MutationObserver(() => {
                this.template.type = typeInput.value;
            });
            observer.observe(typeInput, { attributes: true, attributeFilter: ['value'] });
        }
    },
    methods: {
        slugify(text) {
            if (!text) return '';
            return text.toString().toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '_')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        },

        openAddFieldModal() {
            this.editingFieldIndex = -1;
            this.editingField = {
                id: null,
                label: '',
                name: '',
                type: 'text',
                is_mandatory: false,
                is_global: false,
                depends_on_field: null,
                depends_on_value: null,
                config: {}
            };
            this.fieldModalOpen = true;
        },

        openEditFieldModal(index) {
            this.editingFieldIndex = index;
            this.editingField = JSON.parse(JSON.stringify(this.fields[index]));
            this.fieldModalOpen = true;
        },

        onFieldLabelInput() {
            if (this.editingFieldIndex === -1 && !this.editingField.name) {
                this.editingField.name = this.slugify(this.editingField.label);
            }
        },

        saveFieldModal() {
            if (!this.editingField.label || !this.editingField.name) {
                alert('Пожалуйста, укажите ярлык и имя поля.');
                return;
            }

            const cleanCode = this.slugify(this.editingField.name);
            this.editingField.name = cleanCode;

            const existingIndex = this.fields.findIndex((f, idx) => f.name === cleanCode && idx !== this.editingFieldIndex);
            if (existingIndex !== -1) {
                alert('Поле с таким именем уже существует.');
                return;
            }

            const typeObj = this.fieldTypes.find(t => t.code === this.editingField.type);
            this.editingField.type_name = typeObj ? typeObj.label : this.editingField.type;

            if (this.editingFieldIndex === -1) {
                this.editingField.sort_order = this.fields.length + 1;
                this.fields.push(JSON.parse(JSON.stringify(this.editingField)));
            } else {
                this.fields.splice(this.editingFieldIndex, 1, JSON.parse(JSON.stringify(this.editingField)));
            }

            this.fieldModalOpen = false;
        },

        cancelFieldModal() {
            this.fieldModalOpen = false;
        },

        deleteField(index) {
            const field = this.fields[index];
            if (confirm(`Удалить поле "${field.label}"?`)) {
                const dispIdx = this.template.display_fields.indexOf(field.name);
                if (dispIdx !== -1) {
                    this.template.display_fields.splice(dispIdx, 1);
                }
                this.fields.splice(index, 1);
                this.reindexSortOrder();
            }
        },

        moveFieldUp(index) {
            if (index <= 0) return;
            const item = this.fields.splice(index, 1)[0];
            this.fields.splice(index - 1, 0, item);
            this.reindexSortOrder();
        },

        moveFieldDown(index) {
            if (index >= this.fields.length - 1) return;
            const item = this.fields.splice(index, 1)[0];
            this.fields.splice(index + 1, 0, item);
            this.reindexSortOrder();
        },

        reindexSortOrder() {
            this.fields.forEach((f, idx) => {
                f.sort_order = idx + 1;
            });
        },

        isDisplayFieldActive(fieldName) {
            return this.template.display_fields.includes(fieldName);
        },

        toggleDisplayField(fieldName) {
            const index = this.template.display_fields.indexOf(fieldName);
            if (index === -1) {
                this.template.display_fields.push(fieldName);
            } else {
                this.template.display_fields.splice(index, 1);
            }
        }
    }
};