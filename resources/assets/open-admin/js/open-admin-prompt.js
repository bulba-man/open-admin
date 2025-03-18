/**
 * use:
 *
 * promptShell.init(document.getElementById('modalPrompt'))
 *
 * function prompt(data) {
 *     promptShell.load(data);
 * }
 *
 * prompt({
 *    title: "Title",
 *    content: "Text",
 *    value: "",
 *    validation: true,
 *    attributesForm: {
 *        novalidate: 'novalidate',
 *        action: ''
 *    },
 *    attributesField: {
 *        name: 'name',
 *        required: true
 *    },
 *    actions: {
 *        confirm: function (value) {}
 *        cancel: function () {}
 *        always: function () {}
 *    }
 * });
 */

const promptShell = {
    modalNode: null,
    titleNode: null,
    bodyNode: null,
    contentNode: null,
    formNode: null,
    modalFooterNode: null,
    promptModal: null,
    form: null,
    field: null,
    options: {},

    init: function (modalNode, options = {backdrop: 'static'}) {

        this.modalNode = modalNode;
        this.promptModal = new bootstrap.Modal(modalNode, options);
        this.initModalNodes();
        this.registerModalObservers();
        this.registerButtonsObserver();
    },

    load: function (options) {
        this.options = options;
        this.clean();
        this.fill();
        this.open();
    },

    initModalNodes: function () {
        this.titleNode = this.modalNode.querySelector('.modal-title');
        this.bodyNode = this.modalNode.querySelector('.modal-body');
        this.contentNode = this.bodyNode.querySelector('.content-block');
        this.formNode = this.bodyNode.querySelector('.form-block');
        this.modalFooterNode = this.modalNode.querySelector('.modal-footer');
    },

    registerButtonsObserver: function () {
        var buttons = this.modalFooterNode.querySelectorAll('[data-action]');
        buttons.forEach((but) => {
            var action = but.dataset.action;
            action = action[0].toUpperCase() + action.substring(1);
            var observerName = 'onAction'+action;
            if (this.hasOwnProperty(observerName)) {
                // but.addEventListener("click", (e)=>{promptShell[observerName](e)});
                but.addEventListener("click", function(e) {
                    promptShell[observerName](e);
                });
            }
        });
    },

    registerModalObservers: function () {
        this.modalNode.addEventListener('show.bs.modal', (e)=>{promptShell.onModalShow(e)});
        this.modalNode.addEventListener('shown.bs.modal', (e)=>{promptShell.onModalShown(e)});
        this.modalNode.addEventListener('hide.bs.modal', (e)=>{promptShell.onModalHide(e)});
        this.modalNode.addEventListener('hidden.bs.modal', (e)=>{promptShell.onModalHidden(e)});
        this.modalNode.addEventListener('hidePrevented.bs.modal', (e)=>{promptShell.modalHidePrevented(e)});
    },

    onModalShow: function (event) {},
    onModalShown: function (event) {},
    onModalHide: function (event) {},
    onModalHidden: function (event) {},
    modalHidePrevented: function (event) {},

    onActionConfirm: function (event) {
        if (this.options.hasOwnProperty('validation') && this.options.validation && !this.form.checkValidity()) {
            this.form.classList.add('was-validated');
            event.preventDefault();
            return false;
        }

        if (this.options.hasOwnProperty('actions') && this.options.actions.hasOwnProperty('confirm')) {
            var value = this.input.value;
            this.options.actions.confirm(value);
        }

        if (this.options.hasOwnProperty('actions') && this.options.actions.hasOwnProperty('always')) {
            this.options.actions.always();
        }

        this.close();
    },

    onActionCancel: function (event) {
        if (this.options.hasOwnProperty('actions') && this.options.actions.hasOwnProperty('cancel')) {
            this.options.actions.cancel();
        }

        if (this.options.hasOwnProperty('actions') && this.options.actions.hasOwnProperty('always')) {
            this.options.actions.always();
        }

        this.close();
    },

    onActionClose: function (event) {
        this.close()
    },

    setTitle: function (title) {
        this.titleNode.textContent = title;
    },

    setContent: function (content) {
        this.contentNode.innerHTML = content;
    },

    clean: function () {
        this.titleNode.textContent = '';
        this.contentNode.textContent = '';
        this.formNode.textContent = '';

        var buttons = this.modalFooterNode.querySelectorAll('[data-action]');
        buttons.forEach((but) => {
            if (but.dataset.action != 'close') {
                but.style.display = 'none';
            }
        });
    },

    fill: function () {
        if (this.options.hasOwnProperty('title')) {
            this.setTitle(this.options.title);
        }

        if (this.options.hasOwnProperty('content')) {
            this.setContent(this.options.content);
        }

        if (this.options.hasOwnProperty('attributesField') && Object.keys(this.options.attributesField).length) {
            this.form = document.createElement("form");
            if (this.options.hasOwnProperty('attributesForm') && Object.keys(this.options.attributesForm).length) {
                var attributesForm = this.options.attributesForm;
                for (var formAttr in attributesForm) {
                    this.form.setAttribute(formAttr, attributesForm[formAttr]);
                }
            }

            var attributesField = this.options.attributesField;
            attributesField.class = 'form-control';

            this.input = document.createElement("input");

            if (this.options.attributesField.hasOwnProperty('class')) {
                attributesField.class += ' '+this.options.attributesField.class;
            }

            if (this.options.hasOwnProperty('value')) {
                this.input.value = this.options.value;
            }

            for (var inputAttr in attributesField) {
                this.input.setAttribute(inputAttr, attributesField[inputAttr]);
            }

            this.form.appendChild(this.input);
            this.formNode.appendChild(this.form);

            if (this.options.hasOwnProperty('actions') && Object.keys(this.options.actions).length) {
                var actions = this.options.actions;
                for (var action in actions) {
                    var but = this.modalFooterNode.querySelector('[data-action="'+action+'"]');
                    if(but) {
                        but.style.display = '';
                    }
                }
            }
        }
    },

    open: function () {
        this.promptModal.show();
    },

    close: function () {
        this.promptModal.hide();
    }
}
