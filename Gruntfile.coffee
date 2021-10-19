module.exports = (grunt) ->
  sass = require 'node-sass'
  @initConfig
    pkg: @file.readJSON('package.json')
    watch:
      files: [
        'css/src/**/*.scss'
      ]
      tasks: ['develop']
    postcss:
      pkg:
        options:
          processors: [
            require('autoprefixer')()
          ]
          failOnError: true
        files:
          'css/admin.css': 'css/admin.css'
          'css/styles.css': 'css/styles.css'
          'css/order-form-template.css': 'css/order-form-template.css'
      dev:
        options:
          map: true
          processors: [
            require('autoprefixer')()
          ]
          failOnError: true
        files:
          'css/admin.css': 'css/admin.css'
          'css/styles.css': 'css/styles.css'
          'css/order-form-template.css': 'css/order-form-template.css'
    sass:
      pkg:
        options:
          implementation: sass
          noSourceMap: true
          outputStyle: 'compressed'
          precision: 2
          includePaths: ['node_modules/foundation-sites/scss']
        files:
          'css/admin.css': 'css/src/admin.scss'
          'css/styles.css': 'css/src/styles.scss'
          'css/order-form-template.css': 'css/src/order-form-template.scss'
      dev:
        options:
          implementation: sass
          sourceMap: true
          outputStyle: 'nested'
          precision: 2
          includePaths: ['node_modules/foundation-sites/scss']
        files:
          'css/admin.css': 'css/src/admin.scss'
          'css/styles.css': 'css/src/styles.scss'
          'css/order-form-template.css': 'css/src/order-form-template.scss'
    sasslint:
      options:
        configFile: '.sass-lint.yml'
      target: ['css/**/*.s+(a|c)ss']
    compress:
      main:
        options:
          archive: '<%= pkg.name %>.zip'
        files: [
          {src: ['css/*.css']},
          {src: ['fields/*.php']},
          {src: ['fields/*.json']},
          {src: ['js/*.js']},
          {src: ['src/*.php']},
          {src: ['templates/*.php']},
          {src: ['vendor/setasign/fpdf']},
          {src: ['*.php']},
          {src: ['README.md']},
          {src: ['LICENSE.txt']}
        ]

  @loadNpmTasks 'grunt-contrib-compress'
  @loadNpmTasks 'grunt-contrib-watch'
  @loadNpmTasks 'grunt-sass-lint'
  @loadNpmTasks 'grunt-sass'
  @loadNpmTasks 'grunt-postcss'

  @registerTask 'default', ['sass:pkg', 'postcss:pkg']
  @registerTask 'develop', ['sasslint', 'sass:dev', 'postcss:dev']
  @event.on 'watch', (action, filepath) =>
    @log.writeln('#{filepath} has #{action}')
