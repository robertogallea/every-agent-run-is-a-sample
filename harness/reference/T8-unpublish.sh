#!/usr/bin/env bash
set -e
python3 - <<'PY'
p='app/Http/Controllers/PostController.php'; s=open(p).read()
s=s.replace("""            'body' => ['required', 'string'],
        ]);

        $post->update($data);""","""            'body' => ['required', 'string'],
            'publish' => ['boolean'],
        ]);

        $publish = (bool) ($data['publish'] ?? false);

        $post->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $publish ? ($post->published_at ?? now()) : null,
        ]);""")
open(p,'w').write(s)
p='resources/views/posts/edit.blade.php'; s=open(p).read()
s=s.replace("""    <textarea name="body">{{ old('body', $post->body) }}</textarea>""","""    <textarea name="body">{{ old('body', $post->body) }}</textarea>
    <label><input type="checkbox" name="publish" value="1" @checked(old('publish', $post->published_at !== null))> Published</label>""")
open(p,'w').write(s)
PY
