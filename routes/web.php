<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiTrainingController;
use App\Http\Controllers\Auth\AdultAuthController;
use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TtsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GestureSpeak Web Routes
|--------------------------------------------------------------------------
| - Option 1: Student Login (Student Code + PIN)
| - Option 2: Adult Login (Parent / Teacher / Admin via Email + Password)
*/

// Home / Landing
Route::get('/', [HomeController::class, 'index'])->name('home');

// ==========================================
// Option 1: Student (Child) Authentication
// ==========================================
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/login', [StudentAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [StudentAuthController::class, 'login']);
    Route::post('/logout', [StudentAuthController::class, 'logout'])->name('logout');
});

// ==========================================
// Option 2: Adult Authentication (Parent, Teacher, Admin)
// ==========================================
Route::get('/login', [AdultAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AdultAuthController::class, 'login']);
Route::get('/register', [AdultAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AdultAuthController::class, 'register']);
Route::post('/logout', [AdultAuthController::class, 'logout'])->name('logout');

// ==========================================
// Child Learning Pipeline (Protected by student guard)
// ==========================================
Route::middleware('auth:student')->name('learn.')->prefix('learn')->group(function () {
    Route::get('/', [LearningController::class, 'hub'])->name('hub');
    Route::get('/category/{category}', [LearningController::class, 'category'])->name('category');
    Route::get('/category/{category}/{sign}', [LearningController::class, 'sign'])->name('sign');
    Route::post('/sign/{sign}/progress', [LearningController::class, 'recordProgress'])->name('progress.record');
});

Route::middleware('auth:student')->name('quiz.')->prefix('quiz')->group(function () {
    Route::get('/', [QuizController::class, 'index'])->name('index');
    Route::get('/{category}', [QuizController::class, 'play'])->name('play');
    Route::post('/{category}/submit', [QuizController::class, 'submit'])->name('submit');
});

// ==========================================
// Parent & Teacher Portal (Home-School Collaboration)
// ==========================================
// Waiting page for teachers not yet approved by Admin
Route::middleware(['auth:web', 'role:parent,teacher'])->get('/guardian/pending', [GuardianController::class, 'pending'])->name('guardian.pending');

Route::middleware(['auth:web', 'role:parent,teacher', 'approved'])->name('guardian.')->prefix('guardian')->group(function () {
    Route::get('/dashboard', [GuardianController::class, 'dashboard'])->name('dashboard');
    Route::post('/children', [GuardianController::class, 'storeChild'])->name('child.store');
    Route::post('/link', [GuardianController::class, 'linkChild'])->name('child.link');
    Route::get('/child/{student}', [GuardianController::class, 'showChild'])->name('child.show');
    Route::post('/child/{student}/start', [GuardianController::class, 'startLearning'])->name('child.start');
    Route::put('/child/{student}/profile', [GuardianController::class, 'updateChild'])->name('child.update');
    Route::delete('/child/{student}', [GuardianController::class, 'unlinkChild'])->name('child.unlink');
    Route::delete('/child/{student}/destroy', [GuardianController::class, 'destroyChild'])->name('child.destroy');

    // Teacher-only: feedback note + weekly homework module
    Route::post('/child/{student}/teacher-note', [GuardianController::class, 'saveTeacherNote'])
        ->middleware('role:teacher')
        ->name('child.note');
});

// AI Dataset Endpoint (Accessible for student learning & camera recognition)
Route::get('/ai/dataset', [AiTrainingController::class, 'dataset'])->name('ai.public_dataset');

// Bahasa Melayu TTS Audio Endpoint (ElevenLabs + Disk Cache + Malay Fallback)
Route::get('/tts', [TtsController::class, 'speak'])->name('tts.speak');

// ==========================================
// Admin Control Center (Protected by admin role)
// ==========================================
Route::middleware(['auth:web', 'role:admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::patch('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('users.approve');
    Route::patch('/users/{user}/reject', [AdminController::class, 'rejectUser'])->name('users.reject');
    Route::get('/students', [AdminController::class, 'students'])->name('students');
    Route::delete('/students/{student}', [AdminController::class, 'deleteStudent'])->name('students.delete');
    Route::get('/signs', [AdminController::class, 'signs'])->name('signs');
    Route::put('/signs/{sign}', [AdminController::class, 'updateSign'])->name('signs.update');

    // AI Training Studio
    Route::get('/ai-studio', [AiTrainingController::class, 'studio'])->name('ai.studio');
    Route::post('/ai-studio/sample', [AiTrainingController::class, 'storeSample'])->name('ai.sample.store');
    Route::post('/ai-studio/sign', [AiTrainingController::class, 'storeSign'])->name('ai.sign.store');
    Route::get('/ai-studio/export', [AiTrainingController::class, 'exportCsv'])->name('ai.export');
    Route::post('/ai-studio/import', [AiTrainingController::class, 'importCsv'])->name('ai.import');
    Route::get('/ai-studio/dataset', [AiTrainingController::class, 'dataset'])->name('ai.dataset');
    Route::post('/ai-studio/clear', [AiTrainingController::class, 'clearSamples'])->name('ai.clear');
});
