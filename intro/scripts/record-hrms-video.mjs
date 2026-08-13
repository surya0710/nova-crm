/**
 * Record Konnect Nex HRMS product-introduction walkthrough from the live app.
 * Uses Playwright video capture of real browser UI (not screenshots/slideshow).
 *
 * Usage:
 *   php artisan serve --host=127.0.0.1 --port=8000
 *   node intro/scripts/record-hrms-video.mjs
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const RAW = path.join(ROOT, 'raw');
const BASE = process.env.NOVA_URL || 'http://127.0.0.1:8000';

const USERS = {
  admin: { email: process.env.NOVA_ADMIN_EMAIL || 'demo@novacrm.test', pass: process.env.NOVA_PASS || 'password' },
  hr: { email: process.env.NOVA_HR_EMAIL || 'neha.gupta@novacrm.test', pass: process.env.NOVA_PASS || 'password' },
  manager: { email: process.env.NOVA_MGR_EMAIL || 'priya.sharma@novacrm.test', pass: process.env.NOVA_PASS || 'password' },
  employee: { email: process.env.NOVA_EMP_EMAIL || 'arjun.kapoor@novacrm.test', pass: process.env.NOVA_PASS || 'password' },
};

fs.mkdirSync(RAW, { recursive: true });
for (const entry of fs.readdirSync(RAW)) {
  fs.rmSync(path.join(RAW, entry), { recursive: true, force: true });
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function safeGoto(page, urlPath, dwellMs = 2200) {
  const url = urlPath.startsWith('http') ? urlPath : `${BASE}${urlPath}`;
  try {
    const res = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await sleep(dwellMs);
    const status = res?.status() ?? 0;
    if (status >= 400) {
      console.log(`  skip ${urlPath} status=${status}`);
      return false;
    }
    // Avoid password fields lingering on screen in recorded video.
    const pwd = page.locator('input[type="password"]');
    if (await pwd.count()) {
      await pwd.first().evaluate((el) => {
        el.value = '';
        el.type = 'text';
        el.value = '••••••••';
      }).catch(() => {});
    }
    await page.mouse.move(240, 180);
    await sleep(300);
    await page.mouse.move(720, 360);
    return true;
  } catch (e) {
    console.log(`  error ${urlPath}: ${e.message}`);
    return false;
  }
}

async function scrollTour(page, steps = 3) {
  for (let i = 0; i < steps; i++) {
    await page.mouse.wheel(0, 450);
    await sleep(700);
  }
  await page.evaluate(() => window.scrollTo(0, 0)).catch(() => {});
  await sleep(500);
}

async function clickFirst(page, selector, dwellMs = 1800) {
  const loc = page.locator(selector).first();
  if (await loc.count()) {
    await loc.click({ timeout: 8000 }).catch(() => {});
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    await sleep(dwellMs);
    return true;
  }
  return false;
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
  await sleep(1500);
}

async function logout(page) {
  // Prefer POST logout if form/button exists; fallback to visit logout route.
  const logoutBtn = page.locator('form[action*="logout"] button, button:has-text("Log out"), a:has-text("Log out"), a:has-text("Logout")').first();
  if (await logoutBtn.count()) {
    await logoutBtn.click().catch(() => {});
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await sleep(800);
    return;
  }
  await page.goto(`${BASE}/logout`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await sleep(800);
  // Some apps only accept POST logout; clear cookies as fallback.
  await page.context().clearCookies();
  await sleep(400);
}

async function recordScene(browser, sceneId, title, runner) {
  const dir = path.join(RAW, sceneId);
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(path.join(dir, 'title.txt'), title, 'utf8');

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    deviceScaleFactor: 1,
    recordVideo: {
      dir,
      size: { width: 1920, height: 1080 },
    },
  });
  const page = await context.newPage();
  console.log(`\n== ${sceneId}: ${title}`);
  try {
    await runner(page);
  } catch (e) {
    console.log(`  scene failed: ${e.message}`);
  }
  await context.close();
  // Playwright writes webm after context close.
  const files = fs.readdirSync(dir).filter((f) => f.endsWith('.webm'));
  if (files[0]) {
    const dest = path.join(RAW, `${sceneId}.webm`);
    fs.renameSync(path.join(dir, files[0]), dest);
    console.log(`  saved ${sceneId}.webm`);
  } else {
    console.log(`  WARNING: no video for ${sceneId}`);
  }
}

async function main() {
  // Health check
  try {
    const res = await fetch(`${BASE}/login`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
  } catch (e) {
    console.error(`App not reachable at ${BASE}. Start: php artisan serve --host=127.0.0.1 --port=8000`);
    process.exit(1);
  }

  const browser = await chromium.launch({
    headless: true,
    args: ['--disable-dev-shm-usage'],
  });

  // Scene 01 — Product introduction
  await recordScene(browser, '01-intro', 'Product Introduction', async (page) => {
    await safeGoto(page, '/login', 2500);
    await login(page, USERS.admin);
    await safeGoto(page, '/dashboard', 2800);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms', 3200);
    await scrollTour(page, 3);
  });

  // Scene 02 — HRMS foundation
  await recordScene(browser, '02-foundation', 'HRMS Foundation', async (page) => {
    await login(page, USERS.admin);
    await safeGoto(page, '/hrms', 2200);
    await safeGoto(page, '/hrms/employees', 2800);
    await scrollTour(page, 2);
    await clickFirst(page, 'a[href*="/hrms/employees/"]', 3200);
    await scrollTour(page, 3);
    await clickFirst(page, 'a[href*="documents"]', 2500);
    await safeGoto(page, '/hrms/departments', 2200);
    await safeGoto(page, '/hrms/designations', 2200);
    await safeGoto(page, '/hrms/branches', 2200);
    await safeGoto(page, '/hrms/shifts', 2200);
    await safeGoto(page, '/organization/settings/holidays', 2000);
    await safeGoto(page, '/hrms/leave-types', 2200);
    await safeGoto(page, '/organization/settings/hub', 2400);
  });

  // Scene 03 — Employee management
  await recordScene(browser, '03-employees', 'Employee Management', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/employees', 2600);
    await scrollTour(page, 2);
    await clickFirst(page, 'a:has-text("Create"), a:has-text("Add Employee"), a[href*="employees/create"]', 2200);
    await safeGoto(page, '/hrms/employees', 1800);
    await clickFirst(page, 'a[href*="/hrms/employees/"]', 3000);
    await scrollTour(page, 3);
    await clickFirst(page, 'a:has-text("Edit"), a[href*="/edit"]', 2500);
    await scrollTour(page, 2);
  });

  // Scene 04 — Attendance lifecycle
  await recordScene(browser, '04-attendance', 'Attendance Lifecycle', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/attendance', 2800);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/attendance/summary', 2600);
    await safeGoto(page, '/hrms/attendance/reports', 2400);
    await safeGoto(page, '/hrms/calendar', 2600);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/attendance/corrections', 2400);
    await clickFirst(page, 'a[href*="/hrms/attendance/"]', 2800);
    await scrollTour(page, 2);
  });

  // Scene 05 — Geo attendance
  await recordScene(browser, '05-geo', 'Geo-Attendance & Verification', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/organization/settings/attendance-rules', 3200);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/attendance/geofences', 3200);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/attendance', 2200);
    await clickFirst(page, 'a[href*="/hrms/attendance/"]', 3000);
    await scrollTour(page, 3);
  });

  // Scene 06 — WFH
  await recordScene(browser, '06-wfh', 'Work From Home', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/organization/settings/wfh-policies', 3200);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/wfh/assignments', 3000);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/wfh/requests', 2800);
    await scrollTour(page, 2);
    await clickFirst(page, 'a[href*="/hrms/wfh/requests/"]', 3000);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/wfh/requests/approval-queue', 3000);
  });

  // Scene 07 — Leave
  await recordScene(browser, '07-leave', 'Leave Management', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/leave', 2600);
    await safeGoto(page, '/hrms/leave-types', 2400);
    await safeGoto(page, '/hrms/leave-balances', 2600);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/leave-applications', 2800);
    await scrollTour(page, 2);
    await clickFirst(page, 'a[href*="/hrms/leave-applications/"]', 2800);
    await safeGoto(page, '/hrms/leave-applications/approval-queue', 2800);
    await safeGoto(page, '/organization/settings/leave-policies', 2400);
  });

  // Scene 08 — Payroll
  await recordScene(browser, '08-payroll', 'Payroll', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/payroll', 2800);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/payroll/configuration', 2400);
    await safeGoto(page, '/hrms/payroll/components', 2400);
    await safeGoto(page, '/hrms/payroll/structures', 2400);
    await safeGoto(page, '/hrms/payroll/assignments', 2400);
    await safeGoto(page, '/hrms/payroll/periods', 2400);
    await safeGoto(page, '/hrms/payroll/runs', 2400);
    await safeGoto(page, '/hrms/payroll/payslips', 2400);
    await safeGoto(page, '/hrms/payroll/reports', 2200);
  });

  // Scene 09 — Tax / TDS
  await recordScene(browser, '09-tax', 'Tax & TDS', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/payroll/tax', 2800);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/payroll/tax/regimes', 2200);
    await safeGoto(page, '/hrms/payroll/tax/declarations', 2400);
    await safeGoto(page, '/hrms/payroll/statutory', 2600);
    await scrollTour(page, 2);
  });

  // Scene 10 — Recruitment
  await recordScene(browser, '10-recruitment', 'Recruitment', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/recruitment', 2800);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/recruitment/requisitions', 2400);
    await safeGoto(page, '/hrms/recruitment/openings', 2600);
    await clickFirst(page, 'a[href*="/hrms/recruitment/openings/"]', 2600);
    await safeGoto(page, '/hrms/recruitment/candidates', 2600);
    await clickFirst(page, 'a[href*="/hrms/recruitment/candidates/"]', 2600);
    await safeGoto(page, '/hrms/recruitment/interview-rounds', 2400);
    await safeGoto(page, '/hrms/recruitment/offers', 2400);
    await safeGoto(page, '/hrms/recruitment/analytics', 2600);
    await safeGoto(page, '/hrms/recruitment/hiring-decisions', 2200);
  });

  // Scene 11 — ESS
  await recordScene(browser, '11-ess', 'Employee Self-Service', async (page) => {
    await login(page, USERS.employee);
    await safeGoto(page, '/hrms/ess', 3000);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/ess/profile', 2600);
    await safeGoto(page, '/hrms/ess/attendance', 3000);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/ess/attendance/records', 2400);
    await safeGoto(page, '/hrms/ess/leave', 2800);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/ess/wfh', 3000);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/ess/documents', 2400);
    await safeGoto(page, '/hrms/ess/payroll', 2600);
    await safeGoto(page, '/hrms/ess/payroll/payslips', 2400);
  });

  // Scene 12 — Manager
  await recordScene(browser, '12-manager', 'Manager Experience', async (page) => {
    await login(page, USERS.manager);
    await safeGoto(page, '/hrms', 2400);
    await safeGoto(page, '/hrms/manager/dashboard', 2800);
    await safeGoto(page, '/hrms/employees', 2400);
    await safeGoto(page, '/hrms/attendance', 2400);
    await safeGoto(page, '/hrms/leave-applications/approval-queue', 2800);
    await safeGoto(page, '/hrms/wfh/requests/approval-queue', 3000);
    await scrollTour(page, 2);
  });

  // Scene 13 — RBAC & security
  await recordScene(browser, '13-rbac', 'RBAC & Security', async (page) => {
    await login(page, USERS.admin);
    await safeGoto(page, '/rbac/roles', 2800);
    await scrollTour(page, 2);
    await safeGoto(page, '/rbac/matrix', 3000);
    await scrollTour(page, 3);
    await safeGoto(page, '/rbac/permissions', 2400);
    await safeGoto(page, '/organization/settings/hub', 2400);
  });

  // Scene 14 — Workflow & audit
  await recordScene(browser, '14-workflow', 'Workflow & Audit', async (page) => {
    await login(page, USERS.hr);
    await safeGoto(page, '/hrms/wfh/requests', 2400);
    await clickFirst(page, 'a[href*="/hrms/wfh/requests/"]', 3200);
    await scrollTour(page, 2);
    await safeGoto(page, '/hrms/wfh/requests/approval-queue', 2600);
    await safeGoto(page, '/hrms/attendance/corrections', 2400);
    await safeGoto(page, '/audit-logs', 3000);
    await scrollTour(page, 3);
  });

  // Scene 15 — Dashboard / closing montage
  await recordScene(browser, '15-closing', 'Dashboard & Closing', async (page) => {
    await login(page, USERS.admin);
    await safeGoto(page, '/hrms', 2800);
    await scrollTour(page, 2);
    const montage = [
      '/hrms/employees',
      '/hrms/attendance',
      '/hrms/attendance/geofences',
      '/hrms/wfh/requests',
      '/hrms/leave',
      '/hrms/payroll',
      '/hrms/recruitment',
      '/hrms/ess',
      '/rbac/roles',
      '/hrms',
    ];
    for (const p of montage) {
      await safeGoto(page, p, 1600);
    }
  });

  await browser.close();
  console.log('\nRecording complete. Raw clips in intro/raw/');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
