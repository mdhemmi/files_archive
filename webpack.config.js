const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

// Add entry points
webpackConfig.entry = {
	'files_archive-main': './src/main.js',
	'files_archive-navigation': './src/filesNavigation.js',
	'files_archive-archive': './src/archive.js',
}

// Ensure output directory is explicitly set to js/
webpackConfig.output = {
	...webpackConfig.output,
	path: path.resolve(__dirname, 'js'),
	publicPath: '/apps/files_archive/js/',
}

module.exports = webpackConfig
