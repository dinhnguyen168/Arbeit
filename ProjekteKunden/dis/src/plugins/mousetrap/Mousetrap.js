import Mousetrap from 'mousetrap'

Mousetrap.prototype.stopCallback = function (e, element, combo) {
  // do not stop callbacks
  return false
}

export default Mousetrap
