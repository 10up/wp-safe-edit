module.exports = {
	dist: {
		options: {
			processors: [
				require('autoprefixer')({browsers: 'last 2 versions'})
			]
		},
		files: { 
			'assets/css/wp-post-forking.css': [ 'assets/css/wp-post-forking.css' ]
		}
	}
};