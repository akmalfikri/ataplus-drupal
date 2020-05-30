var gulp = require('gulp'), //http://gulpjs.com/
    util = require("gulp-util"), //https://github.com/gulpjs/gulp-util
    sass = require("gulp-sass"), //https://www.npmjs.org/package/gulp-sass
    autoprefixer = require('gulp-autoprefixer'), //https://www.npmjs.org/package/gulp-autoprefixer
    cleanCSS = require('gulp-clean-css'), //https://www.npmjs.org/package/gulp-clean-css
    rename = require('gulp-rename'), //https://www.npmjs.org/package/gulp-rename
    del=require('del'), //https://github.com/gulpjs/gulp/blob/master/docs/recipes/delete-files-folder.md
    log = util.log;
    runSequence = require('run-sequence'),
    sourcemaps = require('gulp-sourcemaps');

var config = {
  dist: './css'
};


gulp.task('set-dev-node-env', function() {
  return process.env.NODE_ENV = 'dev';
});

gulp.task('set-prod-node-env', function() {
  return process.env.NODE_ENV = 'prod';
});


var sassFiles = "./scss/**/*.scss";
gulp.task("sass", function() {
  log("Generate CSS files " + (new Date()).toString());
  var env = process.env.NODE_ENV || 'dev'; //environment variable that defaults to 'dev'

  var sassOptions = {
    errLogToConsole: true
  };

  if (env === 'dev') {
    log("==> for dev");
    sassOptions.outputStyle = 'expanded';
    sassOptions.sourceComments = 'map';

    gulp.src(sassFiles)
      .pipe(sourcemaps.init())
      .pipe(sass(sassOptions).on('error', sass.logError))
      .pipe(autoprefixer("last 3 version","safari 5", "ie 8", "ie 9"))
      .pipe(sourcemaps.write())
      .pipe(gulp.dest("css"))
  }

  if (env === 'prod') {
    log("==> for prod");
    sassOptions.outputStyle = 'compressed';

    gulp.src(sassFiles)
      .pipe(sass(sassOptions).on('error', sass.logError))
      .pipe(autoprefixer("last 3 version","safari 5", "ie 8", "ie 9"))
      .pipe(gulp.dest("css"))
  }

});

// deletes content of the target directory.
gulp.task('clean', function(cb) {
  del(config.dist + '/**/*', cb);
});

gulp.task("build", ["set-prod-node-env", "clean", "sass"]);

gulp.task("watch", function() {
  log("Watching scss files for modifications");
  gulp.watch(sassFiles, ["set-dev-node-env", "sass"]);
});

gulp.task('default', function() {
  runSequence('build');
});
