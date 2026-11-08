#!/usr/bin/env python3
"""Read-only JSON-LD inventory of the captured public HTML. Not a WordPress boot."""
import json
import os
import re
from collections import Counter

CAP = "/tmp/ww-captures"
OUT = "/workspace/phase4/jsonld-inventory.json"


def types_of(raw):
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        return ["UNPARSED"]
    found = []

    def walk(node):
        if isinstance(node, dict):
            t = node.get("@type")
            if isinstance(t, str):
                found.append(t)
            elif isinstance(t, list):
                found.extend(str(x) for x in t)
            for v in node.values():
                walk(v)
        elif isinstance(node, list):
            for v in node:
                walk(v)

    walk(data)
    return found


def classify(tag, head_end):
    pos = tag["pos"]
    attrs = tag["attrs"].lower()
    if "rank-math-schema" in attrs:
        return "rankmath-head"
    if pos < head_end:
        return "head-other"
    return "in-content"


def main():
    pages = []
    for name in sorted(os.listdir(CAP)):
        if not name.endswith(".html"):
            continue
        html = open(os.path.join(CAP, name), errors="replace").read()
        head_end = html.find("</head>")
        if head_end < 0:
            head_end = 0
        scripts = []
        for m in re.finditer(
            r'<script\b([^>]*)type="application/ld\+json"([^>]*)>(.*?)</script>',
            html,
            re.S,
        ):
            attrs = (m.group(1) or "") + (m.group(2) or "")
            raw = m.group(3).strip()
            scripts.append(
                {
                    "pos": m.start(),
                    "attrs": attrs,
                    "where": classify({"pos": m.start(), "attrs": attrs}, head_end),
                    "types": types_of(raw),
                    "bytes": len(raw),
                }
            )
        pages.append(
            {
                "file": name,
                "scripts": len(scripts),
                "byWhere": dict(Counter(s["where"] for s in scripts)),
                "blocks": [
                    {"where": s["where"], "types": s["types"], "bytes": s["bytes"]}
                    for s in scripts
                ],
            }
        )
    olsp = next(p for p in pages if p["file"] == "blog_olsp-system-review-2026.html")
    summary = {
        "capturedHtmlFixture": True,
        "wordpressBoot": False,
        "pages": len(pages),
        "scripts": sum(p["scripts"] for p in pages),
        "where": dict(
            Counter(
                block["where"] for page in pages for block in page["blocks"]
            )
        ),
        "olsp": olsp,
        "pagesDetail": pages,
    }
    with open(OUT, "w") as fh:
        json.dump(summary, fh, indent=2)
        fh.write("\n")
    print(
        json.dumps(
            {
                "pages": summary["pages"],
                "scripts": summary["scripts"],
                "where": summary["where"],
                "olsp": olsp["blocks"],
            },
            indent=2,
        )
    )


if __name__ == "__main__":
    main()
