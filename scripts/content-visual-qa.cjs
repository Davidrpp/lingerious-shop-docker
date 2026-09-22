/* Live content and layout QA; no customer data or checkout submission. */
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const fs=require('fs');
const root='C:/Users/OpenClawUser/Documents/Lingerious-Visual-Audit/content-seo-20260922';
fs.mkdirSync(root,{recursive:true});
const routes=[['home','/'],['shop','/shop/'],['creator','/lingerie-for-content-creators/'],['pdp','/product/colorful-floral-embroidered-lingerie-set/'],['about','/about/'],['contact','/contact/'],['returns','/returns/']];
(async()=>{const browser=await chromium.launch({headless:true,executablePath:process.env.EDGE_BINARY});let failures=0;
for(const width of [390,768,1440]){
 const context=await browser.newContext({viewport:{width,height:850}});
 for(const [name,path] of routes){const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 try{const r=await page.goto('https://lingerious.shop'+path+'?contentqa=0922',{waitUntil:'domcontentloaded',timeout:35000});await page.waitForTimeout(700);
 const x=await page.evaluate(()=>{const hero=document.querySelector('.lg-atelier-hero')?.getBoundingClientRect();const photo=document.querySelector('.lg-atelier-hero__image')?.getBoundingClientRect();return {width:document.documentElement.scrollWidth,viewport:innerWidth,h1:document.querySelectorAll('h1').length,lang:document.documentElement.lang,header:!!document.querySelector('.lg-brand'),footer:!!document.querySelector('.lg-footer'),heroOK:!hero||!photo||photo.bottom<=hero.bottom+2,creatorLink:!!document.querySelector('a[href="/lingerie-for-content-creators/"]'),body:document.body.innerText.slice(0,7000)};});
 let ok=r.status()===200&&x.width<=width+2&&x.h1===1&&x.lang.startsWith('en')&&x.header&&x.footer&&x.heroOK&&errors.length===0;
 if(name==='home')ok=ok&&x.body.includes('Lingerie for content creators.');if(name==='creator')ok=ok&&x.body.includes('OnlyFans')&&x.body.includes('not affiliated');
 if(name==='contact')ok=ok&&!x.body.includes('tyler.com')&&!x.body.includes('Hood Avenue');if(name==='returns')ok=ok&&!x.body.includes('Política de devoluciones');
 if(!ok)failures++;console.log((ok?'PASS':'FAIL'),width,name,'status='+r.status(),'h1='+x.h1,'width='+x.width+'/'+x.viewport,'js='+errors.length);
 if([390,1440].includes(width)&&['home','creator','pdp'].includes(name))await page.screenshot({path:`${root}/${name}-${width}.png`,fullPage:true,animations:'disabled',timeout:15000});
 }catch(e){failures++;console.log('FAIL',width,name,e.message);}finally{await page.close();}}
 await context.close();}
 await browser.close();console.log('CONTENT_VISUAL_FAILURES='+failures);if(failures)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=1});
