<x-app-layout>
    <x-slot name="title">Profil</x-slot>
    <x-slot name="subtitle">Kelola informasi akun dan password Anda</x-slot>

    <div class="mx-auto max-w-3xl space-y-6">
        <div class="card p-6 sm:p-8">
            <div class="max-w-xl">@include('profile.partials.update-profile-information-form')</div>
        </div>

        <div class="card p-6 sm:p-8">
            <div class="max-w-xl">@include('profile.partials.update-password-form')</div>
        </div>

        <div class="card border-red-200 p-6 sm:p-8">
            <div class="max-w-xl">@include('profile.partials.delete-user-form')</div>
        </div>
    </div>
</x-app-layout>
