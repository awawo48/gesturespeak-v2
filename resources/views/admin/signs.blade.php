@extends('layouts.app', ['title' => 'Pengurusan Isyarat BIM - Admin'])

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; margin: 0;">Kurikulum & Isyarat BIM</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Senarai 90 isyarat Bahasa Isyarat Malaysia merentasi 7 modul</p>
    </div>

    {{-- SEARCH & FILTER FORM --}}
    <form method="GET" action="{{ route('admin.signs') }}" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-input"
            placeholder="Cari isyarat..."
            style="width: 180px; padding: 0.5rem 0.8rem; font-size: 0.9rem;"
        >
        <select name="category" class="form-select" style="width: 160px; padding: 0.5rem 0.8rem; font-size: 0.9rem;">
            <option value="">Semua Modul</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $selectedCategory == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Tapis</button>
    </form>
</div>

<div class="card" style="padding: 0; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                <th style="padding: 1rem 1.2rem;">Isyarat & Label</th>
                <th style="padding: 1rem 1.2rem;">Modul</th>
                <th style="padding: 1rem 1.2rem;">Jenis Isyarat</th>
                <th style="padding: 1rem 1.2rem;">Status Media</th>
                <th style="padding: 1rem 1.2rem;">Panduan Kedudukan Jari</th>
                <th style="padding: 1rem 1.2rem; text-align: right;">Kemaskini</th>
            </tr>
        </thead>
        <tbody>
            @forelse($signs as $sign)
                <tr style="border-bottom: 1px solid var(--border-light);" x-data="{ open: false }">
                    <td style="padding: 1rem 1.2rem;">
                        <strong style="font-size: 1.1rem; color: var(--primary);">{{ $sign->word }}</strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            Label AI: <code>{{ $sign->label }}</code> &bull; {{ $sign->meaning_en }}
                        </div>
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        <span style="font-weight: 800; font-size: 0.85rem; color: {{ $sign->category->color }};">
                            {{ $sign->category->name }}
                        </span>
                    </td>
                    <td style="padding: 1rem 1.2rem;">
                        @if($sign->is_static)
                            <span class="role-badge" style="background: #e0f2fe; color: #0284c7;">Statik (Kamera AI)</span>
                        @else
                            <span class="role-badge" style="background: #fef3c7; color: #d97706;">Dinamik (Pergerakan)</span>
                        @endif
                    </td>
                    <td style="padding: 1rem 1.2rem; font-size: 0.85rem;">
                        @if($sign->video)
                            <span style="color: #16a34a;"><i class="fa-solid fa-circle-check"></i> Video Ada</span>
                        @else
                            <span style="color: var(--text-muted);"><i class="fa-solid fa-minus"></i> Tiada Video</span>
                        @endif
                    </td>
                    <td style="padding: 1rem 1.2rem; font-size: 0.85rem; max-width: 280px; color: var(--text-dark);">
                        {{ Str::limit($sign->instructions, 60) }}
                    </td>
                    <td style="padding: 1rem 1.2rem; text-align: right;">
                        <button type="button" @click="open = !open" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>

                        {{-- INLINE EDIT MODAL/DRAWER --}}
                        <div x-show="open" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 300; align-items: center; justify-content: center; padding: 1rem;" :style="{ display: open ? 'flex' : 'none' }">
                            <div class="card" style="max-width: 500px; width: 100%; text-align: left;" @click.away="open = false">
                                <h3 style="font-size: 1.3rem; margin-bottom: 1rem;">Kemaskini Isyarat: {{ $sign->word }}</h3>
                                <form action="{{ route('admin.signs.update', $sign) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-group">
                                        <label class="form-label">Perkataan (Bahasa Melayu):</label>
                                        <input type="text" name="word" value="{{ $sign->word }}" class="form-input" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Maksud (Bahasa Inggeris):</label>
                                        <input type="text" name="meaning_en" value="{{ $sign->meaning_en }}" class="form-input">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Jenis Isyarat:</label>
                                        <select name="is_static" class="form-select">
                                            <option value="1" {{ $sign->is_static ? 'selected' : '' }}>Statik (Boleh diimbas kamera AI)</option>
                                            <option value="0" {{ !$sign->is_static ? 'selected' : '' }}>Dinamik (Pergerakan)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Panduan Kedudukan Jari:</label>
                                        <textarea name="instructions" rows="3" class="form-textarea">{{ $sign->instructions }}</textarea>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end; gap: 0.8rem; margin-top: 1rem;">
                                        <button type="button" @click="open = false" class="btn btn-outline btn-sm">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Tiada isyarat dijumpai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1.5rem;">
    {{ $signs->links() }}
</div>
@endsection
