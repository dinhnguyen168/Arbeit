import { shallowMount } from '@vue/test-utils'
import { defaultTemplatesState } from '../../helper/store-mock'
import UpdateDataModelTemplate from '@/components/templates-manager/data-models/UpdateDataModelTemplate.vue'

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
    storeDispatchMock.mockImplementation((action, data) => {
      if (action === 'templates/getModelTemplate') {
        return Promise.resolve(defaultTemplatesState.models.find(item => item.fullName === 'ProjectHole'))
      }
      return Promise.resolve(new Error('refresh error'))
    })
    const wrapper = shallowMount(UpdateDataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            modelFullName: 'ProjectHole'
          }
        },
        $setTitle: setTitleMock
      }
    })
    await wrapper.vm.$nextTick()
    expect(setTitleMock).toHaveBeenNthCalledWith(1, 'Update Data Model Template')
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/refreshSummary')
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/refreshBehaviors')
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/getModelTemplate', 'ProjectHole')
  })
  it('notifies refresh errors', async () => {
    storeDispatchMock.mockImplementation(() => Promise.reject(new Error('refresh error')))
    const wrapper = shallowMount(UpdateDataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          },
          dispatch: storeDispatchMock
        },
        $route: {
          params: {
            modelFullName: 'ProjectHole'
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
