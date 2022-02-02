export class Progress {
    constructor($element) {
        this.$element = $element;
        this.hide();
    }
    show() {
        this.reset();
        this.$element.parent().show();
    }
    hide() {
        this.reset();
        this.$element.parent().hide();
    }
    reset() {
        this.$element.css("width", "0%").html("");
    }
    setProgression(num, total) {
        let percent = parseInt((num / total) * 100, 10);
        this.$element.css("width", `${percent}%`).html(`${percent}%`);
    }
}
