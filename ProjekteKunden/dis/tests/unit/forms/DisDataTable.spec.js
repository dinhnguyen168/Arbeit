import { mount } from '@vue/test-utils'
import DisDataTable from '@/components/DisDataTable.vue'
import FormService from '@/services/FormService'

const mockFormGetList = jest.fn()
const mockFormService = {
  getList: mockFormGetList
}

const testPropsData = {
  formName: 'hole',
  dataModel: 'ProjectHole',
  fields: [
    { 'label': '#', 'name': 'id', 'order': -1 },
    { 'name': 'combined_id', 'label': 'Combined Id ', 'order': 0, 'formInput': { 'type': 'text' }, 'searchable': true },
    { 'name': 'site', 'label': 'Site Number', 'order': 1, 'formInput': { 'type': 'text' }, 'searchable': true },
    { 'name': 'name', 'label': 'Name of Site', 'order': 2, 'formInput': { 'type': 'text' }, 'searchable': true },
    { 'name': 'date_start', 'label': 'Start Date', 'order': 3, 'formInput': { 'type': 'datetime' }, 'searchable': true }
  ],
  filterDataModels: { 'expedition': { 'model': 'ProjectExpedition', 'value': 'id', 'text': 'exp_acronym', 'ref': 'expedition_id' } },
  filterByValuesModel: {},
  reports: []
}

