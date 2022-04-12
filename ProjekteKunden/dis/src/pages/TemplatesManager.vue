<template>
    <v-container fluid grid-list-md>
        <v-layout wrap>
            <v-flex sm12 md3 xl3>
                  <v-card dark>
                    <v-card-title>
                        <h3 class="mb-0">Template Manager - Main Page</h3>
                    </v-card-title>
                  </v-card>
            </v-flex>
            <v-flex sm12 md9 xl9>
                <v-combobox solo hide-details placeholder="Filter a Table Set..." clearable
                            :items="$store.state.templates.summary.modules.filter(item => item !== 'Archive')"
                            :value="$store.state.templates.modelsFilterString"
                            @input="setFilterString"
                            @input.native="setFilterString"></v-combobox>
            </v-flex>
            <v-progress-linear v-if="loading" indeterminate />
            <v-flex sm12 md3 xl3>
                <v-card class="mb-2" color="blue-grey" dark>
                    <v-card-text>
                        <h3 class="mb-3">New data model template</h3>
                        <p>
                          Select an existing table set or enter the name of a new one to assign the new data model template
                        </p>
                        <v-combobox label="Table Set" v-model="selectedModule" :items="$store.state.templates.summary.modules" clearable></v-combobox>
                        <v-layout row align-center>
                            <v-spacer></v-spacer>
                            <v-flex shrink>
                                <v-btn @click="createNewTableTemplate" :disabled="!selectedModule" small color="blue">Create</v-btn>
                            </v-flex>
                        </v-layout>
                    </v-card-text>
                </v-card>
                <v-card color="blue-grey" elevation="12" dark>
                    <v-card-text>
                      <h3 class="mb-3">Upload </h3>
                      <div><span>
                          Upload mDIS data model template or form template files.<br/><br/>
                          Download (+ optionally edit) such files by clicking on </span><span class="intra-text-icon"><v-icon>cloud_download</v-icon></span>.
                      </div>
                      <h4 class="mt-2 mb-2">Upload an exported zip file</h4>
                      <dis-file-upload :allowedTypes="['application/zip', 'application/x-zip-compressed']" :multiple="false" @uploaded="onZipUploaded"></dis-file-upload>
                      <h4 class="mt-2 mb-2">Upload a model template (*.json)</h4>
                      <dis-file-upload :allowedTypes="['application/json']" :multiple="false" @uploaded="uploadedFile => onTemplateUploaded('model', uploadedFile)"></dis-file-upload>
                      <h4 class="mt-2 mb-2">Upload a form template (*.json)</h4>
                      <dis-file-upload :allowedTypes="['application/json']" :multiple="false" @uploaded="uploadedFile => onTemplateUploaded('form', uploadedFile)"></dis-file-upload>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex sm12 md9 xl9>
                <v-layout wrap>
                    <v-flex sm12 lg6 xl4 pa-1 v-for="model in filteredModels" :key="model.table">
                        <model-template-summary :model="model"/>
                    </v-flex>
                </v-layout>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import DisTemplateService from '../services/DisTemplateService'
import ModelTemplateSummary from '../components/templates-manager/ModelTemplateSummary'
import upperFirst from 'lodash/upperFirst'
import camelCase from 'lodash/camelCase'
export default {
  name: 'TemplateManager',
  components: { ModelTemplateSummary },
  data () {
    return {
      loading: false,
      selectedModule: null
    }
  },
  computed: {
    filteredModels () {
      if (this.$store.state.templates.modelsFilterString.length === 0) {
        return this.$store.state.templates.summary.models
      } else {
        return this.$store.state.templates.summary.models.filter(item => item.fullName.toLowerCase().includes(this.$store.state.templates.modelsFilterString.toLowerCase()))
      }
    }
  },
  created () {
    this.disTemplateService = new DisTemplateService()
  },
  async mounted () {
    this.$setTitle('Templates Manager')
    try {
      this.loading = true
      await this.$store.dispatch('templates/refreshSummary')
    } catch (error) {
      this.$dialog.notify.warning(error.message, { timeout: 30000 })
      console.log(error)
    } finally {
      this.loading = false
    }
  },
  methods: {
    setFilterString (e) {
      if (!e) {
        this.$store.commit('templates/SET_MODELS_FILTER_STRING', '')
      } else if (typeof e === 'string' || !e) {
        this.$store.commit('templates/SET_MODELS_FILTER_STRING', e)
      } else if (typeof e === 'object') {
        this.$store.commit('templates/SET_MODELS_FILTER_STRING', e.srcElement.value)
      }
    },
    createNewTableTemplate () {
      setTimeout(async () => {
        let text = `this will create a new template in the table set <code>${this.selectedModule}</code>`
        if (!this.$store.state.templates.summary.modules.includes(this.selectedModule)) {
          text = `this will create a new template and a new table set with the name <code>${upperFirst(camelCase(this.selectedModule))}</code>`
        }
        const res = await this.$dialog.confirm({
          title: 'Are you sure?',
          text: text,
          persistent: true
        })
        if (res) {
          this.$router.push(`/settings/templates-manager/data-models/new/${this.selectedModule}`)
        }
      }, 200)
    },

    async onZipUploaded (uploadedFile) {
      try {
        this.loading = true
        await await this.disTemplateService.verifyUploadedZip('model', uploadedFile.name)
        this.$dialog.message.success('template was uploaded successfully')
        await this.$store.dispatch('templates/refreshSummary')
      } catch (e) {
        console.log(e, e.response)
        let message = ''
        if (e.response && e.response.status === 422) {
          message = 'Validation Error: '
          for (let i in e.response.data) {
            message += `*${e.response.data[i].field}*: ${e.response.data[i].message} `
          }
        } else if (e.response && e.response.statusText) {
          message = e.response.statusText
        } else if (e.message) {
          message = e.message
        } else {
          message = 'error: cannot upload template'
        }
        this.$dialog.notify.warning(message)
        console.log(e.response ? e.response : e)
      } finally {
        this.loading = false
      }
    },
    async onTemplateUploaded (type, uploadedFile) {
      try {
        this.loading = true
        await await this.disTemplateService.verifyUploadedTemplate(type, uploadedFile.name)
        this.$dialog.message.success('template was uploaded successfully')
        await this.$store.dispatch('templates/refreshSummary')
      } catch (e) {
        console.log(e, e.response)
        let message = ''
        if (e.response && e.response.status === 422) {
          message = 'Validation Error: '
          for (let i in e.response.data) {
            message += `*${e.response.data[i].field}*: ${e.response.data[i].message} `
          }
        } else if (e.response && e.response.statusText) {
          message = e.response.statusText
        } else if (e.message) {
          message = e.message
        } else {
          message = 'error: cannot upload template'
        }
        this.$dialog.notify.warning(message)
        console.log(e.response ? e.response : e)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.intra-text-icon {
  margin-left: 10px;
  margin-right: 10px;
}
</style>
