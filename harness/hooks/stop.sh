#!/usr/bin/env bash
# Stop hook: the agent can't finish while the visible test suite fails.
cat > /dev/null
echo "$(date +%s) stop fired" >> "${HOOK_LOG:-/dev/null}"
if ! out=$(./vendor/bin/pest 2>&1); then
  echo "$(date +%s) stop BLOCK" >> "${HOOK_LOG:-/dev/null}"
  printf 'The test suite is failing. Fix the code before finishing.\n%s\n' "$(printf '%s' "$out" | tail -c 4000)" >&2
  exit 2
fi
exit 0
