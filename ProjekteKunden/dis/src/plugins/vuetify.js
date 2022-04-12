import Vue from 'vue'
import Vuetify from 'vuetify'
import '../style/main.styl'
import VuetifyDialog from 'vuetify-dialog'

Vue.use(Vuetify, {
  theme: {
    primary: '#03A9F4',
    secondary: '#424242',
    accent: '#82B1FF',
    error: '#FF5252',
    info: '#2196F3',
    success: '#4CAF50',
    warning: '#FFC107'
  },
  customProperties: true,
  iconfont: 'md'
})
Vue.use(VuetifyDialog)
