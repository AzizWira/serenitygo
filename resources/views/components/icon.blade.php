@props(['name'=>'sparkles','size'=>20])
<svg {{ $attributes->merge(['class'=>'icon']) }} xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('package') <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/> @break
@case('search') <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/> @break
@case('calculator') <rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8 6h8v4H8zM8 14h1m4 0h1m-6 4h1m4 0h1"/> @break
@case('arrow-right') <path d="M4 12h16m-7-7 7 7-7 7"/> @break
@case('arrow-left') <path d="M20 12H4m7-7-7 7 7 7"/> @break
@case('heart') <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/> @break
@case('sparkles') <path d="m12 3 1.9 6.1L20 11l-6.1 1.9L12 19l-1.9-6.1L4 11l6.1-1.9L12 3Zm7 13 .7 1.3L21 18l-1.3.7L19 20l-.7-1.3L17 18l1.3-.7L19 16Z"/> @break
@case('check') <path d="m5 12 4 4L19 6"/> @break
@case('check-circle') <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/> @break
@case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
@case('users') <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm12 10v-2a4 4 0 0 0-3-3.87M16 3.1a4 4 0 0 1 0 7.8"/> @break
@case('chart') <path d="M3 3v18h18M7 16v-4m5 4V7m5 9v-7"/> @break
@case('receipt') <path d="M5 2h14v20l-3.5-2-3.5 2-3.5-2L5 22V2Zm4 5h6M9 11h6"/> @break
@case('settings') <path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="2" fill="white"/><circle cx="16" cy="17" r="2" fill="white"/> @break
@case('upload') <path d="M12 16V3m-5 5 5-5 5 5M4 16v5h16v-5"/> @break
@case('logout') <path d="M9 21H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5M16 17l5-5-5-5m5 5H9"/> @break
@case('menu') <path d="M4 7h16M4 12h16M4 17h16"/> @break
@case('close') <path d="M5 5 19 19M19 5 5 19"/> @break
@case('plus') <path d="M12 5v14M5 12h14"/> @break
@case('trash') <path d="M3 6h18m-2 0-1 15H6L5 6m3 0V3h8v3M10 10v7m4-7v7"/> @break
@case('edit') <path d="M15 5 19 9M4 20l5-.8 11-11a3 3 0 0 0-4-4L5 15l-1 5Z"/> @break
@case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10h.01"/> @break
@case('eye') <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/> @break
@case('shield') <path d="m12 2 8 4v6c0 5-3.5 8.3-8 10-4.5-1.7-8-5-8-10V6l8-4Z"/><path d="m9 12 2 2 4-4"/> @break
@case('chevron-down') <path d="m6 9 6 6 6-6"/> @break
@case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/> @break
@default <circle cx="12" cy="12" r="9"/> @endswitch
</svg>
