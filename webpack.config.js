const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = [
    // Admin bundle
    {
        ...defaultConfig,
        entry: {
            index: path.resolve(process.cwd(), 'includes/admin/src', 'index.js'),
        },
        output: {
            filename: '[name].js',
            path: path.resolve(process.cwd(), 'includes/admin/build'),
        },
    },
    // Blocks bundle
    {
        ...defaultConfig,
        entry: {
            index: path.resolve(process.cwd(), 'includes/blocks/src', 'index.js'),
        },
        output: {
            filename: '[name].js',
            path: path.resolve(process.cwd(), 'includes/blocks/build'),
        },
    },
];
