<div class="template-attachment d-flex align-items-center gap-2">
    <a class="template-attachment-preview d-flex align-items-center justify-content-center flex-shrink-0 bg-body-tertiary border rounded text-body-secondary"
       href="{{ route('admin.templates.attachment', ['id' => $attachment->id]) }}"
       tabindex="-1" aria-hidden="true">
        @if ($attachment->isPreviewableImage())
            <img src="{{ route('admin.templates.attachment.preview', ['id' => $attachment->id]) }}"
                 alt="" width="80" height="60" loading="lazy" decoding="async">
        @else
            <i class="far fa-file" aria-hidden="true"></i>
        @endif
    </a>
    <div class="template-attachment-name flex-grow-1">
        <a class="d-block text-truncate" href="{{ route('admin.templates.attachment', ['id' => $attachment->id]) }}"
           title="{{ $attachment->name }}">
            <i class="fas fa-paperclip me-1" aria-hidden="true"></i>{{ $attachment->name }}
        </a>
    </div>
    @if ($removable ?? false)
        <button type="button" data-num="{{ $attachment->id }}" class="remove_attach btn btn-sm btn-outline-danger flex-shrink-0"
                title="{{ __('frontend.str.remove') }}" aria-label="{{ __('frontend.str.remove') }}: {{ $attachment->name }}">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    @endif
</div>
