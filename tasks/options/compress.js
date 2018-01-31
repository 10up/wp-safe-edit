module.exports = {
	main: {
		options: {
			mode: 'zip',
			archive: './release/wp-safe-edit.<%= pkg.version %>.zip'
		},
		expand: true,
		cwd: 'release/<%= pkg.version %>/',
		src: ['**/*'],
		dest: 'wp-safe-edit/'
	}
};