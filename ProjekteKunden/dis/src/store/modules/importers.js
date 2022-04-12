import ImporterService from '../../services/ImporterService'

const REFRESH_LIST = 'refresh importers names list'

export default {
  namespaced: true,
  state: {
    list: []
  },
  getters: {},
  actions: {
    async refreshList ({ commit }) {
      const importerService = new ImporterService()
      let data = await importerService.getList()
      commit(REFRESH_LIST, data)
      return true
    }
  },
  mutations: {
    [REFRESH_LIST] (state, payload) {
      state.list = payload
    }
  }
}
