/**
 * 게시글 작성/수정 페이지 JavaScript (썸네일·첨부파일 등)
 * 본문/커스텀 에디터 필드는 CKEditor(backoffice-ckeditor.js) 사용
 */

window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

function syncEditorContent(form) {
    if (typeof window.syncBackofficeCKEditorFields === 'function') {
        window.syncBackofficeCKEditorFields(form || document);
    }
}

class ThumbnailManager {
    constructor() {
        this.thumbnailInput = document.getElementById('thumbnail');
        this.thumbnailPreview = document.getElementById('thumbnailPreview');
        this.thumbnailUpload = this.thumbnailInput?.closest('.board-file-upload');
        this.maxFileSize = 5 * 1024 * 1024;
        this.allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (this.thumbnailInput && this.thumbnailUpload) {
            this.init();
        }
    }

    init() {
        this.thumbnailInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) this.handleThumbnail(file);
        });

        this.thumbnailUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.thumbnailUpload.classList.add('board-file-drag-over');
        });

        this.thumbnailUpload.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.thumbnailUpload.classList.remove('board-file-drag-over');
        });

        this.thumbnailUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.thumbnailUpload.classList.remove('board-file-drag-over');
            const file = e.dataTransfer.files[0];
            if (file) this.handleThumbnail(file);
        });
    }

    handleThumbnail(file) {
        if (!this.allowedTypes.includes(file.type)) {
            alert('이미지 파일만 업로드 가능합니다. (JPG, PNG, GIF)');
            this.thumbnailInput.value = '';
            return;
        }

        if (file.size > this.maxFileSize) {
            alert('썸네일 이미지는 5MB 이하만 가능합니다.');
            this.thumbnailInput.value = '';
            return;
        }

        const dt = new DataTransfer();
        dt.items.add(file);
        this.thumbnailInput.files = dt.files;
        this.updateThumbnailPreview(file);
    }

    updateThumbnailPreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            this.thumbnailPreview.innerHTML = `
                <div class="board-file-item">
                    <div class="board-file-info">
                        <img src="${e.target.result}" alt="썸네일 미리보기" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px;">
                        <span class="board-file-name">${file.name}</span>
                        <span class="board-file-size">(${(file.size / 1024 / 1024).toFixed(2)}MB)</span>
                    </div>
                    <button type="button" class="board-file-remove" onclick="thumbnailManager.removeThumbnail()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }

    removeThumbnail() {
        if (this.thumbnailInput) this.thumbnailInput.value = '';
        const existingThumbnailInput = document.querySelector('input[name="existing_thumbnail"]');
        if (existingThumbnailInput) existingThumbnailInput.remove();
        if (this.thumbnailPreview) this.thumbnailPreview.innerHTML = '';
    }
}

class FileManager {
    constructor() {
        this.fileInput = document.getElementById('attachments');
        this.filePreview = document.getElementById('filePreview');
        this.fileUpload = this.fileInput?.closest('.board-file-upload');
        this.maxFiles = 5;
        this.maxFileSize = 10 * 1024 * 1024;

        if (this.fileInput && this.fileUpload) this.init();
    }

    init() {
        this.fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            if (files.length > 0) this.replaceAllFiles(files);
        });

        this.fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.fileUpload.classList.add('board-file-drag-over');
        });

        this.fileUpload.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.fileUpload.classList.remove('board-file-drag-over');
        });

        this.fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.fileUpload.classList.remove('board-file-drag-over');
            this.handleFiles(Array.from(e.dataTransfer.files));
        });
    }

    replaceAllFiles(files) {
        if (files.length > this.maxFiles) {
            alert(`최대 ${this.maxFiles}개까지만 선택할 수 있습니다.`);
            this.fileInput.value = '';
            return;
        }

        if (files.some((file) => file.size > this.maxFileSize)) {
            alert('10MB 이상인 파일이 있습니다. 10MB 이하의 파일만 선택해주세요.');
            this.fileInput.value = '';
            return;
        }

        const dt = new DataTransfer();
        files.forEach((file) => dt.items.add(file));
        this.fileInput.files = dt.files;
        this.updateFilePreview();
    }

    handleFiles(files) {
        if (files.length > this.maxFiles) {
            alert(`최대 ${this.maxFiles}개까지만 선택할 수 있습니다.`);
            return;
        }

        if (files.some((file) => file.size > this.maxFileSize)) {
            alert('10MB 이상인 파일이 있습니다. 10MB 이하의 파일만 선택해주세요.');
            return;
        }

        const existingFiles = Array.from(this.fileInput.files);
        const newFiles = files.filter((newFile) =>
            !existingFiles.some(
                (existingFile) => existingFile.name === newFile.name && existingFile.size === newFile.size
            )
        );

        if (newFiles.length === 0) {
            alert('이미 추가된 파일입니다.');
            return;
        }

        const allFiles = [...existingFiles, ...newFiles];
        if (allFiles.length > this.maxFiles) {
            alert(`최대 ${this.maxFiles}개까지만 선택할 수 있습니다.`);
            return;
        }

        const dt = new DataTransfer();
        allFiles.forEach((file) => dt.items.add(file));
        this.fileInput.files = dt.files;
        this.updateFilePreview();
    }

    updateFilePreview() {
        const files = Array.from(this.fileInput.files);
        this.filePreview.innerHTML = '';

        files.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'board-file-item';
            fileItem.innerHTML = `
                <div class="board-file-info">
                    <i class="fas fa-file"></i>
                    <span class="board-file-name">${file.name}</span>
                    <span class="board-file-size">(${(file.size / 1024 / 1024).toFixed(2)}MB)</span>
                </div>
                <button type="button" class="board-file-remove" onclick="fileManager.removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            this.filePreview.appendChild(fileItem);
        });
    }

    removeFile(index) {
        const dt = new DataTransfer();
        const files = Array.from(this.fileInput.files);
        files.splice(index, 1);
        files.forEach((file) => dt.items.add(file));
        this.fileInput.files = dt.files;
        this.updateFilePreview();
    }

    removeExistingFile(index) {
        const existingFiles = document.querySelectorAll('.board-attachment-list .existing-file');
        const targetFile = existingFiles[index];
        const hiddenInput = targetFile?.querySelector('input[name="existing_attachments[]"]');
        if (hiddenInput) {
            hiddenInput.remove();
            targetFile.remove();
        }
    }
}

function initBoardPostFormPage() {
    window.thumbnailManager = new ThumbnailManager();
    window.fileManager = new FileManager();

    window.removeExistingFile = function (index) {
        window.fileManager.removeExistingFile(index);
    };

    window.removeThumbnail = function () {
        if (window.thumbnailManager) window.thumbnailManager.removeThumbnail();
    };

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => syncEditorContent(form));
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBoardPostFormPage);
} else {
    initBoardPostFormPage();
}
