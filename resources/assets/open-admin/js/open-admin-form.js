/*-------------------------------------------------*/
/* forms */
/*-------------------------------------------------*/

admin.form = {
    id: false,
    tabs_ref: false,
    beforeSaveCallbacks: [],

    init: function () {
        this.addAjaxSubmit();
        this.footer();
        this.tabs();
        this.initValidation();
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
        document.querySelector(selector).addEventListener('submit', function (event) {
            let elems = event.target.querySelectorAll('div.cascade-group.d-none input');
            if (elems) {
                elems.forEach((field) => {
                    field.setAttribute('disabled', true);
                });
            }
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
            check.addEventListener('change', function (event) {

                var elemClass = event.target.dataset.elemClass;
                var elemClassSelector = '.' + elemClass.replace(/ /g, ".");
                var choicesVar = 'choices_' + elemClass.replace(/[ -]/g, "_");
                var listboxVar = 'dualListbox_' + elemClass.replace(/[ -]/g, "_");

                var defaultValue = event.target.dataset.defaultValue;
                try {defaultValue = JSON.parse(defaultValue);} catch (e) {}
                var currentValue = event.target.dataset.currentValue;
                try {currentValue = JSON.parse(currentValue);} catch (e) {}



                if (window.hasOwnProperty('choices_vars') && window.choices_vars.hasOwnProperty(choicesVar)) {
                    window.choices_vars[choicesVar].clearInput();

                    if (event.target.checked) {
                        currentValue = window.choices_vars[choicesVar].getValue(true);
                        if (Array.isArray(currentValue)) {
                            currentValue = JSON.stringify(currentValue);
                        }
                        event.target.dataset.currentValue = currentValue;

                        window.choices_vars[choicesVar].removeActiveItems();

                        if (Array.isArray(defaultValue)) {
                            defaultValue.forEach((val) => {
                                window.choices_vars[choicesVar].setChoiceByValue(val);
                            });
                        } else {
                            window.choices_vars[choicesVar].setChoiceByValue(defaultValue);
                        }

                        window.choices_vars[choicesVar].disable();
                    } else {
                        window.choices_vars[choicesVar].enable();
                        window.choices_vars[choicesVar].removeActiveItems();

                        if (Array.isArray(currentValue)) {
                            currentValue.forEach((val) => {
                                window.choices_vars[choicesVar].setChoiceByValue(val);
                            });
                        } else {
                            window.choices_vars[choicesVar].setChoiceByValue(currentValue);
                        }
                    }
                } else if (window.hasOwnProperty('listbox_vars') && window.listbox_vars.hasOwnProperty(listboxVar)) {


                    if (event.target.checked) {
                        currentValue = Array.from(window.listbox_vars[listboxVar].selected).map(e => e.dataset.id);
                        if (Array.isArray(currentValue)) {
                            currentValue = JSON.stringify(currentValue);
                        }
                        event.target.dataset.currentValue = currentValue;

                        window.listbox_vars[listboxVar]._actionAllDeselected(event);

                        if (!Array.isArray(defaultValue)) {
                            defaultValue = [defaultValue];
                        }

                        var available = Array.from(window.listbox_vars[listboxVar].available);
                        defaultValue.forEach((val) => {
                            var item = available.find(c => c.dataset.id == val);
                            if (item) {
                                window.listbox_vars[listboxVar].addSelected(item);
                            }
                        });
                        window.listbox_vars[listboxVar].redraw();
                        window.listbox_vars[listboxVar].disable();
                    } else {
                        window.listbox_vars[listboxVar].enable();
                        window.listbox_vars[listboxVar]._actionAllDeselected(event);

                        if (!Array.isArray(currentValue)) {
                            currentValue = [currentValue];
                        }

                        var available = Array.from(window.listbox_vars[listboxVar].available);
                        currentValue.forEach((val) => {
                            var item = available.find(c => c.dataset.id == val);
                            if (item) {
                                window.listbox_vars[listboxVar].addSelected(item);
                            }
                        });
                    }
                } else {
                    var elem = document.querySelector(elemClassSelector);
                    if (elem) {
                        if (event.target.checked) {
                            if (elem.nodeName == 'SELECT') {
                                currentValue = Array.from(elem.selectedOptions).map(({ value }) => value);
                                elem.value = '';
                                if (Array.isArray(defaultValue)) {
                                    var options = Array.from(elem.querySelectorAll('option'));
                                    defaultValue.forEach((val) => {
                                        options.find(c => c.value == val).selected = true;
                                    });
                                } else if (defaultValue) {
                                    elem.value = defaultValue;
                                }

                                elem.disabled = true;
                            } else {
                                if (elem.type == 'checkbox' || elem.type == 'radio') {
                                    currentValue = [...document.querySelectorAll(elemClassSelector+':checked')].map(e => e.value);

                                    var checkboxes = Array.from(document.querySelectorAll(elemClassSelector));
                                    checkboxes.forEach(el => el.checked = false);

                                    if (Array.isArray(defaultValue)) {
                                        // var checkboxes = Array.from(checkboxes);
                                        defaultValue.forEach((val) => {
                                            checkboxes.find(c => c.value == val).checked = true;
                                        });
                                    } else if (defaultValue) {
                                        checkboxes.find(c => c.value == defaultValue).checked = true;
                                    }

                                    checkboxes.forEach(el => el.disabled = true);
                                } else {
                                    currentValue = elem.value;
                                    elem.value = defaultValue;
                                    elem.disabled = true;
                                }
                            }

                            if (Array.isArray(currentValue)) {
                                currentValue = JSON.stringify(currentValue);
                            }
                            event.target.dataset.currentValue = currentValue;
                        } else {
                            if (elem.nodeName == 'SELECT') {
                                elem.disabled = false;
                                elem.value = '';
                                if (Array.isArray(currentValue)) {
                                    var options = Array.from(elem.querySelectorAll('option'));
                                    currentValue.forEach((val) => {
                                        options.find(c => c.value == val).selected = true;
                                    });
                                } else if (currentValue) {
                                    elem.value = currentValue;
                                }
                            } else {
                                if (elem.type == 'checkbox' || elem.type == 'radio') {
                                    var checkboxes = Array.from(document.querySelectorAll(elemClassSelector));
                                    checkboxes.forEach(el => {el.disabled = false; el.checked = false});
                                    if (Array.isArray(currentValue)) {
                                        // var checkboxes = Array.from(checkboxes);
                                        currentValue.forEach((val) => {
                                            checkboxes.find(c => c.value == val).checked = true;
                                        });
                                    } else if (currentValue) {
                                        checkboxes.find(c => c.value == currentValue).checked = true;
                                    }
                                } else {
                                    elem.value = currentValue;
                                    elem.disabled = false;
                                }
                            }
                        }
                    }
                }
            });
        });
    },
};
