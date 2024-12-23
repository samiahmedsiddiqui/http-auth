const { src, dest, parallel } = require('gulp');
const cleanCSS = require('gulp-clean-css');
const package = require('./package.json');
const rename = require('gulp-rename');

// Get Plugin Version from `package.json`
const pluginVersion = package.version;

async function deleteMinFiles() {
	const fs = require('fs');
	const path = require('path');

	fs.readdir('./assets/css/', (err, files) => {
		files.forEach(file => {
			if (file.endsWith('.min.css')) {
				fs.unlinkSync(path.join('./assets/css/', file));
			}
		});
	});
}

function minifyCss() {
	return src(['assets/**/*.css', '!assets/**/*/*.min.css'])
		.pipe(cleanCSS({ compatibility: 'ie8' }))
		.pipe(rename(function (path) {
			path.extname = '-' + pluginVersion + '.min.css';
		}))
		.pipe(dest('./assets'));
}

exports.build = parallel(deleteMinFiles, minifyCss);
