module.exports = {
    'plugins': [
        // 兼容浏览器，添加前缀
        require('autoprefixer')({
            'overrideBrowserslist': [
                'Android 4.1',
                'iOS 7.1',
                'Chrome > 31',
                'ff > 31',
                'ie >= 10'
            //   'last 2 versions', // 所有主流浏览器最近2个版本
            ], 'grid': true
        })
    ]
};