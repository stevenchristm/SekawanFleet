<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke SekawanFleet</h2>
        <p class="mt-1 text-slate-500">Gunakan akun yang diberikan administrator</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3.5" role="alert">
            <span class="material-symbols-outlined icon-fill mt-0.5 text-red-600" aria-hidden="true">error</span>
            <div>
                <p class="font-semibold text-red-900">Gagal masuk</p>
                <p class="mt-0.5 text-xs text-red-700">{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false }">
        @csrf

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="nama@nikel.test" @class(['form-input', 'form-input-invalid' => $errors->has('email')])
                   @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
            @error('email')
                <p id="email-error" class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="form-label">Password</label>
            <div class="relative">
                <input id="password" name="password" required autocomplete="current-password" placeholder="Masukkan password"
                       :type="show ? 'text' : 'password'" type="password" class="form-input pr-10">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-700"
                        :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <label for="remember_me" class="flex cursor-pointer select-none items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-700">
            <span class="text-xs text-slate-600">Ingat saya</span>
        </label>

        <button type="submit" class="btn-primary h-[42px] w-full">
            Masuk
            <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_forward</span>
        </button>
    </form>

    <div class="mt-6 flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
        <span class="material-symbols-outlined text-base text-slate-500" aria-hidden="true">support_agent</span>
        <p>Lupa password atau belum punya akun? Hubungi <span class="font-semibold text-slate-800">administrator pool kendaraan</span>.</p>
    </div>
</x-guest-layout>
