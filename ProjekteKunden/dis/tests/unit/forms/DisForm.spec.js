import { shallowMount, mount } from '@vue/test-utils'
// import { defaultTemplatesState } from '../../helper/store-mock'
import DisForm from '@/components/DisForm.vue'
import CrudService from '@/services/CrudService'
import FormService from '@/services/FormService'

const mockCrudeGetReports = jest.fn().mockImplementation(() => {
  return Promise.resolve({
    'single': [
      { 'name': 'Details', 'title': 'Details of record', 'model': '.*', 'type': 'report', 'singleRecord': true, 'confirmationMessage': false }
    ],
    'multiple': [
      { 'name': 'ListAllCsv', 'title': 'Export full records as CSV file', 'model': '.*', 'type': 'export', 'singleRecord': false, 'confirmationMessage': false },
      { 'name': 'ListCsv', 'title': 'Export records as CSV file', 'model': '.*', 'type': 'export', 'singleRecord': false, 'confirmationMessage': false },
      { 'name': 'List', 'title': 'List of records', 'model': '.*', 'type': 'report', 'singleRecord': false, 'confirmationMessage': false }
    ]
  })
})
const mockCrudService = {
  getReports: mockCrudeGetReports
}

const routerReplaceMock = jest.fn().mockImplementation(() => Promise.resolve(true))

const mockFormGetDefaults = jest.fn()
const mockFormPost = jest.fn()
const mockFormPut = jest.fn()
const mockFormGetDuplicate = jest.fn()
const storeDispatchMock = jest.fn()
const mockFormService = {
  getDefaults: mockFormGetDefaults,
  post: mockFormPost,
  put: mockFormPut,
  getDuplicate: mockFormGetDuplicate
}

const calculatedFields = {}
const simpleFields = [
  { 'name': 'combined_id', 'label': 'Combined Id', 'description': '', 'group': 'Site Details', 'order': 0, 'formInput': { 'type': 'text' } },
  { 'name': 'site', 'label': 'Site Number', 'description': '', 'group': 'Site Details', 'order': 1, 'formInput': { 'type': 'text' } }
]
const requiredFilters = [{ 'value': 'expedition', 'as': 'expedition_id' }]
const filterDataModels = { 'expedition': { 'model': 'ProjectExpedition', 'value': 'id', 'text': 'exp_acronym', 'ref': 'expedition_id' } }

