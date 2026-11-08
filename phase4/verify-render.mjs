/**
 * Local fixture render for the Phase 4 single-post change-set.
 * Not a WordPress boot and not the live site. Missing Lighthouse, pa11y,
 * and schema scorers are recorded as MANUAL.
 */
import { chromium } from 'playwright-core';
import { createRequire } from 'node:module';
import { spawn } from 'node:child_process';
import { mkdir, writeFile, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const axeSource = require('axe-core').source;
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const outDir = '/opt/cursor/artifacts';
const port = 8765;

function contrast(hexA, hexB) {
  const lin = (c) => {
    const x = c / 255;
    return x <= 0.04045 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4;
  };
  const L = (hex) => {
    const h = hex.replace('#', '');
    const r = parseInt(h.slice(0, 2), 16);
    const g = parseInt(h.slice(2, 4), 16);
    const b = parseInt(h.slice(4, 6), 16);
    return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
  };
  const a = L(hexA);
  const b = L(hexB);
  const hi = Math.max(a, b);
  const lo = Math.min(a, b);
  return (hi + 0.05) / (lo + 0.05);
}

function rgbToHex(rgb) {
  const m = rgb.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
  if (!m) return null;
  return '#' + [m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('');
}

async function prepareCss() {
  const cssDir = path.join(root, 'phase4/fixtures/css');
  await mkdir(cssDir, { recursive: true });
  const { symlink, copyFile } = await import('node:fs/promises');
  const link = async (target, name) => {
    const dest = path.join(cssDir, name);
    try { await symlink(target, dest); } catch (err) {
      if (err.code !== 'EEXIST') throw err;
    }
  };
  await copyFile('/tmp/wraywest-before.css', path.join(cssDir, 'before.css'));
  await link(path.join(root, 'uploads/ww-css/wraywest.css'), 'after.css');
  await link(path.join(root, 'phase4/fixtures/fonts.css'), 'fonts.css');
  await link(path.join(root, 'phase4/fixtures/shell.css'), 'shell.css');
}

async function startServer() {
  await prepareCss();
  const child = spawn('python3', ['-m', 'http.server', String(port), '--bind', '127.0.0.1'], {
    cwd: root,
    stdio: 'ignore',
  });
  for (let i = 0; i < 40; i++) {
    try {
      const res = await fetch(`http://127.0.0.1:${port}/phase4/fixtures/before-welcome.html`);
      if (res.ok) return child;
    } catch {
      await new Promise((r) => setTimeout(r, 100));
    }
  }
  child.kill();
  throw new Error('fixture server did not start');
}

async function measure(page) {
  return page.evaluate(() => {
    const cs = (el) => (el ? getComputedStyle(el) : null);
    const hex = (c) => {
      const m = (c || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
      if (!m) return c;
      return '#' + [m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('');
    };
    const box = (sel) => {
      const el = document.querySelector(sel);
      if (!el) return null;
      const r = el.getBoundingClientRect();
      const style = getComputedStyle(el);
      return {
        sel,
        text: (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 180),
        w: Math.round(r.width),
        h: Math.round(r.height),
        color: hex(style.color),
        bg: hex(style.backgroundColor),
        font: style.fontFamily,
        size: style.fontSize,
        lh: style.lineHeight,
        maxWidth: style.maxWidth,
      };
    };
    const targets = [
      '.menu-toggle',
      '.main-title a',
      '.ww-byline-author',
      '.ww-toc summary',
      '.ww-toc a',
      '.ww-end[data-ww-p4="end"] a',
      '.ww-author-short a',
      '.ww-use-label',
    ].map(box).filter(Boolean);
    const word = document.querySelector('.main-title a');
    let wordmark = null;
    if (word) {
      const range = document.createRange();
      range.selectNodeContents(word);
      const text = range.getBoundingClientRect();
      const parent = word.closest('.site-branding') || word.parentElement;
      const pr = parent.getBoundingClientRect();
      const ps = getComputedStyle(parent);
      wordmark = {
        text: word.textContent.trim(),
        textRight: Math.round(text.right),
        parentRight: Math.round(pr.right),
        parentOverflow: ps.overflow,
        parentDisplay: ps.display,
        clipped: text.right > pr.right + 1 || text.width === 0,
        viewport: document.documentElement.clientWidth,
      };
    }
    const opaqueBg = (el) => {
      let node = el;
      while (node && node !== document.documentElement) {
        const s = getComputedStyle(node);
        const m = s.backgroundColor.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?\)/);
        if (m && (m[4] === undefined || Number(m[4]) > 0.95)) {
          return { bg: hex(s.backgroundColor), image: s.backgroundImage.slice(0, 140) };
        }
        node = node.parentElement;
      }
      return { bg: '#ffffff', image: 'none' };
    };
    const dek = document.querySelector('.ww-site-hero-sub');
    let dekPaint = null;
    if (dek) {
      const behind = opaqueBg(dek);
      const hero = document.querySelector('.ww-site-hero');
      dekPaint = {
        color: hex(getComputedStyle(dek).color),
        bg: behind.bg,
        heroImage: getComputedStyle(hero).backgroundImage.slice(0, 160),
      };
    }
    const doc = document.documentElement;
    return {
      overflowX: doc.scrollWidth > doc.clientWidth + 1,
      scrollWidth: doc.scrollWidth,
      clientWidth: doc.clientWidth,
      targets,
      wordmark,
      dek: dekPaint,
      h1: box('h1'),
      disc: document.querySelectorAll('.ww-disc').length,
      ends: document.querySelectorAll('.ww-end').length,
      cta: document.querySelectorAll('.ww-related-cta').length,
    };
  });
}

async function runAxe(page) {
  await page.addScriptTag({ content: axeSource });
  return page.evaluate(async () => {
    const result = await window.axe.run(document, {
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa'] },
    });
    return {
      violations: result.violations.map((v) => ({
        id: v.id,
        impact: v.impact,
        help: v.help,
        nodes: v.nodes.length,
      })),
    };
  });
}

async function shot(page, name) {
  await page.screenshot({ path: path.join(outDir, name), fullPage: true });
}

const report = {
  kind: 'local-fixture',
  notLiveWordPress: true,
  manual: ['lighthouse', 'pa11y-ci', 'rank-math', 'schema score', 'seo score'],
  pages: {},
};

const server = await startServer();
const browser = await chromium.launch({
  executablePath: '/usr/local/bin/google-chrome',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

async function open(pathname, viewport, options = {}) {
  const context = await browser.newContext({
    viewport,
    hasTouch: Boolean(options.touch),
    isMobile: Boolean(options.touch),
    reducedMotion: options.reduced ? 'reduce' : 'no-preference',
  });
  const page = await context.newPage();
  await page.goto(`http://127.0.0.1:${port}${pathname}`, { waitUntil: 'networkidle' });
  return { context, page };
}

try {
  await mkdir(outDir, { recursive: true });

  const widths = [
    { name: '1440', width: 1440, height: 1000 },
    { name: '390', width: 390, height: 844, touch: true },
  ];

  for (const [label, file] of [
    ['before', '/phase4/fixtures/before-welcome.html'],
    ['after', '/phase4/fixtures/after-welcome.html'],
  ]) {
    for (const vp of widths) {
      const { context, page } = await open(file, { width: vp.width, height: vp.height }, { touch: vp.touch });
      const data = await measure(page);
      const axe = await runAxe(page);
      report.pages[`${label}-${vp.name}`] = { measure: data, axe: axe.violations };
      await shot(page, `phase4-${label}-welcome-${vp.name}.png`);
      await context.close();
    }
  }

  {
    const { context, page } = await open('/phase4/fixtures/after-welcome.html', { width: 1440, height: 1000 }, { reduced: true });
    const motion = await page.evaluate(() => {
      const el = document.querySelector('.ww-site-hero-title');
      const s = getComputedStyle(el);
      return { animation: s.animationName, duration: s.animationDuration, transition: s.transitionDuration };
    });
    report.reducedMotion = motion;
    await shot(page, 'phase4-after-welcome-1440-reduced-motion.png');
    const summary = page.locator('.ww-toc summary');
    await summary.focus();
    const focus = await page.evaluate(() => {
      const el = document.activeElement;
      const s = getComputedStyle(el);
      return { tag: el.tagName, outline: s.outlineStyle, width: s.outlineWidth, color: s.outlineColor };
    });
    report.focusSummary = focus;
    await context.close();
  }

  {
    const { context, page } = await open('/phase4/fixtures/after-review.html', { width: 390, height: 900 }, { touch: true });
    report.review390 = await measure(page);
    report.reviewAxe = (await runAxe(page)).violations;
    await shot(page, 'phase4-after-review-390.png');
    await context.close();
  }

  for (const css of ['before', 'after']) {
    const { context, page } = await open('/phase4/fixtures/home-headline.html', { width: 1440, height: 800 });
    await page.evaluate((which) => {
      const site = [...document.querySelectorAll('link[rel=stylesheet]')].find((l) => /css\/(before|after)\.css/.test(l.getAttribute('href') || ''));
      if (site) site.href = `css/${which}.css`;
    }, css);
    await page.waitForTimeout(300);
    report[`home-${css}`] = await page.evaluate(() => {
      const h = document.querySelector('#wwl-h');
      const s = getComputedStyle(h);
      return {
        text: h.textContent.replace(/\s+/g, ' ').trim(),
        font: s.fontFamily,
        size: s.fontSize,
        weight: s.fontWeight,
      };
    });
    if (css === 'after') await shot(page, 'phase4-home-headline-after-1440.png');
    await context.close();
  }

  for (const width of [390, 350]) {
    for (const label of ['before', 'after']) {
      const { context, page } = await open(
        label === 'before' ? '/phase4/fixtures/before-welcome.html' : '/phase4/fixtures/after-welcome.html',
        { width, height: 700 },
        { touch: true }
      );
      report[`wordmark-${label}-${width}`] = (await measure(page)).wordmark;
      await page.screenshot({
        path: path.join(outDir, `phase4-wordmark-${label}-${width}.png`),
        clip: { x: 0, y: 0, width, height: 120 },
      });
      await context.close();
    }
  }

  const dek = report.pages['after-1440'].measure.dek;
  report.contrast = {};
  if (dek && dek.bg) {
    report.contrast.dek = {
      color: dek.color,
      background: dek.bg,
      heroImage: dek.heroImage,
      ratio: Number(contrast(dek.color, dek.bg).toFixed(2)),
    };
  }
  const h1 = report.pages['after-1440'].measure.h1;
  if (h1 && dek && dek.bg) {
    report.contrast.h1 = {
      color: h1.color,
      background: dek.bg,
      ratio: Number(contrast(h1.color, dek.bg).toFixed(2)),
    };
  }
  const byline = report.pages['after-1440'].measure.targets.find((t) => t.sel === '.ww-byline-author');
  if (byline && dek && dek.bg) {
    report.contrast.byline = {
      color: byline.color,
      background: dek.bg,
      ratio: Number(contrast(byline.color, dek.bg).toFixed(2)),
    };
  }
  report.contrast.gradientEndpoints = {
    note: 'The single-post hero background-color is transparent. The paint is the W-038 gradient. These ratios use the computed gradient stops, which are darker than the body color the walker reports.',
    top: '#fbebd0',
    bottom: '#f7e3c4',
    dekOnTop: Number(contrast('#3d3732', '#fbebd0').toFixed(2)),
    dekOnBottom: Number(contrast('#3d3732', '#f7e3c4').toFixed(2)),
    h1OnBottom: Number(contrast('#1a1714', '#f7e3c4').toFixed(2)),
    bylineOnBottom: Number(contrast('#7d4a17', '#f7e3c4').toFixed(2)),
    kickerEmailOnBottom: Number(contrast('#3a4a8a', '#f7e3c4').toFixed(2)),
    kickerToolsOnBottom: Number(contrast('#1d5c57', '#f7e3c4').toFixed(2)),
  };

  await writeFile(path.join(root, 'phase4/verify-results.json'), JSON.stringify(report, null, 2));
  await writeFile(path.join(outDir, 'phase4-verify-results.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify({
    contrast: report.contrast,
    home: { before: report['home-before'], after: report['home-after'] },
    wordmark: {
      before390: report['wordmark-before-390'],
      after390: report['wordmark-after-390'],
      before350: report['wordmark-before-350'],
      after350: report['wordmark-after-350'],
    },
    overflow: {
      before1440: report.pages['before-1440'].measure.overflowX,
      after1440: report.pages['after-1440'].measure.overflowX,
      before390: report.pages['before-390'].measure.overflowX,
      after390: report.pages['after-390'].measure.overflowX,
    },
    axeAfter1440: report.pages['after-1440'].axe,
    axeAfter390: report.pages['after-390'].axe,
    reduced: report.reducedMotion,
    focus: report.focusSummary,
    targets390: report.pages['after-390'].measure.targets,
  }, null, 2));
} finally {
  await browser.close();
  server.kill();
}
