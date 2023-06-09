'use strict';

module.exports = function ( grunt ) {
	const conf = grunt.file.readJSON( 'extension.json' ),
		messageDirs = conf.MessagesDirs.IncidentReporting;

	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		eslint: {
			options: {
				cache: true,
				fix: grunt.option( 'fix' ),
				maxWarnings: 0
			},
			all: [
				'.'
			]
		},
		stylelint: {
			all: [
				'modules/**/*.{less,vue}'
			]
		},
		banana: {
			docs: {
				files: {
					src: messageDirs
				}
			}
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'banana:docs', 'stylelint' ] );
	grunt.registerTask( 'default', 'test' );
	grunt.registerTask( 'fix', function () {
		grunt.config.set( 'eslint.options.fix', true );
		grunt.task.run( 'eslint' );
	} );
};
