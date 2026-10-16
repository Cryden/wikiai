<div data-control="vue-template-header-controls">
    <div data-vue-template>
        <div class="record-management-controls">
            <backend-dropdown-menu-button
                :menuitems="state.entryTypeOptions"
                preferable-menu-position="bottom-right"
                css-class="record-management-button has-menu entry-type-selector"
                :current-label-command="state.initial.contentGroup"
                :disabled="state.toolbarDisabled"
                @command="onSetEntryType">
            </backend-dropdown-menu-button>
        </div>
    </div>
</div>
