const assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const edge=process.env.EDGE_BINARY;
const base='https://lingerious.shop';
const routes=['/product/sheer-opaque-floral-lingerie-set/','/product/floral-lace-longline-lingerie-set-4-colors/'];
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:edge,args:['--no-sandbox']});let failures=0;
 for(const width of [320,390,768,1440])for(const route of routes){
  const context=await browser.newContext({viewport:{width,height:860}}),page=await context.newPage();const js=[];
  page.on('pageerror',e=>js.push(e.message));
  try{
   const res=await page.goto(base+route+'?designqa='+Date.now(),{waitUntil:'domcontentloaded',timeout:35000});
   await page.locator('.lg-variant-picker--size .lg-variant-option').first().waitFor({timeout:15000});
   const state=await page.evaluate(()=>{const s=document.querySelector('.lg-variant-picker--size button'),c=document.querySelector('.lg-variant-picker--color button'), css=getComputedStyle(s), cc=getComputedStyle(c);return {width:document.documentElement.scrollWidth,viewport:innerWidth,sizeWidth:Math.round(s.getBoundingClientRect().width),sizeHeight:Math.round(s.getBoundingClientRect().height),sizeBorderLeft:css.borderLeftWidth,sizeBorderBottom:css.borderBottomWidth,swatchWidth:Math.round(c.getBoundingClientRect().width),selectorCSS:[...document.styleSheets].some(x=>x.href&&x.href.includes('selectors-v4.css?ver=0.9.7')),guide:!!document.querySelector('.lg-variant-guide'),live:document.querySelector('.lg-variant-current')?.getAttribute('aria-live')}});
   assert.equal(res.status(),200);assert.ok(state.selectorCSS);assert.equal(state.sizeBorderLeft,'0px');assert.equal(state.sizeBorderBottom,'1px');assert.ok(state.sizeHeight>=44);assert.ok(state.swatchWidth>=44);assert.ok(state.guide);assert.equal(state.live,'polite');assert.ok(state.width<=width+2);assert.deepEqual(js,[]);
   await page.locator('.lg-variant-picker--color button:not(:disabled)').first().click();
   await page.locator('.lg-variant-picker--size button:not(:disabled)').first().click();
   await page.waitForFunction(()=>Number(document.querySelector('input[name="variation_id"]')?.value)>0,null,{timeout:12000});
   await page.waitForTimeout(250);
   const selected=await page.locator('.lg-variant-picker--size button.is-selected').first().evaluate(e=>({border:getComputedStyle(e).borderBottomWidth,pressed:e.getAttribute('aria-pressed')}));assert.equal(selected.border,'2px');assert.equal(selected.pressed,'true');
   if(route===routes[0]&&(width===390||width===1440)){await page.locator('.lg-pdp-panel').screenshot({path:`C:/Users/OpenClawUser/Documents/Lingerious-Visual-Audit/selectors-v097-${width}.png`});}
   console.log('PASS',width,route,JSON.stringify(state),'selected='+JSON.stringify(selected));
  }catch(e){failures++;console.error('FAIL',width,route,e.message)}finally{await context.close()}
 }
 await browser.close();console.log('SELECTOR_DESIGN_FAILURES='+failures);if(failures)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=1});
