<x-app-layout>
    <x-slot name="title">Buat Pemesanan Kendaraan</x-slot>
    <x-slot name="subtitle">Pemesanan Kendaraan / Formulir Pengajuan Baru</x-slot>
    <x-slot name="actions">
        <a href="{{ route('bookings.index') }}" class="btn-ghost">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_back</span>Kembali ke Daftar
        </a>
    </x-slot>

    @php
        $oldApprovers = array_values(old('approvers', ['', '']));
        $invalid = fn (string $field) => $errors->has($field) ? 'form-input-invalid' : '';
    @endphp

    <div class="mx-auto w-full max-w-3xl">
        <form method="POST" action="{{ route('bookings.store') }}" class="card space-y-8 p-6 sm:p-8">
            @csrf

            <div class="border-b border-slate-100 pb-5">
                <div class="mb-1 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-700">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">assignment</span>Permohonan Kendaraan
                </div>
                <h2 class="text-xl font-semibold text-slate-900">Formulir Pemesanan Kendaraan</h2>
                <p class="mt-1 text-xs text-slate-500">Isi detail perjalanan, pilih kendaraan &amp; driver, lalu tentukan alur persetujuan minimal 2 level. Kolom bertanda <span class="text-red-600">*</span> wajib diisi.</p>
            </div>

            {{-- 1. Pemohon & jadwal --}}
            <section class="space-y-5" aria-labelledby="sec-1">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 id="sec-1" class="section-title"><span class="h-2 w-2 rounded-full bg-brand-700"></span>Data Pemohon &amp; Jadwal</h3>
                    <span class="text-[11px] text-slate-400">Bagian 1 dari 3</span>
                </div>

                <div>
                    <label for="requester_name" class="form-label">Nama pemohon <span class="text-red-600">*</span></label>
                    <input id="requester_name" name="requester_name" value="{{ old('requester_name') }}" required maxlength="100" placeholder="mis. Hendra Wijaya (Dept. Eksplorasi)" class="form-input {{ $invalid('requester_name') }}">
                    <x-input-error :messages="$errors->get('requester_name')" class="mt-1.5" />
                </div>

                <div>
                    <label for="destination" class="form-label">Tujuan <span class="text-red-600">*</span></label>
                    <div class="relative">
                        <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 left-3 my-auto text-lg text-slate-400" aria-hidden="true">pin_drop</span>
                        <input id="destination" name="destination" value="{{ old('destination') }}" required maxlength="150" placeholder="mis. Site Morowali - Pit Blok B" class="form-input pl-9 {{ $invalid('destination') }}">
                    </div>
                    <x-input-error :messages="$errors->get('destination')" class="mt-1.5" />
                </div>

                <div>
                    <label for="purpose" class="form-label">Keperluan <span class="text-red-600">*</span></label>
                    <textarea id="purpose" name="purpose" rows="3" maxlength="500" required placeholder="Jelaskan kebutuhan perjalanan..." class="form-textarea resize-none {{ $invalid('purpose') }}">{{ old('purpose') }}</textarea>
                    <x-input-error :messages="$errors->get('purpose')" class="mt-1.5" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach (['start_at' => ['Waktu mulai', 'calendar_today'], 'end_at' => ['Waktu selesai', 'event_available']] as $field => [$label, $icon])
                        <div>
                            <label for="{{ $field }}" class="form-label">{{ $label }} <span class="text-red-600">*</span></label>
                            <div class="relative">
                                <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 left-3 my-auto text-lg text-slate-400" aria-hidden="true">{{ $icon }}</span>
                                <input id="{{ $field }}" name="{{ $field }}" type="datetime-local" value="{{ old($field) }}" required class="form-input pl-9 {{ $invalid($field) }}">
                            </div>
                            <x-input-error :messages="$errors->get($field)" class="mt-1.5" />
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- 2. Kendaraan & driver --}}
            <section class="space-y-5" aria-labelledby="sec-2">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 id="sec-2" class="section-title"><span class="h-2 w-2 rounded-full bg-brand-700"></span>Kendaraan &amp; Driver</h3>
                    <span class="text-[11px] text-slate-400">Bagian 2 dari 3</span>
                </div>

                <div>
                    <label for="vehicle_id" class="form-label">Kendaraan <span class="text-red-600">*</span></label>
                    <select id="vehicle_id" name="vehicle_id" required class="form-input {{ $invalid('vehicle_id') }}">
                        <option value="">-- Pilih kendaraan --</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                {{ $vehicle->plate_number }} - {{ $vehicle->brand_model }} ({{ $vehicle->type->label() }}, {{ $vehicle->ownership->label() }}, {{ $vehicle->location->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_id')
                        <div class="mt-2 flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700" role="alert">
                            <span class="material-symbols-outlined mt-0.5 text-lg" aria-hidden="true">error</span>
                            <div class="text-xs">
                                <p class="font-medium">{{ $message }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-600">Pilih kendaraan lain atau sesuaikan jadwal penggunaan.</p>
                            </div>
                        </div>
                    @enderror
                </div>

                <div>
                    <label for="driver_id" class="form-label">Driver <span class="text-red-600">*</span></label>
                    <div class="relative">
                        <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 left-3 my-auto text-lg text-slate-400" aria-hidden="true">person</span>
                        <select id="driver_id" name="driver_id" required class="form-input pl-9 {{ $invalid('driver_id') }}">
                            <option value="">-- Pilih driver --</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected(old('driver_id') == $driver->id)>{{ $driver->name }} ({{ $driver->location->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('driver_id')" class="mt-1.5" />
                </div>
            </section>

            {{-- 3. Approver berjenjang --}}
            <section class="space-y-5" aria-labelledby="sec-3" x-data="{ approvers: @js($oldApprovers) }">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 id="sec-3" class="section-title"><span class="h-2 w-2 rounded-full bg-brand-700"></span>Persetujuan Berjenjang</h3>
                    <span class="text-[11px] text-slate-400">Bagian 3 dari 3</span>
                </div>

                <div class="flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <span class="material-symbols-outlined mt-0.5 text-lg text-brand-700" aria-hidden="true">info</span>
                    <p class="text-xs text-slate-600">Minimal 2 level persetujuan. Level berikutnya baru bisa memproses setelah level sebelumnya menyetujui. Urutan di bawah menentukan level.</p>
                </div>

                <div class="relative space-y-4">
                    <div class="absolute bottom-8 left-[15px] top-5 w-0.5 bg-slate-200" aria-hidden="true"></div>

                    <template x-for="(approver, index) in approvers" :key="index">
                        <div class="relative z-10 flex items-start gap-3.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white ring-4 ring-white"
                                  :class="index < 2 ? 'bg-brand-700' : 'bg-slate-500'" x-text="index + 1"></span>
                            <div class="flex-1 space-y-1.5 rounded-lg border border-slate-200 bg-slate-50/60 p-3">
                                <div class="flex items-center justify-between">
                                    <label :for="'approver_' + index" class="text-[11px] font-bold uppercase tracking-wider"
                                           :class="index < 2 ? 'text-brand-700' : 'text-slate-500'"
                                           x-text="'Approver level ' + (index + 1) + (index < 2 ? '' : ' (opsional)')"></label>
                                    <span x-show="index < 2" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                        <span class="material-symbols-outlined text-xs" aria-hidden="true">lock</span>Wajib
                                    </span>
                                    <button type="button" x-show="index >= 2" @click="approvers.splice(index, 1)"
                                            class="inline-flex items-center gap-1 text-[11px] font-medium text-red-600 hover:text-red-700" :aria-label="'Hapus level ' + (index + 1)">
                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>Hapus
                                    </button>
                                </div>
                                <select :id="'approver_' + index" name="approvers[]" x-model="approvers[index]" required class="form-input h-9">
                                    <option value="">-- Pilih approver --</option>
                                    @foreach ($approvers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}{{ $user->position ? ' - '.$user->position : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="pl-11">
                    <button type="button" x-show="approvers.length < 5" @click="approvers.push('')"
                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 px-4 py-2.5 text-xs font-medium text-slate-500 transition-colors hover:border-brand-700 hover:bg-slate-50 hover:text-brand-700">
                        <span class="material-symbols-outlined text-lg" aria-hidden="true">add_circle</span>Tambah Level Persetujuan
                    </button>
                </div>

                <x-input-error :messages="array_merge($errors->get('approvers'), ...array_values($errors->get('approvers.*')))" />
            </section>

            <div class="flex flex-col-reverse items-stretch justify-end gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center">
                <a href="{{ route('bookings.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary px-6">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">send</span>Simpan Pemesanan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
