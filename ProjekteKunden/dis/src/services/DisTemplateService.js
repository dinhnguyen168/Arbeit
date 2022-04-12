import BackendService from './BackendService'
import axios from 'axios'

class DisTemplateService extends BackendService {
  constructor () {
    super()
    this._controller = this.baseUrl + 'cg/api'
  }

  getSummary () {
    return axios.get(`${this._controller}/summary`, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  getBehaviors () {
    return axios.get(`${this._controller}/behaviors`, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  getModelTemplate (modelFullName) {
    return axios.get(`${this._controller}/get-model-template?name=${modelFullName}`, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  getFormTemplate (formName) {
    return axios.get(`${this._controller}/get-form-template?name=${formName}`, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  getFormTemplateSeed (modelName) {
    return axios.get(`${this._controller}/get-form-template-seed?modelName=${modelName}`, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  createForm (template, availableSubForms, availableSupForms) {
    return axios.post(`${this._controller}/create-form`, { template: Object.assign(template, { availableSubForms, availableSupForms }) }, { headers: this.getBearerHeader() })
      .then(response => {
        return response
      })
  }

  updateForm (formName, template, availableSubForms, availableSupForms) {
    return axios.put(`${this._controller}/update-form?name=${formName}`, { template: Object.assign(template, { availableSubForms, availableSupForms }) }, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  deleteForm (name) {
    return axios.delete(`${this._controller}/delete-form?name=${name}`, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  createModel (model) {
    return axios.post(`${this._controller}/create-model`, { template: model }, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  updateModel (fullName, template) {
    return axios.post(`${this._controller}/update-model?name=${fullName}`, { template }, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  deleteModel (fullName) {
    return axios.delete(`${this._controller}/delete-model?name=${fullName}`, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  duplicate (type, oldName, newName) {
    return axios.post(`${this._controller}/duplicate?type=${type}&oldName=${oldName}&newName=${newName}`, {}, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  verifyUploadedTemplate (type, filename) {
    return axios.post(`${this._controller}/verify-uploaded-template?type=${type}&filename=${filename}`, {}, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  verifyUploadedZip (type, filename) {
    return axios.post(`${this._controller}/verify-uploaded-zip?filename=${filename}`, {}, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  createModelTable (model) {
    return axios.post(`${this._controller}/create-model-table`, { model }, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  cgView (id, generatorAttributes, generate, answers) {
    return axios.post(`${this._controller}/view?id=${id}`, { Generator: generatorAttributes, generate, answers }, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  cgDiff (id, generatorAttributes, file) {
    return axios.post(`${this._controller}/diff?id=${id}&file=${file}`, { Generator: generatorAttributes }, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  cgPreview (id, generatorAttributes, file) {
    return axios.post(`${this._controller}/preview?id=${id}&file=${file}`, { Generator: generatorAttributes }, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  download (type, name) {
    return axios({
      url: `${this._controller}/download?type=${type}&name=${name}`,
      method: 'GET',
      headers: this.getBearerHeader(),
      responseType: 'blob'
    }).then(response => response)
  }

  renameForm (name, newName) {
    console.log(this.getBearerHeader)
    return axios.post(`${this._controller}/rename-form?name=${name}&newName=${newName}`, {}, { headers: this.getBearerHeader() })
      .then(response => response)
  }

  openForm (formName) {
    window.location = `${this.baseUrl}#/forms/${formName}-form`
  }
}

export default DisTemplateService
