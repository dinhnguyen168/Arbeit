import Vue from 'vue'
import Vuex from 'vuex'
import templates from './modules/templates'
import listValues from './modules/listValues'
import importers from './modules/importers'
import config from './modules/config'
import AppService from '../services/AppService'
import WidgetsService from '../services/WidgetsService'
import account from './modules/account'
import ls from 'local-storage'

Vue.use(Vuex)
const defaultSnackbar = {
  visible: false,
  y: null,
  x: null,
  color: 'info',
  timeout: 6000,
  mobile: false,
  multiLine: true,
  text: ''
}
const store = new Vuex.Store({
  modules: {
    templates,
    listValues,
    importers,
    config,
    account
  },
  state: {
    loggedInUser: null,
    snackbar: defaultSnackbar,
    isDark: true,
    appForms: [],
    gitInfo: {
      version: process.env.VERSION,
      commit: process.env.COMMIT,
      branch: process.env.BRANCH,
      commitdate: process.env.COMMITDATE
    },
    widgets: [],
    compactUI: ls.get('compact') ? ls.get('compact') : false
  },
  mutations: {
    TOGGLE_COMPACT_UI (state) {
      state.compactUI = !state.compactUI
    },
    SET_LOGGED_IN_USER (state, user) {
      state.loggedInUser = user
    },
    SHOW_SNACKBAR (state, payload) {
      state.snackbar.multiline = payload.text && payload.text.length > 50
      state.snackbar = Object.assign(defaultSnackbar, payload)
      state.snackbar.visible = true
    },
    CLOSE_SNACKBAR (state) {
      state.snackbar.visible = false
      state.snackbar = defaultSnackbar
    },
    IS_DARK (state, payload) {
      state.isDark = payload
    },
    APP_FORMS (state, payload) {
      state.appForms = payload
    },
    SET_WIDGETS (state, payload) {
      state.widgets = payload
    },
    SET_WIDGET_SETTINGS (state, { id, data }) {
      state.widgets.find(item => item.id === id).title = data.title
      state.widgets.find(item => item.id === id).subtitle = data.subtitle
      state.widgets.find(item => item.id === id).color = data.color
      state.widgets.find(item => item.id === id).is_dark = data.is_dark
      state.widgets.find(item => item.id === id).active = data.active
      state.widgets.find(item => item.id === id).xs_size = data.xs_size
      state.widgets.find(item => item.id === id).sm_size = data.sm_size
      state.widgets.find(item => item.id === id).md_size = data.md_size
      state.widgets.find(item => item.id === id).lg_size = data.lg_size
      state.widgets.find(item => item.id === id).extraSettings = data.extraSettings
    },
    SET_ACTIVE_WIDGETS_ORDER (state, activeWidgets) {
      for (let i = 0; i < state.widgets.length; i++) {
        const activeWidget = activeWidgets.find(item => item.id === state.widgets[i].id)
        if (activeWidget) {
          state.widgets[i].order = activeWidget.order
          state.widgets[i].active = 1
        } else {
          state.widgets[i].active = 0
        }
      }
    },
    ADD_WIDGET (state, widget) {
      state.widgets.push(widget)
    },
    REMOVE_WIDGET (state, id) {
      state.widgets = state.widgets.filter(item => item.id !== id)
    }
  },
  actions: {
    async initApp ({ commit, dispatch }) {
      // @TODO check token expiration
      await dispatch('config/refreshAppConfig')
      if (AppService.user) {
        commit('SET_LOGGED_IN_USER', AppService.user)
        await dispatch('templates/refreshSummary')
        await dispatch('getForms')
        await dispatch('config/refreshUserConfig')
        // await dispatch('account/getProfile')
      }
    },
    async getForms ({ commit, getters }) {
      const appService = new AppService()
      let response = await appService.getForms()
      let unorderedFlat = []
      Object.keys(response.data).forEach(key => {
        unorderedFlat = [...unorderedFlat, ...response.data[key]]
      })
      // remove files form from list
      unorderedFlat = unorderedFlat.filter(item => item.key !== 'files')
      unorderedFlat.sort((a, b) => {
        const aIndex = getters.orderedDataModels.findIndex(item => item === a.dataModel)
        const bIndex = getters.orderedDataModels.findIndex(item => item === b.dataModel)
        // first deal with orphan data models
        if (bIndex === -1) return -1
        if (aIndex === -1) return 1
        // then the normal case
        return aIndex - bIndex
      })
      const orderedKeyed = unorderedFlat.reduce((a, c) => {
        if (!a[c.module]) {
          a[c.module] = [c]
        } else {
          a[c.module].push(c)
        }
        return a
      }, {})
      commit('APP_FORMS', orderedKeyed)
      return true
    },
    async refreshUser ({ commit }) {
      const appService = new AppService()
      let response = await appService.refresh()
      commit('SET_LOGGED_IN_USER', response)
    },
    async getWidgets ({ commit }) {
      const widgetService = new WidgetsService()
      const data = await widgetService.get()
      commit('SET_WIDGETS', data)
    },
    async reorderActiveWidgets ({ commit, state }, orderedActiveWidgets) {
      const widgetService = new WidgetsService()
      const inactiveWidgets = state.widgets
        .filter(item => !orderedActiveWidgets.find(item2 => item2.id === item.id))
        .map(item => ({ id: item.id, active: 0 }))
      const activeWidgets = orderedActiveWidgets.map((item, index) => ({ id: item.id, order: index, active: 1 }))
      commit('SET_ACTIVE_WIDGETS_ORDER', activeWidgets)
      await widgetService.put(null, [...activeWidgets, ...inactiveWidgets])
    },
    async saveWidgetSettings ({ commit }, { id, data }) {
      const widgetService = new WidgetsService()
      const result = await widgetService.put(id, data)
      commit('SET_WIDGET_SETTINGS', { id: result.id, data: result })
    },
    async duplicateWidget ({ commit }, { id }) {
      const widgetService = new WidgetsService()
      const result = await widgetService.duplicate(id)
      commit('ADD_WIDGET', result)
    },
    async deleteWidget ({ commit }, { id }) {
      const widgetService = new WidgetsService()
      await widgetService.delete(id)
      commit('REMOVE_WIDGET', id)
    }
  },
  getters: {
    loggedInUser (state) {
      return state.loggedInUser
    },
    snackbar (state) {
      return state.snackbar
    },
    isDark (state) {
      return state.isDark
    },
    userCanEditDashboard (state) {
      return state.loggedInUser && (state.loggedInUser.roles.includes(`sa`) || state.loggedInUser.roles.includes(`developer`))
    },
    orderedDataModels (state) {
      const dataModelsInfo = state.templates.summary.models.map(item => ({ fullName: item.fullName, parentModel: item.parentModel }))
      const orderedModels = []
      // start with Program. It's always the greatest grandfather
      let nextIndex = dataModelsInfo.findIndex(item => item.fullName === 'ProjectProgram')
      do {
        orderedModels.push(dataModelsInfo[nextIndex])
        dataModelsInfo.slice(nextIndex, 1)
        nextIndex = dataModelsInfo.findIndex(item => item.parentModel === orderedModels[orderedModels.length - 1].fullName)
      } while (nextIndex > -1)
      return orderedModels.map(item => item.fullName)
    },
    getCompactUI (state) {
      return state.compactUI
    }
  }
})

export default store
