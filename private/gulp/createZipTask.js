function createZipTask(gulp, plugins) {
  return () => gulp.src([
    'LICENSE',
    'assets/**/*',
    'config/**',
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
