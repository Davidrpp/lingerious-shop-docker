/* Visual layout regression. PLAYWRIGHT_MODULE and EDGE_BINARY can be supplied by CI. */
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const url=process.env.STORE_URL||'https://lingerious.shop';
(async()=>{const browser=await chromium.launch({headless:true,executablePath:process.env.EDGE_BINARY||undefined});let failures=0;
for(const width of [390,768,1440]) for(const route of ['/','/shop/','/product/colorful-floral-embroidered-lingerie-set/']){
const page=await browser.newPage({viewport:{width,height:850}});const js=[];page.on('pageerror',e=>js.push(e.message));
const response=await page.goto(url+route+'?layoutqa='+Date.now(),{waitUntil:'domcontentloaded',timeout:30000});await page.waitForTimeout(1000);
const layout=await page.evaluate(()=>{let box=s=>document.querySelector(s)?.getBoundingClientRect();let hero=box('.lg-atelier-hero'),photo=box('.lg-atelier-hero__image'),summary=box('.lg-pdp-purchase'),details=box('.lg-pdp-bottom');return {scroll:document.documentElement.scrollWidth,viewport:innerWidth,header:!!document.querySelector('.lg-header'),footer:!!document.querySelector('.lg-footer'),heroContained:!hero||!photo||photo.bottom<=hero.bottom+2,product:!details||!!document.querySelector('.lg-pdp-top'),initialOverlap:!!(summary&&details&&summary.bottom>details.top&&summary.top<details.bottom)}});
let ok=response.status()===200&&layout.scroll<=width+2&&layout.header&&layout.footer&&layout.heroContained&&layout.product&&!layout.initialOverlap&&!js.length;
if(route.includes('/product/')) for(const y of [450,950,1450]){await page.evaluate(x=>scrollTo(0,x),y);let overlaps=await page.evaluate(()=>{let a=document.querySelector('.lg-pdp-purchase')?.getBoundingClientRect(),b=document.querySelector('.lg-pdp-bottom')?.getBoundingClientRect();return !!(a&&b&&a.bottom>b.top&&a.top<b.bottom)});if(overlaps)ok=false;}
if(!ok)failures++;console.log((ok?'PASS':'FAIL'),width,route,response.status(),JSON.stringify(layout),'js='+js.length);await page.close();}
await browser.close();console.log('LAYOUT_FAILURES='+failures);if(failures)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=1});
