import Vue from 'vue'
import './plugins/vuetify'
import App from './App.vue'
import router from './router'
import store from './store'
import './bootstrap'
import './registerServiceWorker'
import './util/modernizr-build'

try {
  require('./assets/scss/custom.scss')
} catch (e) {
  console.log('if you want to use global custom scss check the file "src/assets/custom-example.scss"')
}

Vue.config.productionTip = false

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')
