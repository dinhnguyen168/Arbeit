import { mount, shallowMount } from '@vue/test-utils'
import { defaultTemplatesState } from '../../helper/store-mock'
import DataModelTemplate from '@/components/templates-manager/data-models/DataModelTemplate.vue'
import upperFirst from 'lodash/upperFirst'
import camelCase from 'lodash/camelCase'

let initialModelTemplate

const dialogColumnPromptMock = jest.fn()
const storeDispatchMock = jest.fn()
const dialogMessageSuccessMock = jest.fn()
const routerPushMock = jest.fn().mockImplementation(() => Promise.resolve(true))
const autoCodeGeneratorGenerateMock = jest.fn()
describe('DataModelTemplate component', () => {
  beforeAll(() => {
  })
  beforeEach(() => {
    dialogColumnPromptMock.mockReset()
    storeDispatchMock.mockReset()
    dialogMessageSuccessMock.mockReset()
    routerPushMock.mockReset()
    autoCodeGeneratorGenerateMock.mockReset()
    const app = document.createElement('div')
    app.setAttribute('data-app', true)
    document.body.append(app)
    initialModelTemplate = {
      'module': 'Project',
      'name': '',
      'parentModel': '',
      'columns': [{
        'name': 'id',
        'importSource': '',
        'type': 'integer',
        'size': 11,
        'required': false,
        'primaryKey': true,
        'autoInc': true,
        'label': 'ID',
        'description': '',
        'validator': '',
        'validatorMessage': '',
        'unit': '',
        'selectListName': '',
        'calculate': '',
        'defaultValue': ''
      }],
      'indices': [{ 'name': 'pk_id', 'type': 'PRIMARY', 'columns': ['id'] }],
      'behaviors': [],
      'relations': []
    }
  })
  it('basic functionality', async () => {
    dialogColumnPromptMock.mockImplementation(() => Promise.resolve('New name'))
    const wrapper = mount(DataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          }
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
        initialTemplate: initialModelTemplate
      }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.module).toEqual('Project')
    const parentSelect = wrapper.findComponent({ ref: 'parentSelectInput' })
    expect(parentSelect.vm.disabled).toBeTruthy()
    expect(wrapper.vm.parentModelColumnName).toEqual('')
    wrapper.vm.name = 'Test hole'
    await wrapper.vm.$nextTick()
    expect(parentSelect.vm.disabled).toBeFalsy()
    expect(wrapper.vm.fixModelNameCase())
    expect(wrapper.vm.name).toEqual('TestHole')
    expect(wrapper.vm.relations.length).toEqual(0)
    wrapper.vm.parentModel = 'ProjectSite'
    await wrapper.vm.$nextTick()
    // test adding and removing columns
    expect(wrapper.vm.columns.length).toEqual(2) // one for id and one for parent fk
    wrapper.vm.addColumn()
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'new_name'
        })
      ])
    )
    expect(wrapper.vm.columns.length).toEqual(3)
    wrapper.vm.removeColumn(2)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.columns.length).toEqual(2)
    expect(wrapper.vm.columns).not.toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'new_name'
        })
      ])
    )
    // test adding and removing index
    expect(wrapper.vm.indices.length).toEqual(2)
    wrapper.vm.addIndex()
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.indices.length).toEqual(3)
    expect(wrapper.vm.indices).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'new_name'
        })
      ])
    )
    wrapper.vm.removeIndex(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.indices.length).toEqual(2)
    expect(wrapper.vm.indices).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'new_name'
        })
      ])
    )
    // test adding relation, removing is covered in parent model change test
    wrapper.vm.addRelation()
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: expect.stringMatching(/project_test_hole__project_site__parent/)
        })
      ])
    )
    // test adding and removing behavior
    expect(wrapper.vm.behaviorsFilter).toEqual('')
    expect(wrapper.vm.behaviorsMenu).toBeFalsy()
    const behaviorAddButtonRef = wrapper.findComponent({ ref: 'behaviorAddButton' })
    behaviorAddButtonRef.trigger('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.behaviorsMenu).toBeTruthy()
    const behaviorFilterInputRef = wrapper.findComponent({ ref: 'behaviorFilterInput' })
    expect(behaviorAddButtonRef.exists()).toBeTruthy()
    expect(wrapper.vm.behaviorsList.length).toEqual(9)
    behaviorFilterInputRef.find('input').setValue('Cumu')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.behaviorsList.length).toEqual(1)
    const behaviorsListRef = wrapper.find({ ref: 'behaviorsList' })
    const listTiles = behaviorsListRef.findAllComponents({ name: 'v-list-tile' })
    // the first tile is always visible (contains the filter input)
    expect(listTiles.length).toEqual(2)
    // click on the first behavior
    listTiles.at(1).vm.$emit('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.behaviors.length).toEqual(1)
    expect(wrapper.vm.behaviors).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          behaviorClass: 'app\\behaviors\\template\\CumulativeSectionLengthBehavior'
        })
      ])
    )
    wrapper.vm.removeBehavior(0)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.behaviors.length).toEqual(0)
  })
  it('updates relations on parent model change', async () => {
    dialogColumnPromptMock.mockImplementation(() => Promise.resolve('New name'))
    const wrapper = mount(DataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          }
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
        initialTemplate: initialModelTemplate
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.name = 'Test hole'
    await wrapper.vm.$nextTick()
    // test that parent model change changes the fk column and index
    wrapper.vm.parentModel = 'ProjectExpedition'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.relations.length).toEqual(1)
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'expedition_id', type: 'integer', required: true
        })
      ])
    )
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          foreignTable: 'project_expedition', localColumns: ['expedition_id'], foreignColumns: ['id']
        })
      ])
    )
    wrapper.vm.parentModel = 'ProjectSite'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.relations.length).toEqual(1)
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'site_id', type: 'integer', required: true
        })
      ])
    )
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          foreignTable: 'project_site', localColumns: ['site_id'], foreignColumns: ['id']
        })
      ])
    )
    wrapper.vm.parentModel = null
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.relations.length).toEqual(0)
    expect(wrapper.vm.columns.length).toEqual(0)
    expect(wrapper.vm.indices.length).toEqual(1)
  })
  it('adds non required column with relation if self parent is chosen', async () => {
    dialogColumnPromptMock.mockImplementation(() => Promise.resolve('New name'))
    const wrapper = mount(DataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          }
        },
        $route: {
          params: {
            modelFullName: 'ProjectSite'
          }
        },
        $dialog: {
          prompt: dialogColumnPromptMock
        }
      },
      propsData: {
        scenario: 'create',
        initialTemplate: initialModelTemplate
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.name = 'Site'
    await wrapper.vm.$nextTick()
    // test parent self referencing
    wrapper.vm.parentModel = 'ProjectSite'
    await wrapper.vm.$nextTick()
    console.log('--------------', wrapper.vm.module, wrapper.vm.name, wrapper.vm.parentModel, upperFirst(camelCase(`${wrapper.vm.module}-${wrapper.vm.name}`)), wrapper.vm.parentModelColumnName)
    expect(wrapper.vm.relations.length).toEqual(1)
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'parent_id', type: 'integer', required: false
        })
      ])
    )
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          foreignTable: 'project_site', localColumns: ['parent_id'], foreignColumns: ['id']
        })
      ])
    )
    // test it deletes self reference
    wrapper.vm.parentModel = 'ProjectExpedition'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.relations.length).toEqual(1)
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'expedition_id', type: 'integer', required: true
        })
      ])
    )
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          foreignTable: 'project_expedition', localColumns: ['expedition_id'], foreignColumns: ['id']
        })
      ])
    )
  })
  it('adds | edits | removes many to many relation', async () => {
    const wrapper = mount(DataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          }
        },
        $route: {
          params: {
            modelFullName: 'ProjectSite'
          }
        },
        $dialog: {
          prompt: dialogColumnPromptMock
        }
      },
      propsData: {
        scenario: 'create',
        initialTemplate: initialModelTemplate
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.name = 'Site'
    await wrapper.vm.$nextTick()

    wrapper.vm.relationType = 'nm'
    wrapper.vm.relatedModel = 'ProjectHole'
    wrapper.vm.displayColumn = 'hole'
    wrapper.vm.columnName = 'project_hole_ids'
    wrapper.vm.oppositeColumnName = 'project_site_ids'
    await wrapper.vm.$nextTick()

    // test if relation added
    wrapper.vm.addRelation()
    expect(wrapper.vm.relations.length).toEqual(1)
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__project_hole_ids__nm', relatedTable: 'project_hole', localColumns: ['project_hole_ids'], foreignColumns: null, displayColumns: ['hole'], oppositeColumnName: 'project_site_ids'
        })
      ])
    )
    // test if a pseudo local column automatically added
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_hole_ids', type: 'many_to_many', required: false, displayColumn: 'hole', relatedTable: 'project_hole'
        })
      ])
    )

    // test if the relation updated from DataModelForeignKeyTemplate.vue component
    wrapper.vm.updateDisplayColumns(['id'], 0, 'project_hole_ids')
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__project_hole_ids__nm', relatedTable: 'project_hole', localColumns: ['project_hole_ids'], foreignColumns: null, displayColumns: ['id'], oppositeColumnName: 'project_site_ids'
        })
      ])
    )
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_hole_ids', type: 'many_to_many', required: false, displayColumn: 'id', relatedTable: 'project_hole'
        })
      ])
    )

    wrapper.vm.updateColumnName('project_id_ids', 'project_hole_ids', 0)
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__project_id_ids__nm', relatedTable: 'project_hole', localColumns: ['project_id_ids'], foreignColumns: null, displayColumns: ['id'], oppositeColumnName: 'project_site_ids'
        })
      ])
    )
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_id_ids', type: 'many_to_many', required: false, displayColumn: 'id', relatedTable: 'project_hole'
        })
      ])
    )
    wrapper.vm.updateOppositeColumnName('site_ids', 'project_site_ids', 0)
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__project_id_ids__nm', relatedTable: 'project_hole', localColumns: ['project_id_ids'], foreignColumns: null, displayColumns: ['id'], oppositeColumnName: 'site_ids'
        })
      ])
    )

    // test if the relation removed
    wrapper.vm.removeRelation(0)
    expect(wrapper.vm.relations.length).toEqual(0)
    expect(wrapper.vm.relations).not.toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__project_ids__nm', relatedTable: 'project_hole', localColumns: ['project_ids'], foreignColumns: null, displayColumns: ['id'], oppositeColumnName: 'project_ids'
        })
      ])
    )
    expect(wrapper.vm.columns).not.toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_hole_ids', type: 'many_to_many', required: false, displayColumn: 'hole', relatedTable: 'project_hole'
        })
      ])
    )
  })
  it('adds | edits | removes one to many relation', async () => {
    const wrapper = mount(DataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          }
        },
        $route: {
          params: {
            modelFullName: 'ProjectSite'
          }
        },
        $dialog: {
          prompt: dialogColumnPromptMock
        }
      },
      propsData: {
        scenario: 'create',
        initialTemplate: initialModelTemplate
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.name = 'Site'
    await wrapper.vm.$nextTick()

    wrapper.vm.relationType = '1n'
    wrapper.vm.relatedModel = 'ProjectHole'
    wrapper.vm.displayColumn = 'hole'
    wrapper.vm.columnName = 'hole'
    await wrapper.vm.$nextTick()

    // test if relation added
    wrapper.vm.addRelation()
    expect(wrapper.vm.relations.length).toEqual(1)
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__hole__1n', foreignTable: 'project_hole', localColumns: ['hole'], foreignColumns: ['id'], displayColumns: ['hole']
        })
      ])
    )
    // test if a local column automatically added
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'hole', type: 'one_to_many', required: false, displayColumn: 'hole', relatedTable: 'project_hole'
        })
      ])
    )
    // test if a local index automatically added
    expect(wrapper.vm.indices).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'hole', type: 'KEY', columns: ['hole']
        })
      ])
    )

    // test if the relation updated from DataModelForeignKeyTemplate.vue component
    wrapper.vm.updateDisplayColumns(['id'], 0, 'hole')
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__hole__1n', foreignTable: 'project_hole', localColumns: ['hole'], foreignColumns: ['id'], displayColumns: ['id']
        })
      ])
    )
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'hole', type: 'one_to_many', required: false, displayColumn: 'id', relatedTable: 'project_hole'
        })
      ])
    )

    wrapper.vm.updateColumnName('hole_id', 'hole', 0)
    expect(wrapper.vm.relations).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__hole_id__1n', foreignTable: 'project_hole', localColumns: ['hole_id'], foreignColumns: ['id'], displayColumns: ['id']
        })
      ])
    )
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'hole_id', type: 'one_to_many', required: false, displayColumn: 'id', relatedTable: 'project_hole'
        })
      ])
    )
    expect(wrapper.vm.indices).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'hole_id', type: 'KEY', columns: ['hole_id']
        })
      ])
    )

    // test if the relation removed
    wrapper.vm.removeRelation(0)
    expect(wrapper.vm.relations.length).toEqual(0)
    expect(wrapper.vm.relations).not.toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'project_site__project_hole__hole_id__1n', foreignTable: 'project_hole', localColumns: ['hole_id'], foreignColumns: ['id'], displayColumns: ['id']
        })
      ])
    )
    expect(wrapper.vm.columns).not.toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'hole_id', type: 'one_to_many', required: false, displayColumn: 'hole', relatedTable: 'project_hole'
        })
      ])
    )
    expect(wrapper.vm.indices).not.toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'hole_id', type: 'KEY', columns: ['hole_id']
        })
      ])
    )
  })
  it('saves and generates templates', async () => {
    // shallow mounts is needed to avoid calling child
    // components methods using $refs. stubbing them give
    // us a better control
    const AutoCodeGeneratorStub = {
      render: () => {},
      methods: {
        generate: autoCodeGeneratorGenerateMock
      }
    }
    const wrapper = shallowMount(DataModelTemplate, {
      stubs: {
        'auto-code-generator': AutoCodeGeneratorStub
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
          message: {
            success: dialogMessageSuccessMock
          }
        }
      },
      propsData: {
        scenario: 'create',
        initialTemplate: initialModelTemplate
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.name = 'Test hole'
    wrapper.vm.parentModel = 'ProjectSite'
    // add a behavior for more coverage
    wrapper.vm.addBehavior(wrapper.vm.behaviorsList[0])
    await wrapper.vm.$nextTick()
    // test save template
    storeDispatchMock.mockImplementation(() => Promise.resolve({ fullName: 'modelFullName' }))
    await wrapper.vm.saveModelTemplate(false)
    await wrapper.vm.$nextTick()
    expect(storeDispatchMock).toHaveBeenCalledTimes(1)
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/createModelTemplate', expect.objectContaining({
      columns: expect.objectContaining({
        id: expect.objectContaining({
          autoInc: true
        })
      })
    }))
    // test save and generate
    storeDispatchMock.mockClear()
    await wrapper.vm.saveModelTemplate(true)
    await wrapper.vm.$nextTick()
    autoCodeGeneratorGenerateMock.mockImplementation(() => Promise.resolve('generated'))
    // expect 2 dispatch call. one for template save and one for table create
    expect(storeDispatchMock).toHaveBeenCalledTimes(2)
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/createModelTemplate', expect.objectContaining({
      columns: expect.objectContaining({
        id: expect.objectContaining({
          autoInc: true
        })
      })
    }))
    expect(storeDispatchMock).toHaveBeenCalledWith('templates/createModelTable', 'ProjectTestHole')
  })
  it('handles pseudo column type', async () => {
    const wrapper = mount(DataModelTemplate, {
      mocks: {
        $store: {
          state: {
            templates: defaultTemplatesState
          }
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
        initialTemplate: initialModelTemplate
      }
    })
    await wrapper.vm.$nextTick()
    wrapper.vm.name = 'Test hole'
    wrapper.vm.parentModel = 'ProjectSite'
    dialogColumnPromptMock.mockImplementation(() => Promise.resolve('pseudo column'))
    wrapper.vm.addColumn()
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.columns.length).toEqual(3)
    // manipulate added column
    wrapper.vm.columns[2].type = 'string'
    wrapper.vm.columns[2].size = 255
    wrapper.vm.columns[2].calculate = '[id]*2'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'pseudo_column', type: 'string', size: 255, calculate: '[id]*2'
        })
      ])
    )
    wrapper.vm.columns[2].type = 'pseudo'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.columns).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          name: 'pseudo_column', type: 'pseudo', size: null, calculate: ''
        })
      ])
    )
  })
})
