import Vue from 'vue'
import App from './App.vue'
import './style/main.scss'

// Funktion zum zufälligen Sortieren von Arrays
// eslint-disable-next-line no-extend-native
Object.defineProperty(Array.prototype, 'shuffle', {
  value: function () {
    for (var index = 0; index < this.length - 1; index++) {
      var j = Math.floor(Math.random() * (this.length - index) + index)

      if (j !== index) {
        var x = this[index]
        this[index] = this[j]
        this[j] = x
      }
    }
  }
})

Vue.config.productionTip = false

new Vue({
  render: h => h(App)
}).$mount('#gameWrapper')
