const { src, dest, parallel } = require('gulp');
const cleanCSS = require('gulp-clean-css');
const del = require('del');
const package = require("./package.json");
const rename = require('gulp-rename');

// Get Plugin Version from `package.json`
const pluginVersion = package.preventXssVulnerability.pluginVersion;

async function deleteMinFiles() {
  await del(['admin/**/*.min.css', 'frontend/**/*.min.css']);
}

function minifyCss() {
  return src([
      'admin/**/*.css',
      'frontend/**/*.css',
      '!admin/**/*/*.min.css',
      '!frontend/**/*.min.css'
    ])
    .pipe(cleanCSS({compatibility: 'ie8'}))
    .pipe(rename(function(path) {
        path.extname = "-" + pluginVersion + ".min" + path.extname;
    }))
    .pipe(dest('admin/'));
}

exports.build = parallel(deleteMinFiles, minifyCss);
