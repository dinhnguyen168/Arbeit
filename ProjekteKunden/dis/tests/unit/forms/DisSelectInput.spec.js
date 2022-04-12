import { shallowMount, mount } from '@vue/test-utils'
import DisSelectInput from '@/components/input/DisSelectInput.vue'
import ListValuesService from '@/services/ListValuesService'
import CrudService from '@/services/CrudService'

const mockGetList = jest.fn().mockImplementation(() => {
  return Promise.resolve({
    items: [
      { 'id': 3375, 'list_id': 115, 'display': 'MM', 'remark': 'Max Muster', 'uri': null, 'sort': 10 },
      { 'id': 3376, 'list_id': 115, 'display': 'CK', 'remark': 'Cindy Kunkel', 'uri': null, 'sort': 20 },
      { 'id': 3377, 'list_id': 115, 'display': 'KH', 'remark': '', 'uri': null, 'sort': 30 }
    ]
  })
})
const mockListValuesService = {
  getList: mockGetList
}

const mockCrudeGetList = jest.fn().mockImplementation(() => {
  return Promise.resolve({ 'items': [
    { 'id': 3, 'combined_id': 'B1' },
    { 'id': 4, 'combined_id': 'B2_C1' },
    { 'id': 5, 'combined_id': 'B2' }
  ] })
})
const mockCrudService = {
  getAsyncList: mockCrudeGetList
}

