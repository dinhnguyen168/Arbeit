import DisTemplateService from '../../services/DisTemplateService'

const SET_TABLE_CREATED = 'set table created'
const REFRESH_SUMMARY = 'refresh summary'
const REFRESH_FORM_TEMPLATE = 'refresh form template'
const REFRESH_MODEL_TEMPLATE = 'refresh model template'
const DELETE_MODEL_TEMPLATE = 'delete model template'
const DELETE_FORM_TEMPLATE = 'delete form template'
const REFRESH_BEHAVIORS = 'refresh behaviors list'

export default {
  namespaced: true,
  state: {
    summary: {
      modules: [],
      models: [],
      forms: []
    },
    forms: [],
    models: [],
    behaviors: [],
    modelsFilterString: ''
  },
  getters: {},
  actions: {
    async refreshSummary ({ commit }) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.getSummary()
      commit(REFRESH_SUMMARY, response.data)
      return true
    },
    async refreshBehaviors ({ commit }) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.getBehaviors()
      commit(REFRESH_BEHAVIORS, response.data)
      return true
    },
    async createFormTemplate ({ commit }, { template, subForms, supForms }) {
      console.log({ template, subForms, supForms })
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.createForm(template, subForms, supForms)
      commit(REFRESH_MODEL_TEMPLATE, { formName: response.data.name, template: response.data })
      return response.data
    },
    async updateFormTemplate ({ commit }, { template, subForms, supForms }) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.updateForm(template.name, template, subForms, supForms)
      commit(REFRESH_MODEL_TEMPLATE, { formName: response.data.name, template: response.data })
      return response.data
    },
    async deleteFormTemplate ({ commit }, name) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.deleteForm(name)
      if (response.status === 204) {
        commit(DELETE_FORM_TEMPLATE, name)
      }
    },
    async getFormTemplate ({ commit }, formName) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.getFormTemplate(formName)
      commit(REFRESH_FORM_TEMPLATE, { formName: formName, template: response.data })
      return true
    },
    async createModelTemplate ({ commit }, modelTemplate) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.createModel(modelTemplate)
      commit(REFRESH_MODEL_TEMPLATE, { modelFullName: response.data.fullName, template: response.data })
      return response.data
    },
    async updateModelTemplate ({ commit }, { fullName, template }) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.updateModel(fullName, template)
      commit(REFRESH_MODEL_TEMPLATE, { modelFullName: response.data.fullName, template: response.data })
      return response.data
    },
    async deleteModelTemplate ({ commit }, fullName) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.deleteModel(fullName)
      if (response.status === 204) {
        commit(DELETE_MODEL_TEMPLATE, fullName)
      }
    },
    async createModelTable ({ dispatch, commit }, fullName) {
      const disTemplateService = new DisTemplateService()
      await disTemplateService.createModelTable(fullName)
      return true
    },
    async getModelTemplate ({ commit }, modelFullName) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.getModelTemplate(modelFullName)
      commit(REFRESH_MODEL_TEMPLATE, { modelFullName: modelFullName, template: response.data })
      return response.data
    },
    async cgView ({ commit }, { id, generatorAttributes, generate, answers }) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.cgView(id, generatorAttributes, generate, answers)
      return response
    },
    async cgDiff ({ commit }, { id, generatorAttributes, file }) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.cgDiff(id, generatorAttributes, file)
      return response
    },
    async cgPreview ({ commit }, { id, generatorAttributes, file }) {
      const disTemplateService = new DisTemplateService()
      let response = await disTemplateService.cgPreview(id, generatorAttributes, file)
      return response
    }
  },
  mutations: {
    [REFRESH_SUMMARY] (state, payload) {
      state.summary = payload
    },
    [REFRESH_BEHAVIORS] (state, payload) {
      state.behaviors = payload
    },
    [REFRESH_FORM_TEMPLATE] (state, { formName, template }) {
      state.forms = [
        ...state.forms.filter(item => item.name !== formName),
        template
      ]
    },
    [REFRESH_MODEL_TEMPLATE] (state, { modelFullName, template }) {
      state.models = [
        ...state.models.filter(item => item.fullName !== modelFullName),
        template
      ]
    },
    [SET_TABLE_CREATED] (state, { timestamp, modelName }) {
      // @TODO implement this
    },
    [DELETE_MODEL_TEMPLATE] (state, fullName) {
      console.log('COMMIT delete model', fullName)
      console.log(state.summary.forms)
      state.summary.forms = state.summary.forms.filter(item => item.dataModel !== fullName)
      console.log(state.summary.forms)
      state.summary.models = state.summary.models.filter(item => item.fullName !== fullName)
      state.forms = state.forms.filter(item => item.dataModel !== fullName)
      state.models = state.models.filter(item => item.fullName !== fullName)
    },
    [DELETE_FORM_TEMPLATE] (state, name) {
      state.summary.forms = state.summary.forms.filter(item => item.name !== name)
      state.forms = state.forms.filter(item => item.name !== name)
    },
    SET_MODELS_FILTER_STRING (state, payload) {
      state.modelsFilterString = payload
    }
  }
}
