/* 백오피스 CKEditor5 공통 초기화 */
(function () {
    const FALLBACK_UPLOAD_URL = '/backoffice/upload-image';
    const DEFAULT_UPLOAD_FIELD = 'image';
    const editorByTextarea = new WeakMap();
    const REMOVE_PLUGINS = [
        'AIAssistant', 'CaseChange', 'CKBox', 'CKFinder', 'Comments', 'DocumentOutline',
        'EasyImage', 'ExportPdf', 'ExportWord', 'FormatPainter', 'ImportWord', 'MathType',
        'Mention', 'Pagination', 'PasteFromOfficeEnhanced', 'PresenceList',
        'RealTimeCollaborativeComments', 'RealTimeCollaborativeRevisionHistory',
        'RealTimeCollaborativeTrackChanges', 'RevisionHistory', 'SlashCommand',
        'TableOfContents', 'Template', 'TrackChanges', 'TrackChangesData', 'WProofreader',
        'MultiLevelList'
    ];

    function createUploadAdapterPlugin(uploadUrl, fieldName) {
        class UploadAdapter {
            constructor(loader) {
                this.loader = loader;
                this.xhr = null;
            }

            upload() {
                return this.loader.file.then((file) => new Promise((resolve, reject) => {
                    this.xhr = new XMLHttpRequest();
                    this.xhr.open('POST', uploadUrl, true);
                    this.xhr.responseType = 'json';

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (token) {
                        this.xhr.setRequestHeader('X-CSRF-TOKEN', token);
                    }
                    this.xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    this.xhr.addEventListener('error', () => reject('이미지 업로드에 실패했습니다.'));
                    this.xhr.addEventListener('abort', () => reject());
                    this.xhr.addEventListener('load', () => {
                        const response = this.xhr.response;
                        if (!response || response.uploaded !== true || !response.url) {
                            reject(response?.error?.message || '이미지 업로드에 실패했습니다.');
                            return;
                        }
                        resolve({ default: response.url });
                    });

                    if (this.xhr.upload) {
                        this.xhr.upload.addEventListener('progress', (evt) => {
                            if (!evt.lengthComputable) return;
                            this.loader.uploadTotal = evt.total;
                            this.loader.uploaded = evt.loaded;
                        });
                    }

                    const data = new FormData();
                    data.append(fieldName, file);
                    this.xhr.send(data);
                }));
            }

            abort() {
                if (this.xhr) {
                    this.xhr.abort();
                }
            }
        }

        return function plugin(editor) {
            editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new UploadAdapter(loader);
        };
    }

    function buildConfig(element) {
        const globalDefault = typeof window.BACKOFFICE_CKEDITOR_UPLOAD_URL === 'string'
            ? window.BACKOFFICE_CKEDITOR_UPLOAD_URL
            : '';
        const uploadUrl = (element.dataset.uploadUrl || globalDefault || FALLBACK_UPLOAD_URL).trim();
        const fieldName = (element.dataset.uploadField || DEFAULT_UPLOAD_FIELD).trim() || DEFAULT_UPLOAD_FIELD;
        const enableSourceEditing = element.dataset.sourceEditing === 'true';

        const toolbarItems = [
            'heading', '|',
            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
            'bold', 'italic', 'underline', 'strikethrough', 'removeFormat', '|',
            'alignment', '|',
            'bulletedList', 'numberedList', 'outdent', 'indent', '|',
            'link', 'uploadImage', 'insertTable', 'blockQuote', 'codeBlock', 'horizontalLine', '|',
            'undo', 'redo'
        ];
        if (enableSourceEditing) {
            toolbarItems.unshift('sourceEditing', '|');
        }

        return {
            extraPlugins: [createUploadAdapterPlugin(uploadUrl, fieldName)],
            toolbar: { items: toolbarItems, shouldNotGroupWhenFull: true },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
            },
            removePlugins: REMOVE_PLUGINS
        };
    }

    function initOne(element) {
        if (!element || element.tagName !== 'TEXTAREA') return;
        if (typeof CKEDITOR === 'undefined' || !CKEDITOR.ClassicEditor) return;
        if (element.dataset.ckeditorInitialized === 'true') return;

        CKEDITOR.ClassicEditor.create(element, buildConfig(element))
            .then((editor) => {
                element.dataset.ckeditorInitialized = 'true';
                editorByTextarea.set(element, editor);
                element.removeAttribute('required');
                editor.model.document.on('change:data', () => {
                    if (typeof editor.updateSourceElement === 'function') {
                        editor.updateSourceElement();
                    }
                });
                if (typeof editor.updateSourceElement === 'function') {
                    editor.updateSourceElement();
                }
            })
            .catch((error) => {
                console.error('CKEditor5 initialization failed:', error);
            });
    }

    function initAll(root) {
        const scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('textarea[data-backoffice-ckeditor]').forEach(initOne);
    }

    function syncFields(root) {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
        scope.querySelectorAll('textarea[data-backoffice-ckeditor]').forEach((textarea) => {
            const editor = editorByTextarea.get(textarea);
            if (editor && typeof editor.updateSourceElement === 'function') {
                editor.updateSourceElement();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll(document));
    } else {
        initAll(document);
    }

    window.initBackofficeCKEditors = initAll;
    window.syncBackofficeCKEditorFields = syncFields;
})();
