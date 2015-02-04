var gulp 	= require('gulp');
var sass 	= require('gulp-sass');
var coffee 	= require('gulp-coffee');

gulp.task('default', function() {

	gulp.src('admin/scss/*.scss')
		.pipe(sass())
		.pipe(gulp.dest('admin/css'));

	gulp.src('admin/coffeescript/*.coffee')
		.pipe(coffee())
		.pipe(gulp.dest('admin/js'));

});
