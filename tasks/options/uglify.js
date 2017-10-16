module.exports = {
	all: {
		files: {
			'assets/js/wp-post-forking.min.js': ['assets/js/wp-post-forking.js']
		},
		options: {
			banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
			' * <%= pkg.homepage %>\n' +
			' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
			' * Licensed MIT' +
			' */\n',
			mangle: {
				except: ['jQuery']
			}
		}
	}
};
