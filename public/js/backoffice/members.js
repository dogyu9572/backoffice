document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined') {
        return;
    }

    const $ = jQuery;

    function isEditPage() {
        const action = $('#memberForm').attr('action') || '';
        return action.includes('/members/') && !action.endsWith('/members');
    }

    if (!isEditPage()) {
        $(document).on('change', 'input[name="join_type"]', function () {
            const joinType = $(this).val();
            if (joinType === 'email') {
                $('#passwordGroup, #passwordConfirmationGroup').show();
                $('#password, #password_confirmation').prop('required', true);
            } else {
                $('#passwordGroup, #passwordConfirmationGroup').hide();
                $('#password, #password_confirmation').prop('required', false).val('');
            }
        });
        $('input[name="join_type"]:checked').trigger('change');
    }

    $(document).on('click', '#btnSearchAddress', function () {
        if (typeof daum === 'undefined') return;
        new daum.Postcode({
            oncomplete: function (data) {
                $('#address_postcode').val(data.zonecode);
                $('#address_base').val(data.address);
                $('#address_detail').focus();
            },
        }).open();
    });

    $(document).on('input', '#school_name_direct', function () {
        if ($(this).val()) {
            $('#school_name').val($(this).val());
        }
    });

    function toggleSchoolPopup(isOpen) {
        const popup = document.getElementById('searchSchool');
        if (!popup) {
            return;
        }

        popup.classList.toggle('is-open', isOpen);
    }

    function renderSchoolList(schools, message) {
        const list = document.getElementById('popSchoolList');
        if (!list) {
            return;
        }

        if (!Array.isArray(schools) || schools.length === 0) {
            list.innerHTML = '<p class="no_data">' + (message || '검색 결과가 없습니다.') + '</p>';
            return;
        }

        list.innerHTML = schools
            .map(function (schoolName) {
                return (
                    '<button type="button" class="btn btn-light btn-sm btn-school-select" data-school-name="' +
                    schoolName +
                    '">' +
                    schoolName +
                    '</button>'
                );
            })
            .join('');
    }

    function searchSchool() {
        const keyword = ($('#popSchoolKeyword').val() || '').trim();
        if (!keyword) {
            renderSchoolList([], '학교명을 입력해주세요.');
            return;
        }

        fetch('/backoffice/members/search-school?keyword=' + encodeURIComponent(keyword), {
            method: 'GET',
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                renderSchoolList(result.schools || [], result.message || '');
            })
            .catch(function () {
                renderSchoolList([], '학교 검색 중 오류가 발생했습니다.');
            });
    }

    $(document).on('click', '#btnSearchSchool', function () {
        const currentSchoolName = ($('#school_name').val() || '').trim();
        if (currentSchoolName) {
            $('#popSchoolKeyword').val(currentSchoolName);
        }
        toggleSchoolPopup(true);
    });

    $(document).on('click', '[data-school-popup-close]', function () {
        toggleSchoolPopup(false);
    });

    $(document).on('click', '#popSchoolSearch', function () {
        searchSchool();
    });

    $(document).on('keydown', '#popSchoolKeyword', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchSchool();
        }
    });

    $(document).on('click', '.btn-school-select', function () {
        const schoolName = $(this).data('school-name');
        if (!schoolName) {
            return;
        }

        $('#school_name').val(schoolName);
        $('#school_name_direct').val('');
        toggleSchoolPopup(false);
    });

    $(document).on('click', '#popSchoolRegister', function () {
        const schoolName = ($('#popSchoolKeyword').val() || '').trim();
        if (!schoolName) {
            alert('등록할 학교명을 입력해주세요.');
            return;
        }

        $('#school_name').val(schoolName);
        $('#school_name_direct').val(schoolName);
        toggleSchoolPopup(false);
    });

    $(document).on('click', '#btnCheckEmail', function () {
        const email = $('#email').val();
        const excludeId = $(this).data('exclude-id');
        if (!email) return alert('이메일을 입력해주세요.');
        checkDuplicate('/backoffice/members/check-email', { email, exclude_id: excludeId }, '#emailCheckResult', '#email');
    });

    $(document).on('click', '#btnCheckPhone', function () {
        const phone = $('#phone_number').val();
        const excludeId = $(this).data('exclude-id');
        if (!phone) return alert('휴대폰번호를 입력해주세요.');
        checkDuplicate('/backoffice/members/check-phone', { phone, exclude_id: excludeId }, '#phoneCheckResult', '#phone_number');
    });

    $(document).on('change', '#select-all', function () {
        $('.bo-row-checkbox').prop('checked', $(this).prop('checked'));
    });

    $(document).on('change', '.bo-row-checkbox', function () {
        const total = $('.bo-row-checkbox').length;
        const checked = $('.bo-row-checkbox:checked').length;
        $('#select-all').prop('checked', total > 0 && total === checked);
    });

    $(document).on('click', '#btnDeleteMultiple', function () {
        const ids = $('.bo-row-checkbox:checked').map(function () { return $(this).val(); }).get();
        if (ids.length === 0) return alert('삭제할 회원을 선택해주세요.');
        if (!confirm(`선택한 ${ids.length}명의 회원을 삭제하시겠습니까?`)) return;
        postJson('/backoffice/members/delete-multiple', { ids }).then(function () { location.reload(); });
    });

    $(document).on('click', '.btn-delete-member', function () {
        const memberId = $(this).data('id');
        if (!confirm('정말로 삭제하시겠습니까?')) return;
        fetch(`/backoffice/members/${memberId}`, {
            method: 'DELETE',
            headers: jsonHeaders(),
            body: JSON.stringify({ _token: csrfToken() }),
        }).then(function () { location.reload(); });
    });

    $(document).on('click', '#btnExport', function () {
        const formData = new FormData(document.getElementById('searchForm'));
        const params = new URLSearchParams(formData);
        window.location.href = '/backoffice/members/export?' + params.toString();
    });
});

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function jsonHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        Accept: 'application/json',
    };
}

function postJson(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ ...data, _token: csrfToken() }),
    }).then((r) => r.json());
}

function checkDuplicate(url, payload, resultSelector, focusSelector) {
    postJson(url, payload)
        .then(function (result) {
            const target = document.querySelector(resultSelector);
            if (!target) return;
            target.textContent = result.message;
            target.className = 'check-result ' + (result.available ? 'success' : 'error');
            if (!result.available) document.querySelector(focusSelector)?.focus();
        })
        .catch(function () {
            alert('중복 확인 중 오류가 발생했습니다.');
        });
}
