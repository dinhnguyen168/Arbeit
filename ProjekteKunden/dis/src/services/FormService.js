import CrudService from './CrudService'
import axios from 'axios'
import FormLocalStorage from '../util/FormLocalStorage'

class FormService extends CrudService {
  constructor (model) {
    super(model)
    this._controller = this.baseUrl + 'api/v1/form'
  }

  localStorage = new FormLocalStorage(this.model)

  getList (queryParams, filterParams) {
    return axios.get(`${this._controller}?name=${this.model}&${this.encodeQueryParams(queryParams)}${(filterParams) ? '&' + CrudService.encodeFilterParams(filterParams) : ''}`, { headers: this.getBearerHeader() })
      .then(response => {
        this.setUuidsInStorage(response.data.items)
        response.data.items = this.loadMissedUuid(response.data.items)
        return response.data
      })
  }

  get (id, queryParams = {}) {
    return axios.get(`${this._controller}/${id}?name=${this.model}&${this.encodeQueryParams(queryParams)}`, { headers: this.getBearerHeader() })
      .then(response => {
        this.setUuidsInStorageSingleItem(response.data)
        response.data = this.loadMissedUuidSingleItem(response.data)
        return response.data
      })
  }

  post (data) {
    return axios.post(`${this._controller}?name=${this.model}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        this.setUuidsInStorageSingleItem(response.data)
        response.data = this.loadMissedUuidSingleItem(response.data)
        return response
      })
  }

  put (id, data) {
    return axios.put(`${this._controller}/${id}?name=${this.model}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        this.setUuidsInStorageSingleItem(response.data)
        response.data = this.loadMissedUuidSingleItem(response.data)
        return response
      })
  }

  getDuplicate (id, data) {
    return axios.post(`${this._controller}/duplicate?name=${this.model}&id=${id}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        this.setUuidsInStorageSingleItem(response.data)
        response.data = this.loadMissedUuidSingleItem(response.data)
        return response
      })
  }

  checkIfValidUUID (str) {
    // Regular expression to check if string is a valid UUID
    const regexExp = /^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/gi
    return regexExp.test(str)
  }

  loadMissedUuid (items) {
    const uuids = this.localStorage.uuidsList
    for (const [itemKey, itemValue] of Object.entries(items)) {
      for (let [key, value] of Object.entries(itemValue)) {
        if (typeof value === 'string' && this.checkIfValidUUID(value)) {
          if (value) {
            uuids.forEach(uuid => {
              if (value === uuid.uuid) {
                items[itemKey][key] = uuid.data
              }
            })
          }
        }
        if (Array.isArray(value)) {
          for (const [subKey, subValue] of Object.entries(value)) {
            if (subValue) {
              uuids.forEach(uuid => {
                if (subValue === uuid.uuid) {
                  items[itemKey][key][subKey] = uuid.data
                }
              })
            }
          }
        }
      }
    }
    this.localStorage.uuidsList = null
    this.localStorage.removeKey('@uuids')
    return items
  }

  loadMissedUuidSingleItem (item) {
    const uuids = this.localStorage.uuidsList
    for (let [key, value] of Object.entries(item)) {
      if (typeof value === 'string' && this.checkIfValidUUID(value)) {
        if (value) {
          uuids.forEach(uuid => {
            if (value === uuid.uuid) {
              item[key] = uuid.data
            }
          })
        }
      }
      if (Array.isArray(value)) {
        for (const [subKey, subValue] of Object.entries(value)) {
          if (subValue) {
            uuids.forEach(uuid => {
              if (subValue === uuid.uuid) {
                item[key][subKey] = uuid.data
              }
            })
          }
        }
      }
    }
    this.localStorage.uuidsList = null
    this.localStorage.removeKey('@uuids')
    return item
  }

  setUuidsInStorage (items) {
    let uuids = []
    for (let item of items) {
      for (const [key, value] of Object.entries(item)) {
        if (key && typeof value === 'object') {
          if (value && value.hasOwnProperty('@uuid')) {
            if (Object.keys(value).length > 1) {
              uuids.push({ uuid: value['@uuid'], data: value })
            }
          }
        }
        if (Array.isArray(value)) {
          value.forEach(item => {
            if (item && item.hasOwnProperty('@uuid')) {
              if (Object.keys(item).length > 1) {
                uuids.push({ uuid: item['@uuid'], data: item })
              }
            }
          })
        }
      }
    }
    this.localStorage.uuidsList = uuids
  }

  setUuidsInStorageSingleItem (item) {
    let uuids = []
    for (const [key, value] of Object.entries(item)) {
      if (key && typeof value === 'object') {
        if (value && value.hasOwnProperty('@uuid')) {
          if (Object.keys(value).length > 1) {
            uuids.push({ uuid: value['@uuid'], data: value })
          }
        }
      }
      if (Array.isArray(value)) {
        value.forEach(item => {
          if (item && item.hasOwnProperty('@uuid')) {
            if (Object.keys(item).length > 1) {
              uuids.push({ uuid: item['@uuid'], data: item })
            }
          }
        })
      }
    }
    this.localStorage.uuidsList = uuids
  }
}

export default FormService
