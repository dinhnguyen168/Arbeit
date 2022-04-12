import Vue from 'vue'
import VueRouter from 'vue-router'
import BackendService from './services/BackendService'
import upperFirst from 'lodash/upperFirst'
import camelCase from 'lodash/camelCase'
import DateTimePlugin from './plugins/dateTime'
import PageTitlePlugin from './plugins/pageTitle'
import axios from 'axios'
import store from './store'
import router from './router'
import authCheckerMixin from './mixins/authChecker'
import mousetrap from './plugins/mousetrap'

Vue.use(VueRouter)
Vue.use(DateTimePlugin)
Vue.use(PageTitlePlugin)
Vue.mixin(authCheckerMixin)
Vue.use(mousetrap)
BackendService.bootstrap()

axios.interceptors.response.use(
  response => response,
  error => {
    // Store error in BackendService.lastError
    BackendService.lastError = error.response ? error.response : null
    let message = ''
    console.log('axios: http error', BackendService.lastError)
    if (error.response && error.response.status === 422) {
      return Promise.reject(error)
    }
    if (error.response && error.response.status === 401) {
      BackendService.user = null
      store.commit('SET_LOGGED_IN_USER', null)
      router.push('/login')
      message = `Please login`
    }
    if (error.response && error.response.status === 403) {
      message = `Not allowed. You do not have the required permissions.`
    }
    if (error.response && error.response.status === 404) {
      message = `The requested resource was not found`
    }
    if (error.response && error.response.status === 409) {
      message = error.response.data.message
    }
    if (error.response && error.response.status === 500) {
      // if sql error 1451 constraint violation
      if (typeof error.response.data === 'object' && error.response.data['error-info']) {
        if (error.response.data['error-info'][1] === 1451) {
          message = 'Constraint violation - record has related data'
        }
      } else if (typeof error.response.data === 'object' && error.response.data.message) {
        message = error.response.data.message
      } else if (typeof error.response.data === 'string') {
        message = error.response.data
      } else {
        message = 'Internal server error.'
      }
    }
    if (error.response && error.response.status > 500) {
      // might occur: 502 - Bad Gateway, 503 - Service Unavailable
      message = 'Server-side error + ' + error.response.status + ': ' + error.response.data
    }
    if (message) {
      console.log('axios: rejecting Promise with message: ' + message)
      return Promise.reject(new Error(message))
    } else {
      console.log('axios: Possibly network error')
      return Promise.reject(new Error(error.message))
    }
  }
)

// register global components
const requireComponent = require.context('./components', false, /Dis[A-Z]\w+\.(vue|js)$/)
requireComponent.keys().forEach(fileName => {
  const componentConfig = requireComponent(fileName)
  const componentName = upperFirst(camelCase(fileName.replace(/^\.\/(.*)\.\w+$/, '$1')))
  Vue.component(componentName, componentConfig.default || componentConfig)
})

// register global inputs
const requireInputs = require.context('./components/input', false, /Dis[A-Z]\w+\.(vue|js)$/)
requireInputs.keys().forEach(fileName => {
  const inputConfig = requireInputs(fileName)
  const inputName = upperFirst(camelCase(fileName.replace(/^\.\/(.*)\.\w+$/, '$1')))
  Vue.component(inputName, inputConfig.default || inputConfig)
})
