#!/usr/bin/env python3
"""Build unpublished post_content proposals on isolated HTML fixtures.

Does not touch live WordPress, DNS, or the public captures in /tmp/ww-captures.
A removed figure is recorded as verification unavailable, not as false.
"""

import hashlib
import json
import os
import re
import shutil
from html import unescape

SRC = "/tmp/ww-captures"
OUT = "/workspace/phase5/fixtures/proposed"
MANIFEST = "/workspace/phase5/fixtures/proposed-manifest.json"

CLUSTER1 = [
    "blog_how-to-start-creator-business-no-audience.html",
    "blog_mindset-shift-creator-business.html",
    "blog_creator-burnout-truth.html",
    "blog_content-calendar-publish-working-full-time.html",
    "blog_solo-creator-metrics-that-matter.html",
    "blog_ab-testing-one-person-businesses.html",
    "blog_why-side-hustles-fail.html",
    "blog_profitable-creator-niches-full-time-employees-2026.html",
    "blog_find-your-creator-niche.html",
    "blog_second-income-product-blueprint.html",
    "blog_creator-business-blueprint-second-income-2026.html",
    "engine.html",
    "start-here.html",
    "quit-9-to-5-calculator.html",
]

EMAIL = [
    "blog_email-marketing-playbook-solo-creators.html",
    "blog_email-list-building-100-subscribers.html",
    "blog_lead-magnet-ideas.html",
    "blog_welcome-email-sequence-template.html",
    "blog_how-to-write-emails-people-want-to-open.html",
    "blog_email-deliverability-small-senders.html",
    "blog_convertkit-review-email-marketing-creators.html",
    "blog_convertkit-alternatives.html",
    "blog_systeme-vs-convertkit.html",
    "lead-magnet-builder.html",
]

BLOCK_HOSTS = (
    "demandsage.com",
    "apa.org/topics/research/multitasking",
    "founderreports.com",
    "convert.com",
    "encharge.io",
    "campaignmonitor.com",
    "growthengines.co",
    "researchnester.com",
    "hubspot.com/marketing-statistics",
    "orbitmedia.com",
    "precedenceresearch.com",
    "hootsuite.com/research",
    "sumo.com/stories",
    "simplybusiness.com",
    "socialinsider.io",
    "mailchimp.com/resources/email-marketing-benchmarks",
    "kornferry.com",
    "notion.com/blog/100-million",
    "whop.com/blog/side-hustle",
    "bankrate.com/loans/small-business/side-hustles",
    "circle.so/blog/creator-economy",
    "kit.com/reports",
    "optinmonster.com",
    "brightedge.com",
    "truelist.co",
    "creatorspotlight.co",
    "outlierkit.com",
    "beehiiv.com",
    "prnewswire.com",
    "autofaceless.ai",
    "emarketer.com",
    "keywordseverywhere.com",
    "houseofmarketers.com",
    "sqmagazine.co.uk",
    "gartner.com",
    "teleprompter.com",
    "luisazhou.com",
    "businessresearchinsights.com",
    "ezycourse.com",
    "spiralytics.com",
    "creatortrends.kajabi.com",
    "socialmediatoday.com",
    "kajabi.com/blog/state-of-the-creator-economy",
    "ziprecruiter",
    "emailtooltester",
    "myperfectresume",
    "linktree",
)

BANNED_TOKENS = (
    "46.8%",
    "$234.65",
    "2.7x",
    "$31,000",
    "$31k",
    "207 million",
    "45 million are professional",
    "45 million professionals",
    "434%",
    "4.24%",
    "0.59%",
    "$178.4",
    "$214 billion",
    "46.7%",
    "$44,000 a year",
    "$3,680",
    "71% of ceos",
    "71% of u.s. ceos",
    "20-40%",
    "20–40%",
    "3.5x",
    "3.5 times",
    "3 hours and 48",
    "35% more likely",
    "100 million users",
    "38% of american",
    "67% of them report",
    "$254 billion",
    "between 1% and 5%",
    "1.95%",
    "18% to 34%",
    "35% and 45%",
    "21% average",
    "0.50%",
    "77% of lean",
    "10x to 50x",
    "$39,273",
    "29.8 million",
    "$1.7 trillion",
    "0.2% of all websites",
    "0.2% of websites",
    "27,000",
    "49.29%",
    "541%",
    "25% to 40%",
    "26% of prematurely",
    "1 in 8",
    "57.55",
    "3.78%",
    "7.94%",
    "while you sleep",
    "unlocked my revenue",
    "sell passively",
    "highest-leverage",
    "meal prep for busy",
    "4,200 monthly",
    "five to six focused",
    "under 200 people",
    "3-7%",
    "10-15%",
    "68% of creators cite algorithmic",
    "40.1%",
    "30% of revenue",
    "37% of all email",
    "cfpb recommends",
    "3-6 months of expenses",
    "10-37%",
    "0-13.3%",
    "28-35%",
    "$30k-$150k",
    "$30k to $150k",
    "typical for $75k",
    "96% of",
    "96% earn",
    "pure passive income",
    "my first broadcast",
)

