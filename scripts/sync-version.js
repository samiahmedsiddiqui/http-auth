/**
 * Sync the version from package.json into the plugin files.
 * Runs automatically with `npm version <new-version>`.
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const { version } = require(path.join(root, 'package.json'));

// Each file needs at least one matching pattern.
const replacements = {
	'http-auth.php': [
		[/(\* Version:\s*)[\d.]+/, `$1${version}`],
		[/(define\( 'HTTP_AUTH_VERSION', ')[\d.]+(' \))/, `$1${version}$2`],
	],
	'includes/class-http-auth.php': [
		[/(public \$version = ')[\d.]+(';)/, `$1${version}$2`],
	],
	'readme.txt': [[/(Stable tag:\s*)[\d.]+/, `$1${version}`]],
};

Object.entries(replacements).forEach(([file, rules]) => {
	const filePath = path.join(root, file);
	let content = fs.readFileSync(filePath, 'utf8');
	let matched = false;

	rules.forEach(([pattern, replacement]) => {
		if (pattern.test(content)) {
			content = content.replace(pattern, replacement);
			matched = true;
		}
	});

	// The class property is optional once the version is a constant.
	if (!matched && file !== 'includes/class-http-auth.php') {
		throw new Error(`Version pattern not found in ${file}`);
	}

	fs.writeFileSync(filePath, content);
});

console.log(`Synced version ${version}`);
