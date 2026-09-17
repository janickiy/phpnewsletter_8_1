@extends('admin.app')

@section('title', $title)

@section('css')
    <style>
        .faq-accordion .accordion-button,
        .faq-accordion .accordion-body {
            overflow-wrap: anywhere;
        }

        .faq-accordion .accordion-body {
            line-height: 1.7;
        }

        .faq-accordion .accordion-button {
            gap: 1rem;
        }

        .faq-accordion .accordion-button span {
            min-width: 0;
        }
    </style>
@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-circle-question me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        @php
                            // Keep the translated content, including locales missing an opening <strong> tag.
                            preg_match_all('~(?:<strong>)?(.*?)</strong>\s*<p>(.*?)</p>~s', __('faq.str'), $faqItems, PREG_SET_ORDER);
                        @endphp

                        <div class="accordion accordion-flush faq-accordion" id="faq-accordion">
                            @foreach($faqItems as $item)
                                <div class="accordion-item">
                                    <h4 class="accordion-header" id="faq-heading-{{ $loop->index }}">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#faq-answer-{{ $loop->index }}"
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                aria-controls="faq-answer-{{ $loop->index }}">
                                            <span>{{ trim(html_entity_decode(strip_tags($item[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) }}</span>
                                        </button>
                                    </h4>
                                    <div id="faq-answer-{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                         aria-labelledby="faq-heading-{{ $loop->index }}" data-bs-parent="#faq-accordion">
                                        <div class="accordion-body">
                                            {{ trim(html_entity_decode(strip_tags($item[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->

@endsection
