#!/usr/bin/env bash
# PostToolUse hook: after an edit to a PHP file, report Pint and PHPStan problems back to the agent.
input=$(cat)
file=$(printf '%s' "$input" | python3 -c 'import json,sys; d=json.load(sys.stdin); print(d.get("tool_input",{}).get("file_path",""))')
echo "$(date +%s) post-edit fired file=$file" >> "${HOOK_LOG:-/dev/null}"
case "$file" in *.php) ;; *) exit 0 ;; esac
[ -f "$file" ] || exit 0
out=""
pint=$(./vendor/bin/pint --test "$file" 2>&1) || out+="Pint style problems in $file (run ./vendor/bin/pint to fix):"$'\n'"$pint"$'\n'
case "$file" in */app/*|app/*)
  stan=$(./vendor/bin/phpstan analyse --no-progress --memory-limit=1G --error-format=raw "$file" 2>&1) || out+="PHPStan errors:"$'\n'"$stan"$'\n' ;;
esac
if [ -n "$out" ]; then
  echo "$(date +%s) post-edit BLOCK" >> "${HOOK_LOG:-/dev/null}"
  printf '%s' "$out" >&2
  exit 2
fi
exit 0
