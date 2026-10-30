#!/usr/bin/env bash
set -e
python3 - <<'PY'
p='app/Models/User.php'; s=open(p).read()
s=s.rstrip()[:-1]+"""
    /** @return \\Illuminate\\Database\\Eloquent\\Casts\\Attribute<string, string> */
    protected function email(): \\Illuminate\\Database\\Eloquent\\Casts\\Attribute
    {
        return \\Illuminate\\Database\\Eloquent\\Casts\\Attribute::make(set: fn (string $value) => mb_strtolower($value));
    }
}
"""
open(p,'w').write(s)
p='app/Http/Controllers/Auth/LoginController.php'; s=open(p).read()
s=s.replace("""        if (! Auth::attempt($credentials""","""        $credentials['email'] = mb_strtolower($credentials['email']);

        if (! Auth::attempt($credentials""")
open(p,'w').write(s)
p='app/Http/Controllers/Auth/RegisterController.php'; s=open(p).read()
s=s.replace("""        $data = $request->validate([""","""        $request->merge(['email' => mb_strtolower((string) $request->input('email'))]);

        $data = $request->validate([""")
open(p,'w').write(s)
PY
./vendor/bin/pint -q app/Models/User.php
