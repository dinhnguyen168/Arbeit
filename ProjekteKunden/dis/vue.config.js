const GitRevisionPlugin = require('git-revision-webpack-plugin')
const gitRevisionPlugin = new GitRevisionPlugin()
const { execSync } = require('child_process')

function getCommitDate (n = -1) {
  let commitdate = execSync(`git log --pretty=format:"%ad" --date=short ${n}`)
    .toString()
    .slice(0, 10) // 2019-10-21
  return commitdate
}

module.exports = {
  outputDir: 'web',
  publicPath: './',
  productionSourceMap: false,
  assetsDir: 'assets',
  css: {
    sourceMap: true
  },
  pwa: {
    workboxOptions: {
      skipWaiting: true
    }
  },
  devServer: {
    proxy: 'http://dis.localhost/'
  },
  configureWebpack: {
    performance: {
      hints: 'warning',
      maxEntrypointSize: 3800000,
      maxAssetSize: 3000000
    }
  },
  chainWebpack: config => {
    config
      .plugin('define')
      .tap(args => {
        args[0]['process.env']['VERSION'] = JSON.stringify(gitRevisionPlugin.version())
        args[0]['process.env']['COMMIT'] = JSON.stringify(gitRevisionPlugin.commithash())
        args[0]['process.env']['BRANCH'] = JSON.stringify(gitRevisionPlugin.branch())
        args[0]['process.env']['COMMITDATE'] = JSON.stringify(getCommitDate(-1))
        return args
      })
  }
}
