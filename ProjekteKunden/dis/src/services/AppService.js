import BackendService from './BackendService'
import axios from 'axios'

class AppService extends BackendService {
  constructor () {
    super()
    this._controller = this.baseUrl + 'api/v1/app'
  }
  getForms () {
    return axios.get(`${this._controller}/forms`, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  getReports () {
    return axios.get(`${this._controller}/reports`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }

  async getAppConfig (key = null) {
    const response = await axios.get(`${this._controller}/config${key ? `/${key}` : ``}`, { headers: this.getBearerHeader() })
    return response.data
  }

  async saveAppConfig (appConfig) {
    const response = await axios.post(`${this._controller}/config`, appConfig, { headers: this.getBearerHeader() })
    return response.data
  }

  async getUserConfig (key = null) {
    const response = await axios.get(`${this._controller}/user-config${key ? `/${key}` : ``}`, { headers: this.getBearerHeader() })
    return response.data
  }

  async saveUserConfig (userConfig) {
    const response = await axios.post(`${this._controller}/user-config`, userConfig, { headers: this.getBearerHeader() })
    return response.data
  }

  async findIgsn (igsn) {
    const response = await axios.get(`${this._controller}/find-igsn/${igsn}`, { headers: this.getBearerHeader() })
    return response.data
  }

  async getMessages () {
    const response = await axios.get(`${this._controller}/get-messages`, { headers: this.getBearerHeader() })
    return response.data
  }
}

export default AppService
