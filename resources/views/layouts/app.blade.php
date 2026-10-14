<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? strip_tags($title).' - ' : '' }}{{ config('app.name', 'SekawanFleet') }}</title>

        @include('layouts.partials.fonts')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-50 font-sans text-sm text-slate-800 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Lewati ke konten</a>

        @include('layouts.sidebar')

        {{-- Backdrop drawer (mobile) --}}
        <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

        <div class="flex min-h-full flex-col lg:pl-64">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6 lg:px-8">
                <button type="button" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                        @click="sidebarOpen = true" aria-label="Buka menu" :aria-expanded="sidebarOpen">
                    <span class="material-symbols-outlined" aria-hidden="true">menu</span>
                </button>

                <div class="min-w-0 flex-1">
                    @isset($title)
                        <h1 class="truncate text-lg font-bold leading-tight text-slate-900">{{ $title }}</h1>
                        @isset($subtitle)
                            <p class="hidden truncate text-xs text-slate-500 sm:block">{{ $subtitle }}</p>
                        @endisset
                    @elseif (isset($header))
                        {{ $header }}
                    @endisset
                </div>

                @isset($actions)
                    <div class="hidden shrink-0 items-center gap-2 md:flex">{{ $actions }}</div>
                @endisset

                {{-- User menu --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="flex items-center gap-2.5 rounded-lg py-1 pl-1 pr-2 hover:bg-slate-100" aria-label="Menu pengguna">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full border border-brand-200 bg-brand-100 text-xs font-bold text-brand-800">{{ Auth::user()->initials() }}</span>
                            <span class="hidden text-left xl:block">
                                <span class="block text-sm font-semibold leading-none text-slate-800">{{ Auth::user()->name }}</span>
                                <span class="text-[11px] text-slate-500">{{ Auth::user()->role->label() }}</span>
                            </span>
                            <span class="material-symbols-outlined text-[18px] text-slate-400" aria-hidden="true">expand_more</span>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Keluar</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </header>

            <main id="main" class="flex-1 space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                {{-- Aksi halaman di mobile (top bar terlalu sempit) --}}
                @isset($actions)
                    <div class="flex flex-wrap gap-2 md:hidden">{{ $actions }}</div>
                @endisset

                <x-flash />

                {{ $slot }}
            </main>

            <footer class="px-4 py-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                &copy; {{ now()->year }} SekawanFleet &mdash; Nikel Mining Co.
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
