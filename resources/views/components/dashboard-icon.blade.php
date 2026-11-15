@props(['name'])
<svg {{ $attributes->class('oopy-dashboard-icon') }} width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('book')<path d="M12 5v15M12 5C8 2 3 3 3 3v16s5-1 9 2c4-3 9-2 9-2V3s-5-1-9 2Z"/>@break
        @case('home')<path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-7h6v7"/>@break
        @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>@break
        @case('info')<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>@break
        @case('logout')<path d="M9 4H4v16h5M10 12h11m-4-4 4 4-4 4"/>@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
        @case('scores')<path d="m2 8 10-5 10 5-10 5L2 8Zm4 2v7c4 3 8 3 12 0v-7M22 8v8"/>@break
        @case('clipboard')<rect x="5" y="4" width="14" height="18" rx="2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 11h6M9 15h6M9 19h4"/>@break
        @case('layers')<path d="m3 7 9-4 9 4-9 4-9-4Zm0 5 9 4 9-4M3 17l9 4 9-4"/>@break
        @case('eye')<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
        @case('list')<path d="M9 5h12M9 12h12M9 19h12M3 5h1M3 12h1M3 19h1"/>@break
        @case('code')<path d="m7 6-5 6 5 6m10-12 5 6-5 6M14 3l-4 18"/>@break
        @case('star')<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.2L5.8 21 7 14.2 2 9.3l6.9-1L12 2Z"/>@break
        @case('trophy')<path d="M8 3h8v7a4 4 0 0 1-8 0V3ZM8 5H4v3a4 4 0 0 0 4 4m8-7h4v3a4 4 0 0 1-4 4M12 14v7m-4 0h8"/>@break
        @case('shield-check')<path d="m12 2 8 3v6c0 5-8 11-8 11S4 16 4 11V5l8-3Z"/><path d="m8 11 3 3 5-6"/>@break
        @case('plus')<path d="M12 4v16M4 12h16"/>@break
        @case('edit')<path d="m15 4 5 5M4 20l4-1L21 6a2.8 2.8 0 0 0-4-4L4 15v5Z"/>@break
        @case('refresh')<path d="M20 7a8 8 0 0 0-14-2L3 8m0-5v5h5M4 17a8 8 0 0 0 14 2l3-3m0 5v-5h-5"/>@break
        @case('trash')<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>@break
        @case('send')<path d="m22 2-7 20-4-9-9-4 20-7Zm0 0L11 13"/>@break
        @case('check-circle')<circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/>@break
        @case('check')<path d="m5 12 4 4L20 5"/>@break
    @endswitch
</svg>
