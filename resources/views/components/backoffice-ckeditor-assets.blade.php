@props([
    'ckeditorVersion' => '41.4.2',
    'uploadUrl' => null,
])

@php
    $resolvedUploadUrl = $uploadUrl ?? url('/backoffice/upload-image');
@endphp
<script>
    window.BACKOFFICE_CKEDITOR_UPLOAD_URL = @json($resolvedUploadUrl);
</script>
<script src="https://cdn.ckeditor.com/ckeditor5/{{ $ckeditorVersion }}/super-build/ckeditor.js"></script>
<script src="{{ asset('js/backoffice/backoffice-ckeditor.js') }}"></script>
