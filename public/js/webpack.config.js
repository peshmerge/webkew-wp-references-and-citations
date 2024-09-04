const path = require('path');

module.exports = {
    entry: './src/webkew-wp-references-citations.js',
    output: {
        filename: 'webkew-wp-references-citations.js',
        path: path.resolve(__dirname, 'dist'),
    },
    mode: 'development',
    watch: true, // Enables watch mode in the config
};