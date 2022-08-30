const { src, dest, parallel } = require('gulp');
const cleanCSS = require('gulp-clean-css');
const del = require('del');
const package = require('./package.json');
const rename = require('gulp-rename');

// Get Plugin Version from `package.json`
const pluginVersion = package.httpAuth.pluginVersion;

async function deleteMinFiles() {
  await del(['admin/**/*.min.css', 'frontend/**/*.min.css']);
}

function minifyCss() {
  return src(['assets/**/*.css', '!assets/**/*/*.min.css'])
    .pipe(cleanCSS({ compatibility: 'ie8' }))
    .pipe(rename(function (path) {
      path.extname = '-' + pluginVersion + '.min' + path.extname;
    }))
    .pipe(dest('assets/'));
}

exports.build = parallel(deleteMinFiles, minifyCss);
