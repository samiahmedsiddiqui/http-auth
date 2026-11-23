/**
 * Minify assets/css/*.css into *.min.css.
 * Run with `npm run build`.
 */
const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');

const cssDir = path.join(__dirname, '..', 'assets', 'css');
const files = fs.readdirSync(cssDir);

// Remove old minified files first, including versioned names like x-1.0.1.min.css.
files
	.filter((file) => file.endsWith('.min.css'))
	.forEach((file) => fs.unlinkSync(path.join(cssDir, file)));

const minifier = new CleanCSS({ level: 1 });

files
	.filter((file) => file.endsWith('.css') && !file.endsWith('.min.css'))
	.forEach((file) => {
		const source = fs.readFileSync(path.join(cssDir, file), 'utf8');
		const output = minifier.minify(source);

		if (output.errors.length) {
			throw new Error(`${file}: ${output.errors.join(', ')}`);
		}

		const target = file.replace(/\.css$/, '.min.css');
		fs.writeFileSync(path.join(cssDir, target), output.styles);
		console.log(`${file} -> ${target}`);
	});
