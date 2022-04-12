import AccountService from '../../services/AccountService'

const accountService = new AccountService()

export default {
  namespaced: true,
  state: {
  },
  actions: {
    async updateProfile ({ commit }, payload) {
      return accountService.updateProfile(payload)
    },
    async changePassword ({ commit }, payload) {
      return accountService.changePassword(payload)
    }
  }
}
