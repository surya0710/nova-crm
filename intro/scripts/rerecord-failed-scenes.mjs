/**
 * Re-record scenes that need corrected roles/permissions.
 * Usage: node intro/scripts/rerecord-failed-scenes.mjs
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const RAW = path.join(ROOT, 'raw');
const BASE = process.env.NOVA_URL || 'http://127.0.0.1:8000';
const PASS = process.env.NOVA_PASS || 'password';

const USERS = {
  admin: { email: 'demo@novacrm.test', pass: PASS },
  hr: { email: 'neha.gupta@novacrm.test', pass: PASS },
  manager: { email: 'priya.sharma@novacrm.test', pass: PASS },
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function safeGoto(page, urlPath, dwellMs = 2200) {
  const url = `${BASE}${urlPath}`;
  try {
    const res = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await sleep(dwellMs);
    const status = res?.status() ?? 0;
    if (status >= 400) {
      console.log(`  skip ${urlPath} status=${status}`);
      return false;
    }
    await page.mouse.move(240, 180);
    await sleep(250);
    await page.mouse.move(820, 420);
    return true;
  } catch (e) {
    console.log(`  error ${urlPath}: ${e.message}`);
    return false;
  }
}

async function scrollTour(page, steps = 2) {
  for (let i = 0; i < steps; i++) {
    await page.mouse.wheel(0, 450);
    await sleep(700);
  }
  await page.evaluate(() => window.scrollTo(0, 0)).catch(() => {});
  await sleep(400);
}

async function login(page, user) {
  await page.context().clearCookies();
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForSelector('#email', { timeout: 20000 });
  await sleep(400);
  await page.fill('#email', user.email);
  await page.fill('#password', user.pass);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
  await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
  await sleep(1200);
  console.log(`  logged in ${user.email} -> ${page.url()}`);
}

async function recordScene(browser, sceneId, title, runner) {
  const dir = path.join(RAW, sceneId);
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(path.join(dir, 'title.txt'), title, 'utf8');
  const old = path.join(RAW, `${sceneId}.webm`);
  if (fs.existsSync(old)) fs.unlinkSync(old);

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: { dir, size: { width: 1920, height: 1080 } },
  });
  const page = await context.newPage();
  console.log(`\n== ${sceneId}: ${title}`);
  try {
    await runner(page);
  } catch (e) {
    console.log(`  scene failed: ${e.message}`);
  }
  await context.close();
  const files = fs.readdirSync(dir).filter((f) => f.endsWith('.webm'));
  if (files[0]) {
    fs.renameSync(path.join(dir, files[0]), old);
    console.log(`  saved ${sceneId}.webm`);
  }
}

async function main() {
  const browser = await chromium.launch({ headless: true });

  await recordScene(browser, '05-geo', 'Geo-Attendance & Verification', async (page) => {
    await login(page, USERS.admin);
    await safeGoto(page, '/organization/settings/attendance-rules', 3400);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/attendance/geofences', 3200);
    await scrollTour(page, 2);
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/attendance', 2400);
    await page.locator('a[href*="/hrms/attendance/"]').first().click().catch(() => {});
    await page.waitForLoadState('networkidle').catch(() => {});
    await sleep(2800);
    await scrollTour(page, 2);
  });

  await recordScene(browser, '06-wfh', 'Work From Home', async (page) => {
    await login(page, USERS.admin);
    await safeGoto(page, '/organization/settings/wfh-policies', 3400);
    await scrollTour(page, 2);
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/wfh/assignments', 3000);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/wfh/requests', 2800);
    await page.locator('a[href*="/hrms/wfh/requests/"]').first().click().catch(() => {});
    await page.waitForLoadState('networkidle').catch(() => {});
    await sleep(3000);
    await safeGoto(page, '/hrms/wfh/requests/approval-queue', 3000);
  });

  await recordScene(browser, '07-leave', 'Leave Management', async (page) => {
    await login(page, USERS.admin);
    await safeGoto(page, '/organization/settings/leave-policies', 2800);
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/leave', 2600);
    await safeGoto(page, '/hrms/leave-types', 2400);
    await safeGoto(page, '/hrms/leave-balances', 2600);
    await safeGoto(page, '/hrms/leave-applications', 2800);
    await page.locator('a[href*="/hrms/leave-applications/"]').first().click().catch(() => {});
    await page.waitForLoadState('networkidle').catch(() => {});
    await sleep(2600);
    await safeGoto(page, '/hrms/leave-applications/approval-queue', 2800);
  });

  await recordScene(browser, '12-manager', 'Manager Experience', async (page) => {
    await login(page, USERS.manager);
    await safeGoto(page, '/hrms/home', 2800);
    await safeGoto(page, '/hrms/manager/dashboard', 3200);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/attendance', 2600);
    await safeGoto(page, '/hrms/leave-applications/approval-queue', 3000);
    await safeGoto(page, '/hrms/wfh/requests/approval-queue', 3200);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/directory', 2400);
  });

  await browser.close();
  console.log('\nRe-record complete');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
