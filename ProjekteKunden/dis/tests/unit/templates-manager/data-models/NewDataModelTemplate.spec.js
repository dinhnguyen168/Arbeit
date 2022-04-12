import { shallowMount } from '@vue/test-utils'
import { defaultTemplatesState } from '../../helper/store-mock'
import NewDataModelTemplate from '@/components/templates-manager/data-models/NewDataModelTemplate.vue'

const setTitleMock = jest.fn()
const storeDispatchMock = jest.fn()
const dialogNotifyWarningMock = jest.fn()

describe('DataModelTemplate component', () => {
  beforeAll(() => {
  })
  beforeEach(() => {
    const app = document.createElement('div')
    app.setAttribute('data-app', true)
    document.body.append(app)
    setTitleMock.mockReset()
    storeDispatchMock.mockReset()
    dialogNotifyWarningMock.mockReset()
  })
  it('basic functionality', async () => {
    const wrapper = shallowMount(NewDataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            moduleName: 'project'
          }
        },
        $setTitle: setTitleMock
      }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.template.module).toEqual('Project')
    expect(setTitleMock).toHaveBeenNthCalledWith(1, 'Create Data Model Template')
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/refreshSummary')
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/refreshBehaviors')
  })
  it('notifies refresh errors', async () => {
    storeDispatchMock.mockImplementation(() => Promise.reject(new Error('refresh error')))
    const wrapper = shallowMount(NewDataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            moduleName: 'project'
          }
        },
        $dialog: {
          notify: {
            warning: dialogNotifyWarningMock
          }
        },
        $setTitle: setTitleMock
      }
    })
    await wrapper.vm.$nextTick()
    expect(dialogNotifyWarningMock).toHaveBeenCalledWith('refresh error', { timeout: 30000 })
  })
})
