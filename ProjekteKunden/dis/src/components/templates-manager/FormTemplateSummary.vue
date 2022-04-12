<template>
  <v-card height="100%">
    <v-card-title>
        <span>
            <div class="title">
              {{ form.name }}
              <v-btn icon small :loading="loading" @click="onFormRenameClick" title="rename" dark>
                <v-icon small>edit</v-icon>
              </v-btn>
            </div>
            <span class="subheading">{{ form.modifiedAt * 1000 | formatTimestamp }}</span>
        </span>
      <v-spacer></v-spacer>
      <v-btn icon :loading="loading" color="primary" @click="() => onFormOpenClick(form)" title="open" dark>
        <v-icon>open_in_new</v-icon>
      </v-btn>
      <v-btn icon :loading="loading" color="black" @click="() => onFormDownloadClick(form)" title="download" dark>
        <v-icon>cloud_download</v-icon>
      </v-btn>
      <v-btn icon :loading="loading" color="teal" @click="() => onFormDuplicateClick(form)" title="duplicate" dark>
        <v-icon>file_copy</v-icon>
      </v-btn>
      <v-btn icon :loading="loading" color="orange"
             :to="`/settings/templates-manager/forms/${$route.params.modelFullName}/update/${form.name}`" title="edit"
             dark>
        <v-icon>edit</v-icon>
      </v-btn>
      <v-btn icon :loading="loading" color="red" @click="() => deleteFormTemplate(form.name)" title="delete" dark :disabled="form.dataModel === 'ArchiveFile'">
        <v-icon>delete</v-icon>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <p>
        <span v-if="form.generatedFiles.filter(item => item.modified === false).length === 0">
          <v-icon color="green" small>check_circle</v-icon> All required classes and files were generated</span>
        <span v-else><v-icon color="red" small>error</v-icon> required code-files were not generated.</span>
        <generated-files :files="form.generatedFiles" :template-generated-at="form.generatedAt || 0"></generated-files>
      </p>
      <div v-if="form.customVueFile">
        <div><v-icon color="orange" small>warning</v-icon>Code for this form was customized!<generated-files :files="[form.customVueFile]" :template-generated-at="form.generatedAt || 0"></generated-files></div>
        <div><v-icon color="orange" small>warning</v-icon>This means: do NOT choose ' &check; Overwrite' in
        'Save &amp; Generate' dialog for the '.vue' file.</div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script>
import GeneratedFiles from './GeneratedFiles'
import kebabCase from 'lodash/kebabCase'
import DisTemplateService from '../../services/DisTemplateService'

export default {
  name: 'FormTemplateSummary',
  components: { GeneratedFiles },
  props: {
    form: {
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
    async deleteFormTemplate (formName) {
      const res = await this.$dialog.confirm({
        title: `Confirm deletion of form '${formName}'`,
        text: `Delete the form '${formName}', and also ALL code-files generated previously: the form template (${formName}.json), and all ".php" and ".vue*" files comprising that form.`,
        persistent: true
      })
      try {
        this.loading = true
        if (res) {
          await this.$store.dispatch('templates/deleteFormTemplate', formName)
          await this.$store.dispatch('refreshUser')
          await this.$store.dispatch('getForms')
          this.$dialog.message.success(`Form '${formName}' deleted successfully`, {
            position: 'bottom'
          })
        }
      } catch (e) {
        this.$dialog.notify.warning(`unable to delete form '${formName}': ${e.message}`, { timeout: 30000 })
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async onFormDuplicateClick (form) {
      let newName = await this.$dialog.prompt({
        title: `Duplicate form '${form.name}'`,
        text: `Enter name of new form:`
      })
      if (newName) {
        console.log('duplicate', newName, 'type', typeof newName)
        newName = kebabCase(newName)
        if (this.$store.state.templates.summary.forms.findIndex(item => item.formName === newName) > -1) {
          this.$dialog.error({
            title: 'Name of new form is invalid',
            text: `New form '${form.name}' already exists!`
          })
          return false
        }
        const service = new DisTemplateService()
        try {
          this.loading = true
          await service.duplicate('form', form.name, newName)
          this.$dialog.message.success(`Form template duplicated to: '${newName}'`)
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
            message = `error: cannot create form duplicate ${newName}.`
          }
          this.$dialog.notify.warning(message)
          console.log(e.response ? e.response : e)
        } finally {
          this.loading = false
        }
      }
    },
    async onFormOpenClick (form) {
      const service = new DisTemplateService()
      try {
        this.loading = true
        await service.openForm(form.name)
      } catch (error) {
        console.log('Cannot open form from within template-manager: ', error)
      } finally {
        this.loading = false
      }
    },
    async onFormDownloadClick (form) {
      const service = new DisTemplateService()
      try {
        this.loading = true
        const response = await service.download('form', form.name)
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', form.name + '.zip')
        document.body.appendChild(link)
        link.click()
      } catch (error) {
        console.log('Model Template Download Error', error)
      } finally {
        this.loading = false
      }
    },
    async onFormRenameClick (form) {
      const service = new DisTemplateService()
      let newName = await this.$dialog.prompt({
        title: 'Rename Form',
        text: `Enter the desired new form name:`
      })
      if (newName) {
        console.log('rename', newName, 'type', typeof newName)
        newName = kebabCase(newName)
        try {
          this.loading = true
          await service.renameForm(this.form.name, newName)
          await this.$store.dispatch('templates/refreshSummary')
          await this.$store.dispatch('refreshUser')
          await this.$store.dispatch('getForms')
        } catch (error) {
          this.$dialog.message.error(error)
        } finally {
          this.loading = false
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
