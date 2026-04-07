// 팝업 관리 JavaScript

function loadSortableLibrary() {
    return new Promise((resolve, reject) => {
        if (typeof Sortable !== 'undefined') {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Sortable.js 로드 실패'));
        document.head.appendChild(script);
    });
}

function initPopupSortable() {
    const popupList = document.getElementById('popupList');
    if (!popupList) return;

    new Sortable(popupList, {
        handle: '.popup-drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd() { savePopupOrder(); }
    });
}

function savePopupOrder() {
    const popupOrder = [];
    const totalItems = document.querySelectorAll('#popupList > .popup-item').length;
    document.querySelectorAll('#popupList > .popup-item').forEach((item, index) => {
        const popupId = item.dataset.id;
        if (popupId) popupOrder.push({ id: parseInt(popupId, 10), order: totalItems - index });
    });

    fetch('/backoffice/popups/update-order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ popupOrder })
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) showSuccessMessage('팝업 순서가 저장되었습니다.');
        })
        .catch(() => showErrorMessage('순서 저장 중 오류가 발생했습니다.'));
}

function togglePeriodFields() {
    const periodFields = document.getElementById('period_fields');
    const radioButtons = document.querySelectorAll('input[name="use_period"]');
    if (!periodFields || radioButtons.length === 0) return;

    const checkedRadio = document.querySelector('input[name="use_period"]:checked');
    periodFields.style.display = checkedRadio && checkedRadio.value === '1' ? 'block' : 'none';

    radioButtons.forEach((radio) => {
        radio.addEventListener('change', function () {
            periodFields.style.display = this.value === '1' ? 'block' : 'none';
        });
    });
}

function togglePopupTypeSections() {
    const popupTypeRadios = document.querySelectorAll('input[name="popup_type"]');
    const imageSection = document.getElementById('popup_image_section');
    const htmlSection = document.getElementById('popup_html_section');
    if (popupTypeRadios.length === 0) return;

    popupTypeRadios.forEach((radio) => {
        radio.addEventListener('change', function () {
            if (this.value === 'image') {
                if (imageSection) imageSection.style.display = 'block';
                if (htmlSection) htmlSection.style.display = 'none';
            } else if (this.value === 'html') {
                if (imageSection) imageSection.style.display = 'none';
                if (htmlSection) htmlSection.style.display = 'block';
            }
        });
    });
}

function initImagePreview() {
    const fileInput = document.getElementById('popup_image');
    const preview = document.getElementById('popupImagePreview');
    if (!fileInput || !preview) return;

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (evt) => {
            preview.innerHTML = `
                <img src="${evt.target.result}" alt="미리보기" class="thumbnail-preview">
                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeImagePreview()">
                    <i class="fas fa-trash"></i> 이미지 제거
                </button>
            `;
        };
        reader.readAsDataURL(file);
    });
}

function removeImagePreview() {
    const fileInput = document.getElementById('popup_image');
    const preview = document.getElementById('popupImagePreview');
    const removeInput = document.getElementById('remove_popup_image');
    if (fileInput) fileInput.value = '';
    if (preview) preview.innerHTML = '';
    if (removeInput) removeInput.value = '1';
}

function initDragAndDrop() {
    document.querySelectorAll('.board-file-input').forEach((input) => {
        const wrapper = input.closest('.board-file-input-wrapper');
        if (!wrapper) return;
        wrapper.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        wrapper.addEventListener('dragleave', function (e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        wrapper.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

function initDateInputs() {
    document.querySelectorAll('input[type="date"]').forEach((input) => {
        input.addEventListener('click', function () { this.showPicker && this.showPicker(); });
        input.addEventListener('focus', function () { this.showPicker && this.showPicker(); });
    });
}

function showSuccessMessage(message) {
    const existingAlert = document.querySelector('.alert-success');
    if (existingAlert) existingAlert.remove();
    const alert = document.createElement('div');
    alert.className = 'alert alert-success board-hidden-alert';
    alert.textContent = message;
    const container = document.querySelector('.board-container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        setTimeout(() => alert.remove(), 3000);
    }
}

function showErrorMessage(message) {
    const existingAlert = document.querySelector('.alert-danger');
    if (existingAlert) existingAlert.remove();
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger board-hidden-alert';
    alert.textContent = message;
    const container = document.querySelector('.board-container');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        setTimeout(() => alert.remove(), 5000);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    loadSortableLibrary().then(initPopupSortable);
    togglePeriodFields();
    togglePopupTypeSections();
    initImagePreview();
    initDragAndDrop();
    initDateInputs();

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', function () {
            if (typeof window.syncBackofficeCKEditorFields === 'function') {
                window.syncBackofficeCKEditorFields(form);
            }
        });
    });
});
