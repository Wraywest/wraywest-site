#!/usr/bin/env python3
"""Invariant tests for unpublished proposed HTML. Separate from template ALL PASS."""

import json
import os
import re
from html import unescape

SRC = "/tmp/ww-captures"
OUT = "/workspace/phase5/fixtures/proposed"
REPORT = "/workspace/phase5/fixtures/proposed-tests.json"

BANNED = (
    "46.8",
    "$234.65",
    "2.7x",
    "434%",
    "4.24%",
    "207 million",
    "$214 billion",
    "20-40%",
    "27,000",
    "49.29%",
    "57.55",
    "while you sleep",
    "under 200 people",
    "10-37%",
    "28-35%",
    "cfpb recommends",
    "3-7%",
    "10-15%",
    "541%",
    "0.2% of",
    "$254 billion",
    "100 million users",
    "88.2%",
    "68% of creators cite",
    "meal prep for busy",
    "unlocked my revenue",
    "highest-leverage",
    "$36 to $50",
    "21% do not measure",
)


def entry(html):
    i = html.find('class="entry-content"')
    j = html.find('class="ww-related"', i)
    if j < 0:
        j = html.find("<footer", i)
    if j < 0:
        j = len(html)
    return html[i:j]


def plain(html):
    html = re.sub(r"<script[\s\S]*?</script>", " ", html, flags=re.I)
    html = re.sub(r"<style[\s\S]*?</style>", " ", html, flags=re.I)
    html = re.sub(r"<[^>]+>", " ", html)
    return re.sub(r"\s+", " ", unescape(html)).strip()


def tags(html, name):
    return re.findall(r"<{}\b[^>]*>".format(name), html, flags=re.I)


def visible_faq(html):
    found = {}
    for match in re.finditer(r"<details\b([^>]*)>([\s\S]*?)</details>", html, flags=re.I):
        attrs, body = match.group(1), match.group(2)
        if "ww-toc" in attrs or "data-ww-toc" in attrs:
            continue
        summary = re.search(r"<summary[^>]*>([\s\S]*?)</summary>", body, flags=re.I)
        if not summary:
            continue
        question = plain(summary.group(1))
        if question.lower().startswith("in this article"):
            continue
        found[question] = plain(body[summary.end():])
    return found


def json_faq(html):
    found = {}
    for match in re.finditer(r"<script\b[^>]*type=\"application/ld\+json\"[^>]*>([\s\S]*?)</script>", html, flags=re.I):
        raw = match.group(0)
        if "FAQPage" not in raw or "rank-math-schema" in raw:
            continue
        try:
            data = json.loads(match.group(1))
        except json.JSONDecodeError:
            continue
        if data.get("@type") != "FAQPage":
            continue
        for entity in data.get("mainEntity", []):
            found[plain(entity.get("name", ""))] = plain(entity.get("acceptedAnswer", {}).get("text", ""))
    return found


def main():
    results = []
    failures = 0
    for fn in sorted(os.listdir(OUT)):
        if not fn.endswith(".html"):
            continue
        before = open(os.path.join(SRC, fn), encoding="utf-8", errors="replace").read()
        after = open(os.path.join(OUT, fn), encoding="utf-8", errors="replace").read()
        problems = []
        for name in ("input", "textarea", "select", "button"):
            if tags(before, name) != tags(after, name):
                problems.append("control tags changed: " + name)
        scripts_before = re.findall(r"<script\b[\s\S]*?</script>", before, flags=re.I)
        scripts_after = re.findall(r"<script\b[\s\S]*?</script>", after, flags=re.I)
        if len(scripts_before) != len(scripts_after):
            problems.append("script count {} -> {}".format(len(scripts_before), len(scripts_after)))
        else:
            for index, (old, new) in enumerate(zip(scripts_before, scripts_after)):
                if "FAQPage" in old or "FAQPage" in new:
                    continue
                if old != new:
                    problems.append("non-FAQ script {} changed".format(index))
        styles_before = re.findall(r"<style\b[\s\S]*?</style>", before, flags=re.I)
        styles_after = re.findall(r"<style\b[\s\S]*?</style>", after, flags=re.I)
        if styles_before != styles_after:
            problems.append("style block changed")
        if before.count("$810.95") != after.count("$810.95"):
            problems.append("$810.95 count changed")
        # Visible receipt text, outside scripts.
        def visible_count(doc, needle):
            return re.sub(r"<script[\s\S]*?</script>", " ", doc, flags=re.I).count(needle)
        if visible_count(before, "$17.77") != visible_count(after, "$17.77"):
            problems.append("$17.77 visible count changed")
        if 'href="https://www.make.com/" rel="sponsored noopener"' in before:
            if 'href="https://www.make.com/" rel="sponsored noopener"' not in after:
                problems.append("Make sponsored rel lost")
        if re.search(r"href=\"https://kit\.com/[^\"]*\"[^>]*sponsored|sponsored[^>]*href=\"https://kit\.com/", after, flags=re.I):
            problems.append("Kit link still sponsored")
        def internal(doc):
            return set(re.findall(r'href="(/[^"]*)"', entry(doc)))
        extra = internal(after) - internal(before)
        if extra:
            problems.append("new internal links: " + ", ".join(sorted(extra)[:8]))
        text = plain(entry(after)).lower()
        hits = [token for token in BANNED if token.lower() in text]
        if hits:
            problems.append("unresolved figure still in entry: " + ", ".join(hits))
        vis = visible_faq(after)
        js = json_faq(after)
        for question, answer in vis.items():
            if question in js and js[question] != answer:
                problems.append("FAQ mismatch: " + question[:80])
        if "quit-9-to-5" in fn:
            for value in ('value="75000"', 'value="28"', 'value="30"', 'value="5"', 'value="6"'):
                if after.count(value) != before.count(value):
                    problems.append("calculator default changed: " + value)
        if "lead-magnet-builder" in fn:
            if tags(before, "input") != tags(after, "input"):
                problems.append("lead magnet inputs changed")
        ratio = len(plain(entry(after))) / max(1, len(plain(entry(before))))
        if ratio < 0.35:
            problems.append("entry shrunk to {:.0%}".format(ratio))
        status = "PASS" if not problems else "FAIL"
        if problems:
            failures += 1
        results.append({"page": fn, "status": status, "kept": round(ratio, 3), "problems": problems})
        print(status, fn, "kept", round(ratio, 2), "; ".join(problems))
    open(REPORT, "w", encoding="utf-8").write(json.dumps({"failures": failures, "results": results}, indent=2))
    print("FAILURES", failures)


if __name__ == "__main__":
    main()
