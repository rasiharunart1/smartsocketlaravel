@extends('layouts.app')

@section('title', 'Profil')
@section('header_title', 'Profil Saya')

@section('content')
<section class="content">
    <div class="profile-container">
        <div class="page-header" style="margin-bottom: 20px;">
            <div>
                <h1 class="page-title">Profil Pengguna</h1>
                <p class="page-subtitle">
                    Kelola informasi akun, keamanan kata sandi, dan preferensi Anda.
                </p>
            </div>
        </div>

        @include('profile.partials.update-profile-information-form')

        @include('profile.partials.update-password-form')

        @include('profile.partials.delete-user-form')
    </div>
</section>
@endsection
