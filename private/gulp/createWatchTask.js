function createWatchTask(gulp) {
  return () => {
    gulp.watch(['src/css/**/*', 'src/img/**/*'], null, gulp.series('copy'));
    // Don't use babel in watch mode
    gulp.watch(['src/js/**/*'], null, () => gulp.src('src/js/**/*.js').pipe(gulp.dest('assets/js')));
    gulp.watch(['node_modules/@myparcel/delivery-options/**/*'], null, gulp.series('copy:delivery-options'));
    gulp.watch(['src/scss/**/*'], null, gulp.series('build:scss'));
    gulp.watch(['composer.json'], null, gulp.series('update:composer'));
  };
}

module.exports = {createWatchTask};