describe('DisFilterForm component', () => {
  beforeAll(() => {
  })
  beforeEach(() => {
    const app = document.createElement('div')
    app.setAttribute('data-app', true)
    document.body.append(app)
    ListValuesService.mockClear()
    mockGetList.mockClear()
    CrudService.mockClear()
    mockCrudeGetList.mockClear()
  })
  it('should list items from list values api', async () => {
    // this should be called before mounting the component
    ListValuesService.mockImplementation(() => mockListValuesService)
    const wrapper = shallowMount(DisSelectInput, {
      propsData: {
        name: 'list',
        label: 'List',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'list',
          listName: 'ANALYST',
          textField: 'remark',
          valueField: 'display'
        },
        allowFreeInput: false,
        multiple: false
      }
    })
    await wrapper.vm.$nextTick()
    expect(mockListValuesService.getList).toHaveBeenCalledTimes(1)
    expect(mockListValuesService.getList).toHaveBeenCalledWith({ sort: 'sort' }, { listname: 'ANALYST' })
    // it should concat text and value in text (only if text available)
    expect(wrapper.vm.items).toEqual([
      { 'value': 'MM', 'text': 'MM | Max Muster' },
      { 'value': 'CK', 'text': 'CK | Cindy Kunkel' },
      { 'value': 'KH', 'text': 'KH' }
    ])
    expect(wrapper.vm.items).toHaveLength(3)
  })
  it('should list items from another table', async () => {
    // this should be called before mounting the component
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = shallowMount(DisSelectInput, {
      propsData: {
        name: 'parent',
        label: 'Parent',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'api',
          listName: '',
          textField: 'combined_id',
          valueField: 'id',
          model: 'CurationStorage'
        },
        allowFreeInput: false,
        multiple: false
      }
    })
    await wrapper.vm.$nextTick()
    expect(mockCrudService.getAsyncList).toHaveBeenCalledTimes(1)
    expect(mockCrudService.getAsyncList).toHaveBeenCalledWith({ sort: 'combined_id', fields: 'combined_id,id', q: null, value: [] })
    // it should concat text and value in text (only if text available)
    expect(wrapper.vm.items).toEqual([
      { 'value': 3, 'text': '3 | B1' },
      { 'value': 4, 'text': '4 | B2_C1' },
      { 'value': 5, 'text': '5 | B2' }
    ])
    expect(wrapper.vm.items).toHaveLength(3)
  })
  it('allows defining value field only for api sources', async () => {
    // this should be called before mounting the component
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = shallowMount(DisSelectInput, {
      propsData: {
        name: 'parent',
        label: 'Parent',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'api',
          listName: '',
          textField: '',
          valueField: 'id',
          model: 'CurationStorage'
        },
        allowFreeInput: false,
        multiple: false
      }
    })
    await wrapper.vm.$nextTick()
    expect(mockCrudService.getAsyncList).toHaveBeenCalledTimes(1)
    expect(mockCrudService.getAsyncList).toHaveBeenCalledWith({ sort: 'id', fields: 'id', q: null, value: [] })
    // it should concat text and value in text (only if text available)
    expect(wrapper.vm.items).toEqual([
      { 'value': 3, 'text': '3' },
      { 'value': 4, 'text': '4' },
      { 'value': 5, 'text': '5' }
    ])
    expect(wrapper.vm.items).toHaveLength(3)
  })
  it('opens edit list items dialog when source is list', async () => {
    // this should be called before mounting the component
    ListValuesService.mockImplementation(() => mockListValuesService)
    const wrapper = mount(DisSelectInput, {
      propsData: {
        name: 'list',
        label: 'List',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'list',
          listName: 'ANALYST',
          textField: 'remark',
          valueField: 'display'
        },
        allowFreeInput: false,
        multiple: false
      }
    })
    await wrapper.vm.$nextTick()
    expect(mockListValuesService.getList).toHaveBeenCalledTimes(1)
    const autocomplete = wrapper.findComponent({ name: 'v-autocomplete' })
    expect(autocomplete.exists()).toBeTruthy()
    const outerIcon = autocomplete.find('.v-input__icon--append-outer .v-icon')
    expect(outerIcon.exists()).toBeTruthy()
    outerIcon.trigger('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.listFormDialog).toBeTruthy()
    const dialog = wrapper.findComponent({ name: 'v-dialog' })
    expect(dialog.exists()).toBeTruthy()
    const closeDialogIcon = dialog.find('.v-toolbar .v-btn .v-icon')
    expect(closeDialogIcon.exists()).toBeTruthy()
    closeDialogIcon.trigger('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.listFormDialog).toBeFalsy()
  })
  it('does not open edit list items dialog when source is api', async () => {
    // this should be called before mounting the component
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = mount(DisSelectInput, {
      propsData: {
        name: 'parent',
        label: 'Parent',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'api',
          listName: '',
          textField: 'combined_id',
          valueField: 'id',
          model: 'CurationStorage'
        },
        allowFreeInput: false,
        multiple: false
      }
    })
    await wrapper.vm.$nextTick()
    const autocomplete = wrapper.findComponent({ name: 'v-autocomplete' })
    expect(autocomplete.exists()).toBeTruthy()
    const outerIcon = autocomplete.find('.v-input__icon--append-outer .v-icon')
    expect(outerIcon.exists()).toBeFalsy()
    wrapper.vm.editList()
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.listFormDialog).toBeFalsy()
  })
  it('uses chips when multiple is enabled', async () => {
    // this should be called before mounting the component
    CrudService.mockImplementation(() => mockCrudService)
    const wrapper = shallowMount(DisSelectInput, {
      propsData: {
        name: 'parent',
        label: 'Parent',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'api',
          listName: '',
          textField: 'combined_id',
          valueField: 'id',
          model: 'CurationStorage'
        },
        allowFreeInput: false,
        multiple: true
      }
    })
    await wrapper.vm.$nextTick()
    const autocomplete = wrapper.findComponent({ name: 'v-autocomplete' })
    expect(autocomplete.exists()).toBeTruthy()
    expect(autocomplete.vm.chips).toBeTruthy()
    expect(autocomplete.vm.smallChips).toBeTruthy()
    expect(autocomplete.vm.deletableChips).toBeTruthy()

    const wrapper2 = shallowMount(DisSelectInput, {
      propsData: {
        name: 'parent',
        label: 'Parent',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'api',
          listName: '',
          textField: 'combined_id',
          valueField: 'id',
          model: 'CurationStorage'
        },
        allowFreeInput: true,
        multiple: true
      }
    })
    await wrapper2.vm.$nextTick()
    const combobox = wrapper2.findComponent({ name: 'v-combobox' })
    expect(combobox.exists()).toBeTruthy()
    expect(combobox.vm.chips).toBeTruthy()
    expect(combobox.vm.smallChips).toBeTruthy()
    expect(combobox.vm.deletableChips).toBeTruthy()
  })
  it('should clear search word after selecting an item', async () => {
    // this should be called before mounting the component
    ListValuesService.mockImplementation(() => mockListValuesService)
    const wrapper = mount(DisSelectInput, {
      propsData: {
        name: 'list',
        label: 'List',
        validators: [],
        serverValidationErrors: [],
        value: [],
        selectSource: {
          type: 'list',
          listName: 'ANALYST',
          textField: 'remark',
          valueField: 'display'
        },
        allowFreeInput: false,
        multiple: true
      }
    })
    await wrapper.vm.$nextTick()
    expect(mockListValuesService.getList).toHaveBeenCalledTimes(1)
    expect(mockListValuesService.getList).toHaveBeenCalledWith({ sort: 'sort' }, { listname: 'ANALYST' })
    // it should concat text and value in text (only if text available)
    expect(wrapper.vm.items).toEqual([
      { 'value': 'MM', 'text': 'MM | Max Muster' },
      { 'value': 'CK', 'text': 'CK | Cindy Kunkel' },
      { 'value': 'KH', 'text': 'KH' }
    ])
    expect(wrapper.vm.items).toHaveLength(3)
    expect(wrapper.vm.value).toEqual([])
    const autocomplete = wrapper.findComponent({ name: 'v-autocomplete' })
    expect(autocomplete.exists()).toBeTruthy()
    console.log(autocomplete)
    autocomplete.vm.onClick()
    autocomplete.vm.onClick()
    autocomplete.vm.focus()
    expect(autocomplete.vm.isMenuActive).toBeTruthy()
    wrapper.vm.searchWord = 'Muster'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.value).toEqual([])
    autocomplete.vm.selectItem({ 'value': 'MM', 'text': 'MM | Max Muster' })
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.searchWord).toEqual(null)
  })
})
