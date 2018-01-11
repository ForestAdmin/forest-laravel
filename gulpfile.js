'use strict';
const gulp = require('gulp');
const moment = require('moment');
const fs = require('fs');
const simpleGit = require('simple-git')();
const semver = require('semver');
const exec = require('child_process').execSync;

let BRANCH_MASTER = 'master';
let BRANCH_DEVEL = 'devel';

gulp.task('build', () => {
  let numberToIncrement = 'patch';
  if (process.argv && process.argv[3]) {
    const option = process.argv[3].replace('--', '');
    if (['major', 'minor', 'patch'].indexOf(option) !== -1) {
      numberToIncrement = option;
    }
  }

  // VERSION
  var versionFile = fs.readFileSync('composer.json').toString().split('\n');
  var version = versionFile[3].match(/\w*"version": "(.*)",/)[1];
  version = semver.inc(version, numberToIncrement);
  versionFile[3] = '  "version": "' + version + '",';
  fs.writeFileSync('composer.json', versionFile.join('\n'));

  // CHANGELOG
  let data = fs.readFileSync('CHANGELOG.md').toString().split('\n');
  let today = moment().format('YYYY-MM-DD');

  data.splice(3, 0, `\n## RELEASE ${version} - ${today}`);
  let text = data.join('\n');

  simpleGit
    .checkout(BRANCH_DEVEL)
    .then(function() { console.log('Starting pull on ' + BRANCH_DEVEL + '...'); })
    .pull(function(error) { if (error) { console.log(error); } })
    .then(function() { console.log(BRANCH_DEVEL + ' pull done.'); })
    .then(function() { fs.writeFileSync('CHANGELOG.md', text); })
    .add('*')
    .commit(`Release ${version}`)
    .push()
    .checkout(BRANCH_MASTER)
    .then(function() { console.log('Starting pull on ' + BRANCH_MASTER + '...'); })
    .pull(function(error) { if (error) { console.log(error); } })
    .then(function() { console.log(BRANCH_MASTER + ' pull done.'); })
    .mergeFromTo(BRANCH_DEVEL, BRANCH_MASTER)
    .push();
    .then(function() { console.log('Create a new Release on Github to publish the new package on Packagist.'); })
});
