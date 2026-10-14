{{-- Tombol Setujui / Tolak untuk satu approval. Variabel: $approval (BookingApproval), $stretch (opsional) --}}
@php
    $modal = 'reject-approval-'.$approval->id;
    $stretch = $stretch ?? false;
    $reasons = ['Kendaraan dibutuhkan untuk prioritas lain', 'Jadwal bertabrakan dengan kegiatan site', 'Keperluan kurang jelas, mohon diajukan ulang'];
@endphp

<div @class(['gap-2', 'grid grid-cols-2' => $stretch, 'flex flex-wrap items-center' => ! $stretch])>
    <button type="button" x-data x-on:click.prevent="$dispatch('open-modal', '{{ $modal }}')"
            class="btn border border-red-300 bg-white font-semibold text-red-700 shadow-sm hover:bg-red-50 focus:ring-red-200 {{ $stretch ? '' : 'btn-sm h-9' }}">
        <span class="material-symbols-outlined text-base" aria-hidden="true">close</span>Tolak
    </button>

    <form method="POST" action="{{ route('approvals.approve', $approval) }}" class="{{ $stretch ? '' : 'contents' }}"
          onsubmit="return confirm('Setujui pemesanan {{ $approval->booking->code }} di level {{ $approval->level }}?');">
        @csrf
        <button type="submit" class="btn-success font-semibold {{ $stretch ? 'w-full' : 'btn-sm h-9' }}">
            <span class="material-symbols-outlined text-base" aria-hidden="true">check_circle</span>Setujui
        </button>
    </form>
</div>

<x-modal :name="$modal" :show="old('rejecting') == $approval->id && $errors->has('note')" maxWidth="lg" focusable>
    <form method="POST" action="{{ route('approvals.reject', $approval) }}" x-data="{ note: @js(old('rejecting') == $approval->id ? old('note', '') : '') }">
        @csrf
        <input type="hidden" name="rejecting" value="{{ $approval->id }}">

        <div class="flex items-start justify-between border-b border-slate-100 p-6 pb-4">
            <div class="flex items-start gap-3.5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">error</span>
                </span>
                <div>
                    <h2 class="text-base font-bold leading-snug text-slate-900">Tolak pemesanan {{ $approval->booking->code }}?</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Persetujuan level {{ $approval->level }}</p>
                </div>
            </div>
            <button type="button" x-on:click="$dispatch('close')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <div class="space-y-4 p-6">
            <div class="flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 p-3 text-xs font-medium text-red-900">
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-lg text-red-600" aria-hidden="true">warning</span>
                <p>Pemesanan akan ditolak seluruhnya dan level persetujuan berikutnya tidak diproses.</p>
            </div>

            <div class="space-y-1.5 rounded-lg border border-slate-200 bg-slate-50 p-3.5 text-xs">
                <p class="flex items-center gap-2 font-semibold text-slate-800"><span class="material-symbols-outlined text-base text-slate-500" aria-hidden="true">assignment_ind</span>Ringkasan pengajuan</p>
                <p class="leading-relaxed text-slate-600">
                    <span class="font-medium text-slate-900">Pemohon:</span> {{ $approval->booking->requester_name }} &middot;
                    <span class="font-medium text-slate-900">Unit:</span> {{ $approval->booking->vehicle->brand_model }} ({{ $approval->booking->vehicle->plate_number }}) &middot;
                    <span class="font-medium text-slate-900">Jadwal:</span> {{ $approval->booking->start_at->format('d/m/Y H:i') }} &ndash; {{ $approval->booking->end_at->format('H:i') }}
                </p>
            </div>

            <div class="space-y-2">
                <label for="note-{{ $approval->id }}" class="block text-xs font-semibold text-slate-800">Alasan penolakan <span class="font-bold text-red-600">*</span></label>
                <textarea id="note-{{ $approval->id }}" name="note" rows="3" maxlength="500" required x-model="note"
                          placeholder="Tuliskan alasan penolakan secara spesifik..." class="form-textarea text-xs focus:border-red-500 focus:ring-red-500/20"></textarea>
                @if (old('rejecting') == $approval->id)
                    <x-input-error :messages="$errors->get('note')" />
                @endif
                <div class="space-y-1 pt-1">
                    <span class="block text-[11px] font-semibold text-slate-500">Pilih alasan cepat:</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($reasons as $reason)
                            <button type="button" @click="note = @js($reason)" class="rounded border border-slate-200 bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-700 transition-colors hover:bg-slate-200">{{ $reason }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <button type="button" x-on:click="$dispatch('close')" class="btn-secondary btn-sm h-[38px]">Batal</button>
            <button type="submit" class="btn-danger btn-sm h-[38px] font-semibold">
                <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>Tolak Pemesanan
            </button>
        </div>
    </form>
</x-modal>
