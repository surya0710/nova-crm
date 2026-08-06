import { chromium } from 'playwright';
import path from 'path';

const OUT = 'assets/screenshots';
const BASE = 'http://127.0.0.1:8010';

const browser = await chromium.launch({ headless: true });
const page = await (
  await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1.5 })
).newPage();

await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
await page.fill('input[name="email"]', 'demo@novacrm.test');
await page.fill('input[name="password"]', 'password');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
  page.click('button[type="submit"]'),
]);
await page.waitForTimeout(1000);

const shots = [
  ['02-hrms-dashboard', '/hrms'],
  ['01-dashboard', '/dashboard'],
  ['17-reports', '/reports'],
  ['15-resources-capacity', '/resources/capacity'],
  ['12-tasks-board', '/tasks/board'],
  ['14-projects', '/projects'],
  ['07-recruitment', '/hrms/recruitment'],
];

for (const [name, p] of shots) {
  const res = await page.goto(`${BASE}${p}`, { waitUntil: 'networkidle', timeout: 45000 });
  console.log(name, res?.status());
  await page.waitForTimeout(700);
  await page.screenshot({ path: path.join(OUT, `${name}.png`), fullPage: false });
}

await browser.close();
