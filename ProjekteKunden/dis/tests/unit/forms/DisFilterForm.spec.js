import { shallowMount } from '@vue/test-utils'
// import { defaultTemplatesState } from '../../helper/store-mock'
import DisFilterForm from '@/components/DisFilterForm.vue'
// import CrudService from '@/services/CrudService'
import FormService from '@/services/FormService'

const mockGetFilterLists = jest.fn().mockImplementation(() => {
  return Promise.resolve({
    data: {
      expedition: [{ value: 1, text: 'JET' }, { value: 2, text: 'GRIND' }],
      site: [{ value: 1, text: '1', expedition_id: 1 }, { value: 2, text: '2', expedition_id: 1 }, { value: 3, text: '1', expedition_id: 2 }]
    }
  })
})
const mockFormService = {
  getFilterLists: mockGetFilterLists
}
const mockRouterReplace = jest.fn().mockImplementation(() => Promise.resolve(true))

const testPropsData = {
  dataModel: 'ProjectHole',
  dataModels: {
    expedition: {
      model: 'ProjectExpedition',
      value: 'id',
      text: 'exp_acronym',
      ref: 'expedition_id'
    },
    site: {
      model: 'ProjectSite',
      value: 'id',
      text: 'site',
      ref: 'site_id',
      require: {
        value: 'expedition',
        as: 'expedition_id'
      }
    }
  },
  fields: [],
  formName: 'hole'
}

const mockLoggedInUser = {
  id: 1,
  username: 'administrator',
  email: 'k.behrends@icdp-online.org',
  token: 'rfH8tejbE_4D2gvmZJLXgtuMc7seorF2',
  roles: ['sa'],
  permissions: ['form-site:edit', 'form-site:view'],
  profile: { 'user_id': 1, 'name': 'Knut', 'public_email': '', 'gravatar_email': '', 'gravatar_id': 'd41d8cd98f00b204e9800998ecf8427e', 'location': '', 'website': '', 'bio': '', 'timezone': null }
}

