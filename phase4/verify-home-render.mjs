/**
 * Renders the captured public homepage, blog index, and money category.
 * LOCAL FIXTURE: the HTML is the read-only capture from 2026-11-08, opened
 * in headless Chrome here. This is not a WordPress boot.
 */
import { chromium } from 'playwright-core';
import { createRequire } from 'node:module';
import { spawn } from 'node:child_process';
import { mkdir, writeFile, readFile, copyFile } from 'node:fs/promises';
import path from 'node:path';

const require = createRequire(import.meta.url);
const axeSource = require('axe-core').source;
const outDir = '/opt/cursor/artifacts';
const port = 8766;
const root = '/tmp/ww-serve';

function contrast(a, b) {
  const lin = (c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
  const L = (hex) => {
    const h = hex.replace('#', '');
    const r = parseInt(h.slice(0, 2), 16) / 255;
    const g = parseInt(h.slice(2, 4), 16) / 255;
    const b2 = parseInt(h.slice(4, 6), 16) / 255;
    return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b2);
  };
  const hi = Math.max(L(a), L(b));
  const lo = Math.min(L(a), L(b));
  return (hi + 0.05) / (lo + 0.05);
}

async function prepare() {
  await mkdir(root, { recursive: true });
  await mkdir(path.join(root, 'css'), { recursive: true });
  await copyFile('/tmp/wraywest-before.css', path.join(root, 'css/before.css'));
  await copyFile('/workspace/uploads/ww-css/wraywest.css', path.join(root, 'css/after.css'));
  const swap = async (src, dest, css) => {
    let html = await readFile(src, 'utf8');
    html = html.replace(
      /https:\/\/wraywestblog\.com\/wp-content\/uploads\/ww-css\/wraywest\.css\?ver=20261104\.51/g,
      css
    );
    if (!html.includes(css)) {
      html = html.replace('</head>', `<link rel="stylesheet" href="${css}"></head>`);
    }
    await writeFile(path.join(root, dest), html);
  };
  await swap('/tmp/ww-captures/HOME.html', 'home-before.html', 'css/before.css');
  await swap('/tmp/ww-render-home.html', 'home-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/blog.html', 'blog-before.html', 'css/before.css');
  await swap('/tmp/ww-render-blog.html', 'blog-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/blog_category_money-finance.html', 'money-before.html', 'css/before.css');
  await swap('/tmp/ww-captures/blog_category_money-finance.html', 'money-after.html', 'css/after.css');
}

function startServer() {
  return spawn('python3', ['-m', 'http.server', String(port), '--bind', '127.0.0.1'], { cwd: root, stdio: 'ignore' });
}

const report = { localWordPressBoot: false, source: 'public HTML captured 2026-11-08', pages: {} };

