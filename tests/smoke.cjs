const {chromium}=require('C:/Users/khalc/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');
const base='http://localhost/echozhin/';
(async()=>{
 const browser=await chromium.launch({headless:true,channel:'msedge'});const page=await browser.newPage({viewport:{width:1440,height:1100}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto(base);await page.waitForTimeout(600);assert.equal(await page.locator('.listing').count(),4);await page.screenshot({path:'tests/artifacts/desktop.png',fullPage:true});
 await page.locator('[data-filter="Walnut"]').click();assert.equal(await page.locator('.listing-wrapper:visible').count(),1);
 await page.goto(base+'index.php?view=login');const login=page.locator('form').filter({has:page.locator('input[value="login"]')});await login.locator('[name=email]').fill('buyer@ecozin.test');await login.locator('[name=password]').fill('EcozinDemo2026!');await login.locator('button').click();await page.waitForURL('**/business_dashboard.php');
 await page.locator('.follow').first().click();await page.waitForURL('**/chat.php?receiver=*');await page.waitForTimeout(600);assert.ok(await page.locator('.bubble').count()>0);
 await page.locator('#message-text').fill('Demo quality check: can you share a sample?');await page.locator('.composer button').last().click();await page.waitForTimeout(600);assert.ok((await page.locator('#messages').innerText()).includes('Demo quality check'));
 const security=await page.evaluate(async()=>{const r=await fetch('api_handler.php',{method:'POST',body:new URLSearchParams({action:'follow',listing_id:'1'})});return r.status;});assert.equal(security,403);
 await page.goto(base+'ai_knowledge_base.php');await page.locator('#knowledge-form button').click();await page.waitForFunction(()=>document.getElementById('ai-source').textContent.includes('Local'));assert.ok((await page.locator('#knowledge-text').innerText()).includes('POMEGRANATE'));
 await page.locator('[name=language]').selectOption('Kurdish Sorani');await page.locator('#knowledge-form button').click();await page.waitForTimeout(5200);if(!(await page.locator('#knowledge-text').getAttribute('dir')==='rtl'))await page.locator('#knowledge-form button').click();await page.waitForFunction(()=>document.getElementById('knowledge-text').dir==='rtl');
 await page.goto(base+'map.php');await page.waitForTimeout(700);assert.equal(await page.locator('.farm-pin').count(),4);
 await page.setViewportSize({width:390,height:844});await page.goto(base);await page.waitForTimeout(300);assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);await page.screenshot({path:'tests/artifacts/mobile.png',fullPage:true});
 await page.context().route('https://**/*',r=>r.abort());await page.reload();await page.waitForTimeout(500);assert.equal(await page.locator('.listing').count(),4);
 assert.deepEqual(errors,[]);console.log('PASS: marketplace/filter, login, matches/follow, chat send, CSRF, multilingual fallback, map pins, mobile overflow and offline shell.');await browser.close();
})().catch(e=>{console.error(e);process.exit(1);});
