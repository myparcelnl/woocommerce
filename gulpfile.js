const {createBuildJsTask} = require('./private/gulp/createBuildJsTask');
const {createBuildScssTask} = require('./private/gulp/createBuildScssTask');
const {createBuildTask} = require('./private/gulp/createBuildTask');
const {createCleanTask} = require('./private/gulp/createCleanTask');
const {createCopyDeliveryOptionsTask} = require('./private/gulp/createCopyDeliveryOptionsTask');
const {createCopyTask} = require('./private/gulp/createCopyTask');
const {createZipTask} = require('./private/gulp/createZipTask');
const gulp = require('gulp');
const plugins = require('gulp-load-plugins')();

/**
 * Empty the dist folder.
 */
gulp.task('clean', createCleanTask(gulp, plugins));

/**
 * Run babel on the javascript files.
 */
gulp.task('build:js', createBuildJsTask(gulp, plugins));

/**
 * Copy the delivery options js.
 */
gulp.task('copy:delivery-options', createCopyDeliveryOptionsTask(gulp));

/**
 * Compile and run postcss.
 */
gulp.task('build:scss', createBuildScssTask(gulp, plugins));

/**
 * Copy the static css files and images.
 */
gulp.task('copy', createCopyTask(gulp));

/**
 * Collect all files and put it in a zip file.
 */
gulp.task('zip', createZipTask(gulp, plugins));

const baseBuild = createBuildTask(gulp);

const build = gulp.series(baseBuild, 'zip');

/**
 * The default task.
 */
gulp.task('build', build);

exports.default = build;
