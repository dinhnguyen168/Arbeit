import BackendService from './BackendService'
import axios from 'axios'

class ListValuesService extends BackendService {
  constructor (model) {
    super()
    this._controller = this.baseUrl + 'api/v1/list-values'
  }
  get (id, queryParams = {}) {
    return axios.get(`${this._controller}/${id}?${this.encodeQueryParams(queryParams)}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  getList (queryParams, filterParams) {
    return axios.get(`${this._controller}?${this.encodeQueryParams(queryParams)}${(filterParams) ? '&' + this.encodeFilterParams(filterParams) : ''}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  post (data) {
    return axios.post(`${this._controller}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  put (id, data) {
    return axios.put(`${this._controller}/${id}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  delete (id) {
    return axios.delete(`${this._controller}/${id}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  getListNames () {
    return axios.get(`${this._controller}/list-names`, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  getListInfo (listName) {
    return axios.get(`${this._controller}/list?listname=${listName}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  updateListInfo (listName, data) {
    return axios.put(`${this._controller}/list?listname=${listName}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  encodeFilterParams (params) {
    let encodedParams = []
    for (let key in params) {
      encodedParams.push(`filter[${key}]=${params[key]}`)
    }
    return encodedParams.join('&')
  }
}

export default ListValuesService
