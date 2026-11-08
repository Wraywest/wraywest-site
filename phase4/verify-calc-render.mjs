/**
 * Captured-HTML fixtures for the three calculators.
 * Runs the page's own script. Does not submit a form.
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
const port = 8777;
const root = '/tmp/ww-serve-calc';

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
  await swap('/tmp/ww-captures/quit-9-to-5-calculator.html', 'quit-before.html', 'css/before.css');
  await swap('/tmp/ww-render-quit.html', 'quit-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/freelance-rate-calculator.html', 'rate-before.html', 'css/before.css');
  await swap('/tmp/ww-render-rate.html', 'rate-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/lead-magnet-builder.html', 'lmb-before.html', 'css/before.css');
  await swap('/tmp/ww-captures/lead-magnet-builder.html', 'lmb-after.html', 'css/after.css');
}

await prepare();
const server = spawn('/usr/bin/python3', ['-m', 'http.server', String(port), '--bind', '127.0.0.1'], { cwd: root, stdio: 'ignore' });
await new Promise((r) => setTimeout(r, 300));
const browser = await chromium.launch({
  executablePath: '/usr/local/bin/google-chrome',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const report = { capturedHtmlFixture: true, wordpressBoot: false, formsSubmitted: false, pages: {}, cases: {} };

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

async function rateCase(page, values) {
  return page.evaluate((vals) => {
    Object.entries(vals).forEach(([id, value]) => {
      const el = document.getElementById(id);
      el.value = String(value);
      el.dispatchEvent(new Event('input', { bubbles: true }));
    });
    return {
      rate: document.getElementById('frc-rate').textContent,
      gross: document.getElementById('frc-gross').textContent,
      billable: document.getElementById('frc-billable').textContent,
      day: document.getElementById('frc-day').textContent,
      values: ['frc-take', 'frc-hours', 'frc-weeksoff', 'frc-exp', 'frc-buffer'].map((id) => document.getElementById(id).value),
    };
  }, values);
}

async function quitCase(page, values) {
  return page.evaluate((vals) => {
    Object.entries(vals).forEach(([id, value]) => {
      const el = document.getElementById(id);
      el.value = String(value);
      el.dispatchEvent(new Event('input', { bubbles: true }));
    });
    return {
      text: document.getElementById('qcalc-result').innerText.replace(/\s+/g, ' ').trim(),
      values: ['qc-w2', 'qc-wh', 'qc-mrr', 'qc-g', 'qc-ct', 'qc-exp', 'qc-bm', 'qc-s'].map((id) => document.getElementById(id).value),
    };
  }, values);
}

try {
  await mkdir(outDir, { recursive: true });
  const pairs = [
    ['quit-before', 'quit-before.html'],
    ['quit-after', 'quit-after.html'],
    ['rate-before', 'rate-before.html'],
    ['rate-after', 'rate-after.html'],
    ['lmb-before', 'lmb-before.html'],
    ['lmb-after', 'lmb-after.html'],
  ];
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
        const control = document.querySelector('#qc-w2, #frc-take, #lmb-topic');
        const cs = control ? getComputedStyle(control) : null;
        const doc = document.documentElement;
        return {
          h1: h1 ? h1.textContent.replace(/\s+/g, ' ').trim() : null,
          h1size: hs ? hs.fontSize : null,
          overflow: doc.scrollWidth > doc.clientWidth + 1,
          controlH: control ? Math.round(control.getBoundingClientRect().height) : null,
          controlColor: cs ? hex(cs.color) : null,
        };
      });
      await page.addScriptTag({ content: axeSource });
      const violations = await page.evaluate(async () => {
        const r = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa'] } });
        return r.violations.map((v) => ({
          id: v.id,
          impact: v.impact,
          nodes: v.nodes.length,
          sample: v.nodes[0] ? v.nodes[0].html.slice(0, 140) : '',
          ratio: v.nodes[0] && v.nodes[0].any && v.nodes[0].any[0] && v.nodes[0].any[0].data ? v.nodes[0].any[0].data.contrastRatio : null,
        }));
      });
      report.pages[`${name}-${vp.w}`] = { measure, violations };
      await page.screenshot({ path: path.join(outDir, `phase4-${name}-${vp.w}.png`) });
      await context.close();
    }
  }

  const rateValues = {
    defaults: null,
    zero: { 'frc-take': 0, 'frc-hours': 1, 'frc-weeksoff': 0, 'frc-exp': 0, 'frc-buffer': 0 },
    boundary: { 'frc-take': 60000, 'frc-hours': 1, 'frc-weeksoff': 50, 'frc-exp': 0, 'frc-buffer': 80 },
  };
  report.cases.rate = {};
  for (const [label, values] of Object.entries(rateValues)) {
    const pair = {};
    for (const file of ['rate-before.html', 'rate-after.html']) {
      const { context, page } = await open(file, { width: 1440, height: 900 });
      if (label === 'defaults') {
        pair[file] = await page.evaluate(() => ({
          rate: document.getElementById('frc-rate').textContent,
          gross: document.getElementById('frc-gross').textContent,
          billable: document.getElementById('frc-billable').textContent,
          day: document.getElementById('frc-day').textContent,
        }));
      } else {
        pair[file] = await rateCase(page, values);
      }
      await context.close();
    }
    report.cases.rate[label] = { equal: JSON.stringify(pair['rate-before.html']) === JSON.stringify(pair['rate-after.html']), before: pair['rate-before.html'], after: pair['rate-after.html'] };
  }
  {
    const pair = {};
    for (const file of ['rate-before.html', 'rate-after.html']) {
      const { context, page } = await open(file, { width: 1440, height: 900 });
      await page.locator('#frc-example').click();
      pair[file] = await page.evaluate(() => ({
        rate: document.getElementById('frc-rate').textContent,
        values: ['frc-take', 'frc-hours', 'frc-weeksoff', 'frc-exp', 'frc-buffer'].map((id) => document.getElementById(id).value),
      }));
      await context.close();
    }
    report.cases.rate.example = { equal: JSON.stringify(pair['rate-before.html']) === JSON.stringify(pair['rate-after.html']), before: pair['rate-before.html'], after: pair['rate-after.html'] };
  }

  const quitValues = {
    defaults: null,
    zero: { 'qc-w2': 0, 'qc-wh': 0, 'qc-mrr': 0, 'qc-g': 0, 'qc-ct': 0, 'qc-exp': 0, 'qc-bm': 0, 'qc-s': 0 },
    boundary: { 'qc-w2': 200000, 'qc-wh': 60, 'qc-mrr': 0, 'qc-g': 50, 'qc-ct': 50, 'qc-exp': 10000, 'qc-bm': 36, 'qc-s': 0 },
  };
  report.cases.quit = {};
  for (const [label, values] of Object.entries(quitValues)) {
    const pair = {};
    for (const file of ['quit-before.html', 'quit-after.html']) {
      const { context, page } = await open(file, { width: 1440, height: 900 });
      if (label === 'defaults') {
        pair[file] = await page.evaluate(() => document.getElementById('qcalc-result').innerText.replace(/\s+/g, ' ').trim());
      } else {
        pair[file] = await quitCase(page, values);
      }
      await context.close();
    }
    report.cases.quit[label] = { equal: JSON.stringify(pair['quit-before.html']) === JSON.stringify(pair['quit-after.html']), before: pair['quit-before.html'], after: pair['quit-after.html'] };
  }

  report.cases.lmb = {};
  {
    const pair = {};
    for (const file of ['lmb-before.html', 'lmb-after.html']) {
      const { context, page } = await open(file, { width: 1440, height: 900 });
      pair[file] = await page.evaluate(() => {
        const topic = document.getElementById('lmb-topic');
        const audience = document.getElementById('lmb-audience');
        const goal = document.getElementById('lmb-goal');
        topic.value = 'side income';
        audience.selectedIndex = 1;
        goal.selectedIndex = 1;
        topic.dispatchEvent(new Event('input', { bubbles: true }));
        audience.dispatchEvent(new Event('change', { bubbles: true }));
        goal.dispatchEvent(new Event('change', { bubbles: true }));
        const result = document.getElementById('lmb-result');
        return {
          topic: topic.value,
          audience: audience.value,
          goal: goal.value,
          text: (result.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 500),
        };
      });
      await context.close();
    }
    report.cases.lmb.normal = { equal: JSON.stringify(pair['lmb-before.html']) === JSON.stringify(pair['lmb-after.html']), before: pair['lmb-before.html'], after: pair['lmb-after.html'] };
  }

  report.focus = {};
  for (const [key, file, sel] of [
    ['quit', 'quit-after.html', '#qc-w2'],
    ['rate', 'rate-after.html', '#frc-take'],
    ['lmb', 'lmb-after.html', '#lmb-topic'],
  ]) {
    const { context, page } = await open(file, { width: 390, height: 844 }, { touch: true, reduced: true });
    report.focus[key] = await page.evaluate((selector) => {
      const el = document.querySelector(selector);
      el.focus();
      const s = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      return { h: Math.round(r.height), outline: s.outlineStyle, width: s.outlineWidth, transition: s.transitionProperty };
    }, sel);
    await context.close();
  }

  const sub = report.pages['rate-after-1440'].measure;
  if (sub.controlColor && sub.controlColor.startsWith('#')) {
    report.rateInputContrast = Number(contrast(sub.controlColor, '#ffffff').toFixed(2));
  }
  await writeFile('/workspace/phase4/calculator-results.json', JSON.stringify(report, null, 2));
  await writeFile(path.join(outDir, 'phase4-calculator-results.json'), JSON.stringify(report, null, 2));
  const brief = {};
  for (const [k, v] of Object.entries(report.pages)) {
    brief[k] = { ...v.measure, axe: v.violations.map((x) => x.id + ':' + x.impact + ':' + x.nodes + (x.ratio ? ':' + x.ratio : '')) };
  }
  console.log(JSON.stringify({ brief, cases: report.cases, focus: report.focus, rateInputContrast: report.rateInputContrast }, null, 2));
} finally {
  await browser.close();
  server.kill();
}
