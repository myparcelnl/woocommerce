const {createBuildJsTask} = require('./private/gulp/createBuildJsTask');
const {createBuildScssTask} = require('./private/gulp/createBuildScssTask');
const {createBuildTask} = require('./private/gulp/createBuildTask');
const {createCleanTask} = require('./private/gulp/createCleanTask');
const {createCopyDeliveryOptionsTask} = require('./private/gulp/createCopyDeliveryOptionsTask');
const {createCopyTask} = require('./private/gulp/createCopyTask');
const {createTranslationsImportTask} = require('./private/gulp/createTranslationsImportTask');
const {createUpdateComposerTask} = require('./private/gulp/createUpdateComposerTask');
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

/**
 * Run composer update.
 */
gulp.task('update:composer', createUpdateComposerTask());

/**
 * Download translations as csv and convert them to .po files.
 */
gulp.task('translations:import', createTranslationsImportTask());

/**
 * The default task.
 */
const baseBuild = createBuildTask(gulp);
const build = gulp.series(baseBuild, 'zip');

gulp.task('build', build);
gulp.task('watch', gulp.series(baseBuild, () => {
  gulp.watch(['src/css/**/*', 'src/img/**/*'], null, gulp.series('copy'));
  // Don't use babel in watch mode
  gulp.watch(['src/js/**/*'], null, () => gulp.src('src/js/**/*.js').pipe(gulp.dest('assets/js')));
  gulp.watch(['node_modules/@myparcel/delivery-options/**/*'], null, gulp.series('copy:delivery-options'));
  gulp.watch(['src/scss/**/*'], null, gulp.series('build:scss'));
  gulp.watch(['composer.json'], null, gulp.series('update:composer'));
}));

exports.default = build;