describe('DisDataTable component', () => {
  beforeAll(() => {
  })
  beforeEach(() => {
    const app = document.createElement('div')
    app.setAttribute('data-app', true)
    document.body.append(app)
    mockFormGetList.mockReset()
    FormService.mockReset()
    jest.useRealTimers()
  })
  it('refreshes items and paginates through items and pages', async () => {
    jest.useFakeTimers()
    mockFormGetList.mockImplementation((queryParams) => {
      const items = [
        { 'id': ((queryParams.page - 1) * 5) + 1, 'coordinate_system': 'WGS84', 'site_id': 5, 'hole': 'A', 'combined_id': '5065_1_A', 'latitude_dec': 52.9, 'longitude_dec': -2.66, 'ground_level': 65, 'elevation_rig': 0, 'direction': null, 'inclination': null, 'start_date': '2019-12-29 12:00:00', 'end_date': null, 'comments': 'Technical Drilling before Conductor Casing', 'core_depth_ccsf': null, 'core_depth_csf': null, 'drilling_depth_dsf': 37, 'drilling_depth_drf': 37, 'comments_2': null, 'igsn': 'ICDP5065EH10001', 'ukbgs_hole_id': 'UK.BGS.SJ53SE52', 'ukbgs_natlgrid': 'SJ53SE', 'methods_in_hole': [], 'comments_3': ['BGS Number manually entered'], 'expedition_id': 4, 'program_id': 1, 'archive_files': { 'filter': { 'expedition': 4, 'site': 5, 'hole': 1 }, 'files': [] } },
        { 'id': ((queryParams.page - 1) * 5) + 2, 'coordinate_system': 'WGS84', 'site_id': 5, 'hole': 'B', 'combined_id': '5065_1_B', 'latitude_dec': 52.9, 'longitude_dec': -2.66, 'ground_level': 65, 'elevation_rig': 3.5, 'direction': null, 'inclination': null, 'start_date': '2020-11-02 07:00:00', 'end_date': null, 'comments': 'Technical Drilling before Conductor Casing', 'core_depth_ccsf': null, 'core_depth_csf': null, 'drilling_depth_dsf': 0.5, 'drilling_depth_drf': 4, 'comments_2': null, 'igsn': 'ICDP5065EH20001', 'ukbgs_hole_id': 'UK.BGS.SJ53SE52', 'ukbgs_natlgrid': 'SJ53SE', 'methods_in_hole': [], 'comments_3': ['BGS Number manually entered'], 'expedition_id': 4, 'program_id': 1, 'archive_files': { 'filter': { 'expedition': 4, 'site': 5, 'hole': 2 }, 'files': [] } },
        { 'id': ((queryParams.page - 1) * 5) + 3, 'coordinate_system': 'WGS84', 'site_id': 5, 'hole': 'B', 'combined_id': '5065_1_B', 'latitude_dec': 52.9, 'longitude_dec': -2.66, 'ground_level': 65, 'elevation_rig': 3.5, 'direction': null, 'inclination': null, 'start_date': '2020-11-02 07:00:00', 'end_date': null, 'comments': 'Technical Drilling before Conductor Casing', 'core_depth_ccsf': null, 'core_depth_csf': null, 'drilling_depth_dsf': 0.5, 'drilling_depth_drf': 4, 'comments_2': null, 'igsn': 'ICDP5065EH20001', 'ukbgs_hole_id': 'UK.BGS.SJ53SE52', 'ukbgs_natlgrid': 'SJ53SE', 'methods_in_hole': [], 'comments_3': ['BGS Number manually entered'], 'expedition_id': 4, 'program_id': 1, 'archive_files': { 'filter': { 'expedition': 4, 'site': 5, 'hole': 2 }, 'files': [] } },
        { 'id': ((queryParams.page - 1) * 5) + 4, 'coordinate_system': 'WGS84', 'site_id': 5, 'hole': 'B', 'combined_id': '5065_1_B', 'latitude_dec': 52.9, 'longitude_dec': -2.66, 'ground_level': 65, 'elevation_rig': 3.5, 'direction': null, 'inclination': null, 'start_date': '2020-11-02 07:00:00', 'end_date': null, 'comments': 'Technical Drilling before Conductor Casing', 'core_depth_ccsf': null, 'core_depth_csf': null, 'drilling_depth_dsf': 0.5, 'drilling_depth_drf': 4, 'comments_2': null, 'igsn': 'ICDP5065EH20001', 'ukbgs_hole_id': 'UK.BGS.SJ53SE52', 'ukbgs_natlgrid': 'SJ53SE', 'methods_in_hole': [], 'comments_3': ['BGS Number manually entered'], 'expedition_id': 4, 'program_id': 1, 'archive_files': { 'filter': { 'expedition': 4, 'site': 5, 'hole': 2 }, 'files': [] } },
        { 'id': ((queryParams.page - 1) * 5) + 5, 'coordinate_system': 'WGS84', 'site_id': 5, 'hole': 'B', 'combined_id': '5065_1_B', 'latitude_dec': 52.9, 'longitude_dec': -2.66, 'ground_level': 65, 'elevation_rig': 3.5, 'direction': null, 'inclination': null, 'start_date': '2020-11-02 07:00:00', 'end_date': null, 'comments': 'Technical Drilling before Conductor Casing', 'core_depth_ccsf': null, 'core_depth_csf': null, 'drilling_depth_dsf': 0.5, 'drilling_depth_drf': 4, 'comments_2': null, 'igsn': 'ICDP5065EH20001', 'ukbgs_hole_id': 'UK.BGS.SJ53SE52', 'ukbgs_natlgrid': 'SJ53SE', 'methods_in_hole': [], 'comments_3': ['BGS Number manually entered'], 'expedition_id': 4, 'program_id': 1, 'archive_files': { 'filter': { 'expedition': 4, 'site': 5, 'hole': 2 }, 'files': [] } }
      ]
      return Promise.resolve({
        items,
        '_links': { 'self': { 'href': 'http://localhost:8000/api/v1/form?name=hole&per-page=5&page=1&sort=combined_id&filter%5Bexpedition_id%5D=4&filter%5Bsite_id%5D=5' } },
        '_meta': { 'totalCount': queryParams.page * 5 + 3, 'pageCount': queryParams.page + 1, 'currentPage': queryParams.page, 'perPage': 5 }
      })
    })
    FormService.mockImplementation(() => mockFormService)
    const wrapper = mount(DisDataTable, {
      mocks: {
        $store: {
          state: {
            loggedInUser: {
              id: 1,
              username: 'administrator',
              email: 'k.behrends@icdp-online.org',
              token: 'rfH8tejbE_4D2gvmZJLXgtuMc7seorF2',
              roles: ['sa'],
              permissions: ['form-site:edit', 'form-site:view'],
              profile: { 'user_id': 1, 'name': 'Knut', 'public_email': '', 'gravatar_email': '', 'gravatar_id': 'd41d8cd98f00b204e9800998ecf8427e', 'location': '', 'website': '', 'bio': '', 'timezone': null }
            },
            templates: {
              models: [],
              forms: []
            }
          }
        },
        $route: { params: {}, query: {} },
        $dialog: { notify: {} }
      },
      propsData: testPropsData
    })
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()
    expect(mockFormGetList).toHaveBeenNthCalledWith(1, { 'page': 1, 'per-page': 5, 'sort': 'id' }, {})
    // mockFormGetList.mockClear()
    // await wrapper.vm.selectFirstRecord()
    // // jest.advanceTimersByTime(300)
    // await wrapper.vm.$nextTick()
    // expect(mockFormGetList).toHaveBeenCalledTimes(0)
    // expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 1 }))

    mockFormGetList.mockClear()
    await wrapper.vm.selectNextRecord()
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    expect(mockFormGetList).toHaveBeenCalledTimes(0)
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 1 }))

    wrapper.vm.internalValue = Object.assign({}, wrapper.vm.internalValue, { id: 111 }) // some value that does not exist in items
    await wrapper.vm.$nextTick()
    mockFormGetList.mockClear()
    await wrapper.vm.selectNextRecord()
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    expect(mockFormGetList).toHaveBeenCalledTimes(0)
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 1 }))

    mockFormGetList.mockClear()
    await wrapper.vm.selectNextRecord() // 2
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.selectNextRecord() // 3
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.selectNextRecord() // 4
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.selectNextRecord() // 5
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    expect(mockFormGetList).toHaveBeenCalledTimes(0)
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 5 }))

    mockFormGetList.mockClear()
    await wrapper.vm.selectNextRecord() // next page
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()
    expect(mockFormGetList).toHaveBeenNthCalledWith(1, { 'page': 2, 'per-page': 5, 'sort': 'id' }, {})
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 6 }))
    expect(wrapper.vm.pagination.page).toEqual(2)

    mockFormGetList.mockClear()
    await wrapper.vm.selectPreviousRecord() // previous page
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()
    expect(mockFormGetList).toHaveBeenNthCalledWith(1, { 'page': 1, 'per-page': 5, 'sort': 'id' }, {})
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 5 }))

    mockFormGetList.mockClear()
    await wrapper.vm.selectPreviousRecord() // previous page
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()
    expect(mockFormGetList).toHaveBeenCalledTimes(0)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 4 }))

    wrapper.vm.internalValue = Object.assign({}, wrapper.vm.internalValue, { id: 111 }) // some value that does not exist in items
    await wrapper.vm.$nextTick()
    mockFormGetList.mockClear()
    await wrapper.vm.selectPreviousRecord() // previous page
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()
    expect(mockFormGetList).toHaveBeenCalledTimes(0)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 5 }))

    wrapper.vm.internalValue = null // some value that does not exist in items
    await wrapper.vm.$nextTick()
    mockFormGetList.mockClear()
    await wrapper.vm.selectPreviousRecord() // previous page will trigger select last record
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()
    expect(mockFormGetList).toHaveBeenNthCalledWith(1, { 'page': 2, 'per-page': 5, 'sort': 'id' }, {})
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.internalValue).toEqual(expect.objectContaining({ id: 10 }))
  })
})
