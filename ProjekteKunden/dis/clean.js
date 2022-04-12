// script to clean web directory before build
const fs = require('fs')
const dirsToDelete = [
  './web/assets/css',
  './web/assets/js',
  './web/assets/img',
  './web/assets/fonts',
  'web/img/icons'
]

const filesToDelete = [
  './web/manifest.json',
  './web/service-worker.js',
  './web/index.html',
  './web/favicon.ico'
]

const deletePrecacheManifests = function () {
  fs.readdir('./web', (error, files) => {
    if (error) throw error

    files.map(file => {
      if (file.startsWith('precache-manifest.')) {
        console.log(`\t - [file] ./web/${file}`)
        fs.unlinkSync(`./web/${file}`)
      }
    })
  })
}

const deleteFolderRecursive = function (path) {
  if (fs.existsSync(path)) {
    console.log(`\t - [folder] ${path}`)
    fs.readdirSync(path).forEach(function (file, index) {
      let curPath = path + '/' + file
      if (fs.lstatSync(curPath).isDirectory()) { // recurse
        deleteFolderRecursive(curPath)
      } else { // delete file
        fs.unlinkSync(curPath)
      }
    })
    fs.rmdirSync(path)
  }
}

console.log('\x1b[36m%s\x1b[0m', 'cleaning web directory...')

dirsToDelete.map(path => {
  deleteFolderRecursive(path)
})

filesToDelete.map(file => {
  if (fs.existsSync(file)) {
    console.log(`\t - [file] ${file}`)
    fs.unlinkSync(file)
  }
})

deletePrecacheManifests()
