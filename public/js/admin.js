window.onload = () => {
    const addButton = document.querySelector('.field-collection-add-button');
    if (!addButton) return;

    addButton.addEventListener('click', () => {
        // Wait until EasyAdmin inserts the new item into the DOM
        setTimeout(() => {
            const items = document.querySelectorAll('.field-collection-item');
            const lastItem = items[items.length - 1];
            if (!lastItem) return;

            const input = lastItem.querySelector('input[type="file"]');
            if (!input) return;

            input.addEventListener('change', () => {
                const file = input.files[0];
                if (!file) return;

                let preview = lastItem.querySelector('.image-preview');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'image-preview';
                    preview.style.maxWidth = '150px';
                    preview.style.marginTop = '10px';

                    input.closest('.ea-vich-image')?.appendChild(preview);
                }

                URL.revokeObjectURL(preview.src); // prevents memory leaks when changing files repeatedly
                preview.src = URL.createObjectURL(file);
            });
        }, 50); // allow DOM update
    });
};
