const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const cleanCss = require('gulp-clean-css');
const ts = require('gulp-typescript');
const tsProject = ts.createProject('tsconfig.json');
const uglify = require('gulp-uglify');

gulp.task('Scss', done => {
  gulp.src(['./Resources/Private/Scss/**/*.scss'])
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(cleanCss(process.env.GULP_CONTEXT !== 'development' ? null : {
      format: 'beautify'
    }))
    .pipe(gulp.dest('./Resources/Public/Css'));

  done();
});

gulp.task('JavaScript', () => tsProject.src()
  .pipe(tsProject()).on('error', () => {})
  .js.pipe(uglify(process.env.GULP_CONTEXT !== 'development' ? {} : {
    compress: false,
    mangle: false,
    output: {
      beautify: true
    }
  })).pipe(gulp.dest('./Resources/Public/JavaScript/Backend/')));

gulp.task('watchScss', done => {
  gulp.watch('./Resources/Private/Scss/**/*.scss', gulp.series('Scss'));

  done();
});

gulp.task('watchJavaScript', done => {
  gulp.watch('./Resources/Private/JavaScript/**/*.ts', gulp.series('JavaScript'));

  done();
});

gulp.task('build', gulp.parallel('Scss', 'JavaScript'));

gulp.task('default', gulp.series('build', gulp.parallel('watchScss', 'watchJavaScript')));
