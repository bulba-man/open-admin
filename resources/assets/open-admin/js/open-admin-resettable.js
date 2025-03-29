class ResettableField {
    constructor(selfEl) {
        this.selfEl = selfEl;

        this.elemClass = this.selfEl.dataset.elemClass;
        this.elemClassSelector = '.' + this.elemClass.replace(/ /g, ".");
        this.elem = document.querySelector(this.elemClassSelector);
        this.choicesVarName = 'choices_' + this.elemClass.replace(/[ -]/g, "_");
        this.listBoxVarName = 'dualListbox_' + this.elemClass.replace(/[ -]/g, "_");

        this.flags = {};

        this.defaultValue = this.selfEl.dataset.defaultValue;
        if (this.defaultValue) {
            try {this.defaultValue = JSON.parse(this.defaultValue);} catch (e) {}
        }

        this.currentValue = this.selfEl.dataset.currentValue;
        if (this.currentValue) {
            try {this.currentValue = JSON.parse(this.currentValue);} catch (e) {}
        }

        var self = this;
        this.selfEl.addEventListener('change', (e)=>{self.onChange(e)});

        this.availableFields = [
            "Choices",
            "ListBox",
            "Select",
            "Checkbox",
            "Radio",
            "Switch",
            "Textarea",
        ];

        if (this.selfEl.checked) {
            this.disableField();
        }
    }

    onChange(event) {
        this.event = event;

        if (event.target.checked) {
            this.resetField();
        } else {
            this.setField();
        }
    }

    runAction(action) {
        for (var i=0; i < this.availableFields.length; i++) {
            var field = this.availableFields[i];
            var fieldName = field.charAt(0).toUpperCase() + field.slice(1);

            if (this['is'+fieldName+'Field']()) {
                return this[action+fieldName]();
            }
        }

        if (this.isInputField()) {
            return this[action+'Input']();
        }
    }

    resetField() {
        this.runAction('reset');
    }

    setField() {
        this.runAction('set');
    }

    disableField() {
        this.runAction('disable');

        // if (this.isChoicesField()) {
        //     return this.disableChoices();
        // } else if (this.isListBoxField()) {
        //     return this.disableListBox();
        // } else if (this.isSelectField()) {
        //     return this.disableSelect();
        // } else if (this.isCheckboxField() || this.isRadioField()) {
        //     return this.disableCheckbox();
        // } else if (this.isInputField()) {
        //     return this.disableInput();
        // }
    }

    enableField() {
        this.runAction('enable');

        // if (this.isChoicesField()) {
        //     return this.enableChoices();
        // } else if (this.isListBoxField()) {
        //     return this.enableListBox();
        // } else if (this.isSelectField()) {
        //     return this.enableSelect();
        // } else if (this.isCheckboxField() || this.isRadioField()) {
        //     return this.enableCheckbox();
        // } else if (this.isInputField()) {
        //     return this.enableInput();
        // }
    }

    isChoicesField() {
        if (!this.flags.hasOwnProperty('isChoicesField')) {
            this.flags['isChoicesField'] = (window.hasOwnProperty('choices_vars') && window.choices_vars.hasOwnProperty(this.choicesVarName));

            if (this.flags['isChoicesField']) {
                this.choices = window.choices_vars[this.choicesVarName];
            }
        }

        return this.flags.isChoicesField;
    }

    isListBoxField() {
        if (!this.flags.hasOwnProperty('isListBoxField')) {
            this.flags['isListBoxField'] = (window.hasOwnProperty('listbox_vars') && window.listbox_vars.hasOwnProperty(this.listBoxVarName));
            if (this.flags['isListBoxField']) {
                this.listBox = window.listbox_vars[this.listBoxVarName];
            }
        }

        return this.flags.isListBoxField;
    }

    isSelectField() {
        if (!this.flags.hasOwnProperty('isSelectField')) {
            this.flags['isSelectField'] = (this.elem && this.elem.nodeName === 'SELECT');
        }

        return this.flags.isSelectField;
    }

    isInputField() {
        if (!this.flags.hasOwnProperty('isInputField')) {
            this.flags['isInputField'] = (this.elem && this.elem.nodeName === 'INPUT');
        }

        return this.flags.isInputField;
    }

    isCheckboxField() {
        if (!this.flags.hasOwnProperty('isCheckboxField')) {
            var isCheckbox = (this.isInputField() && this.elem.type.toLowerCase() === 'checkbox');
            if (isCheckbox) {
                var wrap = this.elem.closest('.form-check');
                if (wrap && wrap.classList.contains('form-switch')) {
                    isCheckbox = false;
                }
            }
            this.flags['isCheckboxField'] = isCheckbox;
        }

        return this.flags.isCheckboxField;
    }

    isSwitchField() {
        if (!this.flags.hasOwnProperty('isSwitchField')) {
            var isSwitch = (this.isInputField() && this.elem.type.toLowerCase() === 'checkbox');
            if (isSwitch) {
                var wrap = this.elem.closest('.form-check');
                if (wrap && !wrap.classList.contains('form-switch')) {
                    isSwitch = false;
                }
            }
            this.flags['isSwitchField'] = isSwitch;
        }

        return this.flags.isSwitchField;
    }

    isRadioField() {
        if (!this.flags.hasOwnProperty('isRadioField')) {
            this.flags['isRadioField'] = (this.isInputField() && this.elem.type.toLowerCase() === 'radio');
        }

        return this.flags.isRadioField;
    }

    isTextareaField() {
        if (!this.flags.hasOwnProperty('isTextareaField')) {
            this.flags['isTextareaField'] = (this.elem && this.elem.nodeName === 'TEXTAREA');
        }

        return this.flags.isTextareaField;
    }

    resetChoices() {
        this.choices.clearInput();

        this.currentValue = this.choices.getValue(true);

        this.clearChoices();

        if (!Array.isArray(this.defaultValue)) {
            this.defaultValue = [this.defaultValue];
        }

        this.defaultValue.forEach((val) => {
            this.choices.setChoiceByValue(val);
        });

        this.disableChoices();
    }

    setChoices() {
        this.enableChoices();
        this.clearChoices();

        if (!Array.isArray(this.currentValue)) {
            this.currentValue = [this.currentValue];
        }

        this.currentValue.forEach((val) => {
            this.choices.setChoiceByValue(val);
        });
    }

    clearChoices() {
        this.choices.removeActiveItems();
    }

    disableChoices() {
        this.choices.disable();
    }

    enableChoices() {
        this.choices.enable();
    }

    resetListBox() {
        this.currentValue = Array.from(this.listBox.selected).map(e => e.dataset.id);

        this.clearListBox();

        if (!Array.isArray(this.defaultValue)) {
            this.defaultValue = [this.defaultValue];
        }

        var available = Array.from(this.listBox.available);
        this.defaultValue.forEach((val) => {
            var item = available.find(c => c.dataset.id == val);
            if (item) {
                this.listBox.addSelected(item);
            }
        });

        this.listBox.redraw();
        this.disableListBox();
    }

    setListBox() {
        this.enableListBox();
        this.clearListBox();

        if (!Array.isArray(this.currentValue)) {
            this.currentValue = [this.currentValue];
        }

        var available = Array.from(this.listBox.available);
        this.currentValue.forEach((val) => {
            var item = available.find(c => c.dataset.id == val);
            if (item) {
                this.listBox.addSelected(item);
            }
        });
    }

    clearListBox() {
        this.listBox._actionAllDeselected(this.event);
    }

    disableListBox() {
        this.listBox.disable();
    }

    enableListBox() {
        this.listBox.enable();
    }

    resetSelect() {
        this.currentValue = Array.from(this.elem.selectedOptions).map(({ value }) => value);
        this.clearSelect();

        if (!Array.isArray(this.defaultValue)) {
            this.defaultValue = [this.defaultValue];
        }

        var options = Array.from(this.elem.querySelectorAll('option'));
        this.defaultValue.forEach((val) => {
            var option = options.find(c => c.value === val);
            if (option) {
                option.selected = true;
            }
        });

        this.disableSelect();
    }

    setSelect() {
        this.enableSelect();
        this.clearSelect();

        if (!Array.isArray(this.currentValue)) {
            this.currentValue = [this.currentValue];
        }

        var options = Array.from(this.elem.querySelectorAll('option'));
        this.currentValue.forEach((val) => {
            var option = options.find(c => c.value === val);
            if (option) {
                option.selected = true;
            }
        });
    }

    clearSelect() {
        this.elem.value = '';
    }

    disableSelect() {
        this.elem.disabled = true;
    }

    enableSelect() {
        this.elem.disabled = false;
    }

    resetCheckbox() {
        this.currentValue = [...document.querySelectorAll(this.elemClassSelector+':checked')].map(e => e.value);
        this.clearCheckbox();

        if (!Array.isArray(this.defaultValue)) {
            this.defaultValue = [this.defaultValue];
        }

        this.defaultValue.forEach((val) => {
            var checkbox = this.getAllCheckboxes().find(c => c.value === val);
            if (checkbox) {
                checkbox.checked = true;
            }
        });

        this.disableCheckbox();
    }

    setCheckbox() {
        this.enableCheckbox();
        this.clearCheckbox();

        if (!Array.isArray(this.currentValue)) {
            this.currentValue = [this.currentValue];
        }

        this.currentValue.forEach((val) => {
            var checkbox = this.getAllCheckboxes().find(c => c.value === val);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }

    getAllCheckboxes() {
        if (!this.checkboxes) {
            this.checkboxes = Array.from(document.querySelectorAll(this.elemClassSelector));
        }

        return this.checkboxes;
    }

    clearCheckbox() {
        this.getAllCheckboxes().forEach(el => el.checked = false);
    }

    disableCheckbox() {
        this.getAllCheckboxes().forEach(el => el.disabled = true);
    }

    enableCheckbox() {
        this.getAllCheckboxes().forEach(el => el.disabled = false);
    }


    resetRadio() {
        this.resetCheckbox();
    }

    setRadio() {
        this.setCheckbox();
    }


    clearRadio() {
        this.clearCheckbox();
    }

    disableRadio() {
        this.disableCheckbox();
    }

    enableRadio() {
        this.enableCheckbox();
    }

    resetInput() {
        this.currentValue = this.elem.value;
        this.clearInput();
        this.elem.value = this.defaultValue;
        this.disableInput();
    }

    setInput() {
        this.enableInput();
        this.clearInput();

        this.elem.value = (this.currentValue) ? this.currentValue : this.defaultValue;
    }

    clearInput() {
        this.elem.value = '';
    }

    disableInput() {
        this.elem.disabled = true;
    }

    enableInput() {
        this.elem.disabled = false;
    }

    resetSwitch() {
        this.currentValue = this.elem.checked;
        this.clearSwitch();
        this.disableSwitch();
    }

    setSwitch() {
        this.enableSwitch();
        this.clearSwitch();

        this.elem.checked = (this.currentValue) ? this.currentValue : this.defaultValue;
    }

    clearSwitch() {
        this.elem.checked = false;
    }

    disableSwitch() {
        this.elem.disabled = true;
    }

    enableSwitch() {
        this.elem.disabled = false;
    }

    resetTextarea() {
        this.resetInput();
    }

    setTextarea() {
        this.setInput();
    }

    clearTextarea() {
        this.clearInput();
    }

    disableTextarea() {
        this.disableInput();
    }

    enableTextarea() {
        this.enableInput();
    }
}
