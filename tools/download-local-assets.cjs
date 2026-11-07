// Run explicitly to refresh the project's pinned, locally served browser assets.
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { createHash } = require('node:crypto');
const root = path.resolve(__dirname, '..');
const assets = [
  ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', 'vendor/bootstrap/5.3.3/css/bootstrap.min.css'],
  ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', 'vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js'],
  ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/LICENSE', 'vendor/bootstrap/5.3.3/LICENSE'],
  ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', 'vendor/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css'],
  ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2', 'vendor/bootstrap-icons/1.11.3/font/fonts/bootstrap-icons.woff2'],
  ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff', 'vendor/bootstrap-icons/1.11.3/font/fonts/bootstrap-icons.woff'],
  ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/LICENSE', 'vendor/bootstrap-icons/1.11.3/LICENSE'],
  ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', 'vendor/chart.js/4.4.1/chart.umd.min.js'],
  ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/LICENSE.md', 'vendor/chart.js/4.4.1/LICENSE.md'],
  ['https://fonts.bunny.net/css?family=instrument-sans:400,500,600', 'vendor/instrument-sans/instrument-sans.css'],
  ['https://raw.githubusercontent.com/google/fonts/main/ofl/instrumentsans/OFL.txt', 'vendor/instrument-sans/OFL.txt'],
  ['https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=85&fm=jpg', 'images/dashboard/student-hero.jpg'],
  ['https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1600&q=82&fm=jpg', 'images/dashboard/admin-hero.jpg'],
  ['https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1600&q=82&fm=jpg', 'images/dashboard/staff-hero.jpg'],
  ['https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=900&q=80&fm=jpg', 'images/dashboard/scholarship-merit.jpg'],
  ['https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?auto=format&fit=crop&w=900&q=80&fm=jpg', 'images/dashboard/scholarship-need-based.jpg'],
  ['https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=900&q=80&fm=jpg', 'images/dashboard/scholarship-athletic.jpg'],
  ['https://images.unsplash.com/photo-1519452575417-564c1401ecc0?auto=format&fit=crop&w=900&q=80&fm=jpg', 'images/dashboard/scholarship-default.jpg'],
];
const manifest = [];
function download(url, relative) {
  const target = path.join(root, 'public', relative);
  fs.mkdirSync(path.dirname(target), { recursive: true });
  const temporary = target + '.download';
  const result = spawnSync(process.platform === 'win32' ? 'curl.exe' : 'curl', [
    '--fail', '--location', '--silent', '--show-error', '--retry', '2',
    '--connect-timeout', '15', '--max-time', '60', url, '--output', temporary,
  ], { stdio: 'inherit', windowsHide: true });
  if (result.error || result.status !== 0) {
    fs.rmSync(temporary, { force: true });
    throw new Error(`Download failed: ${relative}`);
  }
  let bytes = fs.readFileSync(temporary);
  if (!bytes.length || /^\s*<!doctype html/i.test(bytes.toString('utf8', 0, 100))) {
    fs.rmSync(temporary, { force: true });
    throw new Error(`Invalid response: ${relative}`);
  }
  if (relative.endsWith('.jpg') && bytes.readUInt16BE(0) !== 0xffd8) {
    fs.rmSync(temporary, { force: true });
    throw new Error(`Expected JPEG: ${relative}`);
  }
  if (/\.(css|js)$/.test(relative)) {
    // Debugger source maps are not runtime dependencies; do not request absent maps.
    bytes = Buffer.from(bytes.toString('utf8')
      .replace(/\/\*[#@]\s*sourceMappingURL=[\s\S]*?\*\//g, '')
      .replace(/^\/\/[#@]\s*sourceMappingURL=.*$/gm, ''));
    fs.writeFileSync(temporary, bytes);
  }
  fs.renameSync(temporary, target);
  manifest.push({ source: url, file: 'public/' + relative });
  console.log(`Copied ${relative} (${bytes.length} bytes)`);
}
for (const [url, relative] of assets) download(url, relative);
const fontCssPath = path.join(root, 'public/vendor/instrument-sans/instrument-sans.css');
let fontCss = fs.readFileSync(fontCssPath, 'utf8');
const fontUrls = [...new Set(fontCss.match(/https:\/\/fonts\.bunny\.net\/[^\s)'";]+/g) || [])];
if (fontUrls.length === 0) throw new Error('No Instrument Sans font files found');
for (const url of fontUrls) {
  const name = path.posix.basename(new URL(url).pathname);
  download(url, 'vendor/instrument-sans/' + name);
  fontCss = fontCss.replaceAll(url, './' + name);
}
fs.writeFileSync(fontCssPath, fontCss);
for (const asset of manifest) {
  const bytes = fs.readFileSync(path.join(root, asset.file));
  asset.bytes = bytes.length;
  asset.sha256 = createHash('sha256').update(bytes).digest('hex');
}
fs.writeFileSync(path.join(root, 'docs/local-assets.json'), JSON.stringify({
  copied_at: new Date().toISOString(),
  note: 'CSS/JS source-map directives removed; Instrument Sans CSS uses local font URLs. Vendor license notices retained.',
  assets: manifest,
}, null, 2) + '\n');
console.log(`Saved ${manifest.length} local files and docs/local-assets.json.`);
