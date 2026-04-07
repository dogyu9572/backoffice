(function () {
    'use strict';

    function token() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function headers() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token(),
            Accept: 'application/json',
        };
    }

    document.addEventListener('change', function (e) {
        if (e.target.id === 'select-all') {
            document.querySelectorAll('.bo-row-checkbox').forEach(function (cb) {
                cb.checked = e.target.checked;
            });
        }
    });

    document.addEventListener('click', function (e) {
        const bulk = e.target.closest('#btnForceDeleteMultiple');
        if (bulk) {
            const ids = Array.from(document.querySelectorAll('.bo-row-checkbox:checked')).map((el) => el.value);
            if (ids.length === 0) return alert('삭제할 회원을 선택해주세요.');
            if (!confirm('선택한 ' + ids.length + '명의 회원을 영구 삭제하시겠습니까?\n이 작업은 되돌릴 수 없습니다.')) return;
            fetch('/backoffice/withdrawn/force-delete-multiple', {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({ ids, _token: token() }),
            }).then(() => location.reload());
        }

        const single = e.target.closest('.btn-force-delete-member');
        if (single) {
            const id = single.getAttribute('data-id');
            if (!confirm('정말로 이 회원을 영구 삭제하시겠습니까?\n이 작업은 되돌릴 수 없습니다.')) return;
            fetch('/backoffice/withdrawn/' + id + '/force-delete', {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({ _token: token() }),
            }).then(() => location.reload());
        }
    });
})();