describe('DisFilterForm component', () => {
  beforeAll(() => {
  })
  beforeEach(() => {
    const app = document.createElement('div')
    app.setAttribute('data-app', true)
    document.body.append(app)
    FormService.mockClear()
    jest.clearAllMocks()
  })
  it('should load filter lists from api', async () => {
    FormService.mockImplementationOnce(() => mockFormService)
    const wrapper = shallowMount(DisFilterForm, {
      mocks: {
        $store: {
          state: {
            loggedInUser: mockLoggedInUser,
            templates: {
              models: [],
              forms: []
            }
          }
        },
        $route: { params: {}, query: {} },
        $router: { replace: mockRouterReplace }
      },
      propsData: Object.assign({}, testPropsData)
    })
    await wrapper.vm.$nextTick()
    expect(mockGetFilterLists).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.listItems.expedition).toEqual([{ value: 1, text: 'JET' }, { value: 2, text: 'GRIND' }])
    expect(wrapper.vm.listItems.site).toEqual([{ value: 1, text: '1', expedition_id: 1 }, { value: 2, text: '2', expedition_id: 1 }, { value: 3, text: '1', expedition_id: 2 }])
    expect(wrapper.vm.selected).toEqual({ expedition: null, site: null })
  })
  it('should set selection list cascadingly', async () => {
    FormService.mockImplementationOnce(() => mockFormService)
    const wrapper = shallowMount(DisFilterForm, {
      mocks: {
        $store: {
          state: {
            loggedInUser: mockLoggedInUser,
            templates: {
              models: [],
              forms: []
            }
          }
        },
        $route: { params: {}, query: {} },
        $router: { replace: mockRouterReplace },
        $setTitle: jest.fn()
      },
      propsData: Object.assign({}, testPropsData)
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.cascadeListItems).toEqual({
      expedition: [{ value: 1, text: 'JET' }, { value: 2, text: 'GRIND' }],
      site: []
    })
    jest.useFakeTimers() // to simulate debounce function
    wrapper.vm.selected.expedition = 1
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(600)
    expect(wrapper.vm.cascadeListItems).toEqual({
      expedition: [{ value: 1, text: 'JET' }, { value: 2, text: 'GRIND' }],
      site: [{ value: 1, text: '1', expedition_id: 1 }, { value: 2, text: '2', expedition_id: 1 }]
    })
    wrapper.vm.selected.site = 1
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(600)
    expect(wrapper.vm.cascadeListItems).toEqual({
      expedition: [{ value: 1, text: 'JET' }, { value: 2, text: 'GRIND' }],
      site: [{ value: 1, text: '1', expedition_id: 1 }, { value: 2, text: '2', expedition_id: 1 }]
    })
    wrapper.vm.selected.expedition = 2
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(600)
    expect(wrapper.vm.cascadeListItems).toEqual({
      expedition: [{ value: 1, text: 'JET' }, { value: 2, text: 'GRIND' }],
      site: [{ value: 3, text: '1', expedition_id: 2 }]
    })
    expect(wrapper.vm.selected).toEqual({ expedition: 2, site: null })
  })
  it('should allow user to filter by value using form', async () => {
    FormService.mockImplementationOnce(() => mockFormService)
    const fields = [
      { 'name': 'core', 'label': 'Core', 'description': '', 'group': 'Core Details', 'order': 0, 'formInput': { 'type': 'text' }, 'searchable': true },
      { 'name': 'combined_id', 'label': 'Combined Id', 'description': '', 'group': 'Core Details', 'order': 1, 'formInput': { 'type': 'text' }, 'searchable': true },
      { 'name': 'analyst', 'label': 'Curator', 'description': '', 'group': 'Core Details', 'order': 2, 'formInput': { 'type': 'select' }, 'searchable': false },
      { 'name': 'igsn', 'label': 'IGSN', 'description': '', 'group': 'Identifiers', 'order': 0, 'formInput': { 'type': 'text' }, 'searchable': true },
      { 'name': 'igsn_ukbgs', 'label': 'UK-BGS ID', 'description': '', 'group': 'Identifiers', 'order': 1, 'formInput': { 'type': 'text' }, 'searchable': true }
    ]
    const mockEmit = jest.fn()
    const wrapper = shallowMount(DisFilterForm, {
      mocks: {
        $store: {
          state: {
            loggedInUser: mockLoggedInUser,
            templates: {
              models: [],
              forms: []
            }
          }
        },
        $route: { params: {}, query: {} },
        $router: { replace: mockRouterReplace },
        $setTitle: jest.fn(),
        $emit: mockEmit
      },
      propsData: Object.assign({}, testPropsData, { fields })
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.fieldsGroups).toEqual(['Core Details', 'Identifiers'])
    expect(wrapper.vm.filterByValues).toBeFalsy()
    // should init filter model with searchable fields only
    expect(Object.keys(wrapper.vm.filterModel)).toEqual(['core', 'combined_id', 'igsn', 'igsn_ukbgs'])
    // const filterByValueSwitch = wrapper.findComponent({ name: 'VSwitch' })
    // expect(filterByValueSwitch.exists()).toBeTruthy()
    wrapper.vm.filterModel.analyst = 'KB'
    await wrapper.vm.$nextTick()
    mockEmit.mockClear()
    wrapper.vm.filterByValues = true
    await wrapper.vm.$nextTick()
    expect(mockEmit).toHaveBeenNthCalledWith(1, 'update:filterByValuesModel', { analyst: 'KB' })
    mockEmit.mockClear()
    wrapper.vm.filterByValues = false
    await wrapper.vm.$nextTick()
    expect(mockEmit).toHaveBeenNthCalledWith(1, 'update:filterByValuesModel', { })
    mockEmit.mockClear()
    wrapper.vm.applyFilterModelValues()
    await wrapper.vm.$nextTick()
    expect(mockEmit).toHaveBeenNthCalledWith(1, 'update:filterByValuesModel', { analyst: 'KB' })
    expect(wrapper.vm.filterByValues).toBeTruthy()
  })
})