# Survey and benchmark assertions. Vendor plan prices and IRS lines are handled
# separately so a pricing comparison does not lose its spine without a source.
SURVEY_HOSTS = BLOCK_HOSTS


def md5(text):
    return hashlib.md5(text.encode("utf-8")).hexdigest()


def plain(html):
    text = re.sub(r"<script[\s\S]*?</script>", " ", html, flags=re.I)
    text = re.sub(r"<style[\s\S]*?</style>", " ", text, flags=re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = unescape(text)
    return re.sub(r"\s+", " ", text).strip()


def entry_bounds(html):
    marker = 'class="entry-content"'
    i = html.find(marker)
    if i < 0:
        raise SystemExit("no entry-content")
    j = html.find('class="ww-related"', i)
    if j < 0:
        j = html.find("<footer", i)
    if j < 0:
        j = len(html)
    return i, j


def stash_blocks(html, pattern):
    blocks = []

    def repl(match):
        blocks.append(match.group(0))
        return "%%BLOCK{}%%".format(len(blocks) - 1)

    return re.sub(pattern, repl, html, flags=re.I), blocks


def restore_blocks(html, blocks):
    for n, block in enumerate(blocks):
        html = html.replace("%%BLOCK{}%%".format(n), block, 1)
    return html


def keep_sentence(text, raw):
    low = text.lower()
    href = raw.lower()
    if "92.35" in text or "irs.gov" in href or "pub 334" in low or "publication 334" in low:
        if any(bad in text for bad in ("10-37", "0-13.3", "28-35", "$30k")):
            return False
        return True
    if "15.3%" in text and ("social security" in low or "medicare" in low):
        if any(bad in text for bad in ("10-37", "0-13.3", "28-35", "$30k")):
            return False
        return True
    if "goldmansachs.com" in href:
        return True
    if "coschedule.com" in href and "331%" in text:
        return True
    if "klaviyo.com" in href and "20%" in text and "30%" in text:
        return True
    if "seerinteractive.com" in href and ("61,000" in text or "122,000" in text):
        return True
    if "nngroup.com" in href and "test users" in low:
        return True
    if "fewer-americans-have-a-side-hustle" in href:
        return True
    if "litmus.com" in href and "$36" in text and "$42" not in text and "4.24" not in text and "36:1" not in text and "$50" not in text and "21%" not in text:
        return True
    if "litmus.com" in href and "12%" in text and "a/b" in low:
        return True
    if "impostor-phenomenon-study" in href and "2020" in text:
        return True
    if ("netinfluencer.com" in href or "billiondollarboy.com" in href) and ("52%" in text or "37%" in text):
        return True
    if "creatorspotlight.com/p/monetization-report" in href and "46.8" not in text and "/month" not in low and "per month" not in low:
        return True
    if "make.com" in href:
        return True
    return False


def should_drop(text, raw):
    if not text:
        return False
    if keep_sentence(text, raw):
        return False
    low = text.lower()
    raw_low = raw.lower()
    for token in BANNED_TOKENS:
        if token in low or token in text:
            return True
    if re.search(r"\d", text):
        for host in SURVEY_HOSTS:
            if host in raw_low or host in low:
                return True
    if re.search(r"\$\s?\d|\d+(?:\.\d+)?\s?%", text):
        urls = re.findall(r"https?://[^\"'\s<]+", raw_low)
        allow = (
            "wraywest",
            "irs.gov",
            "goldmansachs.com",
            "coschedule.com",
            "klaviyo.com",
            "seerinteractive.com",
            "nngroup.com",
            "fewer-americans-have-a-side-hustle",
            "litmus.com",
            "impostor-phenomenon-study",
            "netinfluencer.com",
            "billiondollarboy.com",
            "creatorspotlight.com/p/monetization-report",
            "make.com",
            "support.google.com",
            "developers.google.com",
            "senders.yahooinc.com",
            "yahooinc.com",
        )
        for url in urls:
            if any(ok in url for ok in allow):
                continue
            return True
    return False


def split_sentences(fragment):
    parts = re.split(r"(?<=[.!?])\s+(?=(?:<[^>]+>\s*)*[A-Z0-9\"“])", fragment)
    return parts


def filter_fragment(fragment, log, page):
    if "%%BLOCK" in fragment:
        return fragment
    parts = split_sentences(fragment)
    kept = []
    for part in parts:
        text = plain(part)
        if should_drop(text, part):
            log.append({
                "page": page,
                "action": "omit",
                "reason": "verification unavailable",
                "md5": md5(part),
                "text": text[:400],
            })
            continue
        kept.append(part)
    if not kept:
        return ""
    return " ".join(kept)


def filter_region(region, log, page):
    def repl(match):
        open_tag = match.group(1)
        inner = match.group(2)
        close = match.group(3)
        if open_tag.lower().startswith("<style") or open_tag.lower().startswith("<script"):
            return match.group(0)
        new_inner = filter_fragment(inner, log, page)
        if plain(new_inner) == "":
            log.append({
                "page": page,
                "action": "omit-element",
                "reason": "verification unavailable",
                "md5": md5(match.group(0)),
                "text": plain(match.group(0))[:240],
            })
            return ""
        if new_inner == inner:
            return match.group(0)
        return open_tag + new_inner + close

    return re.sub(
        r"(<(?:p|li|td|h3|small|summary)\b[^>]*>)([\s\S]*?)(</(?:p|li|td|h3|small|summary)>)",
        repl,
        region,
        flags=re.I,
    )


def exact_replace(html, old, new, log, page):
    count = html.count(old)
    if count == 0:
        log.append({"page": page, "action": "missing-exact", "text": old[:180], "md5": md5(old)})
        return html
    log.append({
        "page": page,
        "action": "replace",
        "reason": "narrow correction or removal",
        "count": count,
        "md5": md5(old),
        "text": plain(old)[:400],
    })
    return html.replace(old, new)


def filter_leaf_divs(region, log, page):
    def repl(match):
        open_tag, inner, close = match.group(1), match.group(2), match.group(3)
        if "<div" in inner.lower() or "<table" in inner.lower() or "<form" in inner.lower():
            return match.group(0)
        text = plain(inner)
        if not any(token in text.lower() for token in BANNED_TOKENS):
            return match.group(0)
        new_inner = filter_fragment(inner, log, page)
        if plain(new_inner) == "":
            return ""
        return open_tag + new_inner + close

    return re.sub(r"(<div\b[^>]*>)([\s\S]*?)(</div>)", repl, region, flags=re.I)


def strip_svg_claims(region, log, page):
    def repl(match):
        text = plain(match.group(0))
        low = text.lower()
        percent = "%" in text or "percent" in low
        supported_chart = text.strip() in ("52%", "37%") or text.strip().startswith("52") or text.strip().startswith("37")
        if percent and not supported_chart:
            log.append({
                "page": page,
                "action": "omit-svg",
                "reason": "verification unavailable",
                "md5": md5(match.group(0)),
                "text": text[:240],
            })
            return ""
        return match.group(0)

    region = re.sub(r"<text\b[^>]*>[\s\S]*?</text>", repl, region, flags=re.I)
    region = re.sub(r"<!--[\s\S]*?46\.8[\s\S]*?-->", "", region)
    old_aria = 'aria-label="Creator burnout statistics: 52 percent experienced burnout, 37 percent considered quitting the industry entirely, 68 percent cite algorithmic pressure as a major stressor, 46.8 percent earn under 500 dollars per month"'
    new_aria = 'aria-label="Creator burnout statistics from the July 2025 Billion Dollar Boy survey of 1,000 creators in the US and UK: 52 percent experienced burnout, 37 percent considered leaving the profession"'
    if old_aria in region:
        region = exact_replace(region, old_aria, new_aria, log, page)
    old_desc = "Four anchor statistics on creator burnout. 52 percent of content creators have experienced burnout (Influencer Marketing Hub 2025). 37 percent have considered quitting the industry entirely. 68 percent cite algorithmic pressure as a major stressor. 46.8 percent of creators earn under $500 per month (Creator Spotlight 2025)."
    new_desc = "Billion Dollar Boy, 7 July 2025, surveyed 1,000 creators in the US and UK. 52 percent had experienced burnout as a direct result of their career. 37 percent were actively considering leaving the profession."
    if old_desc in region:
        region = exact_replace(region, old_desc, new_desc, log, page)
    return region


def apply_exact(html, log, page):
    pairs = [
        (
            "$36 to $42",
            "$36",
        ),
        (
            "$36-$42",
            "$36",
        ),
        (
            "), and email converts at 4.24% compared to social media&#8217;s 0.59%, a 7x advantage. ",
            "). ",
        ),
        (
            "<strong>84% of entrepreneurs experience imposter syndrome</strong>",
            "<strong>84% of the entrepreneurs and small business owners Kajabi surveyed in 2020 scored as feeling like an imposter at a moderate, frequent, or intense level</strong>",
        ),
        (
            "But consider: 84% of entrepreneurs feel the same way.",
            "But consider: in that 2020 Kajabi survey, 84% of the business owners who answered scored as feeling like an imposter.",
        ),
        (
            "79% of web users scan rather than read",
            "79 percent of Nielsen Norman Group's test users always scanned a new page rather than read it word by word",
        ),
        (
            "the highest-leverage habit in this system",
            "the habit this system depends on",
        ),
        (
            "Filter two: 4,200 monthly searches for related keywords.",
            "Filter two: people already search for the problem.",
        ),
        (
            "This framework is built for people who have 8 to 12 hours a week.",
            "This framework is built for people who have 8 to 12 hours a week. The hour counts, month ranges, price cap, and 80/20 split on this page are planning assumptions, not survey results.",
        ),
        (
            "I build WrayWest around a 6:30 AM to 3:00 PM kitchen shift. ",
            "",
        ),
        (
            "I'm a full-time chef working to publish content consistently around a kitchen shift that starts at 6:30 AM. ",
            "",
        ),
        (
            "I&#8217;m a full-time chef working to publish content consistently around a kitchen shift that starts at 6:30 AM. ",
            "",
        ),
        (
            "and five to six focused hours per week, applied consistently, is enough to build real momentum over 90 days",
            "",
        ),
        (
            "while you sleep, while you&#8217;re on a shift",
            "while you&#8217;re on a shift",
        ),
        (
            "bring you clients while you sleep",
            "bring you clients later",
        ),
        (
            "The shift that mattered unlocked my revenue.",
            "The shift that mattered was simple: stop chasing attention and start building trust.",
        ),
        (
            ">while you sleep</text>",
            ">with no ongoing work</text>",
        ),
        (
            "Set up automation tools like <a href=\"https://www.make.com/\" rel=\"sponsored noopener\" target=\"_blank\">Make.com</a> or Zapier to handle repetitive tasks. ",
            "<a href=\"https://www.make.com/\" rel=\"sponsored noopener\" target=\"_blank\">Make.com</a> is listed as researched only. This site has not used it hands-on. The sponsored mark stays because affiliation is still unconfirmed. Zapier was never used. ",
        ),
        (
            "Default 28% (typical for $75k W-2).",
            "Default 28%. Not a tax figure for your return.",
        ),
        (
            "30 percent is a common starting point",
            "Default is 30 percent. Not a sourced tax rate.",
        ),
        (
            'rel="nofollow sponsored noopener"',
            'rel="noopener"',
        ),
        (
            "What tasks take 80% of your energy but generate 20% of your results?",
            "What tasks take most of your energy and generate little of the result?",
        ),
        (
            "Ruthlessly eliminate the bottom 80%.",
            "Stop the work that only drains you.",
        ),
        (
            "sell passively",
            "sell",
        ),
        (
            "3% = passive. 5% = active creator. 10%+ = aggressive growth.",
            "Defaults on the fields above. Not a surveyed growth rate.",
        ),
        (
            "Per Kajabi&#8217;s State of the Creator Economy and Influencer Marketing Hub data, established creators typically grow 3-7% MoM in steady state. Newer creators with active publishing schedules can hit 10-15% MoM in the first 12-18 months before plateauing. Use 3% for passive, 5% for active, 10%+ only if you have evidence of recent compounding growth.",
            "The growth field starts at 5 percent. That default is an assumption, not a surveyed month-over-month rate.",
        ),
        (
            "Self-employment income is taxed at 15.3% for Social Security + Medicare (on 92.35% of net earnings, per IRS Pub 334), plus federal income tax (10-37% based on bracket), plus state income tax (0-13.3%). For most creators earning $30k-$150k of side income on top of a W-2, combined effective rate lands around 28-35%. We use 30% as a defensible mid-point. Adjust if your situation is different.",
            "The IRS self-employment tax rate is 15.3%: 12.4% for Social Security and 2.9% for Medicare. Publication 334 (2025) says to multiply self-employment earnings by 92.35% (0.9235) under the regular method. The creator-tax field still starts at 30%. That 30% is a default assumption, not an effective rate for a particular return.",
        ),
        (
            "Calculator does not model state income tax (add 4-13% depending on state).",
            "Calculator does not model state income tax.",
        ),
        (
            "https://creatorspotlight.co/monetization-report",
            "https://www.creatorspotlight.com/p/monetization-report-2025",
        ),
        (
            ", 30% said $36 to $50, 5% said more than $50, and 21% do not measure ROI",
            "",
        ),
        (
            "Algorithmic pressure cited by 68% of creators.",
            "",
        ),
        (
            "After talking to dozens of creators who walked away from their businesses, and cross-referencing with <a href=\"https://influencermarketinghub.com/creator-earnings-report-2025/\" rel=\"noopener\" target=\"_blank\">Influencer Marketing Hub&#8217;s 2025 Creator Earnings Report</a> (which found 68% of creators cite algorithmic pressure as a major stressor), I&#8217;ve identified five core reasons why the attrition rate is so high:",
            "Five pressures show up when the work starts to feel like a second shift:",
        ),
        (
            "Making $5,000 one month and $400 the next destroys your nervous system.",
            "A big month followed by a small one destroys your nervous system.",
        ),
        (
            "Per Kajabi State of the Creator Economy and Influencer Marketing Hub data, established creators typically grow 3-7% month-over-month in steady state. Newer creators with active publishing can hit 10-15% MoM in the first 12-18 months. Use 3% for passive, 5% for active, 10%+ only with evidence of recent compounding growth.",
            "The growth field starts at 5 percent. That default is an assumption, not a surveyed month-over-month rate.",
        ),
        (
            'aria-label="5 root causes of creator burnout ranked by frequency: algorithmic pressure 68 percent, inconsistent income 54 percent, content treadmill 48 percent, vanity metric addiction 41 percent, audience-as-customer-service expectation 33 percent"',
            'aria-label="Five pressures discussed in this section. No survey share is stated."',
        ),
        (
            "Five most-cited drivers of creator burnout: algorithmic pressure at 68 percent, inconsistent income at 54 percent, the always-on content treadmill at 48 percent, vanity metric addiction comparing follower counts at 41 percent, and audience-as-customer-service expectations at 33 percent. Source: Influencer Marketing Hub 2025 plus author&#8217;s interviews.",
            "Five pressures discussed in this section. No survey share is stated.",
        ),
    ]
    for old, new in pairs:
        if old in html:
            html = exact_replace(html, old, new, log, page)
    # Distribution table that restates the unread affiliate-share PDF.
    html = re.sub(
        r'<div style="background:#FFFFFF;border:1px solid #E8E2CC;[\s\S]*?57\.55%[\s\S]*?</div>',
        "",
        html,
        count=1,
    )
    return html


def sync_faq(html, log, page):
    visible = {}
    for match in re.finditer(r"<details\b([^>]*)>([\s\S]*?)</details>", html, flags=re.I):
        attrs = match.group(1)
        body = match.group(2)
        if "ww-toc" in attrs or "data-ww-toc" in attrs:
            continue
        summary = re.search(r"<summary[^>]*>([\s\S]*?)</summary>", body, flags=re.I)
        if not summary:
            continue
        question = plain(summary.group(1))
        if question.lower().startswith("in this article"):
            continue
        answer_html = body[summary.end():]
        visible[question] = plain(answer_html)

    def repl_script(match):
        raw = match.group(0)
        if "FAQPage" not in raw or "application/ld+json" not in raw:
            return raw
        if "rank-math-schema" in raw:
            return raw
        try:
            payload = re.search(r">([\s\S]*?)</script>", raw, flags=re.I).group(1)
            data = json.loads(payload)
        except (json.JSONDecodeError, AttributeError):
            return raw
        if data.get("@type") != "FAQPage":
            return raw
        entities = []
        for entity in data.get("mainEntity", []):
            name = plain(entity.get("name", ""))
            answer = entity.get("acceptedAnswer", {})
            text = answer.get("text", "")
            if name in visible:
                answer["text"] = visible[name]
                entity["acceptedAnswer"] = answer
                entities.append(entity)
                if text != visible[name]:
                    log.append({
                        "page": page,
                        "action": "faq-sync",
                        "reason": "visible answer is the schema text",
                        "md5": md5(text),
                        "text": name[:180],
                    })
            else:
                # Question text did not match a visible item. Filter its answer anyway.
                sentences = re.split(r"(?<=[.!?])\s+(?=[A-Z0-9])", text)
                kept = [s for s in sentences if not should_drop(s, s)]
                answer["text"] = " ".join(kept).strip()
                entity["acceptedAnswer"] = answer
                if answer["text"]:
                    entities.append(entity)
                log.append({
                    "page": page,
                    "action": "faq-filter",
                    "reason": "verification unavailable",
                    "md5": md5(text),
                    "text": name[:180],
                })
        data["mainEntity"] = entities
        new_json = json.dumps(data, ensure_ascii=False, separators=(",", ":"))
        return '<script type="application/ld+json">' + new_json + "</script>"

    return re.sub(r"<script\b[^>]*type=\"application/ld\+json\"[^>]*>[\s\S]*?</script>", repl_script, html, flags=re.I)


def propose(page, log):
    src = os.path.join(SRC, page)
    html = open(src, encoding="utf-8", errors="replace").read()
    before_entry_start, before_entry_end = entry_bounds(html)
    before_entry = html[before_entry_start:before_entry_end]
    html = apply_exact(html, log, page)
    start, end = entry_bounds(html)
    region = html[start:end]
    # Leave style, non-FAQ scripts, and forms byte-stable inside the entry.
    region_wo_scripts, scripts = stash_blocks(region, r"<script\b[\s\S]*?</script>")
    region_wo_all, styles = stash_blocks(region_wo_scripts, r"<style\b[\s\S]*?</style>")
    filtered = filter_region(region_wo_all, log, page)
    filtered = filter_leaf_divs(filtered, log, page)
    filtered = strip_svg_claims(filtered, log, page)
    filtered = restore_blocks(filtered, styles)
    filtered = restore_blocks(filtered, scripts)
    html = html[:start] + filtered + html[end:]
    html = sync_faq(html, log, page)
    # Kit stays unsponsored. Make stays sponsored. The exact replace above
    # already strips nofollow-sponsored. Repeat for any leftover kit.com sponsored.
    html2 = re.sub(
        r'(<a\b[^>]*href="https://kit\.com/[^"]*"[^>]*?)\srel="[^"]*sponsored[^"]*"',
        r'\1 rel="noopener"',
        html,
        flags=re.I,
    )
    html2 = re.sub(
        r'(<a\b[^>]*?)\srel="[^"]*sponsored[^"]*"([^>]*href="https://kit\.com/)',
        r'\1 rel="noopener"\2',
        html2,
        flags=re.I,
    )
    if html2 != html:
        log.append({"page": page, "action": "kit-rel", "reason": "Kit link stays unsponsored", "md5": md5("kit-rel"), "text": "kit.com sponsored removed"})
        html = html2
    os.makedirs(OUT, exist_ok=True)
    dest = os.path.join(OUT, page)
    open(dest, "w", encoding="utf-8").write(html)
    after_start, after_end = entry_bounds(html)
    return {
        "page": page,
        "before_entry_md5": md5(before_entry),
        "after_entry_md5": md5(html[after_start:after_end]),
        "bytes_before": len(before_entry),
        "bytes_after": after_end - after_start,
    }


def main():
    log = []
    pages = []
    for name in CLUSTER1 + EMAIL:
        pages.append(propose(name, log))
    manifest = {"pages": pages, "edits": log}
    os.makedirs(os.path.dirname(MANIFEST), exist_ok=True)
    open(MANIFEST, "w", encoding="utf-8").write(json.dumps(manifest, indent=2))
    print("pages", len(pages), "edits", len(log))
    by_page = {}
    for item in log:
        by_page.setdefault(item["page"], 0)
        by_page[item["page"]] += 1
    for page, count in by_page.items():
        print(f"{count:3} {page}")


if __name__ == "__main__":
    main()
