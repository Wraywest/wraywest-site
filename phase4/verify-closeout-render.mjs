/**
 * Representative captured-HTML check after the token/motion closeout.
 * Not a WordPress boot.
 */
import { chromium } from 'playwright-core';
import { createRequire } from 'node:module';
import { spawn } from 'node:child_process';
import { mkdir, writeFile, readFile, copyFile } from 'node:fs/promises';
import path from 'node:path';

const require = createRequire(import.meta.url);
const axeSource = require('axe-core').source;
const outDir = '/opt/cursor/artifacts';
const port = 8781;
const root = '/tmp/ww-serve-close';

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
  await swap('/tmp/ww-captures/HOME.html', 'home-before.html', 'css/before.css');
  await swap('/tmp/ww-captures/HOME.html', 'home-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/blog.html', 'blog-before.html', 'css/before.css');
  await swap('/tmp/ww-captures/blog.html', 'blog-after.html', 'css/after.css');
  await swap('/tmp/ww-captures/blog_robinhood-review.html', 'robin-before.html', 'css/before.css');
  await swap('/tmp/ww-render-robinhood.html', 'robin-after.html', 'css/after.css');
}

await prepare();
const server = spawn('/usr/bin/python3', ['-m', 'http.server', String(port), '--bind', '127.0.0.1'], { cwd: root, stdio: 'ignore' });
await new Promise((r) => setTimeout(r, 250));
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
  await page.waitForTimeout(400);
  return { context, page };
}

try {
  await mkdir(outDir, { recursive: true });
  const pairs = [
    ['home-before', 'home-before.html'],
    ['home-after', 'home-after.html'],
    ['blog-before', 'blog-before.html'],
    ['blog-after', 'blog-after.html'],
    ['robin-before', 'robin-before.html'],
    ['robin-after', 'robin-after.html'],
  ];
  for (const [name, file] of pairs) {
    for (const vp of [
      { w: 1440, h: 1000, touch: false },
      { w: 390, h: 844, touch: true },
    ]) {
      const { context, page } = await open(file, { width: vp.w, height: vp.h }, { touch: vp.touch });
      const measure = await page.evaluate(() => {
        const h1 = document.querySelector('#wwl-h, h1');
        const hs = h1 ? getComputedStyle(h1) : null;
        const pager = document.querySelector('.nav-links .page-numbers, .ww-hero-cta, .ww-path-card');
        const ps = pager ? getComputedStyle(pager) : null;
        const label = document.querySelector('.ww-use-label');
        const doc = document.documentElement;
        return {
          h1: h1 ? h1.textContent.replace(/\s+/g, ' ').trim().slice(0, 80) : null,
          font: hs ? hs.fontFamily : null,
          size: hs ? hs.fontSize : null,
          color: hs ? hs.color : null,
          overflow: doc.scrollWidth > doc.clientWidth + 1,
          transition: ps ? ps.transitionProperty : null,
          label: label ? label.textContent.trim() : null,
        };
      });
      await page.addScriptTag({ content: axeSource });
      const violations = await page.evaluate(async () => {
        const r = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa'] } });
        return r.violations.map((v) => ({
          id: v.id,
          impact: v.impact,
          nodes: v.nodes.length,
          ratio: v.nodes[0] && v.nodes[0].any && v.nodes[0].any[0] && v.nodes[0].any[0].data ? v.nodes[0].any[0].data.contrastRatio : null,
        }));
      });
      report.pages[`${name}-${vp.w}`] = { measure, violations };
      if (name.endsWith('after') || name === 'home-before') {
        await page.screenshot({ path: path.join(outDir, `phase4-close-${name}-${vp.w}.png`) });
      }
      await context.close();
    }
  }
  {
    const { context, page } = await open('blog-after.html', { width: 1440, height: 900 }, { reduced: true });
    const pager = page.locator('.nav-links .page-numbers').first();
    report.reduced = await page.evaluate(() => {
      const el = document.querySelector('.nav-links .page-numbers');
      if (!el) return { missing: true };
      el.focus();
      const s = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      return { h: Math.round(r.height), transition: s.transitionProperty, duration: s.transitionDuration, outline: s.outlineStyle };
    });
    await context.close();
  }
  const before = report.pages['home-before-1440'].measure;
  const after = report.pages['home-after-1440'].measure;
  report.homepageLocked = before.h1 === after.h1 && before.font === after.font && before.size === after.size && before.color === after.color;
  await writeFile('/workspace/phase4/closeout-results.json', JSON.stringify(report, null, 2));
  const brief = {};
  for (const [k, v] of Object.entries(report.pages)) {
    brief[k] = { ...v.measure, axe: v.violations.map((x) => x.id + ':' + x.impact + ':' + x.nodes + (x.ratio ? ':' + x.ratio : '')) };
  }
  console.log(JSON.stringify({ homepageLocked: report.homepageLocked, reduced: report.reduced, brief }, null, 2));
} finally {
  await browser.close();
  server.kill();
}
