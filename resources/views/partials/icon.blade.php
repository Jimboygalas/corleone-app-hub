@props(['name', 'class' => ''])

@switch($name)
    @case('logo')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 2.9 19.5 6.3v5.9c0 4.7-3.1 7.7-7.5 9-4.4-1.3-7.5-4.3-7.5-9V6.3L12 2.9Z" />
            <path d="M8.5 12.6 10.8 15l4.9-5.6" />
            <path d="M12 6.5v1.6" />
        </svg>
        @break

    @case('shield-check')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3 19.4 6.1v5.8c0 4.8-3.1 7.8-7.4 9.1-4.3-1.3-7.4-4.3-7.4-9.1V6.1L12 3Z" />
            <path d="m8.4 12.3 2.2 2.2 5-5.4" />
            <path d="M8.3 6.9 12 5.4l3.7 1.5" />
        </svg>
        @break

    @case('lock')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="5.2" y="10" width="13.6" height="10.2" rx="2.6" />
            <path d="M8.4 10V7.8a3.6 3.6 0 0 1 7.2 0V10" />
            <path d="M12 14.1v2.2" />
            <path d="M9 10h6" />
        </svg>
        @break

    @case('mail-check')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3.4" y="5.4" width="17.2" height="13.2" rx="2.7" />
            <path d="m4.5 7.7 7.5 5 7.5-5" />
            <path d="m13.5 15.2 1.7 1.7 3.4-3.7" />
            <path d="M6.8 16.2h4.1" />
        </svg>
        @break

    @case('inbox')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4.2 10.7 6.1 5.2h11.8l1.9 5.5v7.1a2 2 0 0 1-2 2H6.2a2 2 0 0 1-2-2v-7.1Z" />
            <path d="M4.5 11.2h4.2l1.4 2.5h3.8l1.4-2.5h4.2" />
            <path d="M8.2 7.8h7.6" />
        </svg>
        @break

    @case('sent')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20.7 4.4 10.4 20.1l-1.7-7.2-5.4-2.2 17.4-6.3Z" />
            <path d="m8.9 12.8 5.2-3.4" />
            <path d="M10.4 20.1 13 14" />
        </svg>
        @break

    @case('draft')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 4.4h8.1L18 8.3v11.3H6V4.4Z" />
            <path d="M14 4.7V9h4" />
            <path d="M8.7 13.8h6.6" />
            <path d="M8.7 16.7h4.5" />
            <path d="M8.7 10.9h2.6" />
        </svg>
        @break

    @case('archive')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3.8" y="4.4" width="16.4" height="4.2" rx="1.4" />
            <path d="M5.6 8.8v9a2 2 0 0 0 2 2h8.8a2 2 0 0 0 2-2v-9" />
            <path d="M9.1 12.7h5.8" />
            <path d="M8.4 16h7.2" />
        </svg>
        @break

    @case('bot')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="5" y="7.8" width="14" height="10.8" rx="3.2" />
            <path d="M12 7.8V4.6" />
            <path d="M9.1 12.6h.1" />
            <path d="M14.8 12.6h.1" />
            <path d="M9.5 15.8h5" />
            <path d="M3.4 12v2.4" />
            <path d="M20.6 12v2.4" />
            <path d="m16.8 4.3.6-1.2.6 1.2 1.2.6-1.2.6-.6 1.2-.6-1.2-1.2-.6 1.2-.6Z" />
        </svg>
        @break

    @case('chat')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5.1 6.2h13.8a2.7 2.7 0 0 1 2.7 2.7v5.6a2.7 2.7 0 0 1-2.7 2.7h-6.7l-5 3.1v-3.1H5.1a2.7 2.7 0 0 1-2.7-2.7V8.9a2.7 2.7 0 0 1 2.7-2.7Z" />
            <path d="M8.1 11.7h.1" />
            <path d="M12 11.7h.1" />
            <path d="M15.9 11.7h.1" />
            <path d="m17.4 4.2.5-1 .5 1 1 .5-1 .5-.5 1-.5-1-1-.5 1-.5Z" />
        </svg>
        @break

    @case('dashboard')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="6.2" height="6.2" rx="1.7" />
            <rect x="13.8" y="4" width="6.2" height="6.2" rx="1.7" />
            <rect x="4" y="13.8" width="6.2" height="6.2" rx="1.7" />
            <rect x="13.8" y="13.8" width="6.2" height="6.2" rx="1.7" />
            <path d="M7.1 6.4v1.4" />
            <path d="M16.9 16.2v1.4" />
        </svg>
        @break

    @case('phone-check')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="7.1" y="3.3" width="9.8" height="17.4" rx="2.6" />
            <path d="M10.3 6h3.4" />
            <path d="M10.6 17.5h2.8" />
            <path d="m9.4 11.8 1.8 1.8 3.5-3.9" />
        </svg>
        @break

    @case('plus')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 5.2v13.6" />
            <path d="M5.2 12h13.6" />
        </svg>
        @break

    @case('logout')
        <svg class="app-icon {{ $class }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10.2 5.1H6.7a2 2 0 0 0-2 2v9.8a2 2 0 0 0 2 2h3.5" />
            <path d="M14.1 8.2 18 12l-3.9 3.8" />
            <path d="M8.7 12H18" />
        </svg>
        @break
@endswitch
