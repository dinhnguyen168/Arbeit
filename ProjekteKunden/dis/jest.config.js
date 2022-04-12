module.exports = {
  preset: '@vue/cli-plugin-unit-jest',
  setupFilesAfterEnv: ['./tests/unit/setup.js'],
  transformIgnorePatterns: ['/node_modules/(?!vuetify|vuedl)'],
  collectCoverage: true,
  collectCoverageFrom: [
    'src/**/*.{js,ts,vue}',
    '!**/node_modules/**',
    '!backend/**',
    '!icdp_work/**',
    '!public/**',
    '!test/**',
    '!web/**',
    '!commitlint.config.js',
    '!build/**/*',
    '!coverage/**/*',
    '!dist/**/*',
    '!docs/**/*'
  ]
}
