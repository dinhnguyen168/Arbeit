import AppService from '../../services/AppService'

const appService = new AppService()
const SET_APP_CONFIG = 'set app config'
const SET_USER_CONFIG = 'set user config'

export default {
  namespaced: true,
  state: {
    appConfig: {},
    userConfig: {}
  },
  getters: {
    appName: state => {
      return state.appConfig['AppName'] || 'Drilling Information System'
    },
    appShortName: state => {
      return state.appConfig['AppShortName'] || 'mDIS'
    },
    copyrightText: state => {
      return state.appConfig['CopyrightText'] || 'ICDP 2021'
    },
    copyrightLink: state => {
      return state.appConfig['CopyrightLink'] || 'https://www.icdp-online.org/'
    },
    aboutLink: state => {
      return state.appConfig['AboutLink'] || 'https://data.icdp-online.org/mdis-docs/'
    },
    helpLink: state => {
      return state.appConfig['HelpLink'] || 'https://data.icdp-online.org/mdis-docs/'
    },
    canSendEmails: state => {
      return (state.appConfig['CanSendEmails']) ? state.appConfig['CanSendEmails'] : false
    },
    appIcon: state => {
      return 'img/logos/' + (state.appConfig['AppIcon'] ? state.appConfig['AppIcon'] : 'icon_mDIS.png')
    }
  },
  actions: {
    async refreshAppConfig ({ commit }) {
      const data = await appService.getAppConfig()
      commit(SET_APP_CONFIG, data)
    },
    async refreshUserConfig ({ commit }) {
      const data = await appService.getUserConfig()
      commit(SET_USER_CONFIG, data)
    },
    async saveUserConfig ({ commit }, userConfig) {
      const data = await appService.saveUserConfig(userConfig)
      commit(SET_USER_CONFIG, data)
    }
  },
  mutations: {
    [SET_APP_CONFIG] (state, payload) {
      state.appConfig = payload
    },
    [SET_USER_CONFIG] (state, payload) {
      state.userConfig = payload
    }
  }
}
