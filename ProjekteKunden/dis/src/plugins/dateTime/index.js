import dateTime from './dateTime'

const DateTimePlugin = {
  install: function (Vue, options) {
    Vue.prototype.$dateTime = dateTime
    Vue.filter('formatTimestamp', function (value) {
      if (value) {
        return dateTime(value).formatForDisplay()
      }
    })
  }
}

export default DateTimePlugin
