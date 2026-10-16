(function() {
    'use strict';

    window.initWikixMetaFieldsWidget = function(widgetId, handlerUrl) {
        var container = document.getElementById('widget_' + widgetId) || document.getElementById(widgetId);
        if (!container) return false;

        var fromEl = document.getElementById('from_' + widgetId) || container.querySelector('.drag-and-drop__items--from') || container.querySelector('ul:first-of-type');
        var toEl = document.getElementById('to_' + widgetId) || container.querySelector('.drag-and-drop__items--to') || container.querySelector('ul:last-of-type');
        var inputEl = document.getElementById('input_' + widgetId) || container.querySelector('input[type="hidden"]');
        var inputsContainer = container.querySelector('.metafields-inputs');
        var inputName = container.dataset.inputName || 'Template[display_fields][]';

        if (!fromEl || !toEl || !window.Sortable) return false;

        // Destroy existing sortable instances if already attached
        if (fromEl._sortable) {
            fromEl._sortable.destroy();
        }
        if (toEl._sortable) {
            toEl._sortable.destroy();
        }

        function syncValues(triggerAjax) {
            var toItems = toEl.querySelectorAll('.drag-and-drop__item');
            var values = [];

            toItems.forEach(function(item) {
                var fieldName = item.dataset.fieldName;
                if (fieldName) {
                    values.push(fieldName);
                }
            });

            // Update main JSON input
            if (inputEl) {
                inputEl.value = JSON.stringify(values);
            }

            // Update array inputs for form submission
            if (inputsContainer) {
                inputsContainer.innerHTML = '';
                values.forEach(function(fieldName) {
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = inputName;
                    inp.value = fieldName;
                    inputsContainer.appendChild(inp);
                });
            }

            // Send AJAX update if requested
            if (triggerAjax) {
                var postData = { value: values, display_fields: values };
                var handler = handlerUrl || 'onSetDisplayFields';

                if (window.oc && typeof oc.ajax === 'function') {
                    oc.ajax(handler, {
                        data: postData,
                        error: function(err) {
                            // Fallback to controller onSetDisplayFields if widget handler fails
                            if (handler !== 'onSetDisplayFields') {
                                oc.ajax('onSetDisplayFields', { data: postData });
                            }
                        }
                    });
                } else if (window.jQuery && typeof window.jQuery.request === 'function') {
                    window.jQuery.request(handler, { data: postData });
                }
            }
        }

        fromEl._sortable = new Sortable(fromEl, {
            group: 'shared_' + widgetId,
            animation: 150,
            draggable: '.drag-and-drop__item',
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                syncValues(true);
            }
        });

        toEl._sortable = new Sortable(toEl, {
            group: 'shared_' + widgetId,
            animation: 150,
            draggable: '.drag-and-drop__item',
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                syncValues(true);
            }
        });

        syncValues(false);
        return true;
    };
})();
