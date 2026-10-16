/**
 * WikiX Template Document control
 */
(function() {

    class VueTemplateDocument extends oc.ControlBase {
        init() {
        }

        connect() {
            this.app = oc.VueApp.getFromElement(this.element);
            this.state = this.app.state;
            this.containers = this.app.containers;

            this.app.registerState('toolbarDisabled', false);
            this.app.registerState('toolbarExtensionPoint', []);
            this.app.registerState('toolbarExtraButtons', []);
            this.app.registerState('toolbarElements', []);
            this.app.registerState('entryTypeOptions', []);

            this.app.registerMethod('onCommand', this.proxy(this.onCommand));
            this.app.registerMethod('onSetEntryType', this.proxy(this.onSetEntryType));

            this.vueElement = this.element.querySelector('[data-vue-template]');
            if (this.vueElement) {
                oc.pageReady().then(() => {
                    this.app.createContainer(this, this.vueElement);
                });
            }

            this.initState();
            this.initListeners();
            this.refreshToolbarExtraButtons();
            this.makeEntryTypeOptions();
        }

        disconnect() {
            this.destroyListeners();
            this.previewTracker = null;
            if (this.vueElement) {
                this.app.destroyContainer(this);
            }
        }

        initListeners() {
            $(window).on('oc.updateUi', this.proxy(this.refreshToolbars));
            $(window).one('shown.bs.tab', this.proxy(this.refreshToolbars));
            setTimeout(() => this.refreshToolbars(), 0);

            this.state.eventBus.on('documentloadingstart', () => {
                this.state.processing = true;
            });

            this.state.eventBus.on('documentloadingend', () => {
                this.state.processing = false;
            });
        }

        destroyListeners() {
            $(window).off('oc.updateUi', this.proxy(this.refreshToolbars));
            $(window).off('shown.bs.tab', this.proxy(this.refreshToolbars));
        }

        refreshToolbars() {
            this.state.toolbarExtensionPoint.splice(0);
            this.state.eventBus.emit('extendapptoolbar');
        }

        refreshToolbarExtraButtons() {
            this.state.toolbarExtraButtons.splice(0);
        }

        initState() {
            this.state.toolbarElements = [];
            this.state.entryTypeOptions = [];

            this.state.toolbarElements.push(this.makeSaveButton());

            this.state.toolbarElements.push({
                type: 'button',
                icon: 'icon-keyboard-return',
                label: oc.lang.get('form_save_close'),
                tooltip: oc.lang.get('form_save_close'),
                hotkey: 'ctrl+enter, cmd+enter',
                tooltipHotkey: '⌃S, ⌘S',
                command: 'form:onSave',
                customData: {
                    request: {
                        browserRedirectBack: true,
                        data: { close: 1 }
                    }
                }
            });

            this.state.toolbarElements.push(this.state.toolbarExtraButtons);

            if (!this.state.initial.isCreateAction) {
                this.state.toolbarElements.push(this.makeDeleteButton());
            }

            if (this.state.initial.hasPreviewPage && !this.state.initial.isCreateAction) {
                this.state.toolbarElements.push({
                    type: 'button',
                    icon: 'icon-location-target',
                    command: 'onPreview',
                    label: oc.lang.get('preview')
                });
            }

            this.state.toolbarElements.push(this.state.toolbarExtensionPoint);
        }

        makeSaveButton() {
            return {
                type: 'button',
                icon: 'icon-save-cloud',
                label: oc.lang.get('form_save'),
                tooltip: oc.lang.get('form_save'),
                hotkey: 'ctrl+s, cmd+s',
                tooltipHotkey: '⌃S, ⌘S',
                command: 'form:onSave',
                showBadge: false,
                customData: {
                    request: {
                        data: { 'redirect': 0 }
                    }
                }
            }
        }

        makeDeleteButton() {
            return {
                type: 'button',
                icon: 'icon-delete',
                command: 'form:onDelete',
                hotkey: 'shift+option+d',
                label: oc.lang.get('form_delete'),
                fixedRight: true,
                tooltip: oc.lang.get('form_delete'),
                tooltipHotkey: '⇧⌥D',
                customData: {
                    confirm: oc.lang.get('delete_entry_confirm')
                }
            };
        }

        handleFormSaved(data) {
            if (this.containers.vueTemplateHeaderControls?.$refs?.publishingControls) {
                this.containers.vueTemplateHeaderControls.$refs.publishingControls.updateSavedState();
            }
            $('#tailor-form').trigger('unchange.oc.changeMonitor');

            this.state.toolbarElements[0] = this.makeSaveButton();
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

        async onCommand(command, isHotkey, ev, targetElement, customData, throwOnError) {
            if (!this.app.isFormCommand(command)) {
                return;
            }

            this.state.toolbarDisabled = true;
            $('#tailor-form').trigger('pauseUnloadListener');

            try {
                let data = await this.app.onCommand(command, isHotkey, ev, targetElement, customData);
                this.state.toolbarDisabled = false;

                if (command == 'form:onSave' || command == 'form:onCommitDraft') {
                    this.handleFormSaved(data);
                }

                $('#tailor-form').trigger('resumeUnloadListener');
            }
            catch (error) {
                $('#tailor-form').trigger('resumeUnloadListener');
                this.state.toolbarDisabled = false;
                if (throwOnError) {
                    throw error;
                }
            }
        }

        async onSetEntryType(entryType, isHotkey, ev, targetElement) {
            this.state.processing = true;
            this.state.toolbarDisabled = true;

            const hiddenInput = document.getElementById('template-type-input');
            if (hiddenInput) {
                hiddenInput.value = entryType;
            }

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

    oc.registerControl('vue-template-document', VueTemplateDocument);

})();
