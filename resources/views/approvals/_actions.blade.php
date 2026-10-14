{{-- Tombol Setujui / Tolak untuk satu approval. Variabel: $approval (BookingApproval) --}}
@php $modal = 'reject-approval-'.$approval->id; @endphp

<div class="flex flex-wrap gap-2">
    <form method="POST" action="{{ route('approvals.approve', $approval) }}"
          onsubmit="return confirm('Setujui pemesanan {{ $approval->booking->code }} di level {{ $approval->level }}?');">
        @csrf
        <button type="submit"
                class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            Setujui
        </button>
    </form>

    <x-danger-button type="button" x-data x-on:click.prevent="$dispatch('open-modal', '{{ $modal }}')">
        Tolak
    </x-danger-button>
</div>

<x-modal :name="$modal" :show="old('rejecting') == $approval->id && $errors->has('note')" focusable>
    <form method="POST" action="{{ route('approvals.reject', $approval) }}" class="p-6">
        @csrf
        <input type="hidden" name="rejecting" value="{{ $approval->id }}">

        <h2 class="text-lg font-medium text-gray-900">Tolak pemesanan {{ $approval->booking->code }}?</h2>
        <p class="mt-1 text-sm text-gray-600">
            Pemesanan akan ditolak seluruhnya dan level persetujuan berikutnya tidak diproses.
        </p>

        <div class="mt-4">
            <x-input-label for="note-{{ $approval->id }}" value="Alasan penolakan" />
            <textarea id="note-{{ $approval->id }}" name="note" rows="3" maxlength="500" required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('rejecting') == $approval->id ? old('note') : '' }}</textarea>
            @if (old('rejecting') == $approval->id)
                <x-input-error :messages="$errors->get('note')" class="mt-1" />
            @endif
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
            <x-danger-button>Tolak Pemesanan</x-danger-button>
        </div>
    </form>
</x-modal>
