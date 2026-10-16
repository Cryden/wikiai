<form
    data-request="onImportTemplates"
    data-request-files
    data-request-flash>
    <div class="modal-header">
        <h4 class="modal-title">Импорт шаблонов (JSON)</h4>
        <button type="button" class="btn-close" data-dismiss="popup"></button>
    </div>

    <div class="modal-body">
        <div class="form-group mb-3">
            <label class="form-label font-weight-bold">Загрузить JSON файл</label>
            <input type="file" name="import_file" accept=".json" class="form-control" />
            <div class="form-text text-muted">Выберите экспортированный файл <code>.json</code></div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label font-weight-bold">Или вставьте JSON код</label>
            <textarea
                name="import_json"
                class="form-control font-monospace"
                rows="8"
                placeholder='[ { "name": "Персоны", "code": "persons", ... } ]'></textarea>
        </div>

        <div class="form-check">
            <input type="checkbox" name="truncate_existing" value="1" id="truncateExisting" class="form-check-input" />
            <label for="truncateExisting" class="form-check-label">
                Очистить существующие шаблоны и поля перед импортом
            </label>
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-primary oc-icon-upload">
            Импортировать
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="popup">
            Отмена
        </button>
    </div>
</form>

