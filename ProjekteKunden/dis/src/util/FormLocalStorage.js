import ls from 'local-storage'
import Vue from 'vue'

const filterKey = 'F'
const selectedKey = 'S'
const paginationKey = 'P'
const selectodColumnsKey = 'C'
const originalColumnsKey = 'OC'
const filterModelKey = 'FM'
const ascFilterListsKey = 'AFL'
const autoPrintReportName = 'APRN'
const autoPrintOnSave = 'APS'
const uuidList = '@uuids'

export default class FormLocalStorage {
  constructor (formName) {
    this.lsPrefix = window.baseUrl
    this.formName = formName
    this.reactiveStorage = {}
    Vue.util.defineReactive(this.reactiveStorage, 'ascFilterLists', this.getValue(`${this.formName}_${ascFilterListsKey}`) || [])
  }

  getValue (key) {
    return ls.get(this.lsPrefix + key)
  }

  setValue (key, value) {
    ls.set(this.lsPrefix + key, value)
  }

  removeKey (key) {
    key = `${this.formName}_${key}`
    ls.remove(this.lsPrefix + key)
  }

  get filter () {
    let value = this.getValue(`${this.formName}_${filterKey}`)
    return value || {}
  }
  set filter (value) {
    this.setValue(`${this.formName}_${filterKey}`, value)
  }

  get selectedItemId () {
    let value = this.getValue(`${this.formName}_${selectedKey}`)
    return value || {}
  }
  set selectedItemId (value) {
    this.setValue(`${this.formName}_${selectedKey}`, value)
  }

  get pagination () {
    let value = this.getValue(`${this.formName}_${paginationKey}`)
    return value || {}
  }
  set pagination (value) {
    this.setValue(`${this.formName}_${paginationKey}`, value)
  }

  get selectedColumns () {
    let value = this.getValue(`${this.formName}_${selectodColumnsKey}`)
    return value || null
  }
  set selectedColumns (value) {
    this.setValue(`${this.formName}_${selectodColumnsKey}`, value)
  }

  get originalColumns () {
    let value = this.getValue(`${this.formName}_${originalColumnsKey}`)
    return value || null
  }
  set originalColumns (value) {
    this.setValue(`${this.formName}_${originalColumnsKey}`, value)
  }

  get filterModel () {
    let value = this.getValue(`${this.formName}_${filterModelKey}`)
    return value || null
  }
  set filterModel (value) {
    this.setValue(`${this.formName}_${filterModelKey}`, value)
  }

  get ascFilterLists () {
    return this.reactiveStorage.ascFilterLists
  }

  set ascFilterLists (value) {
    this.reactiveStorage.ascFilterLists = value
    this.setValue(`${this.formName}_${ascFilterListsKey}`, value)
  }

  get autoPrintReportName () {
    let value = this.getValue(`${this.formName}_${autoPrintReportName}`)
    return value || ''
  }

  set autoPrintReportName (value) {
    this.setValue(`${this.formName}_${autoPrintReportName}`, value)
  }

  get autoPrintOnSave () {
    let value = this.getValue(`${this.formName}_${autoPrintOnSave}`)
    return false || value
  }

  set autoPrintOnSave (value) {
    this.setValue(`${this.formName}_${autoPrintOnSave}`, value)
  }

  get uuidsList () {
    let value = this.getValue(`${this.formName}_${uuidList}`)
    return false || value
  }

  set uuidsList (value) {
    this.setValue(`${this.formName}_${uuidList}`, value)
  }

  clear () {
    this.filter = null
    this.pagination = null
    this.selectedItemId = null
    this.selectedColumns = null
    this.filterModel = null
    this.autoPrintReportName = ''
    this.autoPrintOnSave = false
    this.uuidsList = null
  }
}
