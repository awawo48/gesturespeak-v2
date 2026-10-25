@extends('layouts.app', ['title' => 'Pusat Kawalan Pentadbir - GestureSpeak'])

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.8rem;">
            <h1 style="font-size: 2rem; margin: 0;">Panel Kawalan Pentadbir (Admin)</h1>
            <span class="role-badge admin">Pentadbir Sistem</span>
        </div>
        <p style="color: var(--text-muted); margin-top: 0.3rem;">
            Pantau aktiviti keseluruhan sistem, urus akaun pengguna, kurikulum isyarat BIM dan studio AI.
        </p>
    </div>

    <a href="{{ route('admin.ai.studio') }}" class="btn btn-primary">
        <i class="fa-solid fa-robot"></i> Buka Studio Latihan AI
    </a>
</div>

{{-- METRICS SUMMARY CARDS --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.2rem; margin-bottom: 2.5rem;">
    <div class="card" style="border-left: 5px solid var(--primary);">
        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Jumlah Dewasa</div>
        <div style="font-size: 2.2rem; font-weight: 900; color: var(--primary); margin: 0.2rem 0;">{{ $stats['total_users'] }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $stats['total_parents'] }} Ibu Bapa &bull; {{ $stats['total_teachers'] }} Guru</div>
    </div>

    <div class="card" style="border-left: 5px solid var(--secondary);">
        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Jumlah Murid</div>
        <div style="font-size: 2.2rem; font-weight: 900; color: var(--secondary); margin: 0.2rem 0;">{{ $stats['total_students'] }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Profil Kanak-kanak</div>
    </div>

    <div class="card" style="border-left: 5px solid #ffa502;">
        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Isyarat Dikuasai</div>
        <div style="font-size: 2.2rem; font-weight: 900; color: #ffa502; margin: 0.2rem 0;">{{ $stats['total_mastered'] }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Daripada {{ $stats['total_signs'] }} isyarat BIM</div>
    </div>

    <div class="card" style="border-left: 5px solid var(--success);">
        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Sesi Kuiz Selesai</div>
        <div style="font-size: 2.2rem; font-weight: 900; color: var(--success); margin: 0.2rem 0;">{{ $stats['total_quizzes'] }}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">Percubaan gamifikasi</div>
    </div>
</div>

{{-- RECENT STUDENTS & RECENT QUIZZES --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">

    {{-- RECENT STUDENTS --}}
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
            <h3 style="font-size: 1.2rem; margin: 0;">Murid Terkini Didaftar</h3>
            <a href="{{ route('admin.students') }}" style="color: var(--primary); font-size: 0.85rem; font-weight: 800;">
                Lihat Semua &rarr;
            </a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.8rem;">
            @foreach($recentStudents as $student)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.8rem;">{{ $student->avatarEmoji() }}</span>
                        <div>
                            <div style="font-weight: 800; font-size: 1rem;">{{ $student->name }}</div>
                            <div style="font-size: 0.8rem; color: var(--primary); font-weight: 700;">
                                Kod: {{ $student->student_code }}
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="star-pill" style="font-size: 0.8rem; padding: 0.2rem 0.6rem;">
                            ⭐ {{ $student->stars }}
                        </span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                            {{ $student->guardians->count() }} Penjaga
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- RECENT QUIZ ATTEMPTS --}}
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
            <h3 style="font-size: 1.2rem; margin: 0;">Percubaan Kuiz Terkini</h3>
            <span style="color: var(--text-muted); font-size: 0.85rem;">Masa Nyata</span>
        </div>

        @if($recentQuizzes->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.9rem;">Belum ada aktiviti kuiz.</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                @foreach($recentQuizzes as $q)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md);">
                        <div>
                            <div style="font-weight: 800; font-size: 0.95rem;">
                                {{ $q->student->name ?? 'Murid' }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                Kuiz {{ $q->category->name ?? 'Umum' }} &bull; {{ $q->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: #f39c12; font-weight: 900; font-size: 0.9rem;">
                                @for($i = 0; $i < $q->stars; $i++) ⭐ @endfor
                            </div>
                            <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary);">
                                {{ $q->score }}/{{ $q->total }} mata
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