await prepare();
const server = startServer();
await new Promise((r) => setTimeout(r, 400));
const browser = await chromium.launch({
  executablePath: '/usr/local/bin/google-chrome',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

async function open(file, viewport, opts = {}) {
  const context = await browser.newContext({
    viewport,
    hasTouch: Boolean(opts.touch),
    isMobile: Boolean(opts.touch),
    reducedMotion: opts.reduced ? 'reduce' : 'no-preference',
  });
  const page = await context.newPage();
  await page.goto(`http://127.0.0.1:${port}/${file}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForTimeout(400);
  return { context, page };
}

async function axe(page) {
  await page.addScriptTag({ content: axeSource });
  return page.evaluate(async () => {
    const result = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa'] } });
    return result.violations.map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length, help: v.help }));
  });
}

try {
  await mkdir(outDir, { recursive: true });
  const jobs = [
    ['home-before', 'home-before.html'],
    ['home-after', 'home-after.html'],
    ['blog-before', 'blog-before.html'],
    ['blog-after', 'blog-after.html'],
    ['money-before', 'money-before.html'],
    ['money-after', 'money-after.html'],
  ];
  for (const [name, file] of jobs) {
    for (const vp of [
      { w: 1440, h: 1100, touch: false },
      { w: 390, h: 844, touch: true },
    ]) {
      const { context, page } = await open(file, { width: vp.w, height: vp.h }, { touch: vp.touch });
      const measure = await page.evaluate(() => {
        const hex = (c) => {
          const m = (c || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
          if (!m) return c;
          return '#' + [m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('');
        };
        const h1 = document.querySelector('#wwl-h, h1');
        const hs = h1 ? getComputedStyle(h1) : null;
        const chips = [...document.querySelectorAll('a.vb-chip, article:first-of-type .ww-chip')].map((el) => {
          const r = el.getBoundingClientRect();
          return { t: el.textContent.replace(/\s+/g, ' ').trim().slice(0, 40), y: Math.round(r.top), h: Math.round(r.height), w: Math.round(r.width) };
        });
        const doc = document.documentElement;
        return {
          h1: h1 ? { text: h1.textContent.replace(/\s+/g, ' ').trim(), font: hs.fontFamily, size: hs.fontSize, weight: hs.weight, color: hex(hs.color) } : null,
          chips,
          overflow: doc.scrollWidth > doc.clientWidth + 1,
          scroll: doc.scrollWidth,
          client: doc.clientWidth,
          receipts: {
            olsp: document.body.innerText.includes('$810.95'),
            leads: document.body.innerText.includes('$17.77'),
            olspSource: document.body.innerText.includes('Source: my OLSP dashboard'),
            leadsSource: document.body.innerText.includes('Source: my LeadsLeap account'),
          },
          badTeaser: document.body.innerText.includes('What it cost, what broke') || document.body.innerText.includes('you can self-host'),
          narrow: document.body.innerText.includes('Some posts made with n8n'),
        };
      });
      const violations = await axe(page);
      const key = `${name}-${vp.w}`;
      report.pages[key] = { measure, violations };
      await page.screenshot({ path: path.join(outDir, `phase4-${name}-${vp.w}.png`), fullPage: false });
      if (name.startsWith('home') && vp.w === 1440) {
        const receipts = page.locator('text=$810.95').first();
        if (await receipts.count()) {
          await receipts.scrollIntoViewIfNeeded();
          await page.screenshot({ path: path.join(outDir, `phase4-${name}-receipts.png`) });
        }
      }
      if (name.startsWith('blog') && vp.w === 390) {
        await page.screenshot({ path: path.join(outDir, `phase4-${name}-390-top.png`), clip: { x: 0, y: 0, width: 390, height: 520 } });
      }
      await context.close();
    }
  }
  {
    const { context, page } = await open('home-after.html', { width: 1440, height: 900 }, { reduced: true });
    report.reducedMotion = await page.evaluate(() => {
      const el = document.querySelector('#wwl-h') || document.body;
      const s = getComputedStyle(el);
      return { animation: s.animationName, duration: s.animationDuration };
    });
    const chip = page.locator('a.vb-chip, a.ww-chip').first();
    await context.close();
  }
  {
    const { context, page } = await open('blog-after.html', { width: 390, height: 844 }, { touch: true });
    const chip = page.locator('a.vb-chip').first();
    await chip.focus();
    report.chipFocus = await page.evaluate(() => {
      const el = document.activeElement;
      const s = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      return { text: (el.textContent || '').trim().slice(0, 40), outline: s.outlineStyle, width: s.outlineWidth, h: Math.round(r.height) };
    });
    await context.close();
  }
  {
    const { context, page } = await open('home-after.html', { width: 1440, height: 900 });
    await page.locator('text=Some posts on this site were made with n8n.').first().scrollIntoViewIfNeeded();
    await page.screenshot({ path: path.join(outDir, 'phase4-home-after-n8n-teaser.png') });
    await page.locator('text=Money & Finance').first().scrollIntoViewIfNeeded();
    await page.screenshot({ path: path.join(outDir, 'phase4-home-after-money-module.png') });
    await page.evaluate(() => document.fonts && document.fonts.ready);
    await page.addScriptTag({ content: axeSource });
    report.homeAxeAfterFonts = await page.evaluate(async () => {
      const ready = document.querySelector('#wwl-h');
      const font = ready ? getComputedStyle(ready).fontFamily : '';
      const r = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2aa'] } });
      return { font, violations: r.violations.map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length, sample: v.nodes[0] ? v.nodes[0].html.slice(0, 160) : '' })) };
    });
    await context.close();
  }
  {
    const { context, page } = await open('money-after.html', { width: 390, height: 844 }, { touch: true });
    const chips = await page.evaluate(() => [...document.querySelectorAll('nav.ww-cat-chips .ww-chip')].slice(0, 8).map((el) => {
      const r = el.getBoundingClientRect();
      const s = getComputedStyle(el);
      return { t: el.textContent.replace(/\s+/g, ' ').trim().slice(0, 40), y: Math.round(r.y), h: Math.round(r.height), display: s.display };
    }));
    report.moneyChips390 = chips;
    await page.screenshot({ path: path.join(outDir, 'phase4-money-after-390-chips.png'), clip: { x: 0, y: 0, width: 390, height: 640 } });
    await context.close();
  }
  const h1 = report.pages['home-after-1440'].measure.h1;
  report.headlineUnchanged = {
    before: report.pages['home-before-1440'].measure.h1,
    after: h1,
  };
  await writeFile('/workspace/phase4/home-archive-results.json', JSON.stringify(report, null, 2));
  await writeFile(path.join(outDir, 'phase4-home-archive-results.json'), JSON.stringify(report, null, 2));
  const brief = {};
  for (const [k, v] of Object.entries(report.pages)) {
    brief[k] = {
      h1: v.measure.h1,
      overflow: v.measure.overflow,
      chips: v.measure.chips.slice(0, 8),
      receipts: v.measure.receipts,
      badTeaser: v.measure.badTeaser,
      narrow: v.measure.narrow,
      axe: v.violations.map((x) => x.id + ':' + x.impact),
    };
  }
  console.log(JSON.stringify({ headline: report.headlineUnchanged, focus: report.chipFocus, reduced: report.reducedMotion, brief }, null, 2));
} finally {
  await browser.close();
  server.kill();
}
