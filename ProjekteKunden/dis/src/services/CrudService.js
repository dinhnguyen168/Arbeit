import BackendService from './BackendService'
import axios from 'axios'

class CrudService extends BackendService {
  constructor (model) {
    super()
    if (!model) {
      throw new Error('CrudService constructor should have a model')
    }
    this.model = model
    this._controller = this.baseUrl + 'api/v1/global'
  }
  get (id, queryParams = {}) {
    return axios.get(`${this._controller}/${id}?name=${this.model}&${this.encodeQueryParams(queryParams)}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  getList (queryParams, filterParams) {
    return axios.get(`${this._controller}?name=${this.model}&${this.encodeQueryParams(queryParams)}${(filterParams) ? '&' + CrudService.encodeFilterParams(filterParams) : ''}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  post (data) {
    return axios.post(`${this._controller}?name=${this.model}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  put (id, data) {
    return axios.put(`${this._controller}/${id}?name=${this.model}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  delete (id) {
    return axios.delete(`${this._controller}/${id}?name=${this.model}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  getDefaults (data) {
    return axios.post(`${this._controller}/defaults?name=${this.model}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  getFilterLists (params) {
    return axios.get(`${this._controller}/filter-lists?name=${this.model}`, { headers: this.getBearerHeader(), params: params })
      .then(response => {
        return response
      })
  }

  getAsyncList (queryParams) {
    return axios.get(`${this._controller}/async-lists?name=${this.model}&${this.encodeQueryParams(queryParams)}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  getDuplicate (id, data) {
    return axios.post(`${this._controller}/duplicate?name=${this.model}&id=${id}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  getReports () {
    return axios.get(`${this._controller}/reports?name=${this.model}`, { headers: this.getBearerHeader() })
      .then(response => response.data)
  }

  print (id, reportName) {
    return axios.get(`${this._controller}/print?name=${this.model}&id=${id}&reportName=${reportName}`, { headers: this.getBearerHeader() })
      .then(response => response.data)
  }

  static encodeFilterParams (params) {
    let encodedParams = []
    for (let key in params) {
      encodedParams.push(`filter[${key}]=${params[key]}`)
    }
    return encodedParams.join('&')
  }
}

export default CrudService
