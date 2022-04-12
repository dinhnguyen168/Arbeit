import Vue from 'vue'
import upperFirst from 'lodash/upperFirst'
import camelCase from 'lodash/camelCase'
import kebabCase from 'lodash/kebabCase'
import startCase from 'lodash/startCase'

let routes = []

// register global forms
const requireForms = require.context('../forms', false, /[A-Z]\w+Form\.vue$/)
requireForms.keys().forEach(fileName => {
  const formConfig = requireForms(fileName)
  const formName = upperFirst(camelCase(fileName.replace(/^\.\/(.*)\.\w+$/, '$1')))
  Vue.component(formName, formConfig.default || formConfig)
  routes.push({
    path: kebabCase(formName) + '/:id?',
    component: formConfig.default || formConfig,
    name: kebabCase(formName),
    meta: { requiresAuth: true, breadcrumb: startCase(formName) }
  })
})

export default routes
