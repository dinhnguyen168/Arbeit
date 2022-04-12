const compatibleSassVersions = {
    '17': {
        'node-sass': '^7.0.1',
        'sass-loader': '^12.1.0'
    },
    '16': {
        'node-sass': '^6.0.1',
        'sass-loader': '^10.2.1'
    },
    '15': {
        'node-sass': '^5.0.0',
        'sass-loader': '^10.2.1'

    }
}

function getNodeVersion () {
    return process.versions.node.split('.')[0]
}

function getTheCompatibleNodeSassVersionNumber () {
    const nodeVersion = getNodeVersion()
    const nodeVersionAsInteger = parseInt(nodeVersion)
    return nodeVersionAsInteger >= 14 ? compatibleSassVersions[nodeVersion]['node-sass'] : '^4.14.1'
}

function getTheCompatibleSassLoaderVersionNumber () {
    const nodeVersion = getNodeVersion()
    const nodeVersionAsInteger = parseInt(nodeVersion)
    return nodeVersionAsInteger >= 14 ? compatibleSassVersions[nodeVersion]['sass-loader'] : '^8.0.2'
}

function install () {
    const execSync = require('child_process').execSync

    const nodeSassVersion = getTheCompatibleNodeSassVersionNumber()
    const sassLoaderVersion = getTheCompatibleSassLoaderVersionNumber()

    execSync('npm install --save-dev node-sass@' + nodeSassVersion + ' sass-loader@' + sassLoaderVersion, {
        cwd: __dirname,
        stdio: 'inherit'
    })
}

install();