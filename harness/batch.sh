#!/usr/bin/env bash
# Usage: harness/batch.sh <run-id-prefix> <reps> [parallel] [tasks...]
# Runs every task x config x rep combination, in random order, skipping runs already graded.
set -uo pipefail
PREFIX=$1 REPS=$2 PAR=${3:-4}; shift 3 || shift $#
H=$(cd "$(dirname "$0")" && pwd)
tasks=("$@"); [ ${#tasks[@]} -eq 0 ] && mapfile -t tasks < <(ls "$H/tasks")
for t in "${tasks[@]}"; do for c in A B C D; do for r in $(seq -w 1 "$REPS"); do
  echo "$t $c $PREFIX$r"
done; done; done | shuf --random-source=<(yes) | xargs -P "$PAR" -L 1 bash -c '"$0"/run.sh "$1" "$2" "$3" 2>&1 | tail -1' "$H"
