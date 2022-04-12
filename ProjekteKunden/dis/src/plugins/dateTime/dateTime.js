const defaultDateFormat = 'yyyy-MM-dd HH:mm'
const dateTime = (date, format) => {
  format = format || defaultDateFormat // default format
  if (date) {
    try {
      if (typeof date === 'string' && !date.match(/^(-?(?:[1-9][0-9]*)?[0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])(.[0-9]+)?(Z)?$/g)) {
        date = normalizeDateString(date, format)
      }
      return new DateTime(new Date(date))
    } catch (e) {
      console.warn(e.message)
      return new DateTime('Invalid')
    }
  } else {
    return new DateTime(new Date())
  }
}

const normalizeDateString = function (date, format) {
  if (format.includes('MMM')) {
    // replace Month name with order
    let found = date.match(new RegExp(months.join('|')))
    if (found.length === 0) {
      throw new Error(`normalizeDateString: date does not match format ${date} : ${format}`)
    }
    date = date.replace(found[0], monthOrder(found[0]))
    format = format.replace('MMM', 'MM')
  }
  let parts = date.match(/(\d+)/g)
  let i = 0
  let fmt = {}
  // extract date-part indexes from the format
  format.replace(/(yyyy|dd|MM|HH|mm)/g, function (part) {
    fmt[part] = i++
  })
  if (Object.keys(fmt).length !== 5) {
    throw new Error(`dateTime() date string should have 5 segments like yyyy-MM-dd HH:mm, ${date}`)
  }
  // Firefox accepts 2015-05-02 15:1 as a valid date, make sure that format parts string length equals parts length
  for (let fmtKey in fmt) {
    if (parts[fmt[fmtKey]].length !== fmtKey.length) {
      throw new Error(`${parts[fmt[fmtKey]]} length does not match ${fmtKey} length`)
    }
  }
  return `${parts[fmt['yyyy']]}-${parts[fmt['MM']]}-${parts[fmt['dd']]}T${parts[fmt['HH']]}:${parts[fmt['mm']]}:00${date.endsWith('Z') ? 'Z' : ''}`
}

const padStartZeros = function (stringToPad) {
  stringToPad += '' // convert to string
  if (stringToPad.length > 2) {
    throw new Error(`padStartZeros does not accept strings with more than two characters '${stringToPad}'`)
  }
  return stringToPad.length === 2 ? stringToPad : `0${stringToPad}`
}
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

const monthName = function (monthOrder) {
  if (Number.isInteger(monthOrder) && monthOrder > 0 && monthOrder < 13) {
    return months[monthOrder - 1]
  }
  throw new Error(`monthName: ${monthOrder} is not a valid month order`)
}

const monthOrder = function (monthName) {
  let idx = months.indexOf(monthName) + 1
  if (idx > 0) {
    return idx < 10 ? '0' + idx : idx
  }
  throw new Error(`monthOrder: ${monthName} is not a valid month name`)
}

class DateTime {
  constructor (date) {
    if (date instanceof Date && !isNaN(date)) {
      this._dateObject = date
      this._VALID = true
    } else {
      this._VALID = false
    }
  }

  get dateObject () {
    return this._dateObject
  }

  get isValid () {
    return this._VALID
  }

  asUtc () {
    if (!this._VALID) {
      return 'Invalid Date'
    }
    this._dateObject.setMinutes(this._dateObject.getMinutes() - this._dateObject.getTimezoneOffset())
    return dateTime(this._dateObject)
    // return dateTime(dateString + 'Z')
  }

  toUtc () {
    if (!this._VALID) {
      return 'Invalid Date'
    }
    this._dateObject.setMinutes(this._dateObject.getMinutes() + this._dateObject.getTimezoneOffset())
    return dateTime(this._dateObject)
  }

  formatForDB () {
    if (!this._VALID) {
      return 'Invalid Date'
    }
    return `${this._dateObject.getFullYear()}-${padStartZeros(this._dateObject.getMonth() + 1)}-${padStartZeros(this._dateObject.getDate())} ${padStartZeros(this._dateObject.getHours())}:${padStartZeros(this._dateObject.getMinutes())}`
  }

  formatForDisplay () {
    if (!this._VALID) {
      return 'Invalid Date'
    }
    return `${padStartZeros(this._dateObject.getDate())}-${monthName(this._dateObject.getMonth() + 1)}-${this._dateObject.getFullYear()} ${padStartZeros(this._dateObject.getHours())}:${padStartZeros(this._dateObject.getMinutes())}`
  }

  formatForInput () {
    if (!this._VALID) {
      return 'Invalid Date'
    }
    return `${this._dateObject.getFullYear()}-${padStartZeros(this._dateObject.getMonth() + 1)}-${padStartZeros(this._dateObject.getDate())}T${padStartZeros(this._dateObject.getHours())}:${padStartZeros(this._dateObject.getMinutes())}`
  }
}

export default dateTime
