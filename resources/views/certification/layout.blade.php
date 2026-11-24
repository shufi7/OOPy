@extends('layouts.app')

@section('body-class', 'oopy-certification-page')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/oopy/certification.css') }}?v={{ filemtime(public_path('css/oopy/certification.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/oopy-certification.js') }}?v={{ filemtime(public_path('js/oopy-certification.js')) }}" defer></script>
@endpush

@section('content')
<div class="certification container pt-3 pt-lg-4 pb-4">
    @if ($errors->any() || $errors->certificateName->any() || $errors->certificatePassword->any())
        <div class="certification-error" role="alert">
            <ul class="mb-0">@foreach (array_merge($errors->all(), $errors->certificateName->all(), $errors->certificatePassword->all()) as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('certification-content')
</div>
@endsection
