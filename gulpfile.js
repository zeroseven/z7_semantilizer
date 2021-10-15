const gulp = require('gulp');
const rename = require('gulp-rename');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const cleancss = require('gulp-clean-css');
const babel = require('gulp-babel');
const uglify = require('gulp-uglify');

gulp.task('Scss', done => {
  gulp.src(['./Resources/Private/Scss/**/*.scss'])
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(cleancss(process.env.GULP_CONTEXT !== 'development' ? null : {
      format: 'beautify'
    }))
    .pipe(gulp.dest('./Resources/Public/Css'));

  done();
});

gulp.task('JavaScript', done => {
  gulp.src(['./Resources/Private/JavaScript/**/*.js'])
    .pipe(babel({
      presets: ['@babel/preset-env']
    }))
    .on('error', console.error.bind(console))
    .pipe(uglify(process.env.GULP_CONTEXT !== 'development' ? {} : {
      compress: false,
      mangle: false,
      output: {
        beautify: true
      }
    }))
    .pipe(gulp.dest('./Resources/Public/JavaScript'));


  done();
});

gulp.task('watchJavaScript', done => {
  gulp.watch('./Resources/Private/JavaScript/**/*.js', gulp.series('JavaScript'));

  done();
});

gulp.task('watchScss', done => {
  gulp.watch('./Resources/Private/Scss/**/*.scss', gulp.series('Scss'));

  done();
});

gulp.task('build', gulp.parallel('JavaScript', 'Scss'));

gulp.task('default', gulp.series('build', gulp.parallel('watchJavaScript', 'watchScss')));
