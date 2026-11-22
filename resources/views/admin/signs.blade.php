@extends('layouts.admin', ['title' => 'Kurikulum BIM — Sign Cilik'])

@section('content')
<div class="adm-page-head">
    <div>
        <h1>Kurikulum &amp; isyarat BIM</h1>
        <p class="adm-page-sub">Senarai isyarat Bahasa Isyarat Malaysia merentasi modul pembelajaran</p>
    </div>

    <form method="GET" action="{{ route('admin.signs') }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-input"
            placeholder="Cari isyarat"
            style="width: 190px;"
        >
        <select name="category" class="form-select" style="width: 170px;">
            <option value="">Semua modul</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $selectedCategory == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Tapis</button>
    </form>
</div>

<div class="adm-card adm-card-flush" tabindex="0" role="region" aria-label="Senarai isyarat BIM">
    <table class="adm-table">
        <thead>
            <tr>
                <th>Isyarat &amp; label</th>
                <th>Modul</th>
                <th>Jenis</th>
                <th>Video</th>
                <th>Panduan jari</th>
                <th style="text-align: right;">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($signs as $sign)
                <tr>
                    <td>
                        <div style="font-weight: 500; color: var(--adm-ink);">{{ $sign->word }}</div>
                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);">
                            <span class="adm-mono">{{ $sign->label }}</span>
                            @if($sign->meaning_en) &middot; {{ $sign->meaning_en }} @endif
                        </div>
                    </td>
                    <td style="color: var(--adm-text-secondary); font-size: 0.84rem;">
                        {{ $sign->category->name }}
                    </td>
                    <td style="color: var(--adm-text-secondary); font-size: 0.84rem;">
                        {{ $sign->is_static ? 'Statik' : 'Dinamik' }}
                    </td>
                    <td>
                        @if($sign->video)
                            <span class="adm-status"><span class="adm-dot adm-dot--ok"></span>Ada</span>
                        @else
                            <span class="adm-status"><span class="adm-dot adm-dot--muted"></span>Tiada</span>
                        @endif
                    </td>
                    <td style="font-size: 0.8125rem; max-width: 280px; color: var(--adm-text-muted);">
                        {{ Str::limit($sign->instructions, 60) }}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <button type="button" class="adm-row-action" command="show-modal" commandfor="edit-sign-{{ $sign->id }}">
                            Edit
                        </button>

                        <dialog id="edit-sign-{{ $sign->id }}" class="adm-dialog" closedby="any" aria-labelledby="edit-sign-title-{{ $sign->id }}" style="text-align: left;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                <h2 id="edit-sign-title-{{ $sign->id }}" style="font-size: 1.05rem; margin: 0;">Kemaskini isyarat: {{ $sign->word }}</h2>
                                <button type="button" class="adm-row-action" command="close" commandfor="edit-sign-{{ $sign->id }}" aria-label="Tutup">&times;</button>
                            </div>
                            <form action="{{ route('admin.signs.update', $sign) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label class="form-label" for="word-{{ $sign->id }}">Perkataan (Bahasa Melayu)</label>
                                    <input type="text" id="word-{{ $sign->id }}" name="word" value="{{ $sign->word }}" class="form-input" required autofocus>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="meaning-{{ $sign->id }}">Maksud (Bahasa Inggeris)</label>
                                    <input type="text" id="meaning-{{ $sign->id }}" name="meaning_en" value="{{ $sign->meaning_en }}" class="form-input">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="static-{{ $sign->id }}">Jenis isyarat</label>
                                    <select id="static-{{ $sign->id }}" name="is_static" class="form-select">
                                        <option value="1" {{ $sign->is_static ? 'selected' : '' }}>Statik (pegun)</option>
                                        <option value="0" {{ !$sign->is_static ? 'selected' : '' }}>Dinamik (pergerakan / urutan langkah)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="inst-{{ $sign->id }}">Panduan kedudukan jari</label>
                                    <textarea id="inst-{{ $sign->id }}" name="instructions" rows="3" class="form-textarea">{{ $sign->instructions }}</textarea>
                                </div>

                                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                                    <button type="button" class="btn btn-outline btn-sm" command="close" commandfor="edit-sign-{{ $sign->id }}">Batal</button>
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan perubahan</button>
                                </div>
                            </form>
                        </dialog>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--adm-text-muted);">
                        Tiada isyarat dijumpai
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 20px;">
    {{ $signs->links() }}
</div>
@endsection
