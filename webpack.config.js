const webpackConfig = require('@nextcloud/webpack-vue-config')

// Add Files navigation entry point
webpackConfig.entry = {
	'files_archive-main': './src/main.js',
	'files_archive-navigation': './src/filesNavigation.js',
}

module.exports = webpackConfig
