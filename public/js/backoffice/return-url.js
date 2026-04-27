document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.startsWith('/backoffice/')) {
        return;
    }

    appendReturnUrlToEditLinks();
    applyReturnUrlOnEditPage();
});

function appendReturnUrlToEditLinks() {
    const currentUrl = window.location.href;

    document.querySelectorAll('a[href]').forEach(function (anchor) {
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        let url;
        try {
            url = new URL(href, window.location.origin);
        } catch (error) {
            return;
        }

        if (url.origin !== window.location.origin) {
            return;
        }

        const isBackofficeEdit = /^\/backoffice\/.+\/\d+\/edit$/.test(url.pathname);
        if (!isBackofficeEdit) {
            return;
        }

        if (!url.searchParams.has('return_url')) {
            url.searchParams.set('return_url', currentUrl);
            anchor.setAttribute('href', url.toString());
        }
    });
}

function applyReturnUrlOnEditPage() {
    const query = new URLSearchParams(window.location.search);
    const returnUrl = query.get('return_url');

    if (!returnUrl || !isValidBackofficeReturnUrl(returnUrl)) {
        return;
    }

    // 수정 폼 submit 시 return_url 전달
    const forms = document.querySelectorAll('form[method="POST"], form[method="post"]');
    forms.forEach(function (form) {
        const methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput) {
            return;
        }

        const method = (methodInput.value || '').toUpperCase();
        if (method !== 'PUT' && method !== 'PATCH') {
            return;
        }

        let returnInput = form.querySelector('input[name="return_url"]');
        if (!returnInput) {
            returnInput = document.createElement('input');
            returnInput.type = 'hidden';
            returnInput.name = 'return_url';
            form.appendChild(returnInput);
        }

        returnInput.value = returnUrl;
    });

    // 목록/취소 버튼 return_url 적용
    document.querySelectorAll('a[href]').forEach(function (anchor) {
        const text = (anchor.textContent || '').trim();
        if (text !== '목록으로' && text !== '취소') {
            return;
        }

        anchor.setAttribute('href', returnUrl);
    });
}

function isValidBackofficeReturnUrl(returnUrl) {
    let url;
    try {
        url = new URL(returnUrl);
    } catch (error) {
        return false;
    }

    return url.origin === window.location.origin && url.pathname.startsWith('/backoffice/');
}
