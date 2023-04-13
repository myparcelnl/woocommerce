function createCopyDeliveryOptionsTask(gulp) {
  return () => gulp.src(require.resolve('@myparcel/delivery-options/dist/myparcel.js'))
    .pipe(gulp.dest('assets/js'));
}

module.exports = {createCopyDeliveryOptionsTask};
