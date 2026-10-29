const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const projectDir = path.resolve(__dirname, '..');
const args = process.argv.slice(2);
let target = null;

if (args.includes('--local')) target = 'local';
if (args.includes('--prod')) {
  if (target) {
    console.error('Choose only one seed target: --local or --prod.');
    process.exit(1);
  }
  target = 'prod';
}

// npm consumes its own --prod flag, but exposes it as NODE_ENV=production.
if (!target && process.env.NODE_ENV === 'production') target = 'prod';

if (!target) {
  console.log(`Choose a seed target (nothing was changed):

  npm run seed:local
  npm run seed:prod

Argument form:

  npm run seed -- --local
  npm run seed -- --prod

npm consumes --local unless it appears after the extra -- delimiter.`);
  process.exit(0);
}

function run(command, commandArgs, options = {}) {
  const result = spawnSync(command, commandArgs, {
    cwd: projectDir,
    stdio: 'inherit',
    ...options
  });
  if (result.error) {
    console.error(result.error.message);
    process.exit(1);
  }
  if (result.status !== 0) process.exit(result.status || 1);
}

if (target === 'local') {
  console.log('Seeding local WordPress at http://localhost:8400');
  run('docker', ['compose', 'run', '--rm', 'wpcli', 'theme', 'activate', 'lexington116_2026']);
  run('docker', ['compose', 'run', '--rm', 'wpcli', 'lumi', 'seed']);
  process.exit(0);
}

const configPath = path.resolve(projectDir, '..', 'deploy.config.json');
if (!fs.existsSync(configPath)) {
  console.error(`Missing production config: ${configPath}`);
  process.exit(1);
}

let config;
try {
  config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
} catch (error) {
  console.error(`Cannot read ${configPath}: ${error.message}`);
  process.exit(1);
}

const site = config.sites && config.sites.lexington116;
if (!site || !site.user || !site.host || !site.remoteDir) {
  console.error('deploy.config.json needs user, host, and remoteDir for sites.lexington116.');
  process.exit(1);
}

const quote = (value) => `'${String(value).replace(/'/g, `'"'"'`)}'`;
const remoteDir = String(site.remoteDir).replace(/\/$/, '');
const remoteCommand = [
  `test -f ${quote(`${remoteDir}/wp-load.php`)}`,
  `cd ${quote(remoteDir)}`,
  'wp theme activate lexington116_2026',
  `wp --path=${quote(remoteDir)} lumi seed`
].join(' && ');
const sshArgs = ['-o', 'ConnectTimeout=20', '-p', String(site.port || 22)];
if (site.sshKey) {
  const key = String(site.sshKey).replace(/^~(?=\/)/, process.env.HOME || '');
  sshArgs.push('-i', key);
}
sshArgs.push(`${site.user}@${site.host}`, remoteCommand);

console.log(`Seeding production WordPress at ${site.host}:${remoteDir}`);
run('ssh', sshArgs);
