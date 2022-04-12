import Mousetrap from '../Mousetrap'
import { emitEventWithStop } from '../utils'

const mousetrap = (function () {
  return {
    bind (el, binding, vnode) {
      vnode = vnode.componentInstance ? vnode.componentInstance : vnode.context
      Mousetrap(el).bind(binding.value.keys, function (e) {
        return emitEventWithStop(e, vnode)
      })
    },
    unbind (el, binding, vnode) {
      Mousetrap(el).unbind(binding.value.keys)
    },
    update (el, binding, vnode) {
      vnode = vnode.componentInstance ? vnode.componentInstance : vnode.context
      if (binding.value.disabled) {
        Mousetrap(el).unbind(binding.value.keys)
      } else {
        Mousetrap(el).bind(binding.value.keys, function (e) {
          return emitEventWithStop(e, vnode)
        })
      }
    }
  }
})()

export default mousetrap
