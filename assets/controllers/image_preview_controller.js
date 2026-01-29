import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['collection'];

    connect() {
        console.log('image-preview controller connected');

        // Listen for changes on existing inputs
        this.element.querySelectorAll('input[type="file"]').forEach(input => {
            this.addPreviewListener(input);
        });

        // Observe new inputs added dynamically
        const observer = new MutationObserver(mutations => {
            for (const mutation of mutations) {
                mutation.addedNodes.forEach(node => {
                    if (!(node instanceof HTMLElement)) return;

                    node.querySelectorAll('input[type="file"]').forEach(input => {
                        this.addPreviewListener(input);
                    });
                });
            }
        });

        observer.observe(this.element, { childList: true, subtree: true });
    }

    addPreviewListener(input) {
        if (input.dataset.previewAttached) return; // avoid double attachment
        input.dataset.previewAttached = true;

        input.addEventListener('change', (event) => {
            console.log('file changed!', event.target);
            this.showPreview(event.target);
        });
    }

    showPreview(input) {
        const file = input.files?.[0];
        if (!file) return;

        const wrapper = input.closest('.ea-vich-image');
        if (!wrapper) return;

        wrapper.querySelectorAll('.image-preview').forEach(el => el.remove());

        const img = document.createElement('img');
        img.classList.add('image-preview');
        img.style.maxWidth = '150px';
        img.style.marginTop = '10px';

        const reader = new FileReader();
        reader.onload = e => img.src = e.target.result;
        reader.readAsDataURL(file);

        wrapper.appendChild(img);
    }
}
