var gulp = require('gulp');
var sass = require('gulp-sass');

gulp.task('default', function() {

	gulp.src('admin/css/*.scss')
		.pipe(sass())
		.pipe(gulp.dest('admin/css'));

});
