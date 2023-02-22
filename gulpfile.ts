import gulp from 'gulp';
import zip from 'gulp-zip';

const PLUGINS = ['myparcelnl', 'myparcelbe'];

const SOURCE_FILES = [
  'woocommerce-myparcel.php',
  'readme.txt',
  'composer.json',
  'LICENSE.txt',
  'config/**/*',
  'src/**/*',
  'vendor/**/*',
  'views/**/lib/**/*',
  '!**/node_modules/**',
];

const ZIP_FILE_NAME = ':name-:version-:timestamp.zip';

PLUGINS.forEach((name) => {
  gulp.task(`copy:${name}`, () => {
    return gulp.src(SOURCE_FILES, {base: '.'}).pipe(gulp.dest(`dist/${name}/`));
  });

  // TODO: transform plugin name to create myparcelbe

  gulp.task(`zip:${name}`, () => {
    const filename: string = ZIP_FILE_NAME.replace(':name', name)
      .replace(':version', process.env.npm_package_version ?? '?')
      .replace(':timestamp', Date.now().toString());

    return gulp.src(`./dist/${name}/**/*`, {base: './dist'}).pipe(zip(filename)).pipe(gulp.dest('./dist'));
  });
});

gulp.task(`copy`, gulp.parallel(PLUGINS.map((name) => `copy:${name}`)));
gulp.task(`zip`, gulp.parallel(PLUGINS.map((name) => `zip:${name}`)));

const defaultTask = gulp.parallel(
  ...PLUGINS.map((name) => {
    return gulp.series(`copy:${name}`, `zip:${name}`);
  }),
);

gulp.task('default', defaultTask);

export default defaultTask;
