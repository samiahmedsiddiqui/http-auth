/**
 * Sync the version from package.json into the plugin files.
 * Runs automatically with `npm version <new-version>`.
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const { version } = require(path.join(root, 'package.json'));

const replacements = {
	'http-auth.php': [
		[/(\* Version:\s*)[\d.]+/, `$1${version}`],
		[/(define\( 'HTTP_AUTH_VERSION', ')[\d.]+(' \))/, `$1${version}$2`],
	],
	'readme.txt': [[/(Stable tag:\s*)[\d.]+/, `$1${version}`]],
};

Object.entries(replacements).forEach(([file, rules]) => {
	const filePath = path.join(root, file);
	let content = fs.readFileSync(filePath, 'utf8');

	rules.forEach(([pattern, replacement]) => {
		if (!pattern.test(content)) {
			throw new Error(`Version pattern not found in ${file}`);
		}
		content = content.replace(pattern, replacement);
	});

	fs.writeFileSync(filePath, content);
});

console.log(`Synced version ${version}`);
