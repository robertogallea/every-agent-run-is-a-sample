#!/usr/bin/env bash
set -e
python3 - <<'PY'
p='app/Models/Post.php'; s=open(p).read()
s=s.replace("$query->whereNotNull('published_at');","$query->whereNotNull('published_at')->where('published_at', '<=', now());")
open(p,'w').write(s)
p='app/Http/Controllers/PostController.php'; s=open(p).read()
s=s.replace("abort_unless($post->published_at !== null, 404);","abort_unless($post->published_at !== null && $post->published_at->isPast(), 404);")
open(p,'w').write(s)
PY
