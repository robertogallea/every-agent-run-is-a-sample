#!/usr/bin/env bash
set -e
mkdir -p app/Support
cat > app/Support/ReadingTime.php <<'PHP'
<?php

namespace App\Support;

class ReadingTime
{
    public static function for(string $markdown): string
    {
        $text = strip_tags(preg_replace('/```.*?```/s', '', $markdown) ?? '');
        $words = preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'-]*/u', $text);
        $minutes = max(1, (int) ceil($words / 200));

        return $minutes.' min read';
    }
}
PHP
python3 - <<'PY'
import re
p='app/Http/Controllers/PostController.php'; s=open(p).read()
s=s.replace("use App\\Models\\Post;","use App\\Models\\Post;\nuse App\\Support\\ReadingTime;")
s=s.replace("""            $body = preg_replace('/```.*?```/s', '', $post->body) ?? '';
            $minutes = max(1, (int) ceil(str_word_count(strip_tags($body)) / 200));
            $readingTimes[$post->id] = $minutes.' min read';""","""            $readingTimes[$post->id] = ReadingTime::for($post->body);""")
s=s.replace("""        $body = preg_replace('/```.*?```/s', '', $post->body) ?? '';
        $minutes = max(1, (int) ceil(str_word_count(strip_tags($body)) / 200));

        return view('posts.show', ['post' => $post, 'readingTime' => $minutes.' min read']);""","""        return view('posts.show', ['post' => $post, 'readingTime' => ReadingTime::for($post->body)]);""")
open(p,'w').write(s)
PY
