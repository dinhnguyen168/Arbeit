import axios from 'axios'
import ls from 'local-storage'
// import store from '../store'
// import router from '../router'
// import DisLoginDialog from '../components/DisLoginDialog'
// import { create } from 'vue-modal-dialogs'
// const loginDialog = create(DisLoginDialog)

class BackendService {
  constructor () {
    this.baseUrl = window.baseUrl
    this._authController = this.baseUrl + 'api/v1/auth'
  }

  static get user () {
    const user = ls.get(window.baseUrl + 'user')
    return user || null
  }

  static set user (val) {
    ls.set(window.baseUrl + 'user', val)
  }

  /**
   * Get last axios error
   * @returns null | Axios error.response
   */
  static get lastError () {
    return BackendService._lastError ? BackendService._lastError : null
  }

  /**
   * Set last axios error
   * Called in bootstrap.js
   * @param val Axios error.response
   */
  static set lastError (val) {
    BackendService._lastError = val
  }

  isLoggedIn () {
    return !!BackendService.user && !!BackendService.user.token
  }

  getBearerHeader () {
    if (this.isLoggedIn()) {
      return {
        'Authorization': 'Bearer ' + BackendService.user.token
      }
    }
    return {}
  }

  login ({ username, password }) {
    return new Promise((resolve, reject) => {
      axios.post(`${this._authController}/login`, { username, password }, {
        'Cache-Control': 'no-store, no-cache, must-revalidate'
      })
        .then(response => {
          if (response.status === 200) {
            BackendService.user = response.data
            resolve(response.data)
          }
        })
        .catch(e => reject(e))
    })
  }

  refresh () {
    return new Promise((resolve, reject) => {
      axios.get(`${this._authController}/refresh`, {
        'Cache-Control': 'no-store, no-cache, must-revalidate',
        headers: this.getBearerHeader()
      })
        .then(response => {
          if (response.status === 200) {
            BackendService.user = response.data
            resolve(response.data)
          }
        })
        .catch(e => reject(e))
    })
  }

  logout () {
    return new Promise((resolve, reject) => {
      axios.post(`${this._authController}/logout`, null, { headers: this.getBearerHeader() })
        .then((done) => {
          if (!done) {
            console.warn('was not able to regenerate token on server')
          }
          resolve()
        })
        .catch(e => reject(e))
        .finally(() => {
          // set user to null even if an error on server happened
          BackendService.user = null
        })
    })
  }

  encodeQueryParams (paramsObj) {
    if (!paramsObj) {
      return ''
    }
    let str = []
    for (let p in paramsObj) {
      if (paramsObj.hasOwnProperty(p)) {
        str.push(encodeURIComponent(p) + '=' + encodeURIComponent(paramsObj[p]))
      }
    }
    return str.join('&')
  }

  static bootstrap () {
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
    // axios.defaults.baseURL = process.env.NODE_ENV === 'production' ? '' : 'http://localhost:8000'
    // let token = document.head.querySelector('meta[name="csrf-token"]')
    // if (token) {
    //   axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
    // } else {
    //   console.error('CSRF token not found')
    // }
  }
}

export default BackendService
