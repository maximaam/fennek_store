import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['track'];

    index = 0;

    connect() {
        this.update();
    }

    next() {
        this.index++;
        if (this.index >= this.trackTarget.children.length) {
            this.index = 0;
        }
        this.update();
    }

    prev() {
        this.index--;
        if (this.index < 0) {
            this.index = this.trackTarget.children.length - 1;
        }
        this.update();
    }

    update() {
        if (!this.hasTrackTarget) return;

        const width = this.element.clientWidth;
        this.trackTarget.style.transform = `translateX(-${this.index * width}px)`;
    }
}
