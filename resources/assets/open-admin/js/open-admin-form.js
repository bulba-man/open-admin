/*-------------------------------------------------*/
/* forms */
/*-------------------------------------------------*/

admin.form = {
    id: false,
    tabs_ref: false,
    beforeSaveCallbacks: [],
    cascadeEventsBound: false,
    jsonFieldEventsBound: false,

    init: function () {
        this.addAjaxSubmit();
        this.footer();
        this.tabs();
        this.initValidation();
        this.cascade();
        this.jsonFields();
        this.resettable();
    },

    addSaveCallback: function (callback) {
        this.beforeSaveCallbacks.push(callback);
    },

    beforeSave: function () {
        if (this.beforeSaveCallbacks.length) {
            for (i in this.beforeSaveCallbacks) {
                var callback = this.beforeSaveCallbacks[i];
                callback();
            }
        }
    },

    addAjaxSubmit: function () {
        // forms that should be submitted with ajax
        Array.from(document.getElementsByTagName('form')).forEach((form) => {
            if (form.getAttribute('pjax-container') != null && !form.classList.contains('has-ajax-handler')) {
                form.addEventListener('submit', function (event) {
                    admin.form.submit(event.target);

                    event.preventDefault();
                    return false;
                });

                form.classList.add('has-ajax-handler');
            }
        });
    },

    submit: function (form, result_function) {
        let method = form.getAttribute('method').toLowerCase();
        let url = String(form.getAttribute('action')).split('?')[0];
        let obj = {};

        this.beforeSave();

        if (admin.form.validate(form)) {
            if (method === 'post' || method === 'put') {
                obj.data = new FormData(form);
                obj.method = method;
            } else {
                //let data = Object.fromEntries(new FormData(form).entries()); //this doesn't get arrays, not sure why used in the first place
                let data = new FormData(form);
                let searchParams = new URLSearchParams(data);

                let currentUrl = new URL(window.location.href);
                currentUrl.searchParams.forEach((value, key) => {
                    if (!searchParams.has(key)) {
                        searchParams.set(key, value);
                    }
                });

                let query_str = searchParams.toString();
                url += '?' + query_str;

                if (typeof result_function !== 'function') {
                    admin.ajax.setUrl(url);
                }
            }

            if (typeof result_function === 'function') {
                admin.ajax.request(url, obj, result_function);
            } else {
                admin.ajax.load(url, obj);
            }
        } else {
            console.log('Form still has errors');
        }
    },

    footer: function () {
        document.querySelectorAll('.after-submit').forEach((check) => {
            check.addEventListener('click', function () {
                document.querySelectorAll(".after-submit:not([value='" + this.value + "']").forEach((other) => {
                    other.checked = false;
                });
            });
        });
    },

    tabs: function () {
        var hash = document.location.hash;
        if (hash) {
            var activeTab = document.querySelector('.nav-tabs a[href="' + hash + '"]');
            if (activeTab) {
                new bootstrap.Tab(activeTab).show();
            }
        }

        this.tabs_ref = document.querySelectorAll('.nav-tabs');
        if (this.tabs_ref.length) {
            this.tabs_ref.forEach((tab) => {
                tab.addEventListener('shown.bs.tab', function (event) {
                    // replaceState insted of pushSt (prevents tab navigation from going into the history)
                    history.replaceState(null, null, event.target.hash);
                });
            });
        }
        this.check_tab_errors();
    },

    check_tab_errors() {
        let errors = document.querySelectorAll('.tab-pane .has-error, .was-validated .tab-pane .form-control:invalid');
        if (this.tabs_ref.length && errors) {
            let first_tab = false;
            errors.forEach((error) => {
                let tabId = '#' + error.closest('.tab-pane').getAttribute('id');
                document.querySelector('li a[href="' + tabId + '"] i').classList.remove('hide');
                if (!first_tab) {
                    first_tab = tabId;
                }
            });
            if (first_tab) {
                let errorTab = document.querySelector('.nav-tabs a[href="' + first_tab + '"]');
                new bootstrap.Tab(errorTab).show();
            }
        }
    },

    disable_cascaded_forms: function (selector) {
        let form = document.querySelector(selector);

        if (!form || form.dataset.cascadeSubmitInitialized === '1') {
            return;
        }

        form.dataset.cascadeSubmitInitialized = '1';

        form.addEventListener('submit', function (event) {
            admin.form.disableHiddenCascadeGroups(event.target);
        });
    },

    disableHiddenCascadeGroups: function (form) {
        form.querySelectorAll('div.cascade-group.d-none, div.cascade-group.hide').forEach((group) => {
            admin.form.setCascadeGroupFieldsDisabled(group, true);
        });
    },

    cascade: function (container) {
        container = container || document;

        this.bindCascadeEvents();

        let fields = [];

        if (container.matches && container.matches('[data-cascade-groups]')) {
            fields.push(container);
        }

        container.querySelectorAll('[data-cascade-groups]').forEach((field) => {
            fields.push(field);
        });

        fields.forEach((field) => {
            this.updateCascade(field);
        });
    },

    bindCascadeEvents: function () {
        if (this.cascadeEventsBound) {
            return;
        }

        this.cascadeEventsBound = true;

        document.addEventListener('change', function (event) {
            if (!event.target || !event.target.closest) {
                return;
            }

            let field = event.target.closest('[data-cascade-groups]');

            if (!field || (field.dataset.cascadeEvent || 'change') !== event.type) {
                return;
            }

            admin.form.updateCascade(field);
        });
    },

    updateCascade: function (field) {
        let groups = this.getCascadeGroups(field);
        let scope = this.getCascadeScope(field);
        let value = this.getCascadeValue(field, scope);

        groups.forEach((settings) => {
            let selector = 'div.cascade-group.' + this.escapeCascadeSelector(settings.class);

            scope.querySelectorAll(selector).forEach((group) => {
                this.setCascadeGroupVisibility(group, this.compareCascadeValues(value, settings.operator, settings.value));
            });
        });
    },

    getCascadeGroups: function (field) {
        try {
            return JSON.parse(field.getAttribute('data-cascade-groups') || '[]');
        } catch (e) {
            return [];
        }
    },

    getCascadeScope: function (field) {
        return field.closest('.fields-group') || field.closest('form') || document;
    },

    escapeCascadeSelector: function (selector) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(selector);
        }

        return selector;
    },

    getCascadeValue: function (field, scope) {
        let type = field.getAttribute('data-cascade-type');

        if (type === 'switch') {
            return field.checked
                ? field.getAttribute('data-cascade-switch-on')
                : field.getAttribute('data-cascade-switch-off');
        }

        if (type === 'checkbox') {
            return this.getCheckedCascadeValues(field, scope);
        }

        if (type === 'radio') {
            let selector = field.getAttribute('data-cascade-selector');

            if (!selector) {
                return '';
            }

            let checked = scope.querySelector(selector + ':checked');

            return checked ? checked.value : '';
        }

        if (type === 'select-multiple' || field.multiple) {
            return Array.from(field.selectedOptions).map((option) => option.value);
        }

        return field.value;
    },

    getCheckedCascadeValues: function (field, scope) {
        let values = [];
        let selector = field.getAttribute('data-cascade-selector');

        if (!selector) {
            return values;
        }

        scope.querySelectorAll(selector + ':checked').forEach((checked) => {
            values.push(checked.value);
        });

        return values;
    },

    setCascadeGroupVisibility: function (group, visible) {
        let wasVisible = !group.classList.contains('d-none') && !group.classList.contains('hide');

        group.classList.toggle('d-none', !visible);
        group.classList.toggle('hide', !visible);

        this.setCascadeGroupFieldsDisabled(group, !visible);

        if (visible && !wasVisible) {
            this.cascade(group);
        }
    },

    setCascadeGroupFieldsDisabled: function (group, disabled) {
        group.querySelectorAll('input, select, textarea, button').forEach((field) => {
            if (disabled) {
                if (!field.disabled) {
                    field.dataset.cascadeDisabled = '1';
                    field.disabled = true;
                }

                return;
            }

            if (field.dataset.cascadeDisabled === '1') {
                field.disabled = false;
                delete field.dataset.cascadeDisabled;
            }
        });
    },

    normalizeCascadeValue: function (value) {
        if (Array.isArray(value)) {
            return value.map((item) => String(item));
        }

        if (value === null || typeof value === 'undefined') {
            return '';
        }

        return String(value);
    },

    compareCascadeValues: function (currentValue, operator, expectedValue) {
        let current = this.normalizeCascadeValue(currentValue);
        let expected = this.normalizeCascadeValue(expectedValue);

        switch (operator) {
            case '=':
                if (Array.isArray(current) && Array.isArray(expected)) {
                    return current.slice().sort().join() === expected.slice().sort().join();
                }

                return current == expected;
            case '>':
                return current > expected;
            case '<':
                return current < expected;
            case '>=':
                return current >= expected;
            case '<=':
                return current <= expected;
            case '!=':
                if (Array.isArray(current) && Array.isArray(expected)) {
                    return current.slice().sort().join() !== expected.slice().sort().join();
                }

                return current != expected;
            case 'in':
                return Array.isArray(expected) && expected.indexOf(current) !== -1;
            case 'notIn':
                return !Array.isArray(expected) || expected.indexOf(current) === -1;
            case 'has':
                return Array.isArray(current) && current.indexOf(expected) !== -1;
            case 'oneIn':
                return Array.isArray(current) && Array.isArray(expected) && current.filter((value) => expected.includes(value)).length >= 1;
            case 'oneNotIn':
                return !Array.isArray(current) || !Array.isArray(expected) || current.filter((value) => expected.includes(value)).length === 0;
            default:
                return false;
        }
    },

    jsonFields: function () {
        this.bindJsonFieldEvents();
    },

    bindJsonFieldEvents: function () {
        if (this.jsonFieldEventsBound) {
            return;
        }

        this.jsonFieldEventsBound = true;

        document.addEventListener('click', function (event) {
            if (!event.target || !event.target.closest) {
                return;
            }

            let keyValueAdd = event.target.closest('[data-key-value-add]');
            if (keyValueAdd) {
                event.preventDefault();
                admin.form.addKeyValueRow(keyValueAdd);
                return;
            }

            let keyValueRemove = event.target.closest('[data-key-value-remove]');
            if (keyValueRemove) {
                event.preventDefault();
                admin.form.removeJsonFieldRow(keyValueRemove);
                return;
            }

            let listAdd = event.target.closest('[data-list-field-add]');
            if (listAdd) {
                event.preventDefault();
                admin.form.addListFieldRow(listAdd.closest('[data-list-field]'));
                return;
            }

            let listRemove = event.target.closest('[data-list-field-remove]');
            if (listRemove) {
                event.preventDefault();
                admin.form.removeJsonFieldRow(listRemove);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!event.target.matches || !event.target.matches('[data-list-field] input, [data-list-field] textarea')) {
                return;
            }

            admin.form.handleListFieldKeydown(event);
        });

        document.addEventListener('paste', function (event) {
            if (!event.target.matches || !event.target.matches('[data-list-field] input, [data-list-field] textarea')) {
                return;
            }

            admin.form.handleListFieldPaste(event);
        });
    },

    addKeyValueRow: function (trigger) {
        let field = trigger.closest('[data-key-value-field]');

        if (!field) {
            return null;
        }

        let row = this.addJsonFieldRow(field, '[data-key-value-template]', '[data-key-value-table]');

        if (row) {
            this.focusJsonFieldInput(row);
        }

        return row;
    },

    addListFieldRow: function (field) {
        if (!field) {
            return null;
        }

        let row = this.addJsonFieldRow(field, '[data-list-field-template]', '[data-list-field-table]');
        let input = row ? this.focusJsonFieldInput(row) : null;

        return input;
    },

    addJsonFieldRow: function (field, templateSelector, tableSelector) {
        let wrapper = field.parentElement || field;
        let template = field.querySelector(templateSelector) || wrapper.querySelector(templateSelector);
        let table = field.querySelector(tableSelector) || wrapper.querySelector(tableSelector);

        if (!template || !table) {
            return null;
        }

        let fragment;
        let row;

        if (template.content) {
            fragment = template.content.cloneNode(true);
            row = fragment.firstElementChild;
        } else {
            row = htmlToElement(template.innerHTML);
            fragment = row;
        }

        table.appendChild(fragment);

        return row;
    },

    removeJsonFieldRow: function (trigger) {
        let row = trigger.closest('tr');

        if (row) {
            row.remove();
        }
    },

    focusJsonFieldInput: function (row) {
        let input = row.querySelector('input, textarea');

        if (!input) {
            return null;
        }

        input.focus();

        if (typeof input.setSelectionRange === 'function') {
            input.setSelectionRange(input.value.length, input.value.length);
        }

        return input;
    },

    handleListFieldKeydown: function (event) {
        let field = event.target.closest('[data-list-field]');
        let row = event.target.closest('tr');

        if (!field || !row) {
            return;
        }

        if (event.key === 'Enter' || event.keyCode === 13) {
            event.preventDefault();

            let next = row.nextElementSibling;
            if (next && next.nodeName === 'TR') {
                this.focusJsonFieldInput(next);
                return false;
            }

            this.addListFieldRow(field);

            return false;
        }

        if ((event.key === 'Delete' || event.keyCode === 46) && !event.target.value.length) {
            event.preventDefault();

            let prev = row.previousElementSibling;
            row.remove();

            if (prev && prev.nodeName === 'TR') {
                this.focusJsonFieldInput(prev);
            }

            return false;
        }
    },

    handleListFieldPaste: function (event) {
        let field = event.target.closest('[data-list-field]');
        let clipboardData = event.clipboardData || window.clipboardData;

        if (!field || !clipboardData) {
            return;
        }

        let pastedData = clipboardData.getData('Text');
        let rows = pastedData.split(/\r\n|\r|\n/);

        if (!rows.length) {
            return;
        }

        event.stopPropagation();
        event.preventDefault();

        event.target.value = rows[0];

        rows.slice(1).forEach((value) => {
            let input = this.addListFieldRow(field);

            if (input) {
                input.value = value;
            }
        });
    },

    inputmask: function (selector, options, container) {
        if (typeof Inputmask !== 'function') {
            return;
        }

        container = container || document;

        let fields = [];

        if (container.matches && container.matches(selector)) {
            fields.push(container);
        }

        container.querySelectorAll(selector).forEach((field) => {
            fields.push(field);
        });

        fields.forEach((field) => {
            if (field.dataset.inputmaskInitialized === '1') {
                return;
            }

            field.dataset.inputmaskInitialized = '1';
            Inputmask(options).mask(field);
        });
    },

    initValidation: function () {
        var forms = document.querySelectorAll('.needs-validation');
        forms.forEach(function (form) {
            form.addEventListener(
                'submit',
                function (event) {
                    if (!admin.form.validate(form)) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    return false;
                },
                false
            );
        });
    },

    validate: function (form) {
        let res = true;

        if (form.classList.contains('needs-validation')) {
            res = form.checkValidity();
            form.classList.add('was-validated');
            admin.form.check_tab_errors();
        }
        return res;
    },

    resettable: function () {
        document.querySelectorAll('.reset-field-to-default').forEach((check) => {
            new ResettableField(check);
        });
    },
};
