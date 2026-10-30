#!/usr/bin/env bash
set -e
cat > database/migrations/2026_02_01_000000_add_deleted_at_to_posts_table.php <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->softDeletes());
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
PHP
python3 - <<'PY'
p='app/Models/Post.php'; s=open(p).read()
s=s.replace("use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;","use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;")
s=s.replace("    use HasFactory;","    use HasFactory, SoftDeletes;")
open(p,'w').write(s)
p='app/Policies/PostPolicy.php'; s=open(p).read()
s=s.rstrip()[:-1]+"""
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
"""
open(p,'w').write(s)
p='app/Http/Controllers/PostController.php'; s=open(p).read()
s=s.rstrip()[:-1]+"""
    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index');
    }

    public function restore(Post $post): RedirectResponse
    {
        Gate::authorize('restore', $post);

        $post->restore();

        return redirect()->route('posts.show', $post);
    }
}
"""
open(p,'w').write(s)
p='routes/web.php'; s=open(p).read()
s=s.replace("""    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');""","""    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{post}/restore', [PostController::class, 'restore'])->withTrashed()->name('posts.restore');""")
open(p,'w').write(s)
PY
