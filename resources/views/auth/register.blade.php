@extends('layouts.app', ['title' => 'Daftar Akaun Baru - GestureSpeak'])

@section('content')
<div style="max-width: 520px; margin: 1.5rem auto;">
    <div class="card" style="border: 2px solid var(--border-light); padding: 2.5rem 2rem;">

        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 2.8rem; margin-bottom: 0.5rem;">📝 👨‍👩‍👧</div>
            <h1 style="font-size: 1.8rem; margin-bottom: 0.3rem;">Daftar Akaun Dewasa</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Daftar sebagai Ibu Bapa atau Guru untuk mula mendaftar murid
            </p>
        </div>

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Nama Penuh:</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-input"
                    value="{{ old('name') }}"
                    placeholder="Contoh: Puan Siti / Cikgu Tan"
                    required
                    autofocus
                >
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Alamat Emel:</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="{{ old('email') }}"
                    placeholder="nama@contoh.com"
                    required
                >
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- ROLE SELECTOR --}}
            <div class="form-group">
                <label class="form-label">Peranan Anda:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <label style="cursor: pointer;">
                        <input type="radio" name="role" value="parent" {{ old('role', 'parent') === 'parent' ? 'checked' : '' }} style="display: none;" id="role-parent">
                        <div class="card" style="text-align: center; padding: 1rem; border: 2px solid var(--border-light);" id="role-box-parent">
                            <div style="font-size: 1.8rem;">👨‍👩‍👧</div>
                            <div style="font-weight: 800; margin-top: 0.3rem;">Ibu Bapa / Penjaga</div>
                        </div>
                    </label>

                    <label style="cursor: pointer;">
                        <input type="radio" name="role" value="teacher" {{ old('role') === 'teacher' ? 'checked' : '' }} style="display: none;" id="role-teacher">
                        <div class="card" style="text-align: center; padding: 1rem; border: 2px solid var(--border-light);" id="role-box-teacher">
                            <div style="font-size: 1.8rem;">👩‍🏫</div>
                            <div style="font-weight: 800; margin-top: 0.3rem;">Guru Sekolah</div>
                        </div>
                    </label>
                </div>
                @error('role')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Laluan (Minima 8 aksara):</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="••••••••"
                    required
                >
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Sahkan Kata Laluan:</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-input"
                    placeholder="••••••••"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                <i class="fa-solid fa-user-check"></i> Cipta Akaun
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.95rem;">
            Sudah ada akaun?
            <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 800;">Log masuk di sini</a>
        </div>
    </div>
</div>

<script>
    // Visual styling for role selector
    function updateRoleBoxes() {
        const isParent = document.getElementById('role-parent').checked;
        const boxParent = document.getElementById('role-box-parent');
        const boxTeacher = document.getElementById('role-box-teacher');

        if (isParent) {
            boxParent.style.borderColor = 'var(--primary)';
            boxParent.style.background = '#f0edff';
            boxTeacher.style.borderColor = 'var(--border-light)';
            boxTeacher.style.background = '#fff';
        } else {
            boxTeacher.style.borderColor = 'var(--primary)';
            boxTeacher.style.background = '#f0edff';
            boxParent.style.borderColor = 'var(--border-light)';
            boxParent.style.background = '#fff';
        }
    }

    document.getElementById('role-parent').addEventListener('change', updateRoleBoxes);
    document.getElementById('role-teacher').addEventListener('change', updateRoleBoxes);
    updateRoleBoxes();
</script>
@endsection
