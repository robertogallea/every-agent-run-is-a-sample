"""Record which hidden tests already pass on the untouched app (guards) for each task."""

import shutil
import subprocess
import sys
import tempfile
import xml.etree.ElementTree as ET
from pathlib import Path

root = Path(__file__).resolve().parent.parent
for task_dir in sorted((root / "harness" / "tasks").iterdir()):
    with tempfile.TemporaryDirectory() as tmp:
        work = Path(tmp) / "app"
        shutil.copytree(root / "app-template", work, symlinks=True)
        (work / "tests" / "Hidden").mkdir()
        for f in (task_dir / "hidden").glob("*.php"):
            shutil.copy(f, work / "tests" / "Hidden")
        subprocess.run(["./vendor/bin/pest", "tests/Hidden", "--log-junit", "j.xml"], cwd=work, capture_output=True)
        passing = [c.get("name") for c in ET.parse(work / "j.xml").iter("testcase")
                   if c.find("failure") is None and c.find("error") is None]
    (task_dir / "guards.txt").write_text("\n".join(passing) + "\n")
    print(task_dir.name, "guards:", passing)
