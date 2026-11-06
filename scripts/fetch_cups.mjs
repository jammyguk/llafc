// Reads every cup game for the U12 and U13 leagues from Full-Time and writes cups.json.
// Full-Time lists all competitions on the league-wide fixtures/results pages, so one pass
// catches every cup (County Cup, League Cup, Foxes Cup, Presidents, Chairmans, Martin Dexter...).
// Safe by design: if anything looks wrong it leaves cups.json exactly as it was.
import { chromium } from 'playwright';
import fs from 'fs';

const LEAGUE = '3326454', SEASON = '503728397';
const url = p => `https://fulltime.thefa.com/${p}.html?league=${LEAGUE}&selectedSeason=${SEASON}&selectedDivision=0&selectedCompetition=0&selectedFixtureGroupKey=&itemsPerPage=5000`;

const extract = () => {
  const t = e => (e ? e.textContent.trim().replace(/\s+/g, ' ') : '');
  const isCup = n => /\b(cup)\b/i.test(n) && /\b(u12|u13|under 12|under 13)\b/i.test(n) && !/girls/i.test(n);
  const fixtures = [...document.querySelectorAll('.fixtures-table tbody tr')].map(r => ({
    comp: t(r.querySelector('td:nth-last-child(2)')),
    date: t(r.querySelector('td:nth-child(2)')),
    home: t(r.querySelector('.home-team')), away: t(r.querySelector('.road-team')),
    venue: t(r.querySelector('td:nth-last-child(3)')), notes: t(r.querySelector('.status-notes')),
  })).filter(x => isCup(x.comp));
  const results = [...document.querySelectorAll('.datetime-col')].map(c => c.parentElement).map(r => ({
    comp: t(r.querySelector('.fg-col')), date: t(r.querySelector('.datetime-col')),
    home: t(r.querySelector('.home-team-col')), away: t(r.querySelector('.road-team-col')),
    score: t(r.querySelector('.score-col')),
  })).filter(x => isCup(x.comp));
  return { fixtures, results, rows: document.querySelectorAll('tr').length };
};

// Same recipe the data tool uses to get past Full-Time's Cloudflare check:
// real Chromium in "new" headless mode, open the page (403), accept the cookie banner on the
// home page in a separate tab, then RELOAD the first tab.
async function load(ctx, p, ready) {
  const page = await ctx.newPage();
  await page.addInitScript("Object.defineProperty(navigator, 'webdriver', { get: () => undefined });");
  await page.route('**/*', r => ['image', 'stylesheet', 'font', 'media'].includes(r.request().resourceType()) ? r.abort() : r.continue());
  for (let i = 0; i < 3; i++) {
    let resp = await page.goto(url(p), { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(e => { console.log('goto error', String(e).slice(0, 120)); return null; });
    console.log(`try ${i} ${p}: status ${resp && resp.status()}`);
    if (resp && resp.status() === 403) {
      const apex = await ctx.newPage();
      try {
        await apex.goto('https://fulltime.thefa.com', { waitUntil: 'networkidle', timeout: 30000 });
        await apex.locator('#onetrust-accept-btn-handler').click({ timeout: 8000 });
        console.log('accepted cookies');
      } catch (e) { console.log('cookie step failed', String(e).slice(0, 120)); }
      await apex.close();
      resp = await page.reload({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null);
      console.log(`after reload: status ${resp && resp.status()}`);
    }
    if (await page.locator(ready).count().catch(() => 0)) return page;
    await page.waitForTimeout(3000);
  }
  return null;
}

const browser = await chromium.launch({ channel: 'chromium', args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-blink-features=AutomationControlled'] });
const ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
const mk = () => browser.newContext({ userAgent: ua, locale: 'en-GB', timezoneId: 'Europe/London', viewport: { width: 1920, height: 1080 } });
const shared = await mk();
const pf = await load(shared, 'fixtures', '.fixtures-table');
const F = pf ? await pf.evaluate(extract) : null;
const pr = await load(shared, 'results', '.datetime-col');
const R = pr ? await pr.evaluate(extract) : null;
await browser.close();
if (!F || !R) { console.log('Could not load Full-Time pages - cups.json left unchanged'); process.exit(0); }
console.log(`cup fixtures ${F.fixtures.length}, cup results ${R.results.length}`);
if (R.results.length < 20) { console.log('Too few cup results - cups.json left unchanged'); process.exit(0); }

const nice = s => s.replace(/^U12 /, 'U12 ').replace(/\w\S*/g, w => /^(u\d+|fc|afc)$/i.test(w) ? w.toUpperCase() : w[0].toUpperCase() + w.slice(1).toLowerCase())
  .replace(/Sunday Youth/, 'Sunday Youth');
const old = fs.existsSync('cups.json') ? JSON.parse(fs.readFileSync('cups.json', 'utf8')) : { cups: [] };
const oldBy = {}; (old.cups || []).forEach(c => { oldBy[c.name.toLowerCase()] = c; });
const by = {};
const get = n => by[n] || (by[n] = { name: n, fixtures: [], results: [] });
const dkey = d => { const m = d.match(/(\d\d)\/(\d\d)\/(\d\d)/); return m ? m[3] + m[2] + m[1] + (d.slice(9) || '') : ''; };

for (const f of F.fixtures) {
  const c = get(f.comp);
  const o = { date: f.date, home: f.home, away: f.away };
  if (f.venue) o.venue = f.venue;
  if (/postpone|cancel|abandon/i.test(f.notes)) o.status = f.notes;
  c.fixtures.push(o);
}
for (const r of R.results) {
  const m = r.score.match(/^(\d+) - (\d+)/);
  if (!m) continue;                       // walkovers have no score
  const o = { date: r.date.slice(0, 8), home: r.home, away: r.away, hs: +m[1], as: +m[2] };
  const p = r.score.match(/Pens (\d+)-(\d+)/); if (p) o.pens = [+p[1], +p[2]];
  get(r.comp).results.push(o);
}
const cups = Object.values(by).map(c => {
  c.fixtures.sort((a, b) => dkey(a.date).localeCompare(dkey(b.date)));
  c.results.sort((a, b) => dkey(a.date).localeCompare(dkey(b.date)));
  const prev = oldBy[c.name.toLowerCase()] || oldBy[nice(c.name).toLowerCase()];
  const out = { name: nice(c.name).replace(/^Under 12 /i, 'U12 ').replace(/^Under 13 /i, 'U13 ') };
  if (prev && prev.bracket) out.bracket = prev.bracket;   // hand-kept knockout draws survive
  out.fixtures = c.fixtures; out.results = c.results;
  return out;
});
cups.sort((a, b) => a.name.localeCompare(b.name));
const stamp = new Date().toLocaleString('en-GB', { timeZone: 'Europe/London', day: 'numeric', month: 'short', year: '2-digit' }).toUpperCase();
const next = { updated: stamp, cups };
const cmp = x => JSON.stringify({ cups: x.cups });
if (cmp(next) === cmp(old)) { console.log('No change in cups'); process.exit(0); }
fs.writeFileSync('cups.json', JSON.stringify(next, null, 1) + '\n');
console.log('cups.json updated: ' + cups.map(c => `${c.name} (${c.fixtures.length}f/${c.results.length}r)`).join('; '));
