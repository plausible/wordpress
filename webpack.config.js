const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );
const ImageminPlugin = require( 'imagemin-webpack-plugin' ).default;
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const wpPot = require( 'wp-pot' );

const inProduction = ( 'production' === process.env.NODE_ENV );
const mode = inProduction ? 'production' : 'development';

const config = {
	devtool: inProduction ? 'inline-source-map' : 'eval-cheap-module-source-map',
	mode,
	entry: {
		'plausible-admin': [ './assets/src/css/admin/main.scss', './assets/src/js/admin/main.js' ],
	},
	output: {
		path: path.join( __dirname, './assets/dist/' ),
		filename: 'js/[name].js',
	},
	module: {
		rules: [

			// Use Babel to compile JS.
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loader: 'babel-loader',
			},

			// Image files.
			{
				test: /\.(png|jpe?g|gif|svg)$/,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: 'images/[name].[ext]',
							publicPath: '../',
						},
					},
				],
			},
		],
	},

	// Plugins. Gotta have em'.
	plugins: [

		// Removes the "dist" folder before building.
		new CleanWebpackPlugin(),

		new MiniCSSExtractPlugin( {
			filename: 'css/[name].css',
		} ),

		new CopyWebpackPlugin(
			{
				patterns: [
					{ from: 'assets/src/images', to: 'images' },
				],
			}
		),

	],
};

if ( inProduction ) {
	// Minify images.
	// Must go after CopyWebpackPlugin above: https://github.com/Klathmon/imagemin-webpack-plugin#example-usage
	config.plugins.push( new ImageminPlugin( { test: /\.(jpe?g|png|gif|svg)$/i } ) );

	// POT file.
	wpPot( {
		package: 'Plausible Analytics',
		domain: 'plausible-analytics',
		destFile: 'languages/plausible-analytics.pot',
		relativeTo: './',
		src: [ './**/*.php', '!./includes/libraries/**/*', '!./vendor/**/*' ],
		bugReport: 'https://github.com/plausible/wordpress/issues/new',
		team: 'Plausible Analytics Team <hello@plausible.io>',
	} );
}

module.exports = config;
