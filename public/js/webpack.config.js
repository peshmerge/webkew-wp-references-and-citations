const path = require('path');

module.exports = {
    entry: './src/webkew-wp-references-citations-public.js',
    output: {
        filename: 'webkew-wp-references-citations-public.js',
        path: path.resolve(__dirname, 'dist'),
    },
    devtool:false,
    optimization: {
        minimize: true
    },
    mode: 'development',
    watch: true, // Enables watch mode in the config
};
