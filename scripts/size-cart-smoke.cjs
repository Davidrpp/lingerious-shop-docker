const assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
(async()=>{const browser=await chromium.launch({headless:true,executablePath:process.env.EDGE_BINARY||undefined});try{
 const ctx=await browser.newContext({viewport:{width:390,height:844}}),page=await ctx.newPage();
 await page.goto('https://lingerious.shop/product/sheer-opaque-floral-lingerie-set/?cartqa='+Date.now(),{waitUntil:'domcontentloaded'});
 await page.locator('.lg-variant-picker--size [data-value="s32or70abc"]').waitFor();
 await page.locator('.lg-variant-picker--color [data-value="black-set"]').click();
 await page.locator('.lg-variant-picker--size [data-value="s32or70abc"]').click();
 await page.waitForFunction(()=>document.querySelector('input[name="variation_id"]')?.value==='4215',null,{timeout:15000});
 await page.waitForFunction(()=>!document.querySelector('.single_add_to_cart_button')?.classList.contains('disabled'),null,{timeout:15000});
 await page.locator('.single_add_to_cart_button').click();
 await page.waitForTimeout(1800);
 await page.goto('https://lingerious.shop/cart/?cartqa='+Date.now(),{waitUntil:'domcontentloaded'});
 await page.waitForFunction(()=>document.querySelector('.wc-block-cart-items__row')?.textContent?.includes('Floral'),null,{timeout:25000});
 const text=await page.locator('.wc-block-cart-items__row').first().innerText();
 console.log('CART_ROW='+JSON.stringify(text));
 assert.match(text,/Floral Bra.*Thong Set/i);assert.match(text,/Size:\s*S\s*\(32\s*\/\s*70\s*ABC\)/i);assert.doesNotMatch(text,/Cup size/i);
 console.log('CART_SIZE_PASS variation=4215 label=Size: S (32 / 70 ABC)');await ctx.close();
 }finally{await browser.close()}})().catch(e=>{console.error(e);process.exitCode=1});
