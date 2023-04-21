const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const cleanCss = require('gulp-clean-css');
const browserify = require('browserify');
const source = require('vinyl-source-stream');
const tsify = require('tsify');

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

gulp.task('JavaScript', () => browserify({
    basedir: ".",
    debug: true,
    entries: ['./Resources/Private/JavaScript/Backend/Semantilizer.ts'],
    cache: {},
    packageCache: {},
  })
    .plugin(tsify)
    .transform('babelify', {
      presets: ["es2015"],
      extensions: [".ts"]
    })
    .bundle()
    .pipe(source('main.js'))
    .pipe(gulp.dest('./Resources/Public/JavaScript/Backend/')));

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
