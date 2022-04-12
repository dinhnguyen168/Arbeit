import dateTime from '../../src/plugins/dateTime/dateTime'

describe('dateTime', () => {
  it('parses `yyyy-MM-dd hh:mm` string and format display string', () => {
    expect(dateTime('2018-05-14 14:00').formatForDisplay()).toMatch('14-May-2018 14:00')
  })
  it('parses `yyyy-MM-dd hh:mm` string as UTC and format display string', () => {
    expect(dateTime('2018-05-14 14:00').asUtc().formatForDisplay()).toMatch('14-May-2018 16:00')
  })
  it('parses local `yyyy-MM-dd hh:mm` string and format to UTC DB string', () => {
    expect(dateTime('2018-05-14 16:00').toUtc().formatForDB()).toMatch('2018-05-14 14:00')
  })
  it('parses month name', () => {
    expect(dateTime('05-Mar-2017 14:00', 'dd-MMM-yyyy HH:mm').formatForDB('2017-03-05 14:00'))
  })
  it('parses date with month name and convert to utc', () => {
    expect(dateTime('14-May-2017 16:00', 'dd-MMM-yyyy HH:mm').toUtc().formatForDB('2017-05-14 14:00'))
  })
})
