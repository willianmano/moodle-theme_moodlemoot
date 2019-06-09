/* eslint no-undef: "error" */
/* eslint camelcase: 2 */
/* eslint-env node */

"use strict";

module.exports = function(grunt) {

    var path = require('path'),
        PWD = process.env.PWD || process.cwd();

    var decachephp = "../../admin/cli/purge_caches.php";

    var inAMD = path.basename(PWD) == 'amd';

    // Globbing pattern for matching all AMD JS source files.
    var amdSrc = [inAMD ? PWD + '/src/*.js' : '**/amd/src/*.js'];

    /**
     * Function to generate the destination for the uglify task
     * (e.g. build/file.min.js). This function will be passed to
     * the rename property of files array when building dynamically:
     * http://gruntjs.com/configuring-tasks#building-the-files-object-dynamically
     *
     * @param {String} destPath the current destination
     * @param {String} srcPath the  matched src path
     * @return {String} The rewritten destination path.
     */
    var uglifyRename = function(destPath, srcPath) {
        destPath = srcPath.replace('src', 'build');
        destPath = destPath.replace('.js', '.min.js');
        destPath = path.resolve(PWD, destPath);
        return destPath;
    };

    grunt.initConfig({
        eslint: {
            options: {quiet: !grunt.option('show-lint-warnings')},
            amd: {src: amdSrc},
            yui: {src: ['**/yui/src/**/*.js', '!*/**/yui/src/*/meta/*.js']}
        },
        uglify: {
            amd: {
                files: [{
                    expand: true,
                    src: amdSrc,
                    rename: uglifyRename
                }],
                options: {report: 'none'}
            }
        },
        sass: {
            dist: {
                options: {
                    style: 'compressed',
                    spawn: false
                },
                files: {
                    'style/moodlemoot.css': 'scss/preset/default.scss'
                }
            }
        },
        stylelint: {
            scss: {
                options: {syntax: 'scss'},
                src: ['*/**/*.scss']
            },
            css: {
                src: ['*/**/*.css'],
                options: {
                    configOverrides: {
                        rules: {
                            // These rules have to be disabled in .stylelintrc for scss compat.
                            "at-rule-no-unknown": true,
                        }
                    }
                }
            }
        },
        watch: {
            options: {
                livereload: true
            },
            amd: {
                files: ['**/amd/src/**/*.js'],
                tasks: ['amd', 'decache']
            },
            css: {
                files: 'scss/**/*.scss',
                tasks: ['css', 'decache'],
                options: {
                    spawn: false
                }
            }
        },
        exec: {
            decache: {
                cmd: 'php ' + decachephp,
                callback: function(error) {
                    if (!error) {
                        grunt.log.writeln('Moodle theme cache reseted.');
                    }
                }
            }
        }
    });

    // Load contrib tasks.
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-exec');

    // Load core tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-eslint');
    grunt.loadNpmTasks('grunt-stylelint');
    grunt.loadNpmTasks('grunt-sass');

    // Register tasks.
    grunt.registerTask('amd', ['uglify']);
    grunt.registerTask('sass', ['sass']);
    grunt.registerTask('default', ['watch']);

    // Register SCSS taks.
    grunt.registerTask('css', ['stylelint:scss']);
    // grunt.registerTask('css', ['stylelint:scss', 'sass:dist', 'stylelint:css']);
    grunt.registerTask('decache', ['exec:decache']);

    grunt.registerTask('compile', [
        'uglify',
        'css',
        'decache'
    ]);
};