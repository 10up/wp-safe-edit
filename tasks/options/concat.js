module.exports = {
	options: {
		stripBanners: true,
			banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
		' * <%= pkg.homepage %>\n' +
		' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
		' * Licensed MIT' +
		' */\n'
	},
	main: {
		src: [
			'assets/js/src/wp-post-forking.js'
		],
			dest: 'assets/js/wp-post-forking.js'
	}
};
