const webpackConfig = require('@nextcloud/webpack-vue-config')

// Add entry points
webpackConfig.entry = {
	'files_archive-main': './src/main.js',
	'files_archive-navigation': './src/filesNavigation.js',
	'files_archive-archive': './src/archive.js',
}

module.exports = webpackConfig
