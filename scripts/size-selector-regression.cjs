const assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const base=process.env.STORE_URL||'https://lingerious.shop';
const browserOptions={headless:true,executablePath:process.env.EDGE_BINARY||undefined,args:['--no-sandbox']};
const items=[
 {route:'/product/sheer-opaque-floral-lingerie-set/',label:'Size',expected:['S','M','L','XL'],color:'black-set',size:'s32or70abc',variation:'4215',reference:true},
 {route:'/product/floral-lace-longline-lingerie-set-4-colors/',label:'Bra size',expected:['70B','70C','75B','75C','75D','80B','80C','80D','85C','85D'],color:'black',size:'70b'},
 {route:'/product/colorful-floral-embroidered-lingerie-set/',label:'Size',expected:['S','M','L','XL'],color:'deep-blue',size:'s'},
 {route:'/product/off-shoulder-strapless-corset/',label:'Size',expected:['XS','S','M','L','XL','XXL','XXXL','4XL','5XL','6XL'],color:'black',size:'xs'}
];
(async()=>{const browser=await chromium.launch(browserOptions);let errors=0;try{
 for(const width of [320,390,768,1440])for(const item of items){
  const ctx=await browser.newContext({viewport:{width,height:850}}),page=await ctx.newPage(),js=[];
  page.on('pageerror',e=>js.push(e.message));
  try{
   const response=await page.goto(base+item.route+'?sizeqa='+Date.now(),{waitUntil:'domcontentloaded',timeout:45000});
   await page.locator('.lg-variant-picker--size .lg-variant-option').first().waitFor({timeout:15000});
   const data=await page.evaluate(()=>{const buttons=[...document.querySelectorAll('.lg-variant-picker--size .lg-variant-option')];return {labels:buttons.map(x=>x.textContent.trim()),overflow:buttons.some(x=>x.scrollWidth>x.clientWidth+1),width:document.documentElement.scrollWidth,viewport:innerWidth,group:document.querySelector('.lg-variant-picker--size')?.getAttribute('aria-label'),native:document.querySelector('.lg-variant-picker--size')?.previousElementSibling?.name||''}});
   assert.equal(response.status(),200);assert.deepEqual(data.labels,item.expected);
   assert.equal(data.group,item.label+' options');assert.ok(!data.overflow,'button label overflow');
   assert.ok(data.width<=data.viewport+2,'document overflow');assert.deepEqual(js,[]);
   const boxes=await page.locator('.lg-variant-picker--size .lg-variant-option').evaluateAll(els=>els.map(el=>{const r=el.getBoundingClientRect();return {left:r.left,right:r.right,top:r.top,bottom:r.bottom}}));
   for(let i=0;i<boxes.length;i++)for(let j=i+1;j<boxes.length;j++)assert.ok(boxes[i].right<=boxes[j].left+1||boxes[j].right<=boxes[i].left+1||boxes[i].bottom<=boxes[j].top+1||boxes[j].bottom<=boxes[i].top+1,'button overlap');
   if(item.reference)assert.equal(await page.locator('.lg-size-references').count(),1);
   await page.locator(`.lg-variant-picker--color [data-value="${item.color}"]`).click();
   await page.locator(`.lg-variant-picker--size [data-value="${item.size}"]`).click();
   await page.waitForFunction(()=>Number(document.querySelector('input[name="variation_id"]')?.value)>0,null,{timeout:13000});
   const variation=await page.locator('input[name="variation_id"]').inputValue();
   if(item.variation)assert.equal(variation,item.variation);
   await page.waitForFunction(()=>!document.querySelector('.single_add_to_cart_button')?.classList.contains('disabled'),null,{timeout:13000});
   if(item.reference&&width===390)await page.screenshot({path:'C:/Users/OpenClawUser/Documents/Lingerious-Visual-Audit/size-390-after.png',fullPage:true});
   if(item.reference&&width===320)await page.screenshot({path:'C:/Users/OpenClawUser/Documents/Lingerious-Visual-Audit/size-320-after.png',fullPage:true});
   console.log('PASS',width,item.route,'sizes='+data.labels.join('/'),'variation='+variation,'width='+data.width,'JS='+js.length);
  }catch(e){errors++;console.error('FAIL',width,item.route,e.message)}finally{await ctx.close()}
 }
 }finally{await browser.close()}
 console.log('SIZE_TEST_FAILURES='+errors);if(errors)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=1});
