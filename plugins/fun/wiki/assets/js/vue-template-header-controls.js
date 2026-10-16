/**
 * WikiX Template Header Controls
 */
(function() {

    class VueTemplateHeaderControls extends oc.ControlBase {
        init() {
        }

        connect() {
            this.app = oc.VueApp.getFromElement(this.element);
            this.state = this.app.state;
            this.containers = this.app.containers;

            this.app.registerState('entryTypeOptions', []);

            this.app.registerMethod('onSetEntryType', this.proxy(this.onSetEntryType));

            this.vueElement = this.element.querySelector('[data-vue-template]');
            if (this.vueElement) {
                oc.pageReady().then(() => {
                    this.app.createContainer(this, this.vueElement);
                });
            }

            this.state.entryTypeOptions = [];
            this.makeEntryTypeOptions();
        }

        disconnect() {
            if (this.vueElement) {
                this.app.destroyContainer(this);
            }
        }

        makeEntryTypeOptions() {
            if (!this.state.initial.entryTypeOptions) {
                return;
            }

            const options = this.state.initial.entryTypeOptions;
            const keys = Object.keys(options);

            this.state.entryTypeOptions = [];
            keys.forEach(key => {
                this.state.entryTypeOptions.push({
                    type: 'radiobutton',
                    command: key,
                    checked: this.state.initial.contentGroup == key,
                    label: options[key]
                });
            })
        }

        async onSetEntryType(entryType, isHotkey, ev, targetElement) {
            this.state.processing = true;
            this.state.toolbarDisabled = true;

            document.querySelectorAll('input[name="Template[type]"], #template-type-input').forEach(el => {
                el.value = entryType;
            });

            try {
                await oc.request(targetElement, 'onChangeEntryType', {
                    async: true,
                    data: {
                        entry_type: entryType,
                        Template: {
                            type: entryType
                        },
                        EntryRecord: {
                            content_group: entryType
                        }
                    }
                });

                this.state.initial.contentGroup = entryType;
                this.makeEntryTypeOptions();
            }
            catch (response) {
                $.oc.vueComponentHelpers.modalUtils.showAlert(oc.lang.get('form_error'), response.responseText);
            }

            this.state.toolbarDisabled = false;
            this.state.processing = false;
        }
    }

    oc.registerControl('vue-template-header-controls', VueTemplateHeaderControls);

})();
