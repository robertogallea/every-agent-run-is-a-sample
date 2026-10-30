#!/usr/bin/env bash
set -e
python3 - <<'PY'
p='app/Http/Controllers/PostController.php'; s=open(p).read()
s=s.replace("""            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),""","""            'slug' => $this->uniqueSlug($data['title']),""")
s=s.rstrip()[:-1]+"""
    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $n = 2;

        while (Post::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
"""
open(p,'w').write(s)
PY
