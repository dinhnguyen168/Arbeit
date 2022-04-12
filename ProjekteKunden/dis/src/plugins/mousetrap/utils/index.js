export const emitEvent = function (e, vnode) {
  e.preventDefault()
  console.log('MOUSETRAP trigger')
  vnode.$emit('mousetrap', e)
  return false
}

export const emitEventWithStop = function (e, vnode) {
  e.preventDefault()
  e.stopPropagation()
  console.log('MOUSETRAP trigger')
  vnode.$emit('mousetrap', e)
  return false
}
