import { mount, shallowMount } from '@vue/test-utils'
import { defaultTemplatesState } from '../../helper/store-mock'
import FormTemplate from '@/components/templates-manager/forms/FormTemplate.vue'

let initialTemplate
let availableFieldsTemplates

const dialogColumnPromptMock = jest.fn()
const storeDispatchMock = jest.fn()
const dialogMessageSuccessMock = jest.fn()
const dialogNotifyWarning = jest.fn()
const routerPushMock = jest.fn().mockImplementation(() => Promise.resolve(true))
const autoCodeGeneratorGenerateMock = jest.fn()
describe('FormTemplate component', () => {
  beforeAll(() => {
  })
  beforeEach(() => {
    dialogColumnPromptMock.mockReset()
    storeDispatchMock.mockReset()
    dialogMessageSuccessMock.mockReset()
    dialogNotifyWarning.mockReset()
    routerPushMock.mockReset()
    autoCodeGeneratorGenerateMock.mockReset()
    const app = document.createElement('div')
    app.setAttribute('data-app', true)
    document.body.append(app)
    initialTemplate = { 'name': '', 'dataModel': 'ProjectSite', 'fields': [] }
    availableFieldsTemplates = {
      'combined_id': {
        'name': 'combined_id',
        'label': 'Combined Id',
        'description': 'CombinedKey: expedition, site (Only for viewing)',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '' },
        'group': '-group1',
        'order': 1
      },
      'site': {
        'name': 'site',
        'label': 'Site Number',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '' },
        'group': '-group2',
        'order': 2
      },
      'name': {
        'name': 'name',
        'label': 'Name of Site',
        'description': '(if any)',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '' },
        'group': '-group1',
        'order': 3
      },
      'date_start': {
        'name': 'date_start',
        'label': 'Start Date',
        'description': '',
        'validators': [],
        'formInput': { 'type': 'datetime', 'disabled': false, 'calculate': '' },
        'group': '-group1',
        'order': 4
      },
      'drilling_method': {
        'name': 'drilling_method',
        'label': 'Drilling Method',
        'description': '',
        'validators': [],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '' },
        'group': '-group1',
        'order': 7
      },
      'platform_type': {
        'name': 'platform_type',
        'label': 'Platform Type',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '' },
        'group': '-group1',
        'order': 9
      }
    }
  })
  it('basic functionality', async () => {
    dialogColumnPromptMock.mockImplementation(() => Promise.resolve('New name'))
    const wrapper = mount(FormTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            modelFullName: 'Project'
          }
        },
        $dialog: {
          prompt: dialogColumnPromptMock
        }
      },
      propsData: {
        scenario: 'create',
        initialTemplate: initialTemplate,
        availableFieldsTemplates: availableFieldsTemplates
      }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.exportedTemplate.name).toEqual('')
    wrapper.vm.exportedTemplate.name = 'sites'
    const formNameInputRef = wrapper.findComponent({ ref: 'formNameInput' })
    formNameInputRef.find('input').setValue('Sites ')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.exportedTemplate.name).toEqual('sites')
    expect(wrapper.vm.selectedFieldsGroups.length).toEqual(0)
    wrapper.findComponent({ ref: 'addGroupButton' }).trigger('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.addGroupDialog).toBeTruthy()
    wrapper.findComponent({ ref: 'newGroupName' }).find('input').setValue('-group1')
    wrapper.vm.addFieldsGroup()
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.selectedFieldsGroups).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          label: '-group1'
        })
      ])
    )
    // mimic field drag into template
    const fieldToDrag = wrapper.vm.fields[0]
    wrapper.vm.fields.splice(0, 1)
    wrapper.vm.selectedFieldsGroups[0].fields.push(fieldToDrag)
    await wrapper.vm.$nextTick()
    const groups = wrapper.findAll('.c-form-template__group-fields')
    expect(groups.length).toEqual(1)
    const fields = wrapper.findAllComponents({ ref: 'formFieldTemplate' })
    expect(fields.length).toEqual(1)
    // test remove group ignored if group contain fields
    wrapper.vm.removeFieldsGroup(wrapper.vm.selectedFieldsGroups[0])
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.selectedFieldsGroups.length).toEqual(1)
    // mimic field drag out of template
    const fieldToDragBack = wrapper.vm.selectedFieldsGroups[0].fields[0]
    wrapper.vm.selectedFieldsGroups[0].fields.splice(0, 1)
    wrapper.vm.fields.push(fieldToDragBack)
    await wrapper.vm.$nextTick()
    expect(wrapper.findAllComponents({ ref: 'formFieldTemplate' }).length).toEqual(0)
    // test rempve group works if groups does not contain fields
    wrapper.vm.removeFieldsGroup(wrapper.vm.selectedFieldsGroups[0])
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.selectedFieldsGroups.length).toEqual(0)
  })
  it('sorts fields into groups', async () => {
    dialogColumnPromptMock.mockImplementation(() => Promise.resolve('New name'))
    initialTemplate = {
      'name': '',
      'dataModel': 'ProjectSite',
      'fields': [
        {
          'name': 'combined_id',
          'label': '',
          'description': '',
          'validators': [{ 'type': 'required' }, { 'type': 'string' }],
          'formInput': { 'type': 'text', 'disabled': false, 'calculate': '' },
          'group': '-group1',
          'order': 1
        },
        {
          'name': 'site',
          'label': '',
          'description': '',
          'validators': [{ 'type': 'required' }, { 'type': 'string' }],
          'formInput': { 'type': 'text', 'disabled': false, 'calculate': '' },
          'group': '-group2',
          'order': 1
        }
      ]
    }
    const wrapper = mount(FormTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            modelFullName: 'Project'
          }
        },
        $dialog: {
          prompt: dialogColumnPromptMock
        }
      },
      propsData: {
        scenario: 'create',
        initialTemplate: initialTemplate,
        availableFieldsTemplates: availableFieldsTemplates
      }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.selectedFieldsGroups.length).toEqual(2)
    expect(wrapper.vm.selectedFieldsGroups[0].fields.length).toEqual(1)
  })
  it('creates new forms templates', async () => {
    const AutoCodeGeneratorStub = {
      render: () => {},
      methods: {
        generate: autoCodeGeneratorGenerateMock
      }
    }
    const mockValidate = jest.fn()
    const VFormStub = {
      render: () => {},
      methods: {
        validate: mockValidate
      }
    }
    const wrapper = shallowMount(FormTemplate, {
      stubs: {
        'auto-code-generator': AutoCodeGeneratorStub,
        'v-form': VFormStub
      },
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            modelFullName: 'Project'
          }
        },
        $router: {
          push: routerPushMock
        },
        $dialog: {
          prompt: dialogColumnPromptMock,
          message: {
            success: dialogMessageSuccessMock
          },
          notify: {
            warning: dialogNotifyWarning
          }
        }
      },
      propsData: {
        scenario: 'create',
        initialTemplate: initialTemplate,
        availableFieldsTemplates: availableFieldsTemplates
      }
    })
    await wrapper.vm.$nextTick()
    storeDispatchMock.mockClear()
    dialogMessageSuccessMock.mockClear()
    wrapper.vm.exportedTemplate.name = 'holes'
    mockValidate.mockImplementationOnce(() => true)
    storeDispatchMock.mockImplementationOnce(() => Promise.resolve({ exportedTemplate: { dataModel: 'ProjectHole' } }))
    await wrapper.vm.saveTemplate(false)
    expect(storeDispatchMock).toHaveBeenNthCalledWith(1, 'templates/createFormTemplate', expect.objectContaining({ template: expect.objectContaining({ name: 'holes' }) }))
    expect(dialogMessageSuccessMock).toHaveBeenNthCalledWith(1, 'created successfully', { position: 'bottom' })

    // validation error
    storeDispatchMock.mockClear()
    dialogMessageSuccessMock.mockClear()
    wrapper.vm.exportedTemplate.name = 'holes'
    mockValidate.mockImplementationOnce(() => true)
    storeDispatchMock.mockImplementationOnce(() => {
      const customError = new Error()
      customError.response = {
        status: 422,
        data: [
          { field: 'name', errors: ['name is required'] }
        ]
      }
      return Promise.reject(customError)
    })
    await wrapper.vm.saveTemplate(false)
    expect(storeDispatchMock).toHaveBeenNthCalledWith(1, 'templates/createFormTemplate', expect.objectContaining({ template: expect.objectContaining({ name: 'holes' }) }))
    expect(dialogMessageSuccessMock).toHaveBeenCalledTimes(0)
    expect(wrapper.vm.serverValidationErrors).toEqual([
      { field: 'name', errors: ['name is required'] }
    ])

    // error
    storeDispatchMock.mockClear()
    dialogMessageSuccessMock.mockClear()
    wrapper.vm.exportedTemplate.name = 'holes'
    mockValidate.mockImplementationOnce(() => true)
    storeDispatchMock.mockImplementationOnce(() => {
      const customError = new Error()
      customError.message = 'unknown error'
      return Promise.reject(customError)
    })
    await wrapper.vm.saveTemplate(false)
    expect(storeDispatchMock).toHaveBeenNthCalledWith(1, 'templates/createFormTemplate', expect.objectContaining({ template: expect.objectContaining({ name: 'holes' }) }))
    expect(dialogMessageSuccessMock).toHaveBeenCalledTimes(0)
    expect(dialogNotifyWarning).toHaveBeenNthCalledWith(1, 'unknown error')
  })
  it('updates forms templates', async () => {
    const AutoCodeGeneratorStub = {
      render: () => {},
      methods: {
        generate: autoCodeGeneratorGenerateMock
      }
    }
    const mockValidate = jest.fn()
    const VFormStub = {
      render: () => {},
      methods: {
        validate: mockValidate
      }
    }
    const wrapper = shallowMount(FormTemplate, {
      stubs: {
        'auto-code-generator': AutoCodeGeneratorStub,
        'v-form': VFormStub
      },
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            modelFullName: 'Project'
          }
        },
        $router: {
          push: routerPushMock
        },
        $dialog: {
          prompt: dialogColumnPromptMock,
          message: {
            success: dialogMessageSuccessMock
          },
          notify: {
            warning: dialogNotifyWarning
          }
        }
      },
      propsData: {
        scenario: 'update',
        initialTemplate: initialTemplate,
        availableFieldsTemplates: availableFieldsTemplates
      }
    })
    await wrapper.vm.$nextTick()
    storeDispatchMock.mockClear()
    dialogMessageSuccessMock.mockClear()
    wrapper.vm.exportedTemplate.name = 'holes'
    mockValidate.mockImplementationOnce(() => true)
    storeDispatchMock.mockImplementationOnce(() => Promise.resolve({ exportedTemplate: { dataModel: 'ProjectHole' } }))
    await wrapper.vm.saveTemplate(false)
    expect(storeDispatchMock).toHaveBeenNthCalledWith(1, 'templates/updateFormTemplate', expect.objectContaining({ template: expect.objectContaining({ name: 'holes' }) }))
    expect(dialogMessageSuccessMock).toHaveBeenNthCalledWith(1, 'saved successfully', { position: 'bottom' })
  })
})
