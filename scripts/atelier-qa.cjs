const {chromium}=require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs=require('fs');
const out=process.env.QA_OUTPUT || 'C:/Users/OpenClawUser/Documents/Lingerious-Visual-Audit';
fs.mkdirSync(out,{recursive:true});
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.EDGE_BINARY || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
 let errors=0;
 for(const width of [390,768,1440]){
  const context=await browser.newContext({viewport:{width,height:900},deviceScaleFactor:1});
  for(const [name,path] of [['home','/'],['shop','/shop/'],['bras','/bras/'],['product','/product/colorful-floral-embroidered-lingerie-set/'],['cart','/cart/']]){
   const page=await context.newPage();
   const failed=[];page.on('pageerror',e=>failed.push(e.message));
   const response=await page.goto('https://lingerious.shop'+path+'?atelierqa=080', {waitUntil:'domcontentloaded',timeout:25000});
   await page.evaluate(()=>document.querySelectorAll('img[loading="lazy"]').forEach(i=>i.loading='eager'));
   await page.waitForTimeout(1050);
   const data=await page.evaluate(()=>({scroll:document.documentElement.scrollWidth, viewport:innerWidth,header:!!document.querySelector('.lg-header'),footer:!!document.querySelector('.lg-footer'),css:[...document.styleSheets].some(s=>(s.href||'').includes('atelier-v2')),pdp:!!document.querySelector('.lg-product-information'),category:!!document.querySelector('.lg-category-editorial'),cards:document.querySelectorAll('li.product').length,broken:[...document.images].filter(i=>i.complete&&!i.naturalWidth).length}));
   const ok=response.status()===200 && data.scroll<=width+2 && data.header&&data.footer&&data.css&&!failed.length;
   if(!ok)errors++;
   console.log((ok?'PASS ':'FAIL ')+width+' '+name+' HTTP='+response.status()+' '+JSON.stringify(data)+' jsErrors='+failed.length);
   if((width===390||width===1440)&&['home','shop','product'].includes(name)) await page.screenshot({path:`${out}/${name}-${width}-080.png`,fullPage:true});
   await page.close();
  }
  await context.close();
 }
 console.log('QA_FAILS='+errors);await browser.close();if(errors)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=1});
