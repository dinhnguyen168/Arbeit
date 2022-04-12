import BackendService from './BackendService'
import axios from 'axios'

class CrudService extends BackendService {
  constructor () {
    super()
    this._controller = this.baseUrl + 'api/v1/importer'
  }

  getList () {
    return axios.get(`${this._controller}`, { headers: this.getBearerHeader() })
      .then(response => {
        return response.data
      })
  }
}

export default CrudService
