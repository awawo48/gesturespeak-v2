@extends('layouts.app', ['title' => 'Cipta Latihan - Portal Guru'])

@section('content')
<a href="{{ route('guardian.exercises.index') }}" style="color: #0984e3; font-weight: 800; font-size: 0.9rem;">
    <i class="fa-solid fa-arrow-left"></i> Senarai Latihan
</a>
<h1 style="font-size: 1.8rem; margin: 0.4rem 0 1.2rem;">Cipta Latihan Baharu</h1>

@if($errors->any())
    <div class="card" style="border-color: rgba(214, 48, 49, 0.35); background: #fff5f5; margin-bottom: 1rem; color: var(--danger);">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('guardian.exercises.store') }}" class="card" style="max-width: 820px;">
    @csrf

    <div style="margin-bottom: 1.2rem;">
        <label style="font-weight: 800; display: block; margin-bottom: 0.3rem;">Tajuk Latihan</label>
        <input type="text" name="title" class="form-input" value="{{ old('title') }}" maxlength="120" required placeholder="cth: Kuiz Minggu 3 - Keluarga">
    </div>

    <div style="margin-bottom: 1.2rem;">
        <label style="font-weight: 800; display: block; margin-bottom: 0.5rem;">Jenis Latihan</label>
        <label style="margin-right: 1.5rem; cursor: pointer;">
            <input type="radio" name="type" value="choice" {{ old('type', 'choice') === 'choice' ? 'checked' : '' }} onchange="toggleType()"> 🎯 Pilihan Jawapan
        </label>
        <label style="cursor: pointer;">
            <input type="radio" name="type" value="spelling" {{ old('type') === 'spelling' ? 'checked' : '' }} onchange="toggleType()"> ✍️ Mengeja Perkataan
        </label>
    </div>

    <div id="box-choice">
        <label style="font-weight: 800; display: block; margin-bottom: 0.5rem;">Pilih isyarat untuk disoal (min. 2)</label>
        @foreach($categories as $cat)
            @if($cat->signs->isNotEmpty())
                <details style="margin-bottom: 0.6rem; border: 2px solid var(--border-light); border-radius: 12px; padding: 0.6rem 0.9rem;">
                    <summary style="cursor: pointer; font-weight: 800;">{{ $cat->icon }} {{ $cat->name }} ({{ $cat->signs->count() }})</summary>
                    <div style="margin: 0.5rem 0;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="pickAll(this, true)">Pilih semua</button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="pickAll(this, false)">Kosongkan</button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.4rem;">
                        @foreach($cat->signs as $s)
                            <label style="cursor: pointer; font-size: 0.9rem;">
                                <input type="checkbox" name="sign_ids[]" value="{{ $s->id }}" {{ in_array($s->id, old('sign_ids', [])) ? 'checked' : '' }}>
                                {{ $s->word }}
                            </label>
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </div>

    <div id="box-spelling" style="display: none;">
        <label style="font-weight: 800; display: block; margin-bottom: 0.3rem;">Senarai perkataan (satu setiap baris atau asingkan dengan koma)</label>
        <textarea name="words" class="form-input" rows="6" placeholder="BUKU&#10;AYAH&#10;SEKOLAH">{{ old('words') }}</textarea>
        <p style="font-size: 0.8rem; color: var(--text-muted);">Huruf A-Z sahaja, 2-12 huruf. Jika perkataan wujud dalam senarai isyarat, video isyarat akan dipaparkan sebagai petunjuk.</p>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top: 1.2rem; background: #0984e3;">
        <i class="fa-solid fa-floppy-disk"></i> Simpan Latihan
    </button>
</form>

<script>
    function toggleType() {
        const t = document.querySelector('input[name=type]:checked').value;
        document.getElementById('box-choice').style.display = t === 'choice' ? '' : 'none';
        document.getElementById('box-spelling').style.display = t === 'spelling' ? '' : 'none';
    }
    function pickAll(btn, on) {
        btn.closest('details').querySelectorAll('input[type=checkbox]').forEach(c => c.checked = on);
    }
    toggleType();
</script>
@endsection

