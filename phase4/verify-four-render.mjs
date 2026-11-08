/**
 * Captured-HTML fixtures for Tools, Resources, About, and Contact.
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
const port = 8768;
const root = '/tmp/ww-serve-four';

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
  await swap('/tmp/ww-captures/tools.html', 'tools-before.html', 'css/before.css');
  await swap('/tmp/ww-render-tools.html', 'tools-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/resources.html', 'resources-before.html', 'css/before.css');
  await swap('/tmp/ww-captures/resources.html', 'resources-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/about.html', 'about-before.html', 'css/before.css');
  await swap('/tmp/ww-captures/about.html', 'about-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/contact.html', 'contact-before.html', 'css/before.css');
  await swap('/tmp/ww-captures/contact.html', 'contact-after.html', 'css/after.css');
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
  await page.waitForTimeout(600);
  return { context, page };
}

const pairs = [
  ['tools-before', 'tools-before.html'],
  ['tools-after', 'tools-after.html'],
  ['resources-before', 'resources-before.html'],
  ['resources-after', 'resources-after.html'],
  ['about-before', 'about-before.html'],
  ['about-after', 'about-after.html'],
  ['contact-before', 'contact-before.html'],
  ['contact-after', 'contact-after.html'],
];

try {
  await mkdir(outDir, { recursive: true });
  for (const [name, file] of pairs) {
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
        const sub = document.querySelector('.ww-site-hero-sub');
        const ss = sub ? getComputedStyle(sub) : null;
        const hero = document.querySelector('.ww-site-hero');
        const heroS = hero ? getComputedStyle(hero) : null;
        const doc = document.documentElement;
        const next = document.querySelector('.ti-link, .rs-fw-link, .ab-cta-btn, .ww-contact-form button[type=submit], form.wpforms-form button[type=submit]');
        const row = document.querySelector('.ti-row');
        const labels = [...document.querySelectorAll('.ti-name')].map((n) => {
          const meta = n.parentElement ? n.parentElement.querySelector('.ti-used, .ti-researched') : null;
          return { name: (n.textContent || '').trim(), label: meta ? meta.textContent.trim() : '' };
        });
        const portrait = document.querySelector('img[data-src*="dwayne-kitchen-line"], img[src*="dwayne-kitchen-line"]');
        const forms = [...document.querySelectorAll('form')].map((f) => (f.getAttribute('action') || '') + '|' + (f.className || '').slice(0, 40));
        return {
          h1: h1 ? { text: h1.textContent.replace(/\s+/g, ' ').trim(), font: hs.fontFamily, size: hs.fontSize, color: hex(hs.color) } : null,
          sub: sub ? { color: hex(ss.color), size: ss.fontSize } : null,
          heroBorder: heroS ? heroS.borderBottomWidth + ' ' + heroS.borderBottomStyle : null,
          heroGap: heroS ? heroS.marginBottom : null,
          overflow: doc.scrollWidth > doc.clientWidth + 1,
          nextH: next ? Math.round(next.getBoundingClientRect().height) : null,
          nextText: next ? (next.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 48) : null,
          rowTransition: row ? getComputedStyle(row).transitionProperty : null,
          labels,
          portrait: Boolean(portrait),
          forms,
        };
      });
      await page.addScriptTag({ content: axeSource });
      const violations = await page.evaluate(async () => {
        const r = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa'] } });
        return r.violations.map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length, sample: v.nodes[0] ? v.nodes[0].html.slice(0, 160) : '' }));
      });
      report.pages[`${name}-${vp.w}`] = { measure, violations };
      await page.screenshot({ path: path.join(outDir, `phase4-${name}-${vp.w}.png`) });
      await context.close();
    }
  }
  report.focus = {};
  for (const [key, file, sel] of [
    ['tools', 'tools-after.html', 'a.ti-link'],
    ['resources', 'resources-after.html', 'a.rs-fw-link'],
    ['about', 'about-after.html', 'a.ab-cta-btn'],
    ['contact', 'contact-after.html', '#wpforms-submit-3684'],
  ]) {
    const { context, page } = await open(file, { width: 390, height: 844 }, { touch: true, reduced: true });
    report.focus[key] = await page.evaluate((selector) => {
      const el = document.querySelector(selector);
      if (!el) return { missing: true };
      el.focus();
      const s = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      const row = document.querySelector('.ti-row');
      const span = document.querySelector('.ti-link span');
      return {
        active: document.activeElement === el,
        text: (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 48),
        h: Math.round(r.height),
        outline: s.outlineStyle,
        width: s.outlineWidth,
        rowTransition: row ? getComputedStyle(row).transitionProperty : null,
        spanTransition: span ? getComputedStyle(span).transitionProperty : null,
      };
    }, sel);
    await context.close();
  }
  {
    const { context, page } = await open('tools-after.html', { width: 1440, height: 1000 });
    report.consent = await page.evaluate(() => {
      const hex = (c) => {
        const m = (c || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        return m ? '#' + [m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('') : c;
      };
      const buttons = [...document.querySelectorAll('button')].map((b) => {
        const s = getComputedStyle(b);
        const r = b.getBoundingClientRect();
        return { text: (b.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 40), color: hex(s.color), bg: hex(s.backgroundColor), h: Math.round(r.height), w: Math.round(r.width) };
      }).filter((b) => /agree|opt out|personal/i.test(b.text));
      return { buttons, script: Boolean(document.querySelector('script[src*="uniconsent"]')) };
    });
    report.crops = [];
    for (const [label, file] of [
      ['ConvertKit', 'phase4-tools-kit-label.png'],
      ['Notion', 'phase4-tools-notion-label.png'],
      ['Repurpose.io', 'phase4-tools-repurpose-label.png'],
      ['Make.com', 'phase4-tools-make-label.png'],
    ]) {
      const row = page.locator('.ti-row').filter({ hasText: label }).first();
      await row.evaluate((el) => {
        const y = el.getBoundingClientRect().top + window.scrollY - 72;
        window.scrollTo(0, Math.max(0, y));
      });
      await page.waitForTimeout(150);
      const box = await row.boundingBox();
      report.crops.push({ label, box });
      if (!box || box.width < 4 || box.height < 4) continue;
      const clip = {
        x: Math.max(0, box.x),
        y: Math.max(0, box.y),
        width: Math.min(box.width, 1440 - Math.max(0, box.x)),
        height: Math.min(box.height, 180, 1000 - Math.max(0, box.y)),
      };
      if (clip.width > 4 && clip.height > 4) {
        try {
          await page.screenshot({ path: path.join(outDir, file), clip });
        } catch (err) {
          report.crops[report.crops.length - 1].shotError = String(err).slice(0, 180);
        }
      }
    }
    await context.close();
  }
  const sub = report.pages['tools-after-1440'].measure.sub;
  if (sub && sub.color && sub.color.startsWith('#')) {
    report.dekContrastOnBand = Number(contrast(sub.color, '#f7e3c4').toFixed(2));
    report.dekContrastOnLight = Number(contrast(sub.color, '#fbebd0').toFixed(2));
  }
  const h1 = report.pages['about-after-1440'].measure.h1;
  if (h1 && h1.color && h1.color.startsWith('#')) {
    report.aboutH1Contrast = Number(contrast(h1.color, '#f7e3c4').toFixed(2));
  }
  await writeFile('/workspace/phase4/four-pages-results.json', JSON.stringify(report, null, 2));
  await writeFile(path.join(outDir, 'phase4-four-pages-results.json'), JSON.stringify(report, null, 2));
  const brief = {};
  for (const [k, v] of Object.entries(report.pages)) {
    brief[k] = {
      h1: v.measure.h1,
      sub: v.measure.sub,
      heroBorder: v.measure.heroBorder,
      heroGap: v.measure.heroGap,
      overflow: v.measure.overflow,
      nextH: v.measure.nextH,
      nextText: v.measure.nextText,
      portrait: v.measure.portrait,
      axe: v.violations.map((x) => x.id + ':' + x.impact + ':' + x.nodes),
    };
  }
  console.log(JSON.stringify({
    brief,
    focus: report.focus,
    dekContrastOnBand: report.dekContrastOnBand,
    dekContrastOnLight: report.dekContrastOnLight,
    aboutH1Contrast: report.aboutH1Contrast,
    toolsLabels: report.pages['tools-after-1440'].measure.labels,
    consent: report.consent,
  }, null, 2));
} finally {
  await browser.close();
  server.kill();
}
