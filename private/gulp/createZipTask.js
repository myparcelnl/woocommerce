function createZipTask(gulp, plugins) {
  return () => gulp.src([
    '*.png',
    'LICENSE',
    'assets/**/*',
    'includes/**/*',
    'languages/**/*',
    'migration/**/*',
    'readme.txt',
    'templates/**/*',
    'vendor/**/*',
    'composer.json',
    'woocommerce-myparcel.php',
    'wpm-config.json',
  ], { base: '.' })
    .pipe(plugins.zip('woocommerce-myparcel.zip'))
    .pipe(gulp.dest('./'));
}

module.exports = { createZipTask };
