/* Visual regression and horizontal-overflow smoke test. Run with PLAYWRIGHT_PATH set to installed playwright module. */
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright');
const fs = require('fs');
const path = require('path');
const output = process.env.QA_OUTPUT || path.join(process.cwd(), 'visual-audit');
fs.mkdirSync(output, { recursive: true });
const pages = [
  ['home', '/'], ['shop', '/shop/'], ['shop-page-2', '/shop/page/2/'], ['filtered', '/shop/?color=green'], ['bras', '/bras/'],
  ['product', '/product/black-high-waisted-bodysuit-glossy-leather-cut-outs/'],
  ['cart', '/cart/'],
];
const viewports = [{ width: 390, height: 844 }, { width: 768, height: 960 }, { width: 1440, height: 900 }];
(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_PATH || undefined });
  const issues = [];
  for (const viewport of viewports) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: 1, reducedMotion: 'reduce' });
    const page = await context.newPage();
    const pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.message.slice(0, 140)));
    for (const [name, route] of pages) {
      pageErrors.length = 0;
      const url = 'https://lingerious.shop' + route + (route.includes('?') ? '&' : '?') + 'qa=' + Date.now();
      try {
        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await page.waitForTimeout(950);
        await page.evaluate(async () => { const imgs=Array.from(document.images); imgs.forEach(img => img.loading='eager'); await Promise.race([Promise.all(imgs.map(img => img.decode().catch(() => {}))), new Promise(resolve => setTimeout(resolve, 4000))]); });
        const info = await page.evaluate(() => {
          const html = document.documentElement;
          const grid = document.querySelector('ul.products,ul.wc-block-product-template');
          const hero = document.querySelector('.lg-atelier-hero');
          const brand = document.querySelector('.lg-brand');
          const header = document.querySelector('.lg-header');
          const footer = document.querySelector('.lg-footer');
          const overflow = Array.from(document.body.querySelectorAll('*')).filter(el => {
            const s = getComputedStyle(el), r = el.getBoundingClientRect();
            return s.display !== 'none' && s.position !== 'fixed' && r.width && r.right > innerWidth + 3 && r.left < innerWidth;
          }).slice(0, 4).map(el => el.tagName.toLowerCase() + '.' + String(el.className).split(' ').slice(0, 2).join('.'));
          return {
            viewport: innerWidth, scroll: html.scrollWidth,
            header: !!header, footer: !!footer, brandVisible: !!brand && brand.getBoundingClientRect().width > 0,
            gridColumns: grid ? getComputedStyle(grid).gridTemplateColumns : 'none',
            heroColumns: hero ? getComputedStyle(hero).gridTemplateColumns : 'none',
            products: grid ? grid.querySelectorAll('li.product,li.wc-block-product').length : 0,
            overflow,
          };
        });
        const filename = path.join(output, name + '-' + viewport.width + '.png');
        await page.screenshot({ path: filename, fullPage: name === 'home' || name === 'shop', animations: 'disabled', timeout: 25000 });
        const result = { page: name, width: viewport.width, status: response.status(), ...info, errors: pageErrors };
        console.log('QA ' + JSON.stringify(result));
        if ((name === 'shop-page-2' && info.products !== 14) || (name === 'filtered' && info.products !== 2) || result.status !== 200 || !info.header || !info.footer || !info.brandVisible || info.scroll > viewport.width + 3 || pageErrors.length) issues.push(result);
        if (name === 'home' && viewport.width === 390) {
          const menu = page.locator('.lg-mobile-menu summary');
          await menu.click();
          const panel = await page.locator('.lg-mobile-menu__panel').boundingBox();
          console.log('MENU ' + JSON.stringify({ opened: await page.locator('.lg-mobile-menu').getAttribute('open') !== null, panel }));
          if (!panel || panel.x < -1 || panel.x + panel.width > viewport.width + 1) issues.push({ issue: 'mobile menu overflow', panel });
        }
      } catch (error) {
        issues.push({ page: name, width: viewport.width, error: String(error).slice(0, 220) });
        console.log('QA_ERROR ' + name + ' ' + viewport.width + ' ' + String(error));
      }
    }
    await context.close();
  }
  await browser.close();
  console.log('QA_SUMMARY ' + JSON.stringify({ cases: viewports.length * pages.length, failures: issues.length, issues }));
  process.exitCode = issues.length ? 1 : 0;
})().catch(e => { console.error(e); process.exitCode = 2; });
