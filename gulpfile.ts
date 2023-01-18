import gulp from 'gulp';
import zip from 'gulp-zip';

const PLUGINS = ['myparcelnl', 'myparcelbe'];

const SOURCE_FILES = [
  './woocommerce-myparcel.php',
  './readme.txt',
  './LICENSE.txt',
  './config/**/*',
  './src/**/*',
  './vendor/**/*',
  './views/**/lib/**/*',
  '!./**/node_modules/**/*',
  '!./**/.yarn/**/*',
];

const ZIP_FILE_NAME = ':name-:version.zip';

PLUGINS.forEach((name) => {
  gulp.task(`copy:${name}`, () => {
    return gulp.src(SOURCE_FILES, {base: '.', read: false}).pipe(gulp.dest(`./dist/${name}`));
  });

  gulp.task(`transform:${name}`, () => {
    return gulp.src(`./dist/${name}/**/*`).pipe(gulp.dest(`./dist/${name}`));
  });

  gulp.task(`zip:${name}`, () => {
    return gulp
      .src(`./dist/${name}/**/*`, {base: './dist', read: false})
      .pipe(zip(ZIP_FILE_NAME.replace(':name', name).replace(':version', process.env.npm_package_version ?? '?')))
      .pipe(gulp.dest('./dist'));
  });
});

gulp.task(`copy`, gulp.parallel(PLUGINS.map((name) => `copy:${name}`)));
gulp.task(`transform`, gulp.parallel(PLUGINS.map((name) => `transform:${name}`)));
gulp.task(`zip`, gulp.parallel(PLUGINS.map((name) => `zip:${name}`)));

const defaultTask = gulp.parallel(
  ...PLUGINS.map((name) => {
    return gulp.series(`copy:${name}`, `transform:${name}`, `zip:${name}`);
  }),
);

gulp.task('default', defaultTask);

export default defaultTask;
