import BackendService from './BackendService'
import axios from 'axios'

class AppService extends BackendService {
  constructor () {
    super()
    this._controller = this.baseUrl + 'api/v1/file'
  }
  get (queryParams) {
    return axios.get(`${this._controller}?${this.encodeQueryParams(queryParams)}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  updateSelectValues (selectName, value) {
    return axios.get(`${this._controller}/update-select-values?name=${selectName}&value=${value}`, { headers: this.getBearerHeader() })
      .then(response => response.data)
  }

  assign (formData) {
    return axios.post(`${this._controller}/assign`, formData, { headers: this.getBearerHeader() })
      .then(response => response.data)
  }

  delete (formData) {
    return axios.post(`${this._controller}/delete`, formData, { headers: this.getBearerHeader() })
      .then(response => response.data)
  }

  upload (formData, progressCallback) {
    console.log(formData)
    const config = {
      headers: {
        'Content-Type': 'multipart/form-data',
        ...this.getBearerHeader()
      },
      onUploadProgress: function (progressEvent) {
        let percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        progressCallback(percentCompleted)
      }
    }
    return axios.post(`${this._controller}/upload`, formData, config)
      .then(response => response.data)
  }

  metaData (fileName) {
    const queryParams = { filename: fileName }
    return axios.get(`${this._controller}/meta-data?${this.encodeQueryParams(queryParams)}`, { headers: this.getBearerHeader() })
      .then(response => response.data)
  }

  unassign (id) {
    return axios.put(`${this._controller}/unassign/${id}`, null, { headers: this.getBearerHeader() })
      .then(response => response)
  }
}

export default AppService
