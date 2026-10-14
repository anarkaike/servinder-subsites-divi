const path = require('path');

module.exports = {
  mode: 'production',
  entry: './src/index.jsx',
  externals: { react: 'React' },
  module: {
    rules: [{
      test: /\.jsx?$/,
      exclude: /node_modules/,
      use: {
        loader: 'babel-loader',
        options: {
          presets: [
            ['@babel/preset-env', { modules: false, targets: '> 5%' }],
            '@babel/preset-react',
          ],
        },
      },
    }],
  },
  resolve: { extensions: ['.js', '.jsx'] },
  output: {
    filename: 'servinder-subsites-divi.js',
    path: path.resolve(__dirname, 'build'),
  },
};
