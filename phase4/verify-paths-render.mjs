/**
 * Captured-HTML fixtures for Start Here and the Second Income Engine.
 * Not a WordPress boot and not a staging site.
 */
import { chromium } from 'playwright-core';
import { createRequire } from 'node:module';
import { spawn } from 'node:child_process';
import { mkdir, writeFile, readFile, copyFile } from 'node:fs/promises';
import path from 'node:path';

const require = createRequire(import.meta.url);
const axeSource = require('axe-core').source;
const outDir = '/opt/cursor/artifacts';
const port = 8767;
const root = '/tmp/ww-serve-paths';

function contrast(hexA, hexB) {
  const lin = (c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
  const L = (hex) => {
    const h = hex.replace('#', '');
    const r = parseInt(h.slice(0, 2), 16) / 255;
    const g = parseInt(h.slice(2, 4), 16) / 255;
    const b = parseInt(h.slice(4, 6), 16) / 255;
    return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
  };
  const hi = Math.max(L(hexA), L(hexB));
  const lo = Math.min(L(hexA), L(hexB));
  return (hi + 0.05) / (lo + 0.05);
}

async function prepare() {
  await mkdir(path.join(root, 'css'), { recursive: true });
  await copyFile('/tmp/wraywest-before.css', path.join(root, 'css/before.css'));
  await copyFile('/workspace/uploads/ww-css/wraywest.css', path.join(root, 'css/after.css'));
  const swap = async (src, dest, css) => {
    let html = await readFile(src, 'utf8');
    html = html.replace(
      /https:\/\/wraywestblog\.com\/wp-content\/uploads\/ww-css\/wraywest\.css\?ver=20261104\.51/g,
      css
    );
    await writeFile(path.join(root, dest), html);
  };
  await swap('/tmp/ww-captures/start-here.html', 'start-before.html', 'css/before.css');
  await swap('/tmp/ww-render-start.html', 'start-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/engine.html', 'engine-before.html', 'css/before.css');
  await swap('/tmp/ww-render-engine.html', 'engine-after.html', 'css/after.css');
}

await prepare();
const server = spawn('/usr/bin/python3', ['-m', 'http.server', String(port), '--bind', '127.0.0.1'], { cwd: root, stdio: 'ignore' });
await new Promise((r) => setTimeout(r, 300));
const browser = await chromium.launch({
  executablePath: '/usr/local/bin/google-chrome',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const report = { capturedHtmlFixture: true, wordpressBoot: false, pages: {} };

async function open(file, viewport, opts = {}) {
  const context = await browser.newContext({
    viewport,
    hasTouch: Boolean(opts.touch),
    isMobile: Boolean(opts.touch),
    reducedMotion: opts.reduced ? 'reduce' : 'no-preference',
  });
  const page = await context.newPage();
  await page.goto(`http://127.0.0.1:${port}/${file}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForTimeout(500);
  return { context, page };
}

try {
  await mkdir(outDir, { recursive: true });
  for (const [name, file] of [
    ['start-before', 'start-before.html'],
    ['start-after', 'start-after.html'],
    ['engine-before', 'engine-before.html'],
    ['engine-after', 'engine-after.html'],
  ]) {
    for (const vp of [
      { w: 1440, h: 1000, touch: false },
      { w: 390, h: 844, touch: true },
    ]) {
      const { context, page } = await open(file, { width: vp.w, height: vp.h }, { touch: vp.touch });
      const measure = await page.evaluate(() => {
        const hex = (c) => {
          const m = (c || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
          return m ? '#' + [m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('') : c;
        };
        const h1 = document.querySelector('h1');
        const hs = h1 ? getComputedStyle(h1) : null;
        const doc = document.documentElement;
        const summary = document.querySelector('.ww-p4-folded > summary, .ww-p4-case > summary');
        const nav = document.querySelector('.fw-nav-item, .sh-next a, .sh-btn');
        return {
          h1: h1 ? { text: h1.textContent.replace(/\s+/g, ' ').trim(), font: hs.fontFamily, size: hs.fontSize, color: hex(hs.color) } : null,
          overflow: doc.scrollWidth > doc.clientWidth + 1,
          folded: document.querySelectorAll('details.ww-p4-folded, details.ww-p4-case').length,
          summaryH: summary ? Math.round(summary.getBoundingClientRect().height) : null,
          navH: nav ? Math.round(nav.getBoundingClientRect().height) : null,
        };
      });
      await page.addScriptTag({ content: axeSource });
      const violations = await page.evaluate(async () => {
        const r = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa'] } });
        return r.violations.map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length, sample: v.nodes[0] ? v.nodes[0].html.slice(0, 140) : '' }));
      });
      report.pages[`${name}-${vp.w}`] = { measure, violations };
      await page.screenshot({ path: path.join(outDir, `phase4-${name}-${vp.w}.png`) });
      await context.close();
    }
  }
  {
    const { context, page } = await open('start-after.html', { width: 1440, height: 900 }, { reduced: true });
    const summary = page.locator('.ww-p4-folded > summary');
    await summary.focus();
    report.startFocus = await page.evaluate(() => {
      const el = document.activeElement;
      const s = getComputedStyle(el);
      return { text: (el.textContent || '').trim().slice(0, 60), outline: s.outlineStyle, width: s.outlineWidth, animation: s.animationName };
    });
    await context.close();
  }
  {
    const { context, page } = await open('engine-after.html', { width: 390, height: 844 }, { touch: true, reduced: true });
    const nav = page.locator('.fw-nav-item').first();
    await nav.focus();
    report.engineFocus = await page.evaluate(() => {
      const el = document.activeElement;
      const s = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      return { text: (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 40), h: Math.round(r.height), outline: s.outlineStyle, width: s.outlineWidth };
    });
    await context.close();
  }
  const h1 = report.pages['start-after-1440'].measure.h1;
  if (h1) {
    report.contrastNote = 'H1 color measured; background is the warm band #F7E3C4 at the bottom of the existing gradient.';
    report.h1ContrastOnBand = Number(contrast(h1.color, '#f7e3c4').toFixed(2));
  }
  await writeFile('/workspace/phase4/paths-results.json', JSON.stringify(report, null, 2));
  await writeFile(path.join(outDir, 'phase4-paths-results.json'), JSON.stringify(report, null, 2));
  const brief = {};
  for (const [k, v] of Object.entries(report.pages)) {
    brief[k] = { h1: v.measure.h1, overflow: v.measure.overflow, folded: v.measure.folded, summaryH: v.measure.summaryH, navH: v.measure.navH, axe: v.violations.map((x) => x.id + ':' + x.nodes) };
  }
  console.log(JSON.stringify({ brief, startFocus: report.startFocus, engineFocus: report.engineFocus, h1ContrastOnBand: report.h1ContrastOnBand }, null, 2));
} finally {
  await browser.close();
  server.kill();
}
