const assert=require('node:assert/strict');const fs=require('node:fs');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const path=process.env.SIZE_CATALOG_JSON||'C:/Users/OpenClawUser/Documents/Lingerious-Visual-Audit/size-catalog.json';
const products=JSON.parse(fs.readFileSync(path,'utf8').replace(/^\uFEFF/,''));
(async()=>{let failures=0;const browser=await chromium.launch({headless:true,executablePath:process.env.EDGE_BINARY||undefined});try{
for(const entry of products){const ctx=await browser.newContext({viewport:{width:390,height:844}});const page=await ctx.newPage(),js=[];page.on('pageerror',e=>js.push(e.message));
try{assert.ok(entry.choice,'Missing purchasable variant');const response=await page.goto(entry.url+'?catalogsizeqa='+Date.now(),{waitUntil:'domcontentloaded',timeout:40000});
const attrs=Object.entries(entry.choice.attrs),size=attrs.find(([key])=>key.includes('size')),color=attrs.find(([key])=>key.includes('color'));assert.ok(size,'size attribute missing');
await page.locator('.lg-variant-picker--size .lg-variant-option').first().waitFor({timeout:14000});
const layout=await page.locator('.lg-variant-picker--size .lg-variant-option').evaluateAll(buttons=>({labels:buttons.map(x=>x.textContent.trim()),overflow:buttons.some(x=>x.scrollWidth>x.clientWidth+1)}));
assert.equal(response.status(),200);assert.ok(!layout.overflow,'clipped size text');
const dimensions=await page.evaluate(()=>[document.documentElement.scrollWidth,innerWidth]);assert.ok(dimensions[0]<=dimensions[1]+2,'horizontal overflow');
if(color){const target=page.locator(`.lg-variant-picker--color [data-value="${color[1]}"]`);assert.equal(await target.count(),1,'color missing');await target.click();}
const target=page.locator(`.lg-variant-picker--size [data-value="${size[1]}"]`);assert.equal(await target.count(),1,'size missing');await target.click();
await page.waitForFunction(wanted=>document.querySelector('input[name="variation_id"]')?.value===String(wanted),entry.choice.id,{timeout:14000});
await page.waitForFunction(()=>!document.querySelector('.single_add_to_cart_button')?.classList.contains('disabled'),null,{timeout:14000});
assert.deepEqual(js,[]);console.log('PASS',entry.id,'variation='+entry.choice.id,'sizes='+layout.labels.join('/'),'width='+dimensions.join('/'));
}catch(err){failures++;console.log('FAIL',entry.id,err.message)}finally{await ctx.close();}
}
}finally{await browser.close()}
console.log('CATALOG_SIZE_FAILURES='+failures+' PRODUCTS='+products.length);if(failures)process.exitCode=1;
})().catch(err=>{console.error(err);process.exitCode=1});
