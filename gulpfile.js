/*
var elixir = require('laravel-elixir');

elixir(function(mix) {
    mix.sass('app.scss');
});

*/

var gulp = require('gulp'),
	connect = require('gulp-connect'),
	open = require('gulp-open'),
	browserify = require('gulp-browserify'),
	concat = require('gulp-concat'),
	rename = require('gulp-rename'),
	minifyCss = require('gulp-cssnano'),
	uglify = require('gulp-uglify'),
	port = process.env.port || 8080;






