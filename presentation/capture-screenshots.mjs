/**
 * Capture Konnect Nex presentation screenshots via Playwright.
 * Usage: node presentation/capture-screenshots.mjs
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.resolve(__dirname, '../assets/screenshots');
const BASE = process.env.NOVA_URL || 'http://127.0.0.1:8010';
const EMAIL = process.env.NOVA_EMAIL || 'demo@novacrm.test';
const PASS = process.env.NOVA_PASS || 'password';

fs.mkdirSync(OUT, { recursive: true });

const pages = [
  { name: '00-login', path: '/login', beforeLogin: true },
  { name: '01-dashboard', path: '/dashboard' },
  { name: '02-hrms-dashboard', path: '/hrms' },
  { name: '03-employees', path: '/hrms/employees' },
  { name: '04-attendance', path: '/hrms/attendance' },
  { name: '05-attendance-summary', path: '/hrms/attendance/summary' },
  { name: '06-shifts', path: '/hrms/shifts' },
  { name: '07-recruitment', path: '/hrms/recruitment' },
  { name: '07b-recruitment-analytics', path: '/hrms/recruitment/analytics' },
  { name: '08-job-openings', path: '/hrms/recruitment/openings' },
  { name: '09-candidates', path: '/hrms/recruitment/candidates' },
  { name: '10-interviews', path: '/hrms/recruitment/interview-rounds' },
  { name: '11-offers', path: '/hrms/recruitment/offers' },
  { name: '12-tasks-board', path: '/tasks/board' },
  { name: '13-tasks-timeline', path: '/tasks/timeline' },
  { name: '14-projects', path: '/projects' },
  { name: '15-resources-capacity', path: '/resources/capacity' },
  { name: '16-resources-planner', path: '/resources/planner' },
  { name: '17-reports', path: '/reports' },
  { name: '18-leads', path: '/leads' },
  { name: '19-pipeline', path: '/pipeline' },
  { name: '20-quotations', path: '/quotations' },
  { name: '21-customers', path: '/customers' },
  { name: '22-audit-logs', path: '/audit-logs' },
];

async function shot(page, name) {
  await page.waitForTimeout(800);
  const file = path.join(OUT, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  console.log('saved', name);
}

async function main() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 1.5,
  });
  const page = await context.newPage();

  // Login screenshot
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await shot(page, '00-login');

  await page.fill('input[type="email"], input[name="email"]', EMAIL);
  await page.fill('input[type="password"], input[name="password"]', PASS);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
  await page.waitForTimeout(1500);

  // If org switcher exists, prefer Nova Enterprises
  const orgText = await page.content();
  if (orgText.includes('Nova Enterprises')) {
    // try click org switcher if needed
    const switcher = page.locator('text=Nova Enterprises').first();
    if (await switcher.count()) {
      // already on org or visible
    }
  }

  for (const item of pages.filter((p) => !p.beforeLogin)) {
    try {
      const res = await page.goto(`${BASE}${item.path}`, { waitUntil: 'networkidle', timeout: 45000 });
      const status = res?.status() ?? 0;
      if (status >= 400) {
        console.log('skip', item.name, status);
        continue;
      }
      // dismiss flash if any
      await page.waitForTimeout(500);
      await shot(page, item.name);

      // detail shots for first employee / project / task
      if (item.name === '03-employees') {
        const link = page.locator('a[href*="/hrms/employees/"]').first();
        if (await link.count()) {
          await link.click();
          await page.waitForLoadState('networkidle');
          await shot(page, '03b-employee-profile');
        }
      }
      if (item.name === '14-projects') {
        const link = page.locator('a[href*="/projects/"]').filter({ hasNotText: 'New' }).first();
        if (await link.count()) {
          await link.click();
          await page.waitForLoadState('networkidle');
          await shot(page, '14b-project-detail');
        }
      }
      if (item.name === '12-tasks-board') {
        const card = page.locator('[data-task-id], .task-card, a[href*="/tasks/"]').first();
        if (await card.count()) {
          await card.click();
          await page.waitForTimeout(1000);
          await shot(page, '12b-task-detail');
        }
      }
    } catch (e) {
      console.log('error', item.name, e.message);
    }
  }

  await browser.close();
  console.log('done');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
