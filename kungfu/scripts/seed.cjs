const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const projectDir = path.resolve(__dirname, '..');
const args = process.argv.slice(2);
const allowed = ['--local', '--prod', '--dry-run'];
if (args.some((arg) => !allowed.includes(arg)) || (args.includes('--local') && args.includes('--prod'))) {
  console.error('Usage: npm run seed -- [--local | --prod] [--dry-run]');
  process.exit(1);
}

const production = args.includes('--prod');
const dryRun = args.includes('--dry-run');

function run(command, commandArgs, options = {}) {
  const result = spawnSync(command, commandArgs, { cwd: projectDir, stdio: 'inherit', ...options });
  if (result.error) console.error(result.error.message);
  process.exit(result.error ? 1 : (result.status ?? 1));
}

if (!production) {
  const commandArgs = ['compose', 'run', '--rm', 'wpcli', 'kungfu', 'seed'];
  console.log('Seed target: local WordPress at http://localhost:8300');
  if (dryRun) {
    console.log(`Would run: docker ${commandArgs.join(' ')}`);
    process.exit(0);
  }
  run('docker', commandArgs);
}

// Reuse the same configuration file and site key as deploy.sh.
const configPath = path.resolve(projectDir, process.env.DEPLOY_CONFIG || '../deploy.config.json');
let site;
try {
  site = JSON.parse(fs.readFileSync(configPath, 'utf8')).sites?.kungfu;
} catch (error) {
  console.error(`Cannot read deployment configuration: ${error.message}`);
  process.exit(1);
}
if (!site || ['user', 'host', 'remoteDir'].some((key) => typeof site[key] !== 'string' || !site[key].trim())) {
  console.error('Production seeding requires user, host, and remoteDir in deploy.config.json → sites.kungfu.');
  process.exit(1);
}
if (!/^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$/.test(site.user) || !/^[a-zA-Z0-9][a-zA-Z0-9.:-]*$/.test(site.host)) {
  console.error('Invalid SSH user or host in sites.kungfu.');
  process.exit(1);
}
const port = Number(site.port || 22);
if (!Number.isInteger(port) || port < 1 || port > 65535) {
  console.error('Invalid SSH port in sites.kungfu.');
  process.exit(1);
}
const quote = (value) => `'${String(value).replace(/'/g, `'"'"'`)}'`;
const remoteScript = `set -eu
seed_dir=$1
case "$seed_dir" in '~/'*) seed_dir="$HOME/"\${seed_dir#??} ;; esac
seed_root=''
for candidate in "$seed_dir" "$seed_dir/.." "$seed_dir/../.."; do
  if [ -f "$candidate/wp-load.php" ]; then
    seed_root=$(cd "$candidate" && pwd)
    break
  fi
done
if [ -z "$seed_root" ]; then
  echo 'Cannot find WordPress. Set remoteDir to the site root, wp-content, or wp-content/themes.' >&2
  exit 1
fi
if ! command -v wp >/dev/null 2>&1; then
  echo 'WP-CLI must be installed on the production server.' >&2
  exit 1
fi
if [ "$(wp --path="$seed_root" option get stylesheet)" != 'kungfu_2026' ]; then
  echo 'Production must already have the Kungfu 2026 theme active.' >&2
  exit 1
fi
wp --path="$seed_root" kungfu seed
`;
const sshArgs = ['-o', 'ConnectTimeout=20', '-p', String(port)];
if (site.sshKey) {
  if (typeof site.sshKey !== 'string') {
    console.error('sshKey must be a file path.');
    process.exit(1);
  }
  sshArgs.push('-i', site.sshKey.replace(/^~(?=\/)/, os.homedir()));
}
sshArgs.push(`${site.user}@${site.host}`, `sh -s -- ${quote(site.remoteDir)}`);
console.log(`Seed target: production ${site.host}:${site.remoteDir}`);
if (dryRun) {
  console.log('Would connect over SSH and run wp kungfu seed on the active Kungfu 2026 site.');
  process.exit(0);
}
run('ssh', sshArgs, { input: remoteScript, stdio: ['pipe', 'inherit', 'inherit'] });
