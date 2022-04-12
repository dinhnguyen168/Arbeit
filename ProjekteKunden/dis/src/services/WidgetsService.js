import BackendService from './BackendService'
import axios from 'axios'

class WidgetsService extends BackendService {
  constructor () {
    super()
    this._controller = this.baseUrl + 'api/v1/widgets'
  }

  get (id = null) {
    return axios.get(`${this._controller}?sort=order`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  post (data) {
    return axios.post(`${this._controller}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  duplicate (id) {
    return axios.post(`${this._controller}/duplicate/${id}`, null, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  /**
   * used to update a single or multiple records
   * @param id pk of the record to update. If null, data must be an array of the records to update
   * @param data an object of a record data or an array of data objects (every object must have an id property)
   * @returns {Promise<AxiosResponse<any> | never>}
   */
  put (id = null, data) {
    return axios.put(`${this._controller}${id ? '/' + id : ''}`, data, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  delete (id) {
    return axios.delete(`${this._controller}/${id}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }
}

export default WidgetsService
