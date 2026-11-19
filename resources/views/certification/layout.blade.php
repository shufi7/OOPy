@extends('layouts.app')

@section('body-class', 'oopy-certification-page')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/oopy/certification.css') }}?v={{ filemtime(public_path('css/oopy/certification.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/oopy-certification.js') }}?v={{ filemtime(public_path('js/oopy-certification.js')) }}" defer></script>
@endpush

@section('content')
<div class="certification container py-4 py-lg-5">
    <p class="certification-mode">Mode Simulasi — Tanpa Pembayaran Nyata</p>
    @if ($errors->any())
        <div class="certification-error" role="alert">
            <strong>Simulasi belum dapat diproses.</strong>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <a href="{{ route('certification.index') }}">Periksa persyaratan sertifikasi</a>
        </div>
    @endif
    @yield('certification-content')
</div>
@endsection
