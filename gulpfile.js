var gulp 	= require('gulp');
var sass 	= require('gulp-sass');
var coffee 	= require('gulp-coffee');

gulp.task('default', function() {

	//gulp.src('admin/coffeescript/*.coffee')
	//	.pipe(coffee())
	//	.pipe(gulp.dest('admin/js'));
	
	gulp.src('bower_components/jquery-ui/**/*')
		.pipe(gulp.dest('admin/js/jquery-ui'));

	gulp.src('bower_components/highcharts-release/**/*')
		.pipe(gulp.dest('admin/js/highcharts'));

	gulp.src('bower_components/highmaps-release/**/*')
		.pipe(gulp.dest('admin/js/highmaps'));

	gulp.src('admin/scss/*.scss')
		.pipe(sass())
		.pipe(gulp.dest('admin/css'));

});
