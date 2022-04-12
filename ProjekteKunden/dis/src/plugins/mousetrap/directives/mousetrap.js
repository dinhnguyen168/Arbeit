import Mousetrap from '../Mousetrap'
import { emitEvent } from '../utils'

const mousetrap = (function () {
  return {
    bind (el, binding, vnode) {
      vnode = vnode.componentInstance ? vnode.componentInstance : vnode.context
      Mousetrap.bind(binding.value.keys, function (e) {
        return emitEvent(e, vnode)
      })
    },
    unbind (el, binding, vnode) {
      Mousetrap.unbind(binding.value.keys)
    },
    update (el, binding, vnode) {
      vnode = vnode.componentInstance ? vnode.componentInstance : vnode.context
      if (binding.value.disabled) {
        Mousetrap.unbind(binding.value.keys)
      } else {
        Mousetrap.bind(binding.value.keys, function (e) {
          return emitEvent(e, vnode)
        })
      }
    }
  }
})()

export default mousetrap
