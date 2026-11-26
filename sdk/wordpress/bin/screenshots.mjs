// Screenshots of the settings screens, for review and for the wordpress.org
// listing (.wordpress-org/screenshot-N.png, deployed to the SVN assets folder).
//
//   cd sdk/wordpress && npm install && node bin/screenshots.mjs [outDir]
//
// Needs the local WordPress from docker-compose.yml (bin/setup.sh).
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

const BASE = process.env.WP_URL ?? 'http://localhost:19450';
const USER = process.env.WP_USER ?? 'admin';
const PASS = process.env.WP_PASS ?? 'admin';
const out = resolve(process.argv[2] ?? '.wordpress-org');
mkdirSync(out, { recursive: true });

const shots = [
  ['connection', 'screenshot-1.png'],
  ['woocommerce', 'screenshot-2.png'],
  ['forms', 'screenshot-3.png'],
  ['log', 'screenshot-4.png'],
];

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 900 }, deviceScaleFactor: 2 });
const problems = [];
page.on('console', (m) => m.type() === 'error' && problems.push(`console: ${m.text()}`));

await page.goto(`${BASE}/wp-login.php`);
await page.fill('#user_login', USER);
await page.fill('#user_pass', PASS);
await page.click('#wp-submit');
await page.waitForURL(/wp-admin/);

for (const [tab, file] of shots) {
  await page.goto(`${BASE}/wp-admin/admin.php?page=unlimited-messaging&tab=${tab}`);
  const body = await page.content();
  for (const marker of ['Warning:', 'Notice:', 'Fatal error', 'Deprecated:']) {
    if (body.includes(`<b>${marker.replace(':', '')}</b>`) || body.includes(`${marker}</b>`)) {
      problems.push(`${tab}: PHP ${marker}`);
    }
  }
  await page.screenshot({ path: resolve(out, file), fullPage: tab !== 'woocommerce' });
}

await browser.close();
console.log(problems.length ? problems.join('\n') : 'no PHP or console errors');
console.log(`screenshots in ${out}`);
