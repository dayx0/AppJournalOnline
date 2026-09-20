<?php

use App\Http\Controllers\Admin\ClassRoomController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JournalMonitorController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\Mpk\DashboardController as MpkDashboardController;
use App\Http\Controllers\Mpk\JournalValidationController;
use App\Http\Controllers\Mpk\NotificationController as MpkNotificationController;
use App\Http\Controllers\Mpk\RekapController as MpkRekapController;
use App\Http\Controllers\NotificationController as InboxNotificationController;
use App\Models\ClassRoom;
use App\Models\Journal;
use App\Models\Subject;
use App\Services\JournalNotificationService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->isMpk()) {
            return redirect()->route('mpk.dashboard');
        }

        $guruId = auth()->id();

        $recentJournals = Journal::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', $guruId)
            ->latest()
            ->take(3)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => [
                'totalJurnal' => Journal::where('guru_id', $guruId)->count(),
                'totalKelas' => ClassRoom::count(),
                'totalMapel' => Subject::count(),
                'jurnalMingguIni' => Journal::where('guru_id', $guruId)
                    ->where('tanggal', '>=', now()->subDays(7)->toDateString())
                    ->count(),
            ],
            'recentJournals' => $recentJournals,
        ]);
    })->name('dashboard');

    Route::get('/notifikasi', function () {
        // MPK punya halaman sendiri agar href mengarah ke /mpk/jurnal/{id}
        if (auth()->user()->isMpk()) {
            return redirect()->route('mpk.notifikasi');
        }

        $guruId = auth()->id();
        $journals = Journal::with(['kelas', 'mataPelajaran', 'validator'])
            ->where('guru_id', $guruId)
            ->latest()
            ->take(15)
            ->get();

        return Inertia::render('notifikasi', [
            'journals' => $journals,
            'counts' => [
                'pending' => Journal::where('guru_id', $guruId)->where('status', 'pending')->count(),
                'revisi' => Journal::where('guru_id', $guruId)->where('status', 'revisi')->count(),
                'divalidasi' => Journal::where('guru_id', $guruId)->where('status', 'divalidasi')->count(),
            ],
            'unreadKeys' => JournalNotificationService::unreadKeys($guruId),
        ]);
    })->name('notifikasi');

    Route::post('/notifikasi/baca', [InboxNotificationController::class, 'read'])->name('notifikasi.read');
    Route::post('/notifikasi/baca-semua', [InboxNotificationController::class, 'readAll'])->name('notifikasi.read-all');

    Route::inertia('/profil', 'profil')->name('profil');
});
Route::middleware(['auth', 'isGuruOrAdmin'])->group(function () {
    Route::get('/jurnal', [JournalController::class, 'index'])->name('jurnal.index');
    Route::get('/jurnal/create', [JournalController::class, 'create'])
        ->name('jurnal.create');
    Route::get('/jurnal/{journal}/edit', [JournalController::class, 'edit'])
        ->name('jurnal.edit');
    Route::get('/jurnal/{journal}/absensi', [JournalController::class, 'absensi'])
        ->name('jurnal.absensi');
    Route::put('/jurnal/{journal}/absensi', [JournalController::class, 'updateAbsensi'])
        ->name('jurnal.absensi.update');
    Route::get('/jurnal/{journal}', [JournalController::class, 'show'])
        ->name('jurnal.show');
    Route::post('/jurnal', [JournalController::class, 'store'])
        ->name('jurnal.store');
    Route::put('/jurnal/{journal}', [JournalController::class, 'update'])
        ->name('jurnal.update');
    Route::delete('/jurnal/{journal}', [JournalController::class, 'destroy'])
        ->name('jurnal.destroy');
});

Route::middleware(['auth', 'verified', 'isAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('/kelas', [ClassRoomController::class, 'index'])->name('kelas.index');
    Route::post('/kelas', [ClassRoomController::class, 'store'])->name('kelas.store');
    Route::put('/kelas/{kls}', [ClassRoomController::class, 'update'])->name('kelas.update');
    Route::delete('/kelas/{kls}', [ClassRoomController::class, 'destroy'])->name('kelas.destroy');

    Route::get('/mapel', [SubjectController::class, 'index'])->name('mapel.index');
    Route::post('/mapel', [SubjectController::class, 'store'])->name('mapel.store');
    Route::put('/mapel/{subject}', [SubjectController::class, 'update'])->name('mapel.update');
    Route::delete('/mapel/{subject}', [SubjectController::class, 'destroy'])->name('mapel.destroy');

    Route::get('/jurnal', [JournalMonitorController::class, 'index'])->name('jurnal.index');
});

Route::middleware(['auth', 'verified', 'isMpk'])->prefix('mpk')->name('mpk.')->group(function () {
    Route::get('/dashboard', [MpkDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifikasi', [MpkNotificationController::class, 'index'])->name('notifikasi');
    Route::get('/rekap', [MpkRekapController::class, 'index'])->name('rekap.index');
    Route::get('/rekap/export', [MpkRekapController::class, 'export'])->name('rekap.export');
    Route::get('/jurnal', [JournalValidationController::class, 'index'])->name('jurnal.index');
    Route::get('/jurnal/{journal}', [JournalValidationController::class, 'show'])->name('jurnal.show');
    Route::post('/jurnal/{journal}/validate', [JournalValidationController::class, 'validate'])->name('jurnal.validate');
    Route::post('/jurnal/{journal}/revisi', [JournalValidationController::class, 'revisi'])->name('jurnal.revisi');
});

require __DIR__.'/settings.php';
