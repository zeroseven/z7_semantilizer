'use strict';

const gulp = require('gulp');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const autoprefixer = require('gulp-autoprefixer');
const cleancss = require('gulp-clean-css');


gulp.task('Scss', function (done) {

	// Build the task chain
	gulp.src(['./Resources/Private/Scss/**/*.scss'])

		// Render scss
		.pipe(sass().on('error', sass.logError))
		.pipe(autoprefixer())

		// Create a uncompressed version
		.pipe(cleancss({format: 'beautify'}))
		.pipe(rename({suffix: '.dist'}))
		.pipe(gulp.dest('./Resources/Public/Css'))

		// Create a compressed version
		.pipe(cleancss())
		.pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest('./Resources/Public/Css'));

	done();
});

gulp.task('watch', function() {
	gulp.watch(['./Resources/Private/Scss/**/*.scss'], gulp.series('build'));
});

gulp.task('build', gulp.series(['Scss']));

gulp.task('default', gulp.series('build', 'watch'));
