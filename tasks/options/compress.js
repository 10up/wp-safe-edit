module.exports = {
	main: {
		options: {
			mode: 'zip',
			archive: './release/forkit.<%= pkg.version %>.zip'
		},
		expand: true,
		cwd: 'release/<%= pkg.version %>/',
		src: ['**/*'],
		dest: 'forkit/'
	}
};