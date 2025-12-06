const path = require('path')

module.exports = {
  //mode: 'production',
  mode: 'development',
  devtool: 'source-map',
  entry: './src/main.js',
  resolve: {
    extensions: ['.js']
  },
  module: {
    rules: [
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader'],
      }
    ]
  },
  // module: {
  //   rules: [
  //     {
  //       test: /\.css$/,
  //       use: [
  //         { loader: MiniCssExtractPlugin.loader },
  //         { loader: 'css-loader', options: { importLoaders: 1 } }
  //       ]
  //     }
  //   ]
  // },
  output: {
    filename: 'main.js',
    path: path.join(__dirname, 'dist')
  }
}