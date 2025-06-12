const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require("path");

module.exports = {
	entry: "./assets/js/tender-scripts.js",
	output: {
		filename: "./js/tender-scripts.js",
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
		],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: "./css/tender-styles.css", // Especifica la carpeta y el nombre del fichero CSS
		}),
	],
};
