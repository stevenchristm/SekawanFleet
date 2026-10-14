<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Masuk' }} - {{ config('app.name', 'SekawanFleet') }}</title>
        @include('layouts.partials.fonts')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-50 font-sans text-sm text-slate-800 antialiased">
        <div class="grid min-h-full grid-cols-1 lg:grid-cols-12">
            {{-- Panel brand (desktop) --}}
            <section class="relative hidden flex-col justify-between overflow-hidden bg-brand-700 p-12 text-white lg:col-span-6 lg:flex xl:col-span-5">
                <div class="pointer-events-none absolute inset-0 opacity-30 [background-image:radial-gradient(rgba(255,255,255,0.15)_1px,transparent_1px)] [background-size:24px_24px]"></div>
                <svg class="pointer-events-none absolute inset-0 h-full w-full stroke-white opacity-10" fill="none" viewBox="0 0 800 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                    <path d="M-100 200C100 180 250 320 450 280C650 240 700 100 900 120" stroke-dasharray="6 4" stroke-width="1.5"/>
                    <path d="M-100 350C120 320 300 480 500 420C700 360 750 250 950 280" stroke-width="2"/>
                    <path d="M-100 500C150 480 320 620 520 560C720 500 800 400 950 430" stroke-dasharray="4 4" stroke-width="1.5"/>
                    <path d="M-50 650C180 620 380 750 580 700C780 650 850 580 980 610" stroke-width="2"/>
                </svg>

                <div class="relative flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10">
                        <span class="material-symbols-outlined icon-fill text-2xl" aria-hidden="true">local_shipping</span>
                    </span>
                    <span>
                        <span class="block text-lg font-bold leading-tight">SekawanFleet</span>
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-brand-100">Nikel Mining Co.</span>
                    </span>
                </div>

                <div class="relative max-w-lg py-8">
                    <span class="mb-4 inline-flex items-center gap-2 rounded-md border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">hub</span>
                        Sistem Manajemen Armada Tambang
                    </span>
                    <h1 class="mb-4 text-3xl font-bold leading-tight tracking-tight">Kelola pemesanan kendaraan dengan persetujuan berjenjang</h1>
                    <p class="mb-8 text-white/80">Satu tempat untuk memesan kendaraan, menyetujui secara berjenjang, dan memantau pemakaian armada di kantor pusat, cabang, dan site tambang.</p>

                    <ul class="space-y-3 rounded-xl border border-white/10 bg-black/10 p-4">
                        @foreach ([
                            ['bolt', 'Pemesanan cepat', 'Pilih kendaraan, driver, dan approver dalam satu form; jadwal bentrok langsung terdeteksi.'],
                            ['account_tree', 'Persetujuan berjenjang', 'Minimal 2 level. Level berikutnya memproses setelah level sebelumnya menyetujui.'],
                            ['monitoring', 'Laporan & monitoring', 'Grafik pemakaian, konsumsi BBM, dan export laporan periodik ke Excel.'],
                        ] as [$icon, $heading, $text])
                            <li class="flex items-start gap-3.5 p-2">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 text-brand-100">
                                    <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $icon }}</span>
                                </span>
                                <span>
                                    <span class="block font-semibold">{{ $heading }}</span>
                                    <span class="mt-0.5 block text-xs leading-snug text-white/70">{{ $text }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative flex items-center gap-2 border-t border-white/15 pt-4 text-xs text-white/80">
                    <span class="material-symbols-outlined text-lg text-brand-100" aria-hidden="true">corporate_fare</span>
                    1 Kantor Pusat · 1 Kantor Cabang · 6 Site Tambang
                </div>
            </section>

            {{-- Konten --}}
            <main class="flex min-h-screen flex-col items-center justify-between p-6 sm:p-10 lg:col-span-6 xl:col-span-7">
                <div class="mb-4 flex w-full max-w-md items-center gap-2.5 border-b border-slate-200 pb-6 lg:hidden">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-700 text-white">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">local_shipping</span>
                    </span>
                    <span class="text-lg font-bold tracking-tight text-slate-900">SekawanFleet</span>
                </div>

                <div class="my-auto w-full max-w-md py-4">
                    <div class="card p-8 sm:p-10">
                        {{ $slot }}
                    </div>
                </div>

                <footer class="py-4 text-center text-xs text-slate-400">&copy; {{ now()->year }} SekawanFleet &mdash; Nikel Mining Co.</footer>
            </main>
        </div>
    </body>
</html>
