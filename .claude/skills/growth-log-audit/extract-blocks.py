#!/usr/bin/env python3
"""Prints the blocks of one kind from the growth log's daily files in a day range.

    extract-blocks.py <log dir> <from YYYY-MM-DD> <to YYYY-MM-DD> [heading pattern]

The heading pattern is a regular expression matched against `###` headings (default: `failed`,
the "What failed, what remains uncertain" block). Each block is printed under `## <day> — <work>`,
so `grep -c '^## '` over the output counts the blocks read.
"""
import pathlib
import re
import sys

log_dir, lo, hi = sys.argv[1], sys.argv[2], sys.argv[3]
pattern = re.compile(sys.argv[4] if len(sys.argv) > 4 else "failed")

for day in sorted(pathlib.Path(log_dir).glob("*.md")):
    if not (lo <= day.stem <= hi):
        continue
    lines = day.read_text().splitlines()
    work = "?"
    for i, line in enumerate(lines):
        if line.startswith("## "):
            work = line[3:]
        if line.startswith("### ") and pattern.search(line):
            body = []
            for rest in lines[i + 1:]:
                if re.match(r"#{1,3} ", rest):
                    break
                body.append(rest)
            print(f"\n## {day.stem} — {work}\n")
            print("\n".join(body).strip())
