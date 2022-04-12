/**
 * Debouncing enforces that a function not be called again until a certain amount of time has passed without it being called.
 * see http://demo.nimius.net/debounce_throttle/
 *
 * @param {function} fn function to debounce
 * @param {number} wait in milliseconds
 * @param {boolean} immediate whether to execute immediately
 */

export default function (fn, wait = 200, immediate) {
  let timeout

  function debounced (/* ...args */) {
    const args = arguments

    const later = () => {
      timeout = null
      if (!immediate) {
        fn.apply(this, args)
      }
    }

    clearTimeout(timeout)
    if (immediate && !timeout) {
      fn.apply(this, args)
    }
    timeout = setTimeout(later, wait)
  }

  debounced.cancel = () => {
    clearTimeout(timeout)
  }

  return debounced
}
