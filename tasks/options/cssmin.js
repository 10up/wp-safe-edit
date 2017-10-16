module.exports = {
	options: {
		banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
		' * <%=pkg.homepage %>\n' +
		' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
		' * Licensed MIT' +
		' */\n'
	},
	minify: {
		expand: true,

		cwd: 'assets/css/',
		src: ['wp-post-forking.css'],

		dest: 'assets/css/',
		ext: '.min.css'
	}
};
