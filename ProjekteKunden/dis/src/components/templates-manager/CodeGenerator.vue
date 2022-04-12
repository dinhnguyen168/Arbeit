<template>
    <div class="c-code-generator">
        <v-layout row align-center>
            <h3>Code Generator</h3>
            <v-progress-circular class="ml-2" v-if="loading" size="28" indeterminate />
            <v-spacer />
            <v-btn color="info" @click="cgView(false)" :disabled="loading">Preview</v-btn>
            <v-btn color="green" @click="cgView(true)" :disabled="loading" v-if="files && files.length > 0">
                generate
            </v-btn>
        </v-layout>
        <v-alert v-model="showAlert" :class="{ 'error': hasError, 'info': !hasError, 'c-code-generator__results-overflow': true }" dismissible>
            <pre class="c-code-generator__results">{{results}}</pre>
        </v-alert>
        <div class="c-code-generator__table-overflow">
            <table class="c-code-generator__preview-table" v-if="files && files.length > 0">
                <tr>
                    <th>File</th>
                    <th>Operation</th>
                    <th></th>
                </tr>
                <tr v-for="file in files" :key="file.id">
                    <td>{{file.path}}</td>
                    <td>
                        <v-checkbox v-model="answers[file.id]" :label="file.operation" :value="file.id" hide-details :disabled="file.operation === 'skip'"></v-checkbox>
                    </td>
                    <td>
                        <v-layout row>
                            <v-btn @click="() => showContent(file.id)" flat color="green" :disabled="loading" small>Content</v-btn>
                            <v-btn @click="() => showDiff(file.id)" flat color="orange" :disabled="file.operation !== 'overwrite' || loading" small>diff</v-btn>
                        </v-layout>
                    </td>
                </tr>
                <tfoot colspa>
                </tfoot>
            </table>
        </div>
        <v-dialog v-model="fileContentDialog" max-width="760px">
            <v-card>
                <v-card-title>
                    <v-spacer></v-spacer>
                    <v-btn icon @click="fileContentDialog = false">
                        <v-icon>close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text v-html="fileContent">
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import DisTemplateService from '../../services/DisTemplateService'

export default {
  name: 'CodeGenerator',
  props: {
    generatorId: {
      type: String,
      required: true
    },
    generatorAttributes: {
      type: Object,
      required: true
    }
  },
  data () {
    return {
      fileContentDialog: false,
      fileContent: null,
      filesToSave: [],
      loading: false,
      hasError: false,
      results: '',
      files: [],
      answers: {}
    }
  },
  computed: {
    showAlert: {
      get () {
        return !!this.results
      },
      set () {
        this.results = ''
      }
    }
  },
  created () {
    this.disTemplateService = new DisTemplateService()
  },
  methods: {
    async showDiff (file) {
      try {
        this.loading = true
        this.loading = true
        let response = await this.$store.dispatch('templates/cgDiff',
          { id: this.generatorId, generatorAttributes: this.generatorAttributes, file })
        this.fileContent = response.data
        this.fileContentDialog = true
      } catch (e) {
        this.$dialog.notify.warning(e.message, { timeout: 30000 })
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async showContent (file) {
      try {
        this.loading = true
        this.loading = true
        let response = await this.$store.dispatch('templates/cgPreview',
          { id: this.generatorId, generatorAttributes: this.generatorAttributes, file })
        this.fileContent = response.data
        this.fileContentDialog = true
        this.loading = false
      } catch (e) {
        this.$dialog.notify.warning(e.message, { timeout: 30000 })
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async cgView (generate = false) {
      try {
        this.loading = true
        let response = await this.$store.dispatch('templates/cgView',
          { id: this.generatorId, generatorAttributes: this.generatorAttributes, generate, answers: this.answers })
        this.files = response.data.files
        if (Array.isArray(response.data.answers) && response.data.answers.length > 0) {
          this.answers = response.data.answers
        } else if (Array.isArray(response.data.files) && response.data.files.length > 0) {
          this.answers = response.data.files.reduce((acc, cur, i) => {
            acc[cur.id] = false
            return acc
          }, {})
        } else {
          this.answers = []
        }
        this.results = response.data.results
        // await this.$store.dispatch('templates/refreshSummary')
      } catch (e) {
        this.$dialog.notify.warning(e.message, { timeout: 30000 })
        console.log(e)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped lang="stylus">
.c-code-generator
    padding 5px 10px
    &__results-overflow
        overflow-x auto
        overflow-y hidden
    &__results
        white-space pre
    &__table-overflow
        overflow-x auto
        overflow-y hidden
    &__preview-table
        width: 100%
        th
            text-align: left
        .v-input--checkbox
            margin-top 0
</style>
