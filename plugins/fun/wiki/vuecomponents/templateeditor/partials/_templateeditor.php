<div class="control-tabs primary-tabs form-section-stretch" data-control="tab">
    <!-- Hidden input with serialized fields to submit with the form -->
    <input type="hidden" name="fields_json" :value="JSON.stringify(fields)" />

    <div class="form-tab-nav">
        <ul class="nav nav-tabs">
            <li :class="{ active: activeTab === 'settings' }">
                <a href="javascript:;" @click="activeTab = 'settings'">
                    <span class="title">
                        <span>
                            <span class="icon-settings"></span>
                            Настройки
                        </span>
                    </span>
                </a>
            </li>

            <li v-if="templateType !== 'node'" :class="{ active: activeTab === 'fields' }">
                <a href="javascript:;" @click="activeTab = 'fields'">
                    <span class="title">
                        <span>
                            <span class="icon-components"></span>
                            Поля
                            <span class="badge bg-secondary ms-1" v-text="fields.length"></span>
                        </span>
                    </span>
                </a>
            </li>

            <li v-if="templateType !== 'node'" :class="{ active: activeTab === 'display' }">
                <a href="javascript:;" @click="activeTab = 'display'">
                    <span class="title">
                        <span>
                            <span class="icon-window-split"></span>
                            Отображение
                        </span>
                    </span>
                </a>
            </li>

            <li :class="{ active: activeTab === 'info' }">
                <a href="javascript:;" @click="activeTab = 'info'">
                    <span class="title">
                        <span>
                            <span class="icon-callout-info"></span>
                            Информация
                        </span>
                    </span>
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content form-tab-content pt-3">
        <!-- TAB: НАСТРОЙКИ -->
        <div class="tab-pane active" v-show="activeTab === 'settings'">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group span-left is-required" data-field-name="code">
                        <label class="form-label" for="Template-code">Код шаблона (уникальный)</label>
                        <input
                            type="text"
                            name="Template[code]"
                            id="Template-code"
                            class="form-control"
                            v-model="template.code"
                            required />
                        <p class="form-text">Код конвертируется в snake_case</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group span-right" data-field-name="parent">
                        <label class="form-label" for="Template-parent" v-text="parentLabel"></label>
                        <select
                            name="Template[parent]"
                            id="Template-parent"
                            class="form-select custom-select"
                            v-model="template.parent_id">
                            <option :value="null">--ROOT--</option>
                            <option v-for="pt in parentTemplates" :key="pt.id" :value="pt.id">
                                {{ pt.name }}
                            </option>
                        </select>
                        <p class="form-text" v-text="parentComment"></p>
                    </div>
                </div>
            </div>

            <div class="row" v-if="templateType !== 'variant'">
                <div class="col-md-6">
                    <div class="form-group span-left" data-field-name="slug">
                        <label class="form-label" for="Template-slug">Слаг узла (Node slug)</label>
                        <input
                            type="text"
                            name="Template[slug]"
                            id="Template-slug"
                            class="form-control"
                            v-model="template.slug" />
                        <p class="form-text">Фиксированный путь (например, books, characters)</p>
                    </div>
                </div>
            </div>

            <div class="row pt-2" v-if="templateType !== 'node'">
                <div class="col-md-6">
                    <div class="form-group switch-field span-left" data-field-name="is_mandatory">
                        <div class="form-check form-switch">
                            <input type="hidden" name="Template[is_mandatory]" value="0" />
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="Template[is_mandatory]"
                                value="1"
                                id="Template-is_mandatory"
                                v-model="template.is_mandatory" />
                            <label class="form-check-label" for="Template-is_mandatory">Обязательный</label>
                            <p class="form-text">Записи с данным шаблоном обязательны для построения иерархии</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group switch-field span-left" data-field-name="is_auto_create">
                        <div class="form-check form-switch">
                            <input type="hidden" name="Template[is_auto_create]" value="0" />
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="Template[is_auto_create]"
                                value="1"
                                id="Template-is_auto_create"
                                v-model="template.is_auto_create" />
                            <label class="form-check-label" for="Template-is_auto_create">Создавать автоматически</label>
                            <p class="form-text">Разрешено создавать родителя прямо в поле на лету</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" v-if="templateType === 'article'">
                <div class="col-md-6">
                    <div class="form-group switch-field span-left" data-field-name="is_nested">
                        <div class="form-check form-switch">
                            <input type="hidden" name="Template[is_nested]" value="0" />
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="Template[is_nested]"
                                value="1"
                                id="Template-is_nested"
                                v-model="template.is_nested" />
                            <label class="form-check-label" for="Template-is_nested">Вложенный</label>
                            <p class="form-text">Записи с данным шаблоном могут иметь самовложенность</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: ПОЛЯ -->
        <div class="tab-pane active" v-show="activeTab === 'fields' && templateType !== 'node'">
            <div class="relation-widget">
                <div class="toolbar-widget list-toolbar mb-3">
                    <button type="button" class="btn btn-secondary oc-icon-plus" @click="openAddFieldModal">
                        Создать поле
                    </button>
                </div>

                <div class="control-list border rounded">
                    <table class="table data-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center"><span>#</span></th>
                                <th><span>Ярлык поля</span></th>
                                <th><span>Имя поля (код)</span></th>
                                <th><span>Тип данных</span></th>
                                <th><span>Обязательно</span></th>
                                <th><span>Глобальное</span></th>
                                <th style="width: 90px;" class="text-end"><span>Действия</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="fields.length === 0">
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Нет записей для отображения.
                                </td>
                            </tr>
                            <tr v-for="(field, index) in fields" :key="field.id || index">
                                <td class="text-center">
                                    <div class="btn-group btn-group-xs">
                                        <button
                                            type="button"
                                            class="btn btn-default btn-xs"
                                            :disabled="index === 0"
                                            @click="moveFieldUp(index)"
                                            title="Выше">
                                            <i class="oc-icon-chevron-up"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-default btn-xs"
                                            :disabled="index === fields.length - 1"
                                            @click="moveFieldDown(index)"
                                            title="Ниже">
                                            <i class="oc-icon-chevron-down"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ field.label }}</strong>
                                </td>
                                <td>
                                    <code>{{ field.name }}</code>
                                </td>
                                <td>
                                    {{ field.type_name || field.type }}
                                </td>
                                <td>
                                    <i class="oc-icon-check text-success" v-if="field.is_mandatory"></i>
                                    <i class="oc-icon-times text-muted" v-else></i>
                                </td>
                                <td>
                                    <i class="oc-icon-check text-success" v-if="field.is_global"></i>
                                    <i class="oc-icon-times text-muted" v-else></i>
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-default btn-xs me-1"
                                        @click="openEditFieldModal(index)"
                                        title="Редактировать">
                                        <i class="oc-icon-pencil"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-xs"
                                        @click="deleteField(index)"
                                        title="Удалить">
                                        <i class="oc-icon-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: ОТОБРАЖЕНИЕ -->
        <div class="tab-pane active" v-show="activeTab === 'display' && templateType !== 'node'">
            <div class="form-group" data-field-name="display_fields">
                <label class="form-label">Отображаемые поля (Инфобокс)</label>
                <p class="form-text">Коды полей, которые выводятся читателю в публичной карточке</p>

                <div class="field-taglist">
                    <input
                        type="hidden"
                        name="Template[display_fields][]"
                        v-for="df in template.display_fields"
                        :key="df"
                        :value="df" />

                    <div class="p-3 bg-light border rounded mb-3">
                        <div v-if="fields.length === 0" class="text-muted small">
                            Поля пока не добавлены. Сначала создайте поля во вкладке «Поля».
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button
                                type="button"
                                v-for="field in fields"
                                :key="field.name"
                                class="btn btn-sm"
                                :class="isDisplayFieldActive(field.name) ? 'btn-primary' : 'btn-default'"
                                @click="toggleDisplayField(field.name)">
                                <i :class="isDisplayFieldActive(field.name) ? 'oc-icon-check me-1' : 'oc-icon-plus me-1'"></i>
                                {{ field.label }}
                                <code class="ms-1" :class="isDisplayFieldActive(field.name) ? 'text-white' : 'text-muted'">{{ field.name }}</code>
                            </button>
                        </div>
                    </div>

                    <div v-if="template.display_fields.length > 0" class="small text-muted">
                        Выбранный порядок:
                        <span class="badge bg-secondary me-1" v-for="df in template.display_fields" :key="df">{{ df }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: ИНФОРМАЦИЯ -->
        <div class="tab-pane active" v-show="activeTab === 'info'">
            <div class="form-group" data-field-name="description">
                <label class="form-label" for="Template-description">Описание шаблона</label>
                <textarea
                    name="Template[description]"
                    id="Template-description"
                    class="form-control"
                    rows="5"
                    v-model="template.description"
                    placeholder=""></textarea>
            </div>
        </div>
    </div>

    <!-- MODAL: СОЗДАНИЕ / РЕДАКТИРОВАНИЕ ПОЛЯ -->
    <div
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.45); z-index: 1050;"
        v-if="fieldModalOpen"
        @click.self="cancelFieldModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title">{{ editingFieldIndex === -1 ? 'Создать поле' : 'Редактировать поле' }}</h5>
                    <button type="button" class="btn-close" @click="cancelFieldModal"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group is-required mb-3">
                        <label class="form-label">Ярлык поля</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="editingField.label"
                            @input="onFieldLabelInput"
                            placeholder="например: Дата выхода, Автор" />
                    </div>

                    <div class="form-group is-required mb-3">
                        <label class="form-label">Имя поля (код)</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="editingField.name"
                            placeholder="например: release_date, author" />
                        <p class="form-text">Латинские буквы, цифры, дефис или подчеркивание</p>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Тип данных</label>
                        <select class="form-select custom-select" v-model="editingField.type">
                            <option v-for="ft in availableFieldTypes" :key="ft.code" :value="ft.code">
                                {{ ft.label }} ({{ ft.code }})
                            </option>
                        </select>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="modal-field-mandatory" v-model="editingField.is_mandatory" />
                        <label class="form-check-label" for="modal-field-mandatory">Обязательно</label>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="modal-field-global" v-model="editingField.is_global" />
                        <label class="form-check-label" for="modal-field-global">Глобальное</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" @click="cancelFieldModal">Отмена</button>
                    <button type="button" class="btn btn-primary" @click="saveFieldModal">Применить</button>
                </div>
            </div>
        </div>
    </div>
</div>