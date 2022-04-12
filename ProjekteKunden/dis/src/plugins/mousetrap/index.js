import mousetrap from './directives/mousetrap'
import elementMousetrap from './directives/el-mousetrap'

const MousetrapPlugin = {
  install: function (Vue, options) {
    Vue.directive('mousetrap', mousetrap)
    Vue.directive('el-mousetrap', elementMousetrap)
  }
}

export default MousetrapPlugin
