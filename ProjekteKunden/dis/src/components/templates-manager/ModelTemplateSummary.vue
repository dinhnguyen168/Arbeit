<template>
    <v-card class="c-model-template-summary" height="100%">
        <v-card-title>
            <span>
                <div class="title">{{model.name}} <small>({{model.fullName}})</small></div>
                <span class="subheading">{{model.modifiedAt * 1000 | formatTimestamp}}</span>
            </span>
            <v-spacer></v-spacer>
            <v-btn icon :loading="loading" color="black" @click="() => onModelDownloadClick(model)" title="download" dark>
                <v-icon>cloud_download</v-icon>
            </v-btn>
            <v-btn icon :loading="loading" color="teal darken-4" @click="() => onModelDuplicateClick(model)" title="duplicate" dark>
                <v-icon>file_copy</v-icon>
            </v-btn>
            <v-btn icon :loading="loading" color="orange" :to="`/settings/templates-manager/data-models/update/${model.fullName}`" title="edit" dark>
                <v-icon>edit</v-icon>
            </v-btn>
            <v-btn icon :loading="loading" color="red" @click="deleteModelTemplate" title="delete" dark :disabled="model.fullName === 'ArchiveFile'">
                <v-icon>delete</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text>
            <p v-if="model.isTableCreated" style="vertical-align: center;">
                <v-icon color="green" small>check_circle</v-icon> Table was created in DB <span v-if="model.tableGenerationTimestamp"> at {{ model.tableGenerationTimestamp * 1000 | formatTimestamp}}.</span><span v-else>, (no timestamp available).</span>
            </p>
            <p v-else>
                <v-icon color="red" small>error</v-icon> Table not yet created in the database.
            </p>
            <p>
                <span v-if="model.generatedFiles.filter(item => item.modified === false).length === 0"><v-icon color="green" small>check_circle</v-icon> All required code-files were generated.</span>
                <span v-else><v-icon color="red" small>error</v-icon> required code-files were not generated: </span>
                <generated-files :files="model.generatedFiles" :template-generated-at="model.generatedAt || 0"></generated-files>
            </p>
        </v-card-text>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn :to="`/settings/templates-manager/forms/${model.fullName}`" :disabled="model.generatedFiles.filter(item => item.modified).length !== model.generatedFiles.length " small color="teal">
                manage forms
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import GeneratedFiles from './GeneratedFiles'
import upperFirst from 'lodash/upperFirst'
import camelCase from 'lodash/camelCase'
import DisTemplateService from '../../services/DisTemplateService'

export default {
  name: 'ModelTemplateSummary',
  components: { GeneratedFiles },
  props: {
    model: {
      required: true,
      type: Object
    }
  },
  data () {
    return {
      loading: false
    }
  },
  methods: {
    async deleteModelTemplate () {
      this.loading = true
      try {
        const res = await this.$dialog.confirm({
          title: 'Are you sure?',
          text: 'This will deletes the model template, table and it all the generated files that are based on this model.',
          persistent: true
        })
        if (res) {
          await this.$store.dispatch('templates/deleteModelTemplate', this.model.fullName)
          this.$dialog.message.success('deleted successfully', {
            position: 'bottom'
          })
        }
      } catch (e) {
        this.$dialog.notify.warning(`unable to delete: ${e.message}`)
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async onModelDuplicateClick (model) {
      let newName = await this.$dialog.prompt({
        title: 'Duplicate Name',
        text: `enter the desired model name (the model name will be prefixed with module name ${model.module})`
      })
      newName = upperFirst(camelCase(newName))
      if (this.$store.state.templates.summary.models.findIndex(item => item.fullName === model.module + newName) > -1) {
        this.$dialog.error({
          title: 'Model name is invalid',
          text: `model name ${model.module + newName} already exists`
        })
        return false
      }
      const service = new DisTemplateService()
      try {
        this.loading = true
        await service.duplicate('model', model.fullName, model.module + newName)
        this.$dialog.message.success('template was duplicated successfully')
        await this.$store.dispatch('templates/refreshSummary')
      } catch (e) {
        let message = ''
        if (e.response && e.response.status === 422) {
          message = 'Validation Error'
          for (let i in e.response.data) {
            message += `*${e.response.data[i].field}*: ${e.response.data[i].message} `
          }
        } else if (e.response && e.response.statusText) {
          message = e.response.statusText
        } else {
          message = 'error: cannot duplicate template'
        }
        this.$dialog.notify.warning(message)
        console.log(e.response ? e.response : e)
      } finally {
        this.loading = false
      }
    },
    async onModelDownloadClick (model) {
      const service = new DisTemplateService()
      try {
        this.loading = true
        const response = await service.download('model', model.fullName)
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', model.fullName + '.zip')
        document.body.appendChild(link)
        link.click()
      } catch (error) {
        console.log('Model Template Download Error', error)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>

</style>
