<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Pemesanan Kendaraan</h2>
    </x-slot>

    @php
        $selectClass = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
        $oldApprovers = old('approvers', ['', '']);
    @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('bookings.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm">
                @csrf

                {{-- Pemohon --}}
                <section class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Data Pemohon</h3>
                    <div>
                        <x-input-label for="requester_name" value="Nama pemohon" />
                        <x-text-input id="requester_name" name="requester_name" class="mt-1 block w-full" :value="old('requester_name')" required maxlength="100" />
                        <x-input-error :messages="$errors->get('requester_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="destination" value="Tujuan" />
                        <x-text-input id="destination" name="destination" class="mt-1 block w-full" :value="old('destination')" required maxlength="150" />
                        <x-input-error :messages="$errors->get('destination')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="purpose" value="Keperluan" />
                        <textarea id="purpose" name="purpose" rows="3" maxlength="500" required class="{{ $selectClass }}">{{ old('purpose') }}</textarea>
                        <x-input-error :messages="$errors->get('purpose')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="start_at" value="Waktu mulai" />
                            <x-text-input id="start_at" name="start_at" type="datetime-local" class="mt-1 block w-full" :value="old('start_at')" required />
                            <x-input-error :messages="$errors->get('start_at')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="end_at" value="Waktu selesai" />
                            <x-text-input id="end_at" name="end_at" type="datetime-local" class="mt-1 block w-full" :value="old('end_at')" required />
                            <x-input-error :messages="$errors->get('end_at')" class="mt-1" />
                        </div>
                    </div>
                </section>

                {{-- Kendaraan & driver --}}
                <section class="space-y-4 border-t pt-6">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Kendaraan & Driver</h3>
                    <div>
                        <x-input-label for="vehicle_id" value="Kendaraan" />
                        <select id="vehicle_id" name="vehicle_id" required class="{{ $selectClass }}">
                            <option value="">-- Pilih kendaraan --</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                    {{ $vehicle->plate_number }} - {{ $vehicle->brand_model }}
                                    ({{ $vehicle->type->label() }}, {{ $vehicle->ownership->label() }}, {{ $vehicle->location->name }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('vehicle_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="driver_id" value="Driver" />
                        <select id="driver_id" name="driver_id" required class="{{ $selectClass }}">
                            <option value="">-- Pilih driver --</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected(old('driver_id') == $driver->id)>
                                    {{ $driver->name }} ({{ $driver->location->name }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('driver_id')" class="mt-1" />
                    </div>
                </section>

                {{-- Approver berjenjang --}}
                <section class="space-y-4 border-t pt-6"
                         x-data="{ approvers: @js(array_values($oldApprovers)) }">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Persetujuan Berjenjang</h3>
                        <p class="mt-1 text-xs text-gray-500">Minimal 2 level. Level berikutnya baru bisa memproses setelah level sebelumnya menyetujui.</p>
                    </div>

                    <template x-for="(approver, index) in approvers" :key="index">
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label :for="'approver_' + index" class="block font-medium text-sm text-gray-700" x-text="'Approver level ' + (index + 1)"></label>
                                <select :id="'approver_' + index" name="approvers[]" x-model="approvers[index]" required class="{{ $selectClass }}">
                                    <option value="">-- Pilih approver --</option>
                                    @foreach ($approvers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}{{ $user->position ? ' - '.$user->position : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" x-show="approvers.length > 2" @click="approvers.splice(index, 1)"
                                    class="mb-1 rounded-md px-3 py-2 text-sm text-red-600 hover:bg-red-50" :aria-label="'Hapus level ' + (index + 1)">
                                Hapus
                            </button>
                        </div>
                    </template>

                    <button type="button" x-show="approvers.length < 5" @click="approvers.push('')"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        + Tambah level
                    </button>

                    <x-input-error :messages="array_merge($errors->get('approvers'), ...array_values($errors->get('approvers.*')))" class="mt-1" />
                </section>

                <div class="flex items-center justify-end gap-3 border-t pt-6">
                    <a href="{{ route('bookings.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Batal</a>
                    <x-primary-button>Simpan Pemesanan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
