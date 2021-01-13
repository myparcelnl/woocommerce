const babelify = require('babelify');
const browserify = require('browserify');
const buffer = require('vinyl-buffer');
const clean = require('gulp-clean');
const {exec} = require('child_process');
const gulp = require('gulp');
const gulpPoSync = require('gulp-po-sync');
const po2mo = require('gulp-po2mo');
const postcss = require('gulp-postcss');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const tap = require('gulp-tap');
const uglify = require('gulp-uglify');
const wpPot = require('gulp-wp-pot');
const zip = require('gulp-zip');

const PHP_FILES = ['*.php', 'migration/**/*.php', 'templates/**/*.php', 'includes/**/*.php'];

/**
 * Callback for use with tasks using child_process.exec().
 *
 * @param {Function} callback
 * @param {ExecException} err
 * @param {String} stdout
 * @param {String} stderr
 */
function execCallback(callback, err, stdout, stderr) {
  /* eslint-disable no-console */
  console.log(stdout);
  console.warn(stderr);
  /* eslint-enable no-console */
  callback(err);
}

/**
 * Empty the dist folder.
 */
gulp.task('clean', () => gulp.src('dist/**/*.*', {read: false})
  .pipe(clean({force: true})));

/**
 * Run babel on the javascript files.
 */
gulp.task('build:js', () => gulp.src('src/js/**/*.js', {read: false})
  .pipe(tap((file) => {
    file.contents = browserify(file.path)
      .transform(babelify)
      .bundle();
  }))
  .pipe(buffer())
  .pipe(sourcemaps.init())
  .pipe(uglify())
  .pipe(sourcemaps.write('./source-maps'))
  .pipe(gulp.dest('assets/js')));

/**
 * Copy the delivery options js.
 */
gulp.task('copy:delivery-options', () => gulp.src('node_modules/@myparcel/delivery-options/dist/myparcel.js')
  .pipe(gulp.dest('assets/js')));

/**
 * Compile and run postcss.
 */
gulp.task('build:scss', () => gulp.src('src/scss/**/*.scss')
  .pipe(sass())
  .pipe(postcss())
  .pipe(gulp.dest('assets/css')));

/**
 * Copy the static css files and images.
 */
gulp.task('copy', () => gulp.src([
  'src/css/**/*.*',
  'src/img/**/*.*',
], {
  base: 'src',
})
  .pipe(gulp.dest('assets')));

/**
 * Collect all files and put it in a zip file.
 */
gulp.task('zip', () => gulp.src([
  '*.png',
  'LICENSE',
  'assets/**/*',
  'includes/**/*',
  'languages/**/*',
  'migration/**/*',
  'readme.txt',
  'templates/**/*',
  'vendor/**/*',
  'woocommerce-myparcel.php',
], {base: '.'})
  .pipe(zip('woocommerce-myparcel.zip'))
  .pipe(gulp.dest('./')));

/**
 * Sync .pot file with source code.
 */
gulp.task('translations:pot', () => gulp.src(PHP_FILES, {read: false})
  .pipe(wpPot({
    domain: 'woocommerce-myparcel',
    package: 'WooCommerce MyParcel',
    team: 'MyParcel <support@myparcel.nl>',
    bugReport: 'https://github.com/myparcelnl/woocommerce/issues',
  }))
  .pipe(gulp.dest('languages/woocommerce-myparcel.pot')));

/**
 * Sync .po files with .pot file.
 */
gulp.task('translations:po', () => gulp.src('languages/**/*.po', {read: false})
  .pipe(gulpPoSync('languages/woocommerce-myparcel.pot'))
  .pipe(gulp.dest('languages')));

/**
 * Create .mo files from .po files.
 */
gulp.task('translations:mo', () => gulp.src('languages/**/*.po', {read: false})
  .pipe(po2mo())
  .pipe(gulp.dest('languages')));

gulp.task('translations', gulp.series(
  'translations:pot',
  'translations:po',
  'translations:mo',
));

gulp.task('update:composer', (callback) => {
  exec('composer update', (...params) => execCallback(callback, ...params));
});

gulp.task('update:npm', (callback) => {
  exec('npm update', (...params) => execCallback(callback, ...params));
});

/**
 * The default task.
 */
const build = gulp.series(
  'clean',
  gulp.parallel(
    'build:js',
    'build:scss',
    'update:composer',
    'copy',
    'translations',
    gulp.series(
      'update:npm',
      'copy:delivery-options',
    ),
  ),
);

gulp.task('build', build);
gulp.task('build:zip', gulp.series('build', 'zip'));

const watch = () => {
  gulp.watch(['src/css/**/*', 'src/img/**/*'], null, gulp.series('copy'));
  // Don't use babel in watch mode
  gulp.watch(['src/js/**/*'], null, () => gulp.src('src/js/**/*.js').pipe(gulp.dest('assets/js')));
  gulp.watch(['node_modules/@myparcel/delivery-options/**/*'], null, gulp.series('copy:delivery-options'));
  gulp.watch(['src/scss/**/*'], null, gulp.series('build:scss'));
  gulp.watch(PHP_FILES, null, gulp.series('translations'));
  gulp.watch(['composer.json'], null, gulp.series('update:composer'));
  gulp.watch(['package.json'], null, gulp.series('update:npm'));
};

gulp.task('watch', gulp.series(
  build,
  watch,
));

exports.default = build;