const mockDialogNotifyWarning = jest.fn()
const mockDialogWarning = jest.fn()
const mockDialogMessageSuccess = jest.fn()
const refreshItemsMock = jest.fn()
const DisDataTableStub = {
  render: h => h('div'),
  data () {
    return {
      currentRecordIndex: 0,
      pagination: {
        totalItems: 10
      }
    }
  },
  methods: {
    refreshItems: refreshItemsMock
  }
}
// const setFilterFromSelectedItem = jest.fn()
const DisFilterFormStub = {
  render: h => h('div'),
  data () {
    return {}
  },
  methods: {
    // setFilterFromSelectedItemId: setFilterFromSelectedItem,
    blurInputs: jest.fn(),
    focusInputs: jest.fn()
  }
}
describe('DisForm component', () => {
  beforeAll(() => {
  })
  beforeEach(() => {
    const app = document.createElement('div')
    app.setAttribute('data-app', true)
    document.body.append(app)
    mockDialogNotifyWarning.mockReset()
    // setFilterFromSelectedItem.mockReset()
    refreshItemsMock.mockReset()
    CrudService.mockClear()
    mockCrudeGetReports.mockClear()
    mockDialogWarning.mockClear()
    mockFormPost.mockClear()
    mockFormPost.mockClear()
    mockDialogMessageSuccess.mockClear()
    mockFormGetDuplicate.mockClear()
  })
  it('disables button based on user permissions and form state', async () => {
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = shallowMount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub
      },
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
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          }
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields
      }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.userIsDeveloper).toBeTruthy()
    expect(wrapper.vm.userCanViewForm).toBeTruthy()
    expect(wrapper.vm.userCanEditForm).toBeTruthy()
    wrapper.vm.$store.state.loggedInUser.roles = ['developer']
    expect(wrapper.vm.userIsDeveloper).toBeTruthy()
    wrapper.vm.$store.state.loggedInUser.roles = []
    wrapper.vm.$store.state.loggedInUser.permissions = ['form-*:view']
    expect(wrapper.vm.userCanViewForm).toBeTruthy()
    expect(wrapper.vm.userCanEditForm).toBeFalsy()
    wrapper.vm.$store.state.loggedInUser.permissions = ['form-*:edit']
    expect(wrapper.vm.userCanViewForm).toBeFalsy()
    expect(wrapper.vm.userCanEditForm).toBeTruthy()
    wrapper.vm.$store.state.loggedInUser.permissions = []
    expect(wrapper.vm.userCanViewForm).toBeFalsy()
    expect(wrapper.vm.userCanEditForm).toBeFalsy()
    wrapper.vm.$store.state.loggedInUser.permissions = ['form-site:view']
    expect(wrapper.vm.userCanViewForm).toBeTruthy()
    expect(wrapper.vm.userCanEditForm).toBeFalsy()
    wrapper.vm.$store.state.loggedInUser.permissions = ['form-site:edit']
    expect(wrapper.vm.userCanViewForm).toBeFalsy()
    expect(wrapper.vm.userCanEditForm).toBeTruthy()
    // form state test
    expect(wrapper.vm.isTableNavigationDisabled).toBeFalsy()
    expect(wrapper.vm.isSaveButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isEditButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isCancelButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isDeleteButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isNewButtonDisabled).toBeFalsy()
    // replace watcher with mock function to get a better coverage report
    // we do not need to cover a code that we are not testing here
    const onSelectedItemChange = jest.fn()
    wrapper.vm.onSelectedItemChange = onSelectedItemChange
    wrapper.vm.selectedItem = { id: 1, combined_id: '123_1', site: '1' }
    await wrapper.vm.$nextTick()
    expect(onSelectedItemChange).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.isTableNavigationDisabled).toBeFalsy()
    expect(wrapper.vm.isSaveButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isEditButtonDisabled).toBeFalsy()
    expect(wrapper.vm.isCancelButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isDeleteButtonDisabled).toBeFalsy()
    expect(wrapper.vm.isNewButtonDisabled).toBeFalsy()
    wrapper.vm.formScenario = 'edit'
    expect(onSelectedItemChange).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.isTableNavigationDisabled).toBeTruthy()
    expect(wrapper.vm.isSaveButtonDisabled).toBeFalsy()
    expect(wrapper.vm.isEditButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isCancelButtonDisabled).toBeFalsy()
    expect(wrapper.vm.isDeleteButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isNewButtonDisabled).toBeTruthy()
    expect(wrapper.vm.isRequiredFilterSet).toBeFalsy()
    wrapper.vm.formScenario = 'create'
    expect(wrapper.vm.isSaveButtonDisabled).toBeTruthy()
    wrapper.vm.filterValue = { expedition: 1 }
    expect(wrapper.vm.isRequiredFilterSet).toBeTruthy()
    expect(wrapper.vm.isSaveButtonDisabled).toBeFalsy()
  })
  it('watches filterByValue and selectedItem', async () => {
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = shallowMount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub
      },
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
          },
          dispatch: storeDispatchMock
        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          }
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields
      }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isRequiredFilterSet).toBeFalsy()
    wrapper.vm.selectedItem = { id: 12, combined_id: '123_1', site: '1' }
    await wrapper.vm.$nextTick()
    // expect(setFilterFromSelectedItem).toHaveBeenCalledTimes(1)
    expect(routerReplaceMock).toHaveBeenNthCalledWith(1, expect.objectContaining({
      params: { id: 12 }
    }))
    const clearFormMock = jest.fn()
    wrapper.vm.clearForm = clearFormMock
    wrapper.vm.selectedItem = null
    await wrapper.vm.$nextTick()
    // expect(setFilterFromSelectedItem).toHaveBeenCalledTimes(1)
    expect(clearFormMock).toHaveBeenCalledTimes(1)
    expect(routerReplaceMock).toHaveBeenNthCalledWith(2, expect.objectContaining({
      params: { id: null }
    }))
    // test watching filterByValue
    wrapper.vm.filterByValuesModel = { combined_id: '123_1' }
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    expect(refreshItemsMock).toHaveBeenCalledTimes(1)
  })
  it('gets report of current model from api', async () => {
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = shallowMount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub
      },
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
          },
          dispatch: storeDispatchMock

        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          }
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields
      }
    })
    await wrapper.vm.$nextTick()
    expect(mockCrudeGetReports).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.reports.single).toEqual([
      expect.objectContaining({
        name: 'Details'
      })
    ])
    expect(wrapper.vm.reports.multiple).toEqual([
      expect.objectContaining({ name: 'ListAllCsv' }),
      expect.objectContaining({ name: 'ListCsv' }),
      expect.objectContaining({ name: 'List' })
    ])
  })
  it('shows warning if loading reports failed', async () => {
    mockCrudeGetReports.mockImplementationOnce(() => {
      throw new Error()
    })
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = shallowMount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub
      },
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
          },
          dispatch: storeDispatchMock
        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          }
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields
      }
    })
    await wrapper.vm.$nextTick()
    expect(mockCrudeGetReports).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenCalledWith('unable to get reports')
  })
  it('gets defaults on new button click', async () => {
    // const mockFocusOnForm = jest.spyOn(DisForm.methods, 'focusOnForm')
    // const mockClearForm = jest.spyOn(DisForm.methods, 'clearForm')
    CrudService.mockImplementation(() => mockCrudService)
    mockFormGetDefaults
      .mockImplementationOnce(() => {
        const customError = new Error()
        customError.response = {
          data: {
            message: 'error message from response data'
          }
        }
        return Promise.reject(customError)
      })
      .mockImplementationOnce(() => {
        return Promise.reject(new Error('error message from error object'))
      })
      .mockImplementation(() => {
        return Promise.resolve({
          data: {
            site: 3
          }
        })
      })
    FormService.mockImplementation(() => mockFormService)
    const mockConfirmation = jest.fn()
      .mockReturnValueOnce(Promise.resolve(false))
      .mockReturnValue(Promise.resolve(true))
    const wrapper = mount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub
      },
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
          },
          dispatch: storeDispatchMock

        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          },
          warning: mockDialogWarning
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields,
        confirmations: [
          { on: 'new', promise: mockConfirmation }
        ]
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.detailSectionExpand = 0 // to show form
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isNewButtonDisabled).toBeFalsy()
    expect(wrapper.vm.userCanEditForm).toBeTruthy()
    const createNewButton = wrapper.find('.c-dis-form__btn-create')
    expect(createNewButton.exists()).toBeTruthy()

    // first click shows warning that required filter are not set
    createNewButton.trigger('click')
    await wrapper.vm.$nextTick()
    setTimeout(() => {
      expect(mockDialogWarning).toHaveBeenNthCalledWith(1, {
        title: 'Please fill out the filter bar',
        text: 'Please specify items in the filter bar. It is located at the top of the page. Select items from left to right. For editing, all pulldowns must be specified.'
      })
    }, 200)

    // it asks for confirmation if available
    wrapper.vm.filterValue = { expedition: 1 }
    createNewButton.trigger('click')
    await wrapper.vm.$nextTick()
    // first confirmation call returns false
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(mockFormGetDefaults).toHaveBeenCalledTimes(0)
    // expect(mockClearForm).toHaveBeenCalledTimes(0)
    mockConfirmation.mockClear()

    // second confirmation call returns true
    createNewButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    // first api call throw error with message in response data
    expect(mockFormGetDefaults).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenNthCalledWith(1, 'error message from response data', { 'timeout': 30000 })
    mockDialogNotifyWarning.mockClear()
    mockConfirmation.mockClear()
    mockFormGetDefaults.mockClear()

    // second api call throw error with message in error object
    createNewButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(mockDialogNotifyWarning).toHaveBeenNthCalledWith(1, 'error message from error object', { 'timeout': 30000 })
    mockDialogNotifyWarning.mockClear()
    mockConfirmation.mockClear()
    mockFormGetDefaults.mockClear()

    // third api call is a success
    createNewButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(mockFormGetDefaults).toHaveBeenCalledTimes(1)
    // expect(mockClearForm).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formModel).toEqual(expect.objectContaining({
      site: 3
    }))
    expect(wrapper.vm.formScenario).toEqual('create')
    // expect(mockFocusOnForm).toHaveBeenCalledTimes(1)

    // mockFocusOnForm.mockReset()
    // mockClearForm.mockReset()
  })
  it('submits form on create button click', async () => {
    // const mockClearForm = jest.spyOn(DisForm.methods, 'clearForm')
    CrudService.mockImplementation(() => mockCrudService)
    mockFormPost
      .mockImplementationOnce(() => {
        const customError = new Error()
        customError.response = {
          data: {
            message: 'error message from response data'
          }
        }
        return Promise.reject(customError)
      })
      .mockImplementationOnce(() => {
        return Promise.reject(new Error('error message from error object'))
      })
      .mockImplementationOnce(() => {
        const customError = new Error()
        customError.response = {
          status: 422,
          data: [
            { field: 'site', errors: ['site value is not valid'] }
          ]
        }
        return Promise.reject(customError)
      })
      .mockImplementation(() => {
        return Promise.resolve({
          status: 201,
          data: {
            id: 1,
            combined_id: '5054_3',
            site: 3
          }
        })
      })
    FormService.mockImplementation(() => mockFormService)
    const mockConfirmation = jest.fn()
      .mockReturnValueOnce(Promise.resolve(false))
      .mockReturnValue(Promise.resolve(true))
    const VFormStub = {
      render: () => {},
      methods: {
        validate: jest.fn()
          .mockReturnValueOnce(false)
          .mockReturnValue(true),
        reset: jest.fn()
      }
    }
    const wrapper = mount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub,
        'v-form': VFormStub
      },
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
          },
          dispatch: storeDispatchMock

        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          },
          message: {
            success: mockDialogMessageSuccess
          },
          warning: mockDialogWarning
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields,
        confirmations: [
          { on: 'submit', promise: mockConfirmation }
        ]
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.detailSectionExpand = 0 // to show form
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isNewButtonDisabled).toBeFalsy()
    expect(wrapper.vm.userCanEditForm).toBeTruthy()
    wrapper.vm.filterValue = { expedition: 1 }
    wrapper.vm.formScenario = 'create'
    expect(wrapper.vm.isSaveButtonDisabled).toBeFalsy()
    await wrapper.vm.$nextTick()
    const saveButton = wrapper.find('.c-dis-form__btn-save')
    expect(saveButton.exists()).toBeTruthy()
    // mockClearForm.mockClear()

    // first click form validation fails
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(0)
    mockConfirmation.mockClear()

    // second click validation success
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    // first confirmation returns false
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    expect(mockFormPost).toHaveBeenCalledTimes(0)
    mockConfirmation.mockClear()
    mockFormPost.mockClear()

    // confirmation returns true on second try (third click)
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    // first api call fails with error in response data
    expect(mockFormPost).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenNthCalledWith(1, 'error message from response data', { 'timeout': 30000 })
    mockConfirmation.mockClear()
    mockFormPost.mockClear()
    mockDialogNotifyWarning.mockClear()

    // second api call fails with error message in error object
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(mockFormPost).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenNthCalledWith(1, 'error message from error object', { 'timeout': 30000 })
    mockConfirmation.mockClear()
    mockFormPost.mockClear()
    mockDialogNotifyWarning.mockClear()

    // third api call fails with validation error
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(mockFormPost).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.serverValidationErrors).toEqual([{ field: 'site', errors: ['site value is not valid'] }])
    mockConfirmation.mockClear()
    mockFormPost.mockClear()
    mockDialogNotifyWarning.mockClear()

    // fourth api call works
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockConfirmation).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(mockFormPost).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.selectedItem).toEqual({ id: 1, combined_id: '5054_3', site: 3 })
    await wrapper.vm.$nextTick()
    // expect(mockClearForm).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formModel).toEqual({ id: 1, combined_id: '5054_3', site: 3 })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formScenario).toEqual('view')
    expect(DisDataTableStub.methods.refreshItems).toHaveBeenCalledTimes(1)
    expect(mockDialogMessageSuccess).toHaveBeenNthCalledWith(1, 'Record was created successfully')
    mockConfirmation.mockClear()
    mockFormPost.mockClear()
    mockDialogNotifyWarning.mockClear()
    // mockClearForm.mockReset()
  })
  it('submits form on save button click', async () => {
    // const mockFocusOnForm = jest.spyOn(DisForm.methods, 'focusOnForm')
    // const mockClearForm = jest.spyOn(DisForm.methods, 'clearForm')
    CrudService.mockImplementation(() => mockCrudService)
    mockFormPut
      .mockImplementationOnce(() => {
        const customError = new Error()
        customError.response = {
          data: {
            message: 'error message from response data'
          }
        }
        return Promise.reject(customError)
      })
      .mockImplementationOnce(() => {
        return Promise.reject(new Error('error message from error object'))
      })
      .mockImplementationOnce(() => {
        const customError = new Error()
        customError.response = {
          status: 422,
          data: [
            { field: 'site', errors: ['site value is not valid'] }
          ]
        }
        return Promise.reject(customError)
      })
      .mockImplementation(() => {
        return Promise.resolve({
          status: 200,
          data: {
            id: 1,
            combined_id: '5054_3',
            site: 3
          }
        })
      })
    FormService.mockImplementation(() => mockFormService)
    const wrapper = mount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub
      },
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
          },
          dispatch: storeDispatchMock
        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          },
          message: {
            success: mockDialogMessageSuccess
          },
          warning: mockDialogWarning
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.detailSectionExpand = 0 // to show form
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isNewButtonDisabled).toBeFalsy()
    expect(wrapper.vm.userCanEditForm).toBeTruthy()
    expect(wrapper.vm.isSaveButtonDisabled).toBeTruthy()
    wrapper.vm.filterValue = { expedition: 1 }
    wrapper.vm.selectedItem = { id: 1, combined_id: '5054_3', site: 3 }
    await wrapper.vm.$nextTick()
    // mockFocusOnForm.mockClear()
    const editButton = wrapper.find('.c-dis-form__btn-edit')
    expect(editButton.exists).toBeTruthy()
    editButton.trigger('click')
    await wrapper.vm.$nextTick()
    // expect(mockFocusOnForm).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.formScenario).toEqual('edit')
    expect(wrapper.vm.formModel).toEqual({ id: 1, combined_id: '5054_3', site: 3 })
    const saveButton = wrapper.find('.c-dis-form__btn-save')
    expect(saveButton.exists()).toBeTruthy()

    // first api call fails with error in response data
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockFormPut).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenNthCalledWith(1, 'error message from response data', { 'timeout': 30000 })
    mockFormPut.mockClear()
    mockDialogNotifyWarning.mockClear()

    // second api call fails with error message in error object
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    // first api call fails with error in response data
    expect(mockFormPut).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenNthCalledWith(1, 'error message from error object', { 'timeout': 30000 })
    mockFormPut.mockClear()
    mockDialogNotifyWarning.mockClear()

    // third api call fails with validation error
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockFormPut).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.serverValidationErrors).toEqual([{ field: 'site', errors: ['site value is not valid'] }])
    mockFormPut.mockClear()
    mockDialogNotifyWarning.mockClear()
    // mockFocusOnForm.mockClear()
    // mockClearForm.mockClear()

    // fourth api call works with status 200
    saveButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockFormPut).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.selectedItem).toEqual({ id: 1, combined_id: '5054_3', site: 3 })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formModel).toEqual({ id: 1, combined_id: '5054_3', site: 3 })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formScenario).toEqual('view')
    await wrapper.vm.$nextTick()
    expect(DisDataTableStub.methods.refreshItems).toHaveBeenCalledTimes(1)
    expect(mockDialogMessageSuccess).toHaveBeenNthCalledWith(1, 'Record was updated successfully')
    // expect(mockClearForm).toHaveBeenCalledTimes(1)
    mockFormPut.mockClear()
    mockDialogNotifyWarning.mockClear()
  })
  it('duplicates an item', async () => {
    const mockFocusOnForm = jest.spyOn(DisForm.methods, 'focusOnForm')
    const mockClearForm = jest.spyOn(DisForm.methods, 'clearForm')
    CrudService.mockImplementation(() => mockCrudService)
    mockFormGetDuplicate
      .mockImplementationOnce(() => {
        return Promise.reject(new Error('error message from error object'))
      })
      .mockImplementation(() => {
        return Promise.resolve({
          status: 200,
          data: {
            id: 1,
            combined_id: '5054_3',
            site: 4
          }
        })
      })
    FormService.mockImplementation(() => mockFormService)
    const wrapper = mount(DisForm, {
      stubs: {
        'dis-data-table': DisDataTableStub,
        'dis-filter-form': DisFilterFormStub
      },
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
          },
          dispatch: storeDispatchMock
        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          },
          message: {
            success: mockDialogMessageSuccess
          },
          warning: mockDialogWarning
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.detailSectionExpand = 0 // to show form
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isNewButtonDisabled).toBeFalsy()
    expect(wrapper.vm.userCanEditForm).toBeTruthy()
    expect(wrapper.vm.isSaveButtonDisabled).toBeTruthy()
    wrapper.vm.selectedItem = { id: 1, combined_id: '5054_3', site: 3 }
    await wrapper.vm.$nextTick()
    mockFocusOnForm.mockClear()
    mockClearForm.mockClear()
    const duplicateButton = wrapper.find('.c-dis-form__btn-duplicate')
    expect(duplicateButton.exists).toBeTruthy()
    duplicateButton.trigger('click')
    await wrapper.vm.$nextTick()
    setTimeout(() => {
      expect(mockDialogWarning).toHaveBeenNthCalledWith(1, {
        title: 'Please fill out the filter bar',
        text: 'Please specify items in the filter bar. It is located at the top of the page. Select items from left to right. For editing, all pulldowns must be specified.'
      })
    }, 200)

    wrapper.vm.filterValue = { expedition: 1 }
    // first api call fails with error
    duplicateButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockFormGetDuplicate).toHaveBeenCalledTimes(1)
    expect(mockDialogNotifyWarning).toHaveBeenNthCalledWith(1, 'error message from error object', { 'timeout': 30000 })
    mockFormGetDuplicate.mockClear()
    mockDialogNotifyWarning.mockClear()

    // second api call works
    duplicateButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(mockFormGetDuplicate).toHaveBeenCalledTimes(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formScenario).toEqual('create')
    expect(wrapper.vm.formModel).toEqual({ id: 1, combined_id: '5054_3', site: 4 })
    expect(mockFocusOnForm).toHaveBeenCalledTimes(1)
    expect(mockClearForm).toHaveBeenCalledTimes(1)
    mockFocusOnForm.mockClear()
    mockClearForm.mockClear()

    // test that cancel resets form to old value
    const cancelButton = wrapper.find('.c-dis-form__btn-cancel')
    expect(cancelButton.exists()).toBeTruthy()
    cancelButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formScenario).toEqual('view')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formModel).toEqual({ id: 1, combined_id: '5054_3', site: 3 })
    mockClearForm.mockClear()
    wrapper.vm.$destroy()
  })
  it('should execute actiond with keyboard shortcuts', async () => {
    CrudService.mockImplementation(() => mockCrudService)
    const mockBlurForm = jest.spyOn(DisForm.methods, 'blurForm')
    const mockBlurFilter = jest.spyOn(DisForm.methods, 'blurFilter')
    const mockFocusOnFilter = jest.spyOn(DisForm.methods, 'focusOnFilter')
    const mockFocusOnForm = jest.spyOn(DisForm.methods, 'focusOnForm')
    const mockFocusOnTable = jest.spyOn(DisForm.methods, 'focusOnTable')
    const mockClearForm = jest.spyOn(DisForm.methods, 'clearForm')
    const mockOnCreateNewClick = jest.spyOn(DisForm.methods, 'onCreateNewClick').mockImplementation(() => {})
    const mockOnEditClick = jest.spyOn(DisForm.methods, 'onEditClick').mockImplementation(() => {})
    const mockOnDuplicateClick = jest.spyOn(DisForm.methods, 'onDuplicateClick').mockImplementation(() => {})
    const mockOnDeleteClick = jest.spyOn(DisForm.methods, 'onDeleteClick').mockImplementation(() => {})
    const mockOnSaveClick = jest.spyOn(DisForm.methods, 'submit').mockImplementation(() => {})
    const mockOnCancelClick = jest.spyOn(DisForm.methods, 'onCancelClick').mockImplementation(() => {})

    const wrapper = shallowMount(DisForm, {
      stubs: {
        'dis-filter-form': DisFilterFormStub,
        'dis-data-table': DisDataTableStub
      },
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
          },
          dispatch: storeDispatchMock
        },
        $router: {
          replace: routerReplaceMock
        },
        $route: {
          params: {},
          query: {}
        },
        $dialog: {
          notify: {
            warning: mockDialogNotifyWarning
          },
          message: {
            success: mockDialogMessageSuccess
          },
          warning: mockDialogWarning
        }
      },
      propsData: {
        formName: 'site',
        dataModel: 'ProjectSite',
        fields: simpleFields,
        requiredFilters: requiredFilters,
        filterDataModels: filterDataModels,
        calculatedFields: calculatedFields
      }
    })
    window.HTMLElement.prototype.scrollIntoView = jest.fn()
    jest.useFakeTimers()
    await wrapper.vm.$nextTick()
    expect(mockBlurForm).not.toHaveBeenCalled()
    expect(mockFocusOnFilter).not.toHaveBeenCalled()
    expect(mockFocusOnForm).not.toHaveBeenCalled()
    expect(mockFocusOnTable).not.toHaveBeenCalled()
    expect(mockClearForm).not.toHaveBeenCalled()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: '1', which: 49, code: 'Digit1', altKey: true }))
    await wrapper.vm.$nextTick()
    expect(mockFocusOnFilter).toHaveBeenCalledTimes(1)
    mockFocusOnFilter.mockClear()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: '2', which: 50, code: 'Digit2', altKey: true }))
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(500)
    expect(mockFocusOnForm).toHaveBeenCalledTimes(1)
    expect(mockBlurForm).toHaveBeenCalledTimes(1)
    expect(mockBlurFilter).toHaveBeenCalledTimes(1)
    mockFocusOnForm.mockClear()
    mockBlurForm.mockClear()
    mockBlurFilter.mockClear()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: '3', which: 51, code: 'Digit3', altKey: true }))
    await wrapper.vm.$nextTick()
    jest.advanceTimersByTime(500)
    expect(mockBlurForm).toHaveBeenCalledTimes(1)
    expect(mockBlurFilter).toHaveBeenCalledTimes(1)
    expect(mockFocusOnTable).toHaveBeenCalledTimes(1)
    mockFocusOnTable.mockClear()
    mockBlurForm.mockClear()
    mockBlurFilter.mockClear()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'n', which: 78, code: 'KeyN', altKey: true, shiftKey: true }))
    await wrapper.vm.$nextTick()
    expect(mockOnCreateNewClick).toHaveBeenCalledTimes(1)
    mockOnCreateNewClick.mockClear()

    wrapper.vm.selectedItem = { id: 1, site: 3, combinator: '5054_3', expedition_id: 5054 }
    await wrapper.vm.$nextTick()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'e', which: 69, code: 'KeyE', altKey: true, shiftKey: true }))
    await wrapper.vm.$nextTick()
    expect(mockOnEditClick).toHaveBeenCalledTimes(1)
    mockOnEditClick.mockClear()

    wrapper.vm.formScenario = 'edit'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.formScenario).toEqual('edit')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isSaveButtonDisabled).toBeFalsy()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 's', which: 83, code: 'KeyS', altKey: true, shiftKey: true }))
    await wrapper.vm.$nextTick()
    expect(mockOnSaveClick).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.isCancelButtonDisabled).toBeFalsy()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', which: 27, code: 'Escape' }))
    await wrapper.vm.$nextTick()
    expect(mockOnCancelClick).toHaveBeenCalledTimes(1)

    wrapper.vm.formScenario = 'view'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isEditButtonDisabled).toBeFalsy()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'd', which: 68, code: 'KeyD', altKey: true, shiftKey: true }))
    await wrapper.vm.$nextTick()
    expect(mockOnDuplicateClick).toHaveBeenCalledTimes(1)

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Delete', which: 46, code: 'Delete', altKey: true, shiftKey: true }))
    await wrapper.vm.$nextTick()
    expect(mockOnDeleteClick).toHaveBeenCalledTimes(1)
  })
})
