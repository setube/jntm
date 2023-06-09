const path = require('path');
module.exports = {
    'productionSourceMap': false,
    'publicPath':'./',
    'chainWebpack': config => {
        config.module
            .rule('thread-loader')
            .test(/.js$/)
            .include
            .add(path.resolve(__dirname, 'src'))
            .end()
            .use('thread-loader')
            .loader('thread-loader')
            .options({
                'workers': 3
            });
    }
};