import BackendService from './BackendService'
import axios from 'axios'

class AccountService extends BackendService {
  constructor () {
    super()
    this._controller = this.baseUrl + 'api/v1/account'
  }

  async updateProfile (data) {
    return axios.put(`${this._controller}`, data, { headers: this.getBearerHeader() })
  }

  async changePassword (data) {
    return axios.post(`${this._controller}/change-password`, data, { headers: this.getBearerHeader() })
  }

  async requestRecoveryEmail (email) {
    return axios.post(`${this._controller}/send-recovery-email`, { email })
  }

  async checkRecoveryLink (userId, code) {
    return axios.post(`${this._controller}/check-recovery-link`, { userId, code })
  }
  async resetPassord (userId, code, password) {
    return axios.post(`${this._controller}/reset-password`, { userId, code, password })
  }
}

export default AccountService
