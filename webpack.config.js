const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require("path");

module.exports = {
	entry: {
		"tender-scripts": "./assets/js/tender-scripts.js",
		"tender-search-scripts": "./assets/js/book-search/init-library-search.js"},
	output: {
		filename: "./js/[name].js",
		path: path.resolve(__dirname, "dist"), // Especifica la carpeta de salida
	},
	mode: "development",
	module: {
		rules: [
			{
				test: /\.(c|sc|sa)ss$/,
				use: [
					{
						loader: MiniCssExtractPlugin.loader,
					},
					"css-loader",
					"sass-loader",
				],
			},
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: {
					loader: "babel-loader",
					options: {
						presets: ["@wordpress/babel-preset-default"],
					},
				},
			}
		],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: "./css/tender-styles.css", // Especifica la carpeta y el nombre del fichero CSS
		}),
	],
	resolve: {
		extensions: [".js", ".jsx"],
	},
};
