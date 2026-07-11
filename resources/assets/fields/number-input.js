class NumberInput {

    constructor(element) {

        this.input = element;
        this.input.ref = this;
        var plus = element.parentNode.querySelector(".plus");
        plus.ref = this;
        plus.addEventListener("click",function(){
            this.ref.plus();
        });
        var minus = element.parentNode.querySelector(".minus");
        minus.ref = this;
        minus.addEventListener("click",function(){
            this.ref.minus();
        });

        var min = element.getAttribute('min');
        var max = element.getAttribute('max');
        this.min = min === null || min === '' ? null : Number(min);
        this.max = max === null || max === '' ? null : Number(max);
        this.step = Number(element.getAttribute('step'));
        if (this.step == 0){
            this.step = 1;
        }

        element.addEventListener("change",function(){
            this.ref.setText(this.value);
        })
    }
    plus = function(){
        this.setText(Number(this.input.value) + this.step, true);
    }

    minus = function(){
        this.setText(Number(this.input.value) - this.step, true);
    }

    setText = function(n, fireEvents) {
        var previousValue = this.input.value;

        n = Number(n);
        n = isNaN(n) ? 0 : n;
        if (this.min !== null && n < this.min) {
            n = this.min;
        } else if (this.max !== null && n > this.max) {
            n = this.max;
        }
        this.input.value = n;

        if (fireEvents && this.input.value !== previousValue) {
            this.input.dispatchEvent(new Event('input', {bubbles: true}));
            this.input.dispatchEvent(new Event('change', {bubbles: true}));
        }
    }
}
