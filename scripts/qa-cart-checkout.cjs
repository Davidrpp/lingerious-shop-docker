/* Safe smoke test: add one item to an isolated browser session; never place an order. */
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright');
(async () => {
 const browser = await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH||undefined});
 const context = await browser.newContext({viewport:{width:390,height:844}});
 const page = await context.newPage();
 const errors=[]; page.on('pageerror',error=>errors.push((error.stack||error.message).slice(0,550)));
 const product='https://lingerious.shop/product/black-high-waisted-bodysuit-glossy-leather-cut-outs/';
 await page.goto(product,{waitUntil:'networkidle',timeout:30000});
 const selects=page.locator('form.variations_form select');
 console.log('VARIANT_SELECTS='+await selects.count());
 if(await selects.count()) {
  const options=await selects.first().locator('option').evaluateAll(nodes=>nodes.map(n=>({value:n.value,text:n.textContent})).filter(n=>n.value));
  console.log('OPTIONS='+JSON.stringify(options));
  if(!options.length)throw Error('No purchasable variant available');
  await selects.first().selectOption(options[0].value);
  await page.waitForTimeout(1700);
  console.log('VARIATION_ID='+await page.locator('input[name=variation_id]').inputValue().catch(()=> 'missing'));
 }
 const buy=page.locator('button.single_add_to_cart_button');
 console.log('BUTTON='+JSON.stringify({exists:await buy.count(),enabled:await buy.isEnabled()}));
 if(!await buy.isEnabled()) throw Error('Add to bag disabled after variant choice');
 await buy.click(); await page.waitForTimeout(2400);
 console.log('PRODUCT_NOTICES='+JSON.stringify((await page.locator('.woocommerce-notices-wrapper').allInnerTexts()).slice(0,2)));
 console.log('AFTER_PRODUCT_CART_API='+JSON.stringify(await page.request.get('https://lingerious.shop/wp-json/wc/store/v1/cart').then(r=>({status:r.status(),items:r.ok()?r.json().then(j=>j.items?.length):0})).then(async o=>({...o,items:await o.items})).catch(e=>String(e))));
 console.log('AFTER_ADD='+page.url());
 const cart=await page.goto('https://lingerious.shop/cart/',{waitUntil:'domcontentloaded',timeout:30000});
 await page.waitForTimeout(3000);
 const state=await page.evaluate(()=>({url:location.href,brand:!!document.querySelector('.lg-brand'),footer:!!document.querySelector('.lg-footer'),text:document.body.innerText.slice(0,1100)}));
 console.log('CART='+JSON.stringify({status:cart.status(),...state}));
 if(!state.brand||!state.footer||!state.text.toLowerCase().includes('black'))throw Error('Cart does not show added product or branding');
 const checkout=await page.goto('https://lingerious.shop/checkout/',{waitUntil:'domcontentloaded',timeout:30000});
 await page.waitForTimeout(3200);
 const check=await page.evaluate(()=>({url:location.href,brand:!!document.querySelector('.lg-brand'),footer:!!document.querySelector('.lg-footer'),fields:document.querySelectorAll('input').length,checkoutBlocks:document.querySelectorAll('.wc-block-checkout,.woocommerce-checkout').length,text:document.body.innerText.slice(0,500)}));
 console.log('CHECKOUT='+JSON.stringify({status:checkout.status(),url:check.url,brand:check.brand,footer:check.footer,fields:check.fields,checkoutBlocks:check.checkoutBlocks,text:check.text.slice(0,160)}));
 const width=await page.evaluate(()=>({inner:innerWidth,scroll:document.documentElement.scrollWidth}));
 console.log('CHECKOUT_WIDTH='+JSON.stringify(width));
 if(process.env.QA_OUTPUT){require('fs').mkdirSync(process.env.QA_OUTPUT,{recursive:true});await page.screenshot({path:require('path').join(process.env.QA_OUTPUT,'checkout-390.png'),fullPage:true,animations:'disabled',timeout:20000});}
 console.log('PAGE_ERRORS='+JSON.stringify(errors));
 if(!check.url.includes('/checkout/')||!check.brand||!check.footer||!check.fields||width.scroll>width.inner+3)throw Error('Checkout failed smoke test');
 await browser.close(); console.log('CHECKOUT_SMOKE_PASS (no order placed; JS errors reported separately)');
})().catch(e=>{console.error('CHECKOUT_SMOKE_FAIL '+e.message);process.exitCode=1;});
