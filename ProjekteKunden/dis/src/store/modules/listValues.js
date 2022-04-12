import ListValuesService from '../../services/ListValuesService'

const REFRESH_LIST_NAMES = 'refresh list names list'

export default {
  namespaced: true,
  state: {
    listNames: []
  },
  getters: {},
  actions: {
    async refreshListNames ({ commit }) {
      const listValuesService = new ListValuesService()
      let response = await listValuesService.getListNames()
      commit(REFRESH_LIST_NAMES, response.data)
      return true
    }
  },
  mutations: {
    [REFRESH_LIST_NAMES] (state, payload) {
      state.listNames = payload
    }
  }
}
